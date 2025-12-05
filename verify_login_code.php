<?php
session_start();
include 'db.php';
header('Content-Type: text/plain');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['email']) || empty($_POST['code'])) {
    echo "error";
    exit;
}

$email = trim($_POST['email']);
$code = trim($_POST['code']);

// fetch user by email
$stmt = $conn->prepare("SELECT id, username, role, login_code, code_expiry FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($user = $result->fetch_assoc()) {
    // check if code matches and is not expired
    if ($user['login_code'] === $code && strtotime($user['code_expiry']) > time()) {
        // valid → log in
        $_SESSION['user'] = [
            'id' => $user['id'],
            'username' => $user['username']
        ];
        $_SESSION['role'] = $user['role'];

        // clear code after successful login
        $stmt2 = $conn->prepare("UPDATE users SET login_code = NULL, code_expiry = NULL WHERE id = ?");
        $stmt2->bind_param("i", $user['id']);
        $stmt2->execute();
        $stmt2->close();

        echo "success";
    } else {
        echo "invalid"; // wrong code or expired
    }
} else {
    echo "invalid"; // email not found
}

$stmt->close();
?>
