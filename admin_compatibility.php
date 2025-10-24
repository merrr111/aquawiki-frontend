<?php
include 'db.php';
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

$result = $conn->query("SELECT id, name FROM fishes ORDER BY name ASC");
$fishes = $result->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Manage Compatibility</title>
    <style>
        body { font-family: Arial, sans-serif; margin:0; display:flex; }
        .sidebar {
            width:200px;   
            background:#4CAF50; 
            height:100vh; 
            color:#fff;
            position:fixed; 
            padding-top:20px;
        }
        .sidebar h2 { 
            text-align:center; 
            margin-bottom:20px;
        }
        .sidebar a {
            display:block; 
            color:#fff; 
            padding:10px 20px; 
            text-decoration:none;
        }
        .sidebar a:hover { 
            background:#45a049;
        }
        .main { 
            margin-left:200px; 
            padding:20px; 
            flex:1;
        }
        table { 
            width:100%; 
            border-collapse:collapse; 
            margin-top:20px;
        }
        th, td { 
            border:1px solid #ccc; 
            padding:8px; 
            text-align:left;
        }
        .action-btn {
            padding:4px 8px; 
            color:#fff; 
            text-decoration:none; 
            border-radius:4px;
            font-size:12px; 
            margin-right:4px;
        }
    </style>
</head>
<body>

<div class="sidebar">
    <h2>🐟 Admin</h2>
    <a href="admin.php">Dashboard</a>
    <a href="add_fish.php">Add Fish</a>
    <a href="admin_feedback.php">Feedbacks</a>
    <a href="admin_comments.php">Comments</a>
    <a href="admin_compatibility.php">Compatibility</a>
    <a href="browse.php">Browse (User)</a>
    <a href="logout.php">Logout</a>
</div>

<div class="main">
    <h2>Fish Compatibility Manager</h2>
    <table>
        <thead>
            <tr>
                <th>Fish Name</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($fishes as $fish): ?>
                <tr>
                    <td><?php echo htmlspecialchars($fish['name']); ?></td>
                    <td>
                        <a class="action-btn" style="background-color:#2196F3;" href="edit_compatibility.php?id=<?php echo $fish['id']; ?>">Edit Compatibility</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

</body>
</html>
