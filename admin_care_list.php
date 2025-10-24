<?php
include 'db.php';
session_start();
if (!isset($_SESSION['user'])) header("Location: login.php");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Select Fish to Edit Care Guidelines</title>
    <style>
          body {
            font-family: Arial, sans-serif;
            margin: 0;
            color: #333;
        }
        .sidebar {
            width: 200px;
            background-color: #4CAF50;
            padding: 20px 0;
            height: 100vh;
            position: fixed;
        }
        .sidebar h2 {
            color: white;
            text-align: center;
            margin-bottom: 20px;
        }
        .sidebar a {
            display: block;
            padding: 10px 20px;
            color: white;
            text-decoration: none;
            margin-bottom: 5px;
        }
        .sidebar a:hover {
            background-color: #45a049;
        }
        .main-content { margin-left: 200px; padding: 20px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ccc; padding: 8px; }
    </style>
</head>
<body>

<div class="sidebar">
    <h2 style="color:white; text-align:center;">🐟 Admin</h2>
    <a href="admin.php">Dashboard</a>
    <a href="add_fish.php">Add Fish</a>
    <a href="admin_feedback.php">Feedbacks</a>
    <a href="admin_comments.php">Comments</a>
    <a href="admin_compatibility.php">Compatibility</a>
    <a href="admin_care_list.php">Care Guidelines</a>
    <a href="browse.php">Browse (User)</a>
    <a href="logout.php">Logout</a>
</div>

<div class="main-content">
    <h2>Select Fish to Edit Care Guidelines</h2>
    <table>
        <tr><th>Fish Name</th><th>Action</th></tr>
        <?php
        $result = $conn->query("SELECT id, name FROM fishes");
        while ($row = $result->fetch_assoc()) {
            echo "<tr><td>" . htmlspecialchars($row['name']) . "</td>";
            echo "<td><a href='admin_care.php?id=" . $row['id'] . "'>Edit Care Guidelines</a></td></tr>";
        }
        ?>
    </table>
</div>

</body>
</html>

