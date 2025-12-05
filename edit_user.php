<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
include 'db.php';

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

if (!isset($_GET['id'])) {
    header("Location: admin_users.php");
    exit;
}

$user_id = intval($_GET['id']);
$stmt = $conn->prepare("SELECT username, email, role FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $role = $_POST['role'];

    $update_stmt = $conn->prepare("UPDATE users SET username = ?, email = ?, role = ? WHERE id = ?");
    $update_stmt->bind_param("sssi", $username, $email, $role, $user_id);
    $update_stmt->execute();

    header("Location: admin_users.php");
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Edit User</title>
    <style>
        body {
            font-family: "Segoe UI", Arial, sans-serif;
            background: #f4f9f6;
            margin: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        form {
            background: white;
            padding: 20px 30px;
            border-radius: 8px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
            width: 320px;
        }
        h2 {
            text-align: center;
            color: #2d8659;
        }
        input[type="text"], input[type="email"], select {
            width: 100%;
            padding: 8px;
            margin: 10px 0;
            border-radius: 6px;
            border: 1px solid #ccc;
        }
        button {
            background: #3bb77e;
            color: white;
            border: none;
            padding: 10px;
            border-radius: 6px;
            cursor: pointer;
            width: 100%;
        }
        button:hover {
            background: #2d8659;
        }
        a {
            display: block;
            text-align: center;
            margin-top: 10px;
            color: #3bb77e;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <form method="POST">
        <h2>Edit User</h2>
        <input type="text" name="username" value="<?= htmlspecialchars($user['username']) ?>" required>
        <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
        
        <!-- Role dropdown -->
        <label for="role" style="font-size:14px; color:#333;">Role:</label>
        <select name="role" id="role" required>
            <option value="user" <?= $user['role'] === 'user' ? 'selected' : '' ?>>User</option>
            <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
        </select>

        <button type="submit">Save Changes</button>
        <a href="admin_users.php">Cancel</a>
    </form>
</body>
</html>
