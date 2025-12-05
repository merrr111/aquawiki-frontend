<?php
session_start();
include 'db.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';
require 'PHPMailer/Exception.php';

header('Content-Type: text/plain');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['email'])) {
    echo "error";
    exit;
}

$email = trim($_POST['email']);
$email = filter_var($email, FILTER_SANITIZE_EMAIL);

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo "error";
    exit;
}

// check if user exists
$stmt = $conn->prepare("SELECT id, username FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($user = $result->fetch_assoc()) {
    // generate 6-digit code
    $code = random_int(100000, 999999);
    $expiry = date("Y-m-d H:i:s", strtotime('+10 minutes'));

    // store code + expiry in DB
    $stmt2 = $conn->prepare("UPDATE users SET login_code = ?, code_expiry = ? WHERE id = ?");
    $stmt2->bind_param("ssi", $code, $expiry, $user['id']);
    $stmt2->execute();
    $stmt2->close();

    // send code via email
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.hostinger.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'admin@aquawiki.space';
        $mail->Password = 'Hk76Yg78*';
        $mail->SMTPSecure = 'ssl';
        $mail->Port = 465;

        $mail->setFrom('admin@aquawiki.space', 'AquaWiki Login');
        $mail->addAddress($email, $user['username']);

        $mail->isHTML(false);
        $mail->Subject = "Your AquaWiki Login Code";
        $mail->Body = "Hello {$user['username']},\n\nYour login code is: $code\nIt expires in 10 minutes.";

        $mail->send();
        echo "success";
    } catch (Exception $e) {
        error_log("Login code email error: " . $mail->ErrorInfo);
        echo "error";
    }

} else {
    echo "error";
}

$stmt->close();
?>
