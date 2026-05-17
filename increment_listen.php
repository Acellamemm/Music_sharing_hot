<?php
header('Content-Type: application/json');
session_start();
include("connection.php");

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mix_id'])) {
    // CSRF check
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
        echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
        exit();
    }

    $mix_id = (int)$_POST['mix_id'];

    $stmt = $conn->prepare("UPDATE mixes SET listens = listens + 1 WHERE id = ?");
    $stmt->bind_param("i", $mix_id);
    $stmt->execute();

    // Get new count
    $count_stmt = $conn->prepare("SELECT listens FROM mixes WHERE id = ?");
    $count_stmt->bind_param("i", $mix_id);
    $count_stmt->execute();
    $new_count = $count_stmt->get_result()->fetch_assoc()['listens'];

    echo json_encode(['success' => true, 'new_count' => $new_count]);
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
}
?>