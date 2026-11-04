<?php
require_once dirname(__DIR__) . '/include/config.php';

header('Content-Type: application/json');

if (empty($_SESSION['uid'])) {
    http_response_code(401);
    echo json_encode(['status' => 'unauthorized']);
    exit;
}


$user_id = (int)$_SESSION['uid'];
$marker_id = isset($_POST['marker_id']) ? (int)$_POST['marker_id'] : 0;

if ($marker_id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Błędne ID ogłoszenia']);
    exit;
}
if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Nieważny token CSRF.']);
    exit;
}

// Sprawdzenie, czy użytkownik już polubił to ogłoszenie
$stmt = $conn->prepare("SELECT id FROM liked WHERE user_id = ? AND marker_id = ?");
$stmt->bind_param("ii", $user_id, $marker_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    // Jeśli tak - usuwamy z ulubionych (tzw. "toggle")
    $delStmt = $conn->prepare("DELETE FROM liked WHERE user_id = ? AND marker_id = ?");
    $delStmt->bind_param("ii", $user_id, $marker_id);
    $delStmt->execute();
    echo json_encode(['status' => 'removed']);
} else {
    // Jeśli nie - dodajemy do ulubionych
    $insStmt = $conn->prepare("INSERT INTO liked (user_id, marker_id) VALUES (?, ?)");
    $insStmt->bind_param("ii", $user_id, $marker_id);
    $insStmt->execute();
    echo json_encode(['status' => 'added']);
}
?>