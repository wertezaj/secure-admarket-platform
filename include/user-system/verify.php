<?php
require_once dirname(__DIR__) . '/config.php';

if (empty($_SESSION['verify_email'])) {
    header("Location: /rejestracja");
    exit();
}

$email = $_SESSION['verify_email'];
$error = '';
$success = '';

require_once dirname(__DIR__, 2) . '/class/UserSystem.php';
$UserSystem = new UserSystem($conn);

// 1. OBSŁUGA PONOWNEGO WYSŁANIA KODU (RESEND OTP)
if (isset($_POST['resend_code'])) {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        http_response_code(403);
        die("Nieważny token bezpieczeństwa.");
    }

    // Rate Limiting: 60 sekund cooldownu na poziomie sesji
    $cooldown = 60;
    $timeSinceLastResend = time() - ($_SESSION['last_otp_resend'] ?? 0);

    if ($timeSinceLastResend < $cooldown) {
        $secondsLeft = $cooldown - $timeSinceLastResend;
        $error = "Musisz odczekać jeszcze {$secondsLeft} sekund przed kolejną prośbą o kod.";
    } else {
        try {
            if ($UserSystem->resendVerificationCode($email)) {
                $_SESSION['last_otp_resend'] = time();
                $success = "Nowy kod został wysłany na Twój adres e-mail!";
            } else {
                $error = "Nie można wygenerować kodu dla tego konta.";
            }
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}

// 2. OBSŁUGA WERYFIKACJI PODANEGO KODU
if (isset($_POST['submit_code'])) {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        http_response_code(403);
        die("Nieważny token bezpieczeństwa.");
    }

    $digits = [];
    for ($i = 1; $i <= 6; $i++) {
        $val = $_POST["code$i"] ?? '';
        if (!is_string($val) || !preg_match('/^\d$/', $val)) {
            $error = "Kod musi składać się dokładnie z 6 cyfr.";
            break;
        }
        $digits[] = $val;
    }

    if (empty($error)) {
        $code = implode('', $digits);
        $result = $UserSystem->verifyAccountCode($email, $code);

        if ($result === "success") {
            unset($_SESSION['verify_email']);
            unset($_SESSION['last_otp_resend']);
            header("Location: /?registered=true");
            exit();
        } elseif ($result === "locked") {
            $error = "Przekroczono limit 5 prób. Kod został unieważniony. Kliknij poniżej, aby wygenerować nowy kod.";
        } elseif ($result === "expired") {
            $error = "Twój kod wygasł. Kliknij poniżej, aby wygenerować nowy kod.";
        } else {
            $error = "Podano nieprawidłowy kod weryfikacyjny.";
        }
    }
}

// Obliczenie pozostałego czasu cooldownu dla frontendu
$remainingCooldown = max(0, 60 - (time() - ($_SESSION['last_otp_resend'] ?? 0)));

require_once dirname(__DIR__, 2) . '/views/header.php';
?>
    <style>
        body { padding: 0; }
        header, footer { display: none !important; }
        main { overflow: hidden; }
        .code-inputs {
            display: flex; gap: 10px; justify-content: center; margin-bottom: 20px;
        }
        .code-inputs input {
            width: 48px; height: 56px; font-size: 24px; text-align: center; border-radius: 8px; border: 1px solid #ccc;
        }
        .code-inputs input:focus {
            border-color: #8B6F4F; outline: none; box-shadow: 0 0 0 3px rgba(139, 111, 79, 0.1);
        }
        .btn-resend {
            background: none; border: none; color: #0071e3; font-weight: 600; cursor: pointer;
            text-decoration: underline; padding: 0; font-size: 0.95rem; margin-top: 15px;
        }
        .btn-resend:disabled {
            color: #86868b; cursor: not-allowed; text-decoration: none;
        }
        .php-success {
            color: #155724; background-color: #d4edda; border: 1px solid #c3e6cb;
            padding: 10px 15px; border-radius: 8px; margin-top: 15px; font-size: 0.9rem;
        }
        .php-error {
            color: #721c24; background-color: #f8d7da; border: 1px solid #f5c6cb;
            padding: 10px 15px; border-radius: 8px; margin-top: 15px; font-size: 0.9rem;
        }
    </style>

    <div class="form-users-con">
        <div class="form-users text-center">
            <h2>Potwierdź swój adres e-mail</h2>
            <p style="margin-bottom: 25px; color: #666;">
                Na adres <strong><?php echo htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?></strong> wysłaliśmy 6-cyfrowy kod autoryzacyjny.
            </p>

            <!-- GŁÓWNY FORMULARZ WPROWADZANIA KODU -->
            <form action="/weryfikacja-email" method="POST" autocomplete="off" id="verify-form">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                <div class="code-inputs" id="otp-container">
                    <input type="text" name="code1" maxlength="1" inputmode="numeric" pattern="[0-9]*" required autofocus>
                    <input type="text" name="code2" maxlength="1" inputmode="numeric" pattern="[0-9]*" required>
                    <input type="text" name="code3" maxlength="1" inputmode="numeric" pattern="[0-9]*" required>
                    <input type="text" name="code4" maxlength="1" inputmode="numeric" pattern="[0-9]*" required>
                    <input type="text" name="code5" maxlength="1" inputmode="numeric" pattern="[0-9]*" required>
                    <input type="text" name="code6" maxlength="1" inputmode="numeric" pattern="[0-9]*" required>
                </div>

                <button type="submit" name="submit_code" id="register">Weryfikuj konto</button>
            </form>

            <!-- KOMUNIKATY BŁĘDU / SUKCESU -->
            <?php if (!empty($error)): ?>
                <p class="php-error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p>
            <?php endif; ?>
            <?php if (!empty($success)): ?>
                <p class="php-success"><?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?></p>
            <?php endif; ?>

            <!-- OSOBNY FORMULARZ PONOWNEGO WYSŁANIA KODU (BEZ ZAGNIEŻDŻANIA TAGÓW FORM) -->
            <form action="/weryfikacja-email" method="POST" style="margin-top: 20px;">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                <p style="font-size: 0.9rem; color: #666; margin-bottom: 5px;">Nie otrzymałeś wiadomości lub kod wygasł?</p>
                <button type="submit" name="resend_code" id="btn-resend-otp" class="btn-resend">
                    Wyślij kod ponownie
                </button>
            </form>

            <div class="register" style="margin-top: 25px;">
                <p><a href="/logowanie">Wróć do logowania</a></p>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const container = document.getElementById('otp-container');
            const inputs = container.querySelectorAll('input');
            const resendBtn = document.getElementById('btn-resend-otp');

            // --- Obsługa licznika (Cooldown) dla ponownej wysyłki kodu ---
            let cooldown = <?php echo (int)$remainingCooldown; ?>;

            function updateResendButton() {
                if (cooldown > 0) {
                    resendBtn.disabled = true;
                    resendBtn.innerText = `Wyślij kod ponownie (za ${cooldown}s)`;
                    cooldown--;
                    setTimeout(updateResendButton, 1000);
                } else {
                    resendBtn.disabled = false;
                    resendBtn.innerText = "Wyślij kod ponownie";
                }
            }
            if (cooldown > 0) {
                updateResendButton();
            }

            // --- Inteligentne wklejanie ze schowka (Paste Event) ---
            container.addEventListener('paste', (e) => {
                e.preventDefault();
                const pastedData = (e.clipboardData || window.clipboardData).getData('text').trim().replace(/\D/g, '');

                if (!pastedData) return;

                for (let i = 0; i < inputs.length; i++) {
                    if (pastedData[i]) {
                        inputs[i].value = pastedData[i];
                    }
                }

                const focusIndex = Math.min(pastedData.length, inputs.length - 1);
                inputs[focusIndex].focus();

                if (pastedData.length >= inputs.length) {
                    setTimeout(() => {
                        document.getElementById('register').click();
                    }, 100);
                }
            });

            inputs.forEach((input, index) => {
                input.addEventListener('keydown', (e) => {
                    if (e.key === 'Backspace' && !input.value && index > 0) {
                        inputs[index - 1].focus();
                    }
                });

                input.addEventListener('input', () => {
                    input.value = input.value.replace(/\D/g, ''); // Blokada wpisywania liter
                    if (input.value && index < inputs.length - 1) {
                        inputs[index + 1].focus();
                    }
                });
            });
        });
    </script>

<?php require_once dirname(__DIR__, 2) . '/views/footer.php'; ?>