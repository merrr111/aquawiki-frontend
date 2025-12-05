<?php
$host = "srv2088.hstgr.io"; // Hostinger MySQL server
$user = "u915767734_admin";
$pass = "Hk76Yg78*";
$dbname = "u915767734_aquawiki";

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
//echo "Connected successfully!";
?>
