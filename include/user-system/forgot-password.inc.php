<?php
require_once dirname(__DIR__) . '/config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

if (isset($_POST["reset-request-submit"])) {
    require_once dirname(__DIR__, 2) . '/class/UserSystem.php';

    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        http_response_code(403);
        die("Nieważny token bezpieczeństwa. Proszę odświeżyć stronę i spróbować ponownie.");
    }

    $UserSystem = new UserSystem($conn);
    if (isset($_SESSION['last_reset_request']) && (time() - $_SESSION['last_reset_request']) < 60) {
        header("Location: /zapomnialem-hasla/success");
        exit();
    }
    $_SESSION['last_reset_request'] = time();
    $userEmail = filter_var(trim($_POST["email"] ?? ''), FILTER_VALIDATE_EMAIL);
    if ($userEmail && $UserSystem->isEmailExists($userEmail)) {
        $selector = bin2hex(random_bytes(8));
        $token = random_bytes(32);
        $url = $GLOBALS['url']."/reset-hasla/" . $selector . "/" . bin2hex($token);
        $expires = date("U") + 1800;
        $hashedToken = password_hash(bin2hex($token), PASSWORD_DEFAULT);

        try {
            $UserSystem->insertResetToken($userEmail, $selector, $hashedToken, $expires);
            $mail = new PHPMailer(true);

            try {
                $mail->isSMTP();
                $mail->Host = $_ENV['SMTP_HOST'];
                $mail->Port = (int) $_ENV['SMTP_PORT'];
                $mail->Username = $_ENV['SMTP_USER'];
                $mail->Password = $_ENV['SMTP_PASSWORD'];
                $mail->setFrom($_ENV['SMTP_FROM'], $_ENV['SMTP_FROM_NAME'] ?? 'Reklama');
                $mail->SMTPAuth   = true;
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                $mail->CharSet = 'UTF-8';

                $mail->addAddress($userEmail);

                $mail->isHTML(true);
                $mail->Subject = 'Reset hasła w Twoim serwisie';
                $safeUrl = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
                $mail->Body = "<p>Otrzymaliśmy prośbę o reset hasła. Link jest ważny przez 30 minut.</p>
                   <p>Oto Twój link: <br><a href='" . $safeUrl . "'>" . $safeUrl . "</a></p>
                   <p><small>Jeśli nie prosiłeś o reset, zignoruj tę wiadomość.</small></p>";
                $mail->AltBody = "Otrzymaliśmy prośbę o reset hasła. Skopiuj ten link do przeglądarki, aby ustalić nowe hasło: " . $url;

                $mail->send();
            } catch (Exception $e) {
                header("Location: /zapomnialem-hasla/stmtfailed");
                exit();
            }
        } catch (Exception $e) {
            header("Location: /zapomnialem-hasla/stmtfailed");
            exit();
        }
    }else{
        usleep(random_int(500000, 900000));
    }

    header("Location: /zapomnialem-hasla/success");
    exit();
} else {
    header("Location: /");
}
?>