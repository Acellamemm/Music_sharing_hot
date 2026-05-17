<?php
header('Content-Type: application/json');
session_start();
include("connection.php");

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mix_id'], $_POST['comment'])) {
    $user_id = $_SESSION['user_id'];
    $mix_id = (int)$_POST['mix_id'];
    $comment_text = trim($_POST['comment']);

    // CSRF check
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
        echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
        exit();
    }

    if (empty($comment_text)) {
        echo json_encode(['success' => false, 'error' => 'Empty comment']);
        exit();
    }

    $stmt = $conn->prepare("INSERT INTO comments (mix_id, user_id, comment, created_at) VALUES (?, ?, ?, NOW())");
    $stmt->bind_param("iis", $mix_id, $user_id, $comment_text);
    $success = $stmt->execute();

    if ($stmt) $stmt->close();

    if ($success) {
        // Get user's dj_alias for display
        $user_stmt = $conn->prepare("SELECT dj_alias FROM users WHERE id = ?");
        $user_stmt->bind_param("i", $user_id);
        $user_stmt->execute();
        $dj_alias = $user_stmt->get_result()->fetch_assoc()['dj_alias'] ?? 'DJ';
        $user_stmt->close();

        echo json_encode([
            'success' => true,
            'dj_alias' => htmlspecialchars($dj_alias),
            'comment'  => htmlspecialchars($comment_text)
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Database error']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
}
?>