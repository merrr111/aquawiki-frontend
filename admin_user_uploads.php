<?php
include 'db.php';
session_start();
if (!isset($_SESSION['user'])) header("Location: login.php");

// Handle reset (delete all uploads of a user and reset AUTO_INCREMENT)
if (isset($_GET['reset_user']) && is_numeric($_GET['reset_user'])) {
    $user_id = intval($_GET['reset_user']);

    // Delete all uploads for this user
    $stmt = $conn->prepare("DELETE FROM user_uploads WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->close();

    // Reset AUTO_INCREMENT to 1
    $conn->query("ALTER TABLE user_uploads AUTO_INCREMENT = 1");

    header("Location: admin_user_uploads.php");
    exit;
}

// Fetch uploads grouped by user_id, include matched_fish_id and user email
$query = "
    SELECT u.user_id, usr.email, u.matched_fish_id, COUNT(*) AS total_uploads, MAX(u.upload_time) AS last_upload
    FROM user_uploads u
    INNER JOIN users usr ON u.user_id = usr.id
    GROUP BY u.user_id, u.matched_fish_id, usr.email
    ORDER BY last_upload DESC
";
$result = $conn->query($query);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin - User Uploads</title>
    <style>
        body { font-family: Arial, sans-serif; margin:0; display:flex; }
        .sidebar {
            width: 200px; background:#4CAF50; padding:20px 0; height:100vh; position:fixed;
        }
        .sidebar h2 { color:white; text-align:center; margin-bottom:20px; }
        .sidebar a { display:block; padding:10px 20px; color:white; text-decoration:none; margin-bottom:5px; }
        .sidebar a:hover { background:#45a049; }
        .main-content { margin-left:200px; padding:20px; flex:1; }
        h2 { margin-bottom:15px; }
        table { border-collapse:collapse; width:100%; font-size:14px; }
        th, td { border:1px solid #ccc; padding:8px; text-align:center; }
        .reset-btn { background:#dc3545; color:white; padding:5px 8px; border-radius:4px; text-decoration:none; font-size:12px; }
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
    <a href="admin_care_list.php">Care Guidelines</a> 
    <a href="admin_user_uploads.php">User Uploads</a>
    <a href="browse.php">Browse (User)</a>
    <a href="logout.php">Logout</a>
</div>

<div class="main-content">
    <h2>User Uploads</h2>
    <table>
        <tr>
            <th>Email</th>
            <th>Total Uploads</th>
            <th>Last Upload</th>
            <th>Matched Fish ID</th>
            <th>Action</th>
        </tr>
        <?php while ($row = $result->fetch_assoc()): ?>
        <tr>
            <td><?php echo htmlspecialchars($row['email']); ?></td>
            <td><?php echo $row['total_uploads']; ?></td>
            <td><?php echo $row['last_upload']; ?></td>
            <td><?php echo $row['matched_fish_id']; ?></td>
            <td>
                <a href="admin_user_uploads.php?reset_user=<?php echo $row['user_id']; ?>" 
                   class="reset-btn" 
                   onclick="return confirm('Reset all matched fish IDs for this user?')">Reset</a>
            </td>
        </tr>
        <?php endwhile; ?>
    </table>
</div>
</body>
</html>
