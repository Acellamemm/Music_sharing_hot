<?php
session_start();
include("connection.php");

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// ensure errors is defined for GET requests
$errors = [];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // CSRF check
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
        $errors[] = "Invalid CSRF token.";
    }
    $title = trim($_POST['title']);
    $genre = trim($_POST['genre']);
    $description = trim($_POST['description']);
    $soundcloud_link = trim($_POST['soundcloud_link']);

    $errors = [];

    if (empty($title)) $errors[] = "Title is required.";
    if (empty($genre)) $errors[] = "Genre is required.";
    if (empty($soundcloud_link)) $errors[] = "SoundCloud link is required.";

    // Basic SoundCloud link validation
    // Basic SoundCloud link validation (compatible with PHP < 8)
    if (!empty($soundcloud_link) && strpos($soundcloud_link, 'soundcloud.com') === false) {
        $errors[] = "Please enter a valid SoundCloud link.";
    }
    if (empty($errors)) {
        $user_id = (int)$_SESSION['user_id'];
        $stmt = $conn->prepare("INSERT INTO mixes (user_id, title, genre, description, soundcloud_link) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("issss", $user_id, $title, $genre, $description, $soundcloud_link);

        if ($stmt->execute()) {
            header("Location: dashboard.php?uploaded=1");
            exit();
        } else {
            $errors[] = "Upload failed. Try again.";
        }
        $stmt->close();
    }
}
mysqli_close($conn);
?>

<!-- If errors, show simple error page (you can style later) -->
<!DOCTYPE html>
<html>
<head><title>Upload Error</title><link rel="stylesheet" href="style.css"></head>
<body>
    <div class="content-wrapper" style="text-align:center;margin-top:100px;">
        <h2>Upload Failed</h2>
        <?php if (!empty($errors)): ?>
        <ul style="color:#ff6b6b;">
            <?php foreach ($errors as $error): ?>
                <li><?= htmlspecialchars($error) ?></li>
            <?php endforeach; ?>
        </ul>
        <?php endif; ?>
        <a href="dashboard.php" style="color:var(--accent1);">← Back to Dashboard</a>
    </div>
</body>
</html>