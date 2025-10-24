<?php
include 'db.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user'])) {
    $message = trim($_POST['message']);
    $user_id = $_SESSION['user']['id'];

    if (!empty($message)) {
        $stmt = $conn->prepare("INSERT INTO feedback (user_id, message) VALUES (?, ?)");
        $stmt->bind_param("is", $user_id, $message);
        $stmt->execute();
        header("Location: browse.php?feedback=success");
        exit;
    } else {
        header("Location: browse.php?feedback=empty");
        exit;
    }
} else {
    header("Location: browse.php?feedback=unauthorized");
    exit;
}
?>
