<?php
require_once __DIR__ . '/config.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        http_response_code(403);
        exit('n');
    }

    // Prosty Rate Limiting (maksymalnie 1 wiadomość na 60 sekund z jednej sesji)
    if (isset($_SESSION['last_contact_submit']) && (time() - $_SESSION['last_contact_submit']) < 60) {
        http_response_code(429);
        exit('n');
    }

    if (isset($_POST['name'], $_POST['email'], $_POST['title'], $_POST['message'])) {
        $name = trim(strip_tags($_POST['name']));
        $email = filter_var(trim($_POST['email']), FILTER_VALIDATE_EMAIL);
        // Usunięcie znaków CRLF chroniące przed Email Header Injection
        $title = preg_replace('/[\r\n]+/', ' ', trim(strip_tags($_POST['title'])));
        $message = trim(strip_tags($_POST['message']));

        if (!$email || empty($name) || empty($title) || empty($message)) {
            exit('n');
        }

        $_SESSION['last_contact_submit'] = time();
        $admin_email = "formularz@ubezpieczonko.pro";

        $subject = "Nowa wiadomość z formularza kontaktowego ubezpieczonko.pro: " . $title;

        $email_message = "Imię i nazwisko: $name\n";
        $email_message .= "Email: $email\n";
        $email_message .= "Temat: $title\n\n";
        $email_message .= "Wiadomość:\n$message\n";


        $headers = "From: sender@ubezpieczonko.pro\r\n";
        $headers .= "Reply-To: formularz@ubezpieczonko.pro\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

        if (mail($admin_email, $subject, $email_message, $headers)) {
            echo 'g';
        } else {
            echo 'n';
        }
    }
}