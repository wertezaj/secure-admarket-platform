<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as MailerException;
class UserSystem
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    public function isEmptyInputSignup($name, $surname, $email, $phone, $password, $repeatPassword)
    {
        return empty($name) || empty($surname) || empty($email) || empty($phone) || empty($password) || empty($repeatPassword);
    }

    public function isInvalidUsername($username)
    {
        return !preg_match("/^[a-zA-Z0-9]*$/", $username);
    }
    public function isInvalidEmail($email)
    {
        return !filter_var($email, FILTER_VALIDATE_EMAIL);
    }
    public function isInvalidPhone($phone)
    {
        $cleanedPhone = preg_replace('/[\s\-]/', '', $phone);

        if (!preg_match("/^\+?[0-9]{9,15}$/", $cleanedPhone)) {
            return true;
        }
        return false;
    }

    public function isPasswordsDontMatch($password, $repeatPassword)
    {
        return $password !== $repeatPassword;
    }

    public function isUsernameExists($username)
    {
        $sql = "SELECT * FROM users WHERE username = ?;";
        $stmt = $this->conn->prepare($sql);

        if (!$stmt) {
            throw new Exception("Statement preparation failed.");
        }

        $stmt->bind_param("s", $username);
        $stmt->execute();
        $resultData = $stmt->get_result();

        return $resultData->fetch_assoc() ?: false;
    }

    public function isEmailExists($email)
    {
        $sql = "SELECT * FROM users WHERE email = ?;";
        $stmt = $this->conn->prepare($sql);

        if (!$stmt) {
            throw new Exception("Statement preparation failed.");
        }

        $stmt->bind_param("s", $email);
        $stmt->execute();
        $resultData = $stmt->get_result();

        return $resultData->fetch_assoc() ?: false;
    }

    public function createUser($name, $surname, $email, $phone, $password)
    {
        // Sprawdzamy, czy konto już istnieje
        $existingUser = $this->isEmailExists($email);

        if ($existingUser) {
            if ((int)$existingUser['status'] !== 0 || (!empty($existingUser['verification_expires']) && strtotime($existingUser['verification_expires']) > time())) {
                return false;
            }
        }

        // Jeśli konto istnieje i jest już aktywne (status 1) lub zablokowane (status 2), nie pozwalamy na rejestrację
        if ($existingUser && (int)$existingUser['status'] !== 0) {
            return false;
        }

        $verificationCode = sprintf("%06d", random_int(100000, 999999));
        $hashedCode = password_hash($verificationCode, PASSWORD_BCRYPT);
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

        if ($existingUser && (int)$existingUser['status'] === 0) {
            // Nadpisujemy porzuconą / wygasłą rejestrację nowym hasłem i nowym kodem OTP
            $sql = "UPDATE users 
                    SET name = ?, surname = ?, phone = ?, password = ?, verification_code = ?, 
                        verification_expires = DATE_ADD(NOW(), INTERVAL 15 MINUTE), verification_attempts = 0 
                    WHERE id = ?;";
            $stmt = $this->conn->prepare($sql);
            if (!$stmt) {
                throw new Exception("Błąd bazy danych.");
            }
            $stmt->bind_param("sssssi", $name, $surname, $phone, $hashedPassword, $hashedCode, $existingUser['id']);
        } else {
            // Całkowicie nowy rekord w bazie
            $sql = "INSERT INTO users (name, surname, email, phone, password, status, verification_code, verification_expires, created_at) 
                    VALUES (?, ?, ?, ?, ?, 0, ?, DATE_ADD(NOW(), INTERVAL 15 MINUTE), NOW());";
            $stmt = $this->conn->prepare($sql);
            if (!$stmt) {
                throw new Exception("Błąd bazy danych.");
            }
            $stmt->bind_param("ssssss", $name, $surname, $email, $phone, $hashedPassword, $hashedCode);
        }

        $stmt->execute();
        $stmt->close();

        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = $_ENV['SMTP_HOST'];
            $mail->Port = (int)$_ENV['SMTP_PORT'];
            $mail->Username = $_ENV['SMTP_USER'];
            $mail->Password = $_ENV['SMTP_PASSWORD'];
            $mail->setFrom($_ENV['SMTP_FROM'], $_ENV['SMTP_FROM_NAME'] ?? 'AdMarket');
            $mail->SMTPAuth = true;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->CharSet = 'UTF-8';

            $mail->addAddress($email);

            $mail->isHTML(true);
            $mail->Subject = 'Kod weryfikacyjny do Twojego konta';
            $safeName = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
            $mail->Body = "<p>Witaj <strong>{$safeName}</strong>,</p>
                           <p>Twój 6-cyfrowy kod autoryzacyjny to: <strong style='font-size: 20px; letter-spacing: 2px;'>{$verificationCode}</strong></p>
                           <p>Kod jest ważny przez 15 minut.</p>
                           <p><small>Jeśli to nie Ty zakładałeś konto, zignoruj tę wiadomość.</small></p>";
            $mail->AltBody = "Witaj {$name},\n\nTwój kod weryfikacyjny to: {$verificationCode}\n\nKod jest ważny przez 15 minut.";

            $mail->send();
        } catch (MailerException $e) {
            error_log("Błąd wysyłki OTP: " . $mail->ErrorInfo);
            throw new Exception("Nie udało się wysłać e-maila weryfikacyjnego. Spróbuj ponownie.");
        }

        return true;
    }

    public function isEmptyInputLogin($email, $password)
    {
        return empty($email) || empty($password);
    }

    public function loginUser($email, $password)
    {
        $isTooLong = strlen($password) > 72;
        $user = $this->isEmailExists($email);

        $dummyHash = '$2y$10$usesomesillystringfore7hnbRJHxXVLeakoG8K30oukPsA.ztMG';
        $hashedPassword = ($user && !$isTooLong) ? $user["password"] : $dummyHash;

        // Stały czas wykonania niezależnie od długości hasła i istnienia konta
        $isPasswordCorrect = password_verify(substr($password, 0, 72), $hashedPassword) && !$isTooLong;

        if ($user && !empty($user['lockout_until']) && strtotime($user['lockout_until']) > time()) {
            throw new Exception("Invalid login credentials.");
        }

        if (!$isPasswordCorrect || $user === false) {
            if ($user) {
                $stmt = $this->conn->prepare("
                UPDATE users 
                SET failed_login_attempts = IF(lockout_until IS NOT NULL AND lockout_until <= NOW(), 1, failed_login_attempts + 1),
                    lockout_until = IF(
                        (lockout_until IS NOT NULL AND lockout_until <= NOW()),
                        NULL,
                        IF(failed_login_attempts + 1 >= 5, DATE_ADD(NOW(), INTERVAL 15 MINUTE), lockout_until)
                    )
                WHERE id = ?
            ");
                $stmt->bind_param("i", $user['id']);
                $stmt->execute();
                $stmt->close();
            }
            throw new Exception("Invalid login credentials.");
        }

        $stmt = $this->conn->prepare("UPDATE users SET failed_login_attempts = 0, lockout_until = NULL WHERE id = ?");
        $stmt->bind_param("i", $user['id']);
        $stmt->execute();
        $stmt->close();

        if ((int)$user["status"] === 0) {
            throw new Exception("Account unverified.");
        }

        if ((int)$user["status"] === 2) {
            throw new Exception("Account blocked.");
        }

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        session_regenerate_id(true);
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

        $_SESSION["uid"] = (int)$user["id"];
        $_SESSION["name"] = $user["name"];
        $_SESSION["surname"] = $user["surname"];

        header("Location: /");
        exit();
    }
    public function updateUser($userId, $name, $surname, $phone)
    {
        if (empty($name) || empty($surname) || empty($phone)) {
            throw new Exception("Wszystkie pola są wymagane.");
        }

        if ($this->isInvalidPhone($phone)) {
            throw new Exception("Nieprawidłowy format numeru telefonu.");
        }

        $sql = "UPDATE users SET name = ?, surname = ?, phone = ? WHERE id = ?;";
        $stmt = $this->conn->prepare($sql);

        if (!$stmt) {
            throw new Exception("Błąd serwera. Nie można zaktualizować danych.");
        }

        $stmt->bind_param("sssi", $name, $surname, $phone, $userId);
        $success = $stmt->execute();
        $stmt->close();

        if ($success) {
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            $_SESSION["name"] = $name;
            $_SESSION["surname"] = $surname;
        }

        return $success;
    }
    public function isPasswordStrong($password)
    {
        if (strlen($password) < 8 || strlen($password) > 72) {
            return false;
        }
        $uppercase = preg_match('@[A-Z]@', $password);
        $lowercase = preg_match('@[a-z]@', $password);
        $number    = preg_match('@[0-9]@', $password);
        $special   = preg_match('@[^\w]@', $password);

        return $uppercase && $lowercase && $number && $special;
    }

    public function deleteOldResetTokens($email)
    {
        $sql = "DELETE FROM pwdReset WHERE pwdResetEmail = ?;";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Statement preparation failed.");
        }
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->close();
    }

    public function insertResetToken($email, $selector, $hashedToken, $expires)
    {
        $this->deleteOldResetTokens($email);

        $sql = "INSERT INTO pwdReset (pwdResetEmail, pwdResetSelector, pwdResetToken, pwdResetExpires) VALUES (?, ?, ?, ?);";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Statement preparation failed.");
        }
        $stmt->bind_param("ssss", $email, $selector, $hashedToken, $expires);
        $stmt->execute();
        $stmt->close();
    }

    public function getResetTokenBySelector($selector)
    {
        $currentDate = date("U");
        $sql = "SELECT * FROM pwdReset WHERE pwdResetSelector = ? AND pwdResetExpires >= ?;";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Statement preparation failed.");
        }
        $stmt->bind_param("ss", $selector, $currentDate);
        $stmt->execute();
        $resultData = $stmt->get_result();

        return $resultData->fetch_assoc() ?: false;
    }

    public function updatePasswordByEmail($email, $newPassword)
    {
        $sql = "UPDATE users SET password = ? WHERE email = ?;";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Statement preparation failed.");
        }

        $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
        $stmt->bind_param("ss", $hashedPassword, $email);
        $stmt->execute();
        $stmt->close();

        $this->deleteOldResetTokens($email);
    }
    public function verifyAccountCode($email, $code)
    {
        $sql = "SELECT id, verification_code, verification_expires, name, surname, password, verification_attempts FROM users WHERE email = ?;";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $resultData = $stmt->get_result();

        if ($row = $resultData->fetch_assoc()) {
            if ($row['verification_attempts'] >= 5) {
                return "locked";
            }

            if (strtotime($row['verification_expires']) < time()) {
                return "expired";
            }

            if (password_verify($code, $row['verification_code'])) {
                $updateSql = "UPDATE users SET status = 1, verification_code = NULL, verification_expires = NULL, verification_attempts = 0 WHERE id = ?";
                $updateStmt = $this->conn->prepare($updateSql);
                $updateStmt->bind_param("i", $row['id']);
                $updateStmt->execute();

                if (session_status() === PHP_SESSION_NONE) {
                    session_start();
                }
                session_regenerate_id(true);
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                $_SESSION["uid"] = $row["id"];
                $_SESSION["name"] = $row["name"];
                $_SESSION["surname"] = $row["surname"];
                return "success";
            } else {
                $incrementSql = "UPDATE users SET verification_attempts = verification_attempts + 1 WHERE id = ?";
                $incrementStmt = $this->conn->prepare($incrementSql);
                $incrementStmt->bind_param("i", $row['id']);
                $incrementStmt->execute();

                return "invalid";
            }
        }
        return "invalid";
    }
    public function resendVerificationCode($email)
    {
        $sql = "SELECT id, name, status FROM users WHERE email = ? LIMIT 1;";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Błąd serwera podczas przygotowania zapytania.");
        }
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$user || (int)$user['status'] !== 0) {
            // Konto nie istnieje lub jest już zweryfikowane/zablokowane
            return false;
        }

        $verificationCode = sprintf("%06d", random_int(100000, 999999));
        $hashedCode = password_hash($verificationCode, PASSWORD_BCRYPT);

        $updateSql = "UPDATE users 
                      SET verification_code = ?, 
                          verification_expires = DATE_ADD(NOW(), INTERVAL 15 MINUTE), 
                          verification_attempts = 0 
                      WHERE id = ?;";
        $updateStmt = $this->conn->prepare($updateSql);
        if (!$updateStmt) {
            throw new Exception("Błąd zapisu nowego kodu.");
        }
        $updateStmt->bind_param("si", $hashedCode, $user['id']);
        $success = $updateStmt->execute();
        $updateStmt->close();

        if (!$success) {
            return false;
        }

        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = $_ENV['SMTP_HOST'];
            $mail->Port       = (int)$_ENV['SMTP_PORT'];
            $mail->Username   = $_ENV['SMTP_USER'];
            $mail->Password   = $_ENV['SMTP_PASSWORD'];
            $mail->setFrom($_ENV['SMTP_FROM'], $_ENV['SMTP_FROM_NAME'] ?? 'AdMarket');
            $mail->SMTPAuth   = true;
            $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
            $mail->CharSet    = 'UTF-8';

            $mail->addAddress($email);

            $mail->isHTML(true);
            $mail->Subject = 'Twój nowy kod weryfikacyjny';
            $safeName = htmlspecialchars($user['name'], ENT_QUOTES, 'UTF-8');
            $mail->Body = "<p>Witaj <strong>{$safeName}</strong>,</p>
                           <p>Oto Twój nowy 6-cyfrowy kod autoryzacyjny: <strong style='font-size: 22px; letter-spacing: 3px;'>{$verificationCode}</strong></p>
                           <p>Kod jest ważny przez 15 minut od teraz.</p>
                           <p><small>Jeśli to nie Ty prosiłeś o ponowną wysyłkę, zignoruj tę wiadomość.</small></p>";
            $mail->AltBody = "Witaj {$user['name']},\n\nTwój nowy kod weryfikacyjny to: {$verificationCode}\n\nKod jest ważny przez 15 minut.";

            $mail->send();
            return true;
        } catch (\PHPMailer\PHPMailer\Exception $e) {
            error_log("Błąd wysyłki nowego kodu OTP: " . $mail->ErrorInfo);
            throw new Exception("Nie udało się wysłać nowej wiadomości e-mail.");
        }
    }
}
