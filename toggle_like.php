<?php
header('Content-Type: application/json');
session_start();
include("connection.php");

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mix_id'])) {
    $user_id = $_SESSION['user_id'];
    $mix_id = (int)$_POST['mix_id'];

    // Check if already liked
    $check_stmt = $conn->prepare("SELECT 1 FROM likes WHERE user_id = ? AND mix_id = ?");
    $check_stmt->bind_param("ii", $user_id, $mix_id);
    $check_stmt->execute();
    $liked = $check_stmt->get_result()->num_rows > 0;

    if ($liked) {
        // Unlike
        $stmt = $conn->prepare("DELETE FROM likes WHERE user_id = ? AND mix_id = ?");
        $stmt->bind_param("ii", $user_id, $mix_id);
        $stmt->execute();
    } else {
        // Like
        $stmt = $conn->prepare("INSERT INTO likes (user_id, mix_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $user_id, $mix_id);
        $stmt->execute();
    }

    // Get new count
    $count_stmt = $conn->prepare("SELECT COUNT(*) as c FROM likes WHERE mix_id = ?");
    $count_stmt->bind_param("i", $mix_id);
    $count_stmt->execute();
    $like_count = $count_stmt->get_result()->fetch_assoc()['c'];

    echo json_encode(['success' => true, 'liked' => !$liked, 'like_count' => $like_count]);
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
}
?>