<?php
session_start();
include("connection.php");

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mix_id'], $_POST['comment'])) {
    $user_id = $_SESSION['user_id'];
    // CSRF check
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
        header("Location: dashboard.php?error=invalid_csrf");
        exit();
    }

    $mix_id = (int)$_POST['mix_id'];
    $comment = trim($_POST['comment']);

    if (!empty($comment)) {
        $stmt = $conn->prepare("INSERT INTO comments (mix_id, user_id, comment, created_at) VALUES (?, ?, ?, NOW())");
        $stmt->bind_param("iis", $mix_id, $user_id, $comment);
        $stmt->execute();
        $stmt->close();
    }
}

header("Location: dashboard.php"); // Redirect back to dashboard
exit();
?>