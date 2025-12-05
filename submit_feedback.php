<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

include 'db.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';
require 'PHPMailer/Exception.php';

session_start();

// Ensure AJAX receives plain text response
header('Content-Type: text/plain');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user'])) {
    $message = trim($_POST['message']);
    $user_id = $_SESSION['user']['id'];
    $username = $_SESSION['user']['username'];

    if (!empty($message)) {
        // 1️⃣ Save feedback to database
        $stmt = $conn->prepare("INSERT INTO feedback (user_id, message) VALUES (?, ?)");
        $stmt->bind_param("is", $user_id, $message);
        if (!$stmt->execute()) {
            echo "db_error";
            exit;
        }

        // 2️⃣ Send feedback via Hostinger SMTP
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.hostinger.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'admin@aquawiki.space'; // Hostinger mailbox
            $mail->Password = 'Hk76Yg78*';           // Hostinger email password
            $mail->SMTPSecure = 'ssl';
            $mail->Port = 465;

            // Sender
            $mail->setFrom('admin@aquawiki.space', 'AquaWiki Feedback');

            // Recipient = Hostinger mailbox
            $mail->addAddress('admin@aquawiki.space', 'AquaWiki Admin');

            // BCC to your personal Gmail
            $mail->addBCC('giemerherrera12@gmail.com', 'Giemer Herrera');

            $mail->isHTML(false);
            $mail->Subject = "New Feedback from AquaWiki User: $username";
            $mail->Body = "You have received new feedback:\n\nUsername: $username (ID: $user_id)\nMessage:\n$message";

            $mail->send();
        } catch (Exception $e) {
            error_log("Feedback email could not be sent: {$mail->ErrorInfo}");
            echo "email_error";
            exit;
        }

        echo "success";
        exit;
    } else {
        echo "empty";
        exit;
    }
} else {
    echo "unauthorized";
    exit;
}
?>
