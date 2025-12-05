<?php
include 'db.php';
session_start();
if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// Toggle comment status (Enable/Disable)
if (isset($_GET['toggle'], $_GET['id'], $_GET['status'])) {
    $id     = intval($_GET['id']);
    $status = intval($_GET['status']) === 1 ? 0 : 1;
    $stmt = $conn->prepare("UPDATE comments SET status = ? WHERE id = ?");
    $stmt->bind_param("ii", $status, $id);
    $stmt->execute();
    header("Location: admin_comments.php");
    exit;
}

// Delete comment
if (isset($_GET['delete'], $_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = $conn->prepare("DELETE FROM comments WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    header("Location: admin_comments.php");
    exit;
}

// Fetch all comments with fish name
$query = "
  SELECT 
    c.id,
    c.fish_id,
    c.username,
    c.comment AS text,
    c.status,
    c.created_at,
    f.name AS fish_name
  FROM comments c
  JOIN fishes f ON c.fish_id = f.id
  ORDER BY c.created_at DESC
";
$comments = $conn->query($query);
if (!$comments) {
    die("Database error: " . $conn->error);
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Manage Comments</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            margin:0; 
            padding:0;
            background:#f4f9f6;
        }

        /* 🔙 Back Button */
        .back-btn {
            display:inline-block;
            margin:15px;
            padding:8px 14px;
            background:#3bb77e;
            color:white;
            text-decoration:none;
            border-radius:6px;
            font-size:14px;
            transition:0.3s;
        }
        .back-btn:hover {
            background:#2d8659;
        }

        .main { 
            padding:20px;
        }

        h2 {
            color:#2d8659;
            margin-top:10px;
        }

        table { 
            width:100%; 
            border-collapse:collapse; 
            margin-top:20px; 
            background:white;
            box-shadow:0 2px 6px rgba(0,0,0,0.1);
        }
        th, td { 
            border:1px solid #ccc; 
            padding:10px; 
            text-align:left; 
            font-size:14px;
        }
        th {
            background:#3bb77e;
            color:white;
        }
        tr:nth-child(even) {
            background:#f9fdfb;
        }

        .action-btn {
            padding:6px 10px; 
            color:#fff; 
            text-decoration:none; 
            border-radius:4px;
            font-size:12px; 
            margin-right:4px;
        }
        .enable-btn { background:#28a745; }
        .disable-btn { background:#ffc107; color:#000; }
        .delete-btn { background:#dc3545; }
    </style>
</head>
<body>

    <!-- 🔙 Back Button -->
    <a class="back-btn" href="admin.php">← Back</a>

    <div class="main">
        <h2>Manage Comments</h2>
        <table>
            <tr>
                <th>Fish</th>
                <th>User</th>
                <th>Comment</th>
                <th>Status</th>
                <th>When</th>
                <th>Actions</th>
            </tr>
            <?php while ($c = $comments->fetch_assoc()): ?>
            <tr>
                <td><?php echo htmlspecialchars($c['fish_name']); ?></td>
                <td><?php echo htmlspecialchars($c['username']); ?></td>
                <td><?php echo htmlspecialchars($c['text']); ?></td>
                <td><?php echo $c['status'] ? 'Enabled' : 'Disabled'; ?></td>
                <td><?php echo htmlspecialchars($c['created_at']); ?></td>
                <td>
                <a
                    class="action-btn <?php echo $c['status'] ? 'disable-btn' : 'enable-btn'; ?>"
                    href="admin_comments.php?toggle=1&id=<?php echo $c['id'];?>&status=<?php echo $c['status'];?>"
                >
                    <?php echo $c['status'] ? 'Disable' : 'Enable'; ?>
                </a>
                <a
                    class="action-btn delete-btn"
                    href="admin_comments.php?delete=1&id=<?php echo $c['id'];?>"
                    onclick="return confirm('Delete this comment?')"
                >Delete</a>
                </td>
            </tr>
            <?php endwhile; ?>
        </table>
    </div>

</body>
</html>
