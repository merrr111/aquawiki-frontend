<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

include 'db.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';
require 'PHPMailer/Exception.php';

session_start();

// Ensure AJAX receives plain text
header('Content-Type: text/plain');

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo "unauthorized";
    exit;
}

// Require login
if (!isset($_SESSION['user']['id'])) {
    echo "unauthorized";
    exit;
}

$user_id = $_SESSION['user']['id'];
$username = $_SESSION['user']['username'];
$fish_name = trim($_POST['fish_name'] ?? '');
$fish_info = trim($_POST['fish_info'] ?? '');

// Validate input
if (empty($fish_name) || empty($fish_info)) {
    echo "empty";
    exit;
}

// Insert into database
$stmt = $conn->prepare("INSERT INTO fish_submissions (user_id, username, fish_name, fish_info) VALUES (?, ?, ?, ?)");
$stmt->bind_param("isss", $user_id, $username, $fish_name, $fish_info);

if ($stmt->execute()) {

    // Send email notification
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.hostinger.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'admin@aquawiki.space'; // Must match your Hostinger email
        $mail->Password = 'Hk76Yg78*';
        $mail->SMTPSecure = 'ssl'; // You can try 'tls' and port 587 if ssl fails
        $mail->Port = 465;

        // Sender
        $mail->setFrom('admin@aquawiki.space', 'AquaWiki Fish Submission');

        // Recipients
        $mail->addAddress('giemerherrera12@gmail.com', 'Giemer Herrera'); // Your personal email
        $mail->addBCC('admin@aquawiki.space', 'AquaWiki Admin'); // Hostinger mailbox copy

        $mail->isHTML(false);
        $mail->Subject = "New Fish Submission by $username";
        $mail->Body = "A new fish has been submitted on AquaWiki:\n\n".
                      "Username: $username (ID: $user_id)\n".
                      "Fish Name: $fish_name\n".
                      "Information:\n$fish_info";

        $mail->send();
    } catch (Exception $e) {
        error_log("Fish submission email could not be sent: {$mail->ErrorInfo}");
        // Optional: still return success even if email fails
    }

    echo "success";

} else {
    echo "db_error";
}

$stmt->close();
exit;
?>
