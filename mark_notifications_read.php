<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user'])) exit;

$userId = $_SESSION['user']['id'];

// Mark all as read
$stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$stmt->close();

echo "OK";
?>
