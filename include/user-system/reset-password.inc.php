<?php

require_once dirname(__DIR__) . '/config.php';

if (isset($_POST["reset-password-submit"])) {
    require_once dirname(__DIR__, 2) . '/class/UserSystem.php';

    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        http_response_code(403);
        die("Nieważny token bezpieczeństwa. Proszę odświeżyć stronę i spróbować ponownie.");
    }
    $UserSystem = new UserSystem($conn);

    $password = $_POST["pwd"];
    $passwordRepeat = $_POST["pwd-repeat"];

    $selector = $_POST["selector"] ?? '';
    $validator = $_POST["validator"] ?? '';

    if (!ctype_xdigit($selector) || !ctype_xdigit($validator)) {
        header("Location: /zapomnialem-hasla/tokenexpired");
        exit();
    }

    if (empty($password) || empty($passwordRepeat)) {
        header("Location: /reset-hasla/" . $selector . "/" . $validator . "?error=empty");
        exit();
    } else if ($password !== $passwordRepeat) {
        header("Location: /reset-hasla/" . $selector . "/" . $validator . "?error=pwdnotsame");
        exit();
    }

    if (!$UserSystem->isPasswordStrong($password)) {
        header("Location: /reset-hasla/" . $selector . "/" . $validator . "?error=weakpassword");
        exit();
    }

    try {
        $row = $UserSystem->getResetTokenBySelector($selector);

        if ($row === false) {
            header("Location: /zapomnialem-hasla/tokenexpired");
            exit();
        }

        $tokenCheck = password_verify($validator, $row["pwdResetToken"]);

        if ($tokenCheck === false) {
            $UserSystem->deleteOldResetTokens($row["pwdResetEmail"]);
            header("Location: /zapomnialem-hasla/tokenexpired");
            exit();
        } elseif ($tokenCheck === true) {

            $tokenEmail = $row["pwdResetEmail"];

            if (!$UserSystem->isEmailExists($tokenEmail)) {
                header("Location: /zapomnialem-hasla/tokenexpired");
                exit();
            }

            $UserSystem->updatePasswordByEmail($tokenEmail, $password);
            header("Location: /logowanie/passwordupdated");
            exit();
        }
    } catch (Exception $e) {
        header("Location: /reset-hasla/stmtfailed");
        exit();
    }
} else {
    header("Location: /");
}
?>