<?php
session_start();

if (isset($_SESSION['user'])) {
    // Logged in → go to home
    header("Location: home.php");
} else {
    // Guest → go to browse
    header("Location: browse.php");
}
exit;
