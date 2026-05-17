<?php
// Database connection using environment variables for deployment.
$host = getenv('DB_HOST') ?: 'localhost';
$db_user = getenv('DB_USER') ?: 'root';
$db_password = getenv('DB_PASSWORD') ?: 'Pronunciation';
$db_name = getenv('DB_NAME') ?: 'registration';

$conn = mysqli_connect($host, $db_user, $db_password, $db_name);

if (!$conn) {
    die('Database connection failed: ' . mysqli_connect_error());
}
?>
