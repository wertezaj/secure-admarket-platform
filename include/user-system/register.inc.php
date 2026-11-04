<?php


if (isset($_POST["submit"])) {
    require_once dirname(__DIR__) . '/config.php';
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        http_response_code(403);
        die("Nieważny token CSRF.");
    }
    require_once dirname(__DIR__, 2) . '/class/UserSystem.php';


    $UserSystem = new UserSystem($conn);

    $raw_name = trim($_POST['name']);
    $raw_surname = trim($_POST['surname']);

    $name = mb_convert_case(mb_strtolower($raw_name, 'UTF-8'), MB_CASE_TITLE, 'UTF-8');
    $surname = mb_convert_case(mb_strtolower($raw_surname, 'UTF-8'), MB_CASE_TITLE, 'UTF-8');

    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $password = $_POST['password'];
    $repeatPassword = $_POST['repeatpassword'];

    $_SESSION['reg_data'] = [
        'name' => $name,
        'surname' => $surname,
        'email' => $email,
        'phone' => $phone
    ];

    try {
        if ($UserSystem->isEmptyInputSignup($name, $surname, $email, $phone, $password, $repeatPassword)) {
            header("Location: /rejestracja?error=emptyinput");
            exit();
        }

        if ($UserSystem->isInvalidEmail($email)) {
            header("Location: /rejestracja?error=invalidusername");
            exit();
        }

        if ($UserSystem->isInvalidPhone($phone)) {
            header("Location: /rejestracja?error=invalidphone");
            exit();
        }

        if ($UserSystem->isPasswordsDontMatch($password, $repeatPassword)) {
            header("Location: /rejestracja?error=passwordsdontmatch");
            exit();
        }

        if (!$UserSystem->isPasswordStrong($password)) {
            header("Location: /rejestracja?error=weakpassword");
            exit();
        }

        $created = $UserSystem->createUser($name, $surname, $email, $phone, $password);

        if (!$created) {
            header("Location: /rejestracja?error=emailexists");
            exit();
        }

        $_SESSION['verify_email'] = $email;
        unset($_SESSION['reg_data']);
        header("Location: /weryfikacja-email");
        exit();

    } catch (Exception $e) {
        header("Location: /rejestracja?error=stmtfailed");
        exit();
    }
} else {
    header("Location: /rejestracja");
    exit();
}