<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
include 'db.php';

// Check if admin is logged in
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

// Check if ID is provided
if (!isset($_GET['id'])) {
    die("No fish ID specified.");
}

$fish_id = intval($_GET['id']);

try {
    // Prepare the delete query
    $stmt = $conn->prepare("DELETE FROM fishes WHERE id = ?");
    $stmt->bind_param("i", $fish_id);
    
    if ($stmt->execute()) {
        // Success
        $_SESSION['message'] = "Fish ID $fish_id deleted successfully.";
        header("Location: admin.php");
        exit;
    } else {
        // Handle foreign key constraint error
        if ($conn->errno == 1451) {
            $_SESSION['error'] = "Cannot delete Fish ID $fish_id. It is referenced in another table (e.g., user uploads).";
        } else {
            $_SESSION['error'] = "Error deleting fish: " . $conn->error;
        }
        header("Location: admin.php");
        exit;
    }
} catch (Exception $e) {
    $_SESSION['error'] = "Exception: " . $e->getMessage();
    header("Location: admin.php");
    exit;
}
?>
