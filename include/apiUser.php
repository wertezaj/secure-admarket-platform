<?php
header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    require_once __DIR__ . '/config.php';

    if (empty($_SESSION['uid'])) {
        echo json_encode(['status' => 'error', 'message' => 'Musisz być zalogowany.']);
        exit;
    }

    require_once dirname(__DIR__) . '/class/UserSystem.php';

    $UserSystem = new UserSystem($conn);
    $userId = $_SESSION['uid'];

    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'Nieważny token CSRF.']);
        exit;
    }

    $raw_imie = mb_substr(trim(strip_tags($_POST['imie'] ?? '')), 0, 80, 'UTF-8');
    $raw_nazwisko = mb_substr(trim(strip_tags($_POST['nazwisko'] ?? '')), 0, 80, 'UTF-8');
    $telefon = trim(strip_tags($_POST['phone'] ?? ''));

    $imie = mb_convert_case(mb_strtolower($raw_imie, 'UTF-8'), MB_CASE_TITLE, 'UTF-8');
    $nazwisko = mb_convert_case(mb_strtolower($raw_nazwisko, 'UTF-8'), MB_CASE_TITLE, 'UTF-8');
    try {
        if ($UserSystem->updateUser($userId, $imie, $nazwisko, $telefon)) {
            echo json_encode(['status' => 'success', 'message' => 'Dane zostały zaktualizowane!']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Nie wprowadzono żadnych zmian.']);
        }
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Nieprawidłowe zapytanie.']);
}