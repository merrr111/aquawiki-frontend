<?php
include 'db.php';
session_start();
if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// Toggle feedback status
if (isset($_GET['toggle'], $_GET['id'], $_GET['status'])) {
    $id     = intval($_GET['id']);
    $status = intval($_GET['status']) === 1 ? 0 : 1;
    $stmt = $conn->prepare("UPDATE feedback SET status = ? WHERE id = ?");
    $stmt->bind_param("ii", $status, $id);
    $stmt->execute();
    header("Location: admin_feedback.php");
    exit;
}

// Delete feedback
if (isset($_GET['delete'], $_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = $conn->prepare("DELETE FROM feedback WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    header("Location: admin_feedback.php");
    exit;
}

// Fetch feedbacks with status and username
$query = "SELECT f.id, f.message, f.created_at, f.status, u.username 
          FROM feedback f 
          JOIN users u ON f.user_id = u.id 
          ORDER BY f.created_at DESC";

$result = $conn->query($query);
if (!$result) {
    die("Database error: " . $conn->error);
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>User Feedbacks</title>
    <style>
        body { font-family: Arial, sans-serif; margin:0; display:flex; }
        .sidebar {
            width:200px; background:#4CAF50; height:100vh; color:#fff;
            position:fixed; padding-top:20px;
        }
        .sidebar h2 { text-align:center; margin-bottom:20px; }
        .sidebar a {
            display:block; color:#fff; padding:10px 20px; text-decoration:none;
        }
        .sidebar a:hover { background:#45a049; }
        .main { margin-left:200px; padding:20px; flex:1; }
        table { width:100%; border-collapse:collapse; margin-top:20px; }
        th, td { border:1px solid #ccc; padding:8px; text-align:left; }
        .action-btn {
            padding:4px 8px; color:#fff; text-decoration:none; border-radius:4px;
            font-size:12px; margin-right:4px;
        }
        .enable-btn { background:#28a745; }
        .disable-btn { background:#ffc107; color:#000; }
        .delete-btn { background:#dc3545; }
    </style>
</head>
<body>
  <div class="sidebar">
    <h2>🐟 Admin</h2>
    <a href="admin.php">Dashboard</a>
    <a href="add_fish.php">Add Fish</a>
    <a href="admin_feedback.php">Feedbacks</a>
    <a href="admin_comments.php">Comments</a>
    <a href="browse.php">Browse (User)</a>
    <a href="logout.php">Logout</a>
  </div>

  <div class="main">
    <h2>User Feedbacks</h2>
    <table>
      <tr>
        <th>User</th>
        <th>Feedback</th>
        <th>Status</th>
        <th>When</th>
        <th>Actions</th>
      </tr>
      <?php while ($row = $result->fetch_assoc()): ?>
      <tr>
        <td><?php echo htmlspecialchars($row['username']); ?></td>
        <td><?php echo nl2br(htmlspecialchars($row['message'])); ?></td>
        <td><?php echo $row['status'] ? 'Enabled' : 'Disabled'; ?></td>
        <td><?php echo htmlspecialchars($row['created_at']); ?></td>
        <td>
          <a
            class="action-btn <?php echo $row['status'] ? 'disable-btn' : 'enable-btn'; ?>"
            href="admin_feedback.php?toggle=1&id=<?php echo $row['id'];?>&status=<?php echo $row['status'];?>"
          >
            <?php echo $row['status'] ? 'Disable' : 'Enable'; ?>
          </a>
          <a
            class="action-btn delete-btn"
            href="admin_feedback.php?delete=1&id=<?php echo $row['id'];?>"
            onclick="return confirm('Delete this feedback?')"
          >Delete</a>
        </td>
      </tr>
      <?php endwhile; ?>
    </table>
  </div>
</body>
</html>
