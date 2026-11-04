<?php
// 1. Inicjalizacja Autoloadera i zmiennych środowiskowych .env NA SAMYM POCZĄTKU
$projectRoot = dirname(__DIR__);
require_once $projectRoot . '/vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable($projectRoot);
$dotenv->safeLoad();

// 2. Konfiguracja raportowania błędów na podstawie załadowanego APP_ENV
$appEnv = $_ENV['APP_ENV'] ?? 'production';
if ($appEnv === 'development') {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
} else {
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
    ini_set('log_errors', '1');
}
error_reporting(E_ALL);

$appUrl = rtrim($_ENV['APP_URL'] ?? 'http://reklama.test', '/');

// 3. Konfiguracja parametrów sesji PRZED session_start()
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => str_starts_with($appUrl, 'https://'),
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();

    $idleTimeout = 1800; // 30 minut bezczynności
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $idleTimeout) {
        session_unset();
        session_destroy();
        session_start();
        session_regenerate_id(true);
    }
    $_SESSION['last_activity'] = time();
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$GLOBALS['url'] = $appUrl;

// 4. Połączenie z bazą danych
$dbHost = $_ENV['DB_HOST'] ?? '127.0.0.1';
$dbPort = (int)($_ENV['DB_PORT'] ?? 3306);
$dbUser = $_ENV['DB_USER'] ?? '';
$dbPass = $_ENV['DB_PASSWORD'] ?? '';
$dbName = $_ENV['DB_NAME'] ?? '';

$conn = new mysqli($dbHost, $dbUser, $dbPass, $dbName, $dbPort);

if ($conn->connect_errno) {
    error_log('Database connection failed: ' . $conn->connect_error);
    http_response_code(500);
    exit('Błąd połączenia z bazą danych.');
}

$conn->set_charset('utf8mb4');

// 5. Globalna weryfikacja czy zalogowany użytkownik nie został zablokowany/usunięty
if (!empty($_SESSION['uid'])) {
    $stmtAuth = $conn->prepare("SELECT status FROM users WHERE id = ? LIMIT 1");
    $stmtAuth->bind_param("i", $_SESSION['uid']);
    $stmtAuth->execute();
    $resAuth = $stmtAuth->get_result();
    $currentUser = $resAuth->fetch_assoc();
    $stmtAuth->close();

    if (!$currentUser || (int)$currentUser['status'] !== 1) {
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();
        header("Location: /logowanie/zablokowane");
        exit();
    }
}

// 6. Pobranie ustawień globalnych
$GLOBALS['settings'] = [];
$result = $conn->query('SELECT setting_key, setting_value FROM settings');
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $GLOBALS['settings'][$row['setting_key']] = $row['setting_value'];
    }
}

$GLOBALS['page_name'] = $GLOBALS['settings']['company_name'] ?? 'AdMarket';
$GLOBALS['logo_path'] = $GLOBALS['settings']['logo_path'] ?? '/assets/img/logomain.jpeg';

// 7. Nagłówki HTTP Security
if (!headers_sent()) {
    header("X-Frame-Options: SAMEORIGIN");
    header("X-Content-Type-Options: nosniff");
    header("Referrer-Policy: strict-origin-when-cross-origin");
    header("Permissions-Policy: geolocation=(self), microphone=(), camera=()");
    header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://unpkg.com https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://unpkg.com; img-src 'self' data: https://*.tile.openstreetmap.org https://unpkg.com; connect-src 'self' https://nominatim.openstreetmap.org; font-src 'self' https://fonts.gstatic.com; frame-src https://www.google.com; frame-ancestors 'self'; base-uri 'self'; form-action 'self'; object-src 'none';");
    if (str_starts_with($appUrl, 'https://')) {
        header("Strict-Transport-Security: max-age=31536000; includeSubDomains");
    }
}