<?php

if (isset($_POST["submit"])) {

    require_once dirname(__DIR__) . '/config.php';
    require_once dirname(__DIR__, 2) . '/class/UserSystem.php';

    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        http_response_code(403);
        die("Nieważny token bezpieczeństwa. Proszę odświeżyć stronę i spróbować ponownie.");
    }

    $UserSystem = new UserSystem($conn);

    $email = $_POST['email'];
    $password = $_POST['password'];

    try {
        if ($UserSystem->isEmptyInputLogin($email, $password)) {
            header("Location: /logowanie/emptyinput");
            exit();
        }

        $UserSystem->loginUser($email, $password);
    } catch (Exception $e) {
        $message = $e->getMessage();

        if ($message === 'Invalid login credentials.') {
            header("Location: /logowanie/niepoprawnedane");
            exit();
        } elseif ($message === 'Account blocked.') {
            header("Location: /logowanie/zablokowane");
            exit();
        } elseif ($message === 'Account unverified.') {
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            $_SESSION['verify_email'] = $email;
            header("Location: /weryfikacja-email");
            exit();
        } else {
            header("Location: /logowanie/stmtfailed");
            exit();
        }
    }
}