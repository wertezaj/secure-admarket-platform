<?php
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__, 2) . '/views/header.php';

$regData = $_SESSION['reg_data'] ?? [];
$oldName = htmlspecialchars($regData['name'] ?? '', ENT_QUOTES);
$oldSurname = htmlspecialchars($regData['surname'] ?? '', ENT_QUOTES);
$oldEmail = htmlspecialchars($regData['email'] ?? '', ENT_QUOTES);
$oldPhone = htmlspecialchars($regData['phone'] ?? '', ENT_QUOTES);

unset($_SESSION['reg_data']);
?>
    <style>
        body{padding: 0;}
        header, footer { display: none !important; }
        main { overflow: hidden; }
    </style>

    <div class="form-users-con">
        <div class="form-users">
            <form action="/include/user-system/register.inc.php" method="POST" autocomplete="off">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <div class="title">
                    <h2>Stwórz konto</h2>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-12 col-md-6">
                        <div class="inputbox">
                            <input type="text" name="name" id="name" required placeholder=" " value="<?php echo $oldName; ?>">
                            <label for="name">Imię</label>
                        </div>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="inputbox">
                            <input type="text" name="surname" id="surname" required placeholder=" " value="<?php echo $oldSurname; ?>">
                            <label for="surname">Nazwisko</label>
                        </div>
                    </div>
                </div>

                <div class="inputbox">
                    <ion-icon name="mail-outline"></ion-icon>
                    <input type="email" name="email" id="reg-email" required placeholder=" " value="<?php echo $oldEmail; ?>">
                    <label for="reg-email">E-mail</label>
                </div>

                <div class="inputbox">
                    <ion-icon name="call-outline"></ion-icon>
                    <input type="tel" name="phone" id="reg-phone" required placeholder=" " value="<?php echo $oldPhone; ?>">
                    <label for="reg-phone">Numer telefonu</label>
                </div>

                <div class="inputbox">
                    <ion-icon name="lock-closed-outline"></ion-icon>

                    <ion-icon name="eye-off-outline" class="toggle-password"></ion-icon>
                    <input type="password" name="password" id="reg-password" required placeholder=" ">
                    <label for="reg-password">Hasło</label>
                </div>

                <div class="inputbox">
                    <ion-icon name="lock-closed-outline"></ion-icon>

                    <ion-icon name="eye-off-outline" class="toggle-password"></ion-icon>
                    <input type="password" name="repeatpassword" id="repeatpassword" required placeholder=" ">
                    <label for="repeatpassword">Powtórz hasło</label>
                </div>

                <button type="submit" name="submit" id="register">Stwórz konto</button>

                <div class="register">
                    <p>Masz już konto? <a href="/logowanie">Zaloguj</a></p>
                </div>
            </form>

            <?php
            if (isset($_GET["error"])) {
                $errorMsg = match ($_GET["error"]) {
                    "passwordsdontmatch" => "Hasła nie są takie same.",
                    "usernameexists", "emailexists" => "Adres e-mail jest już zajęty.",
                    "invalidusername" => "Nieprawidłowy adres e-mail.",
                    "invalidphone" => "Nieprawidłowy format numeru telefonu.",
                    "emptyinput" => "Uzupełnij wszystkie pola.",
                    "stmtfailed" => "Coś poszło nie tak. Spróbuj ponownie.",
                    "weakpassword" => "Hasło musi mieć min. 8 znaków, dużą i małą literę, cyfrę oraz znak specjalny.",
                    default => ""
                };
                if ($errorMsg) echo "<p class=\"php-error\">$errorMsg</p>";
            }
            ?>
        </div>
    </div>

    <script>
        document.querySelectorAll('.toggle-password').forEach(icon => {
            icon.addEventListener('click', function() {
                const input = this.parentElement.querySelector('input');
                if (input.type === 'password') {
                    input.type = 'text';
                    this.setAttribute('name', 'eye-outline');
                } else {
                    input.type = 'password';
                    this.setAttribute('name', 'eye-off-outline');
                }
            });
        });
    </script>
<?php require_once dirname(__DIR__, 2) . '/views/footer.php'; ?>