<?php
header('Content-Type: application/json');
include("connection.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mix_id'])) {
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