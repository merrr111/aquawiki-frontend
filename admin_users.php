<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
include 'db.php';

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

// ✅ Fetch all users including their role
$users = $conn->query("SELECT id, username, email, role FROM users ORDER BY id ASC");

// ✅ Handle user deletion
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);

    // 1️⃣ Delete related comments first (avoid foreign key error)
    $conn->query("DELETE FROM community_comments WHERE user_id = $delete_id");

    // 2️⃣ Delete the user
    $delete_stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $delete_stmt->bind_param("i", $delete_id);
    $delete_stmt->execute();

    // 3️⃣ Reset AUTO_INCREMENT (optional)
    $conn->query("ALTER TABLE users AUTO_INCREMENT = 1");

    header("Location: admin_users.php");
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Manage Users — Admin</title>
    <style>
        body { 
            font-family: "Segoe UI", Arial, sans-serif; 
            margin: 0; 
            background: #f4f9f6; 
            color: #333;
        }

        /* 🔙 Back Button */
        .back-btn {
            display: inline-block;
            margin: 15px;
            padding: 8px 14px;
            background: #3bb77e;
            color: white;
            font-size: 14px;
            text-decoration: none;
            border-radius: 6px;
            transition: 0.3s;
        }

        .back-btn:hover {
            background: #2d8659;
        }

        .main-content { 
            padding: 20px; 
        }

        h2 { 
            margin-bottom: 15px; 
            font-size: 24px; 
            color: #2d8659; 
        }

        .table-container { 
            overflow-x: auto; 
            margin-bottom: 25px; 
            border-radius: 8px; 
            background: white; 
            padding: 10px; 
            box-shadow: 0 2px 6px rgba(0,0,0,0.05); 
        }

        table { 
            border-collapse: collapse; 
            width: 100%; 
            margin: 5px 0; 
            min-width: 700px; 
            font-size: 14px; 
        }

        th { 
            background: #3bb77e; 
            color: white; 
            padding: 10px; 
            text-align: center; 
            font-weight: 600; 
        }

        td { 
            padding: 8px; 
            border: 1px solid #eee; 
            text-align: center; 
            vertical-align: middle; 
        }

        tr:nth-child(even) { 
            background: #f9fdfb; 
        }

        .action-btn { 
            padding: 5px 8px; 
            border-radius: 6px; 
            color: white; 
            font-size: 12px; 
            text-decoration: none; 
            transition: 0.3s; 
        }

        .edit-btn { background:#007BFF; }
        .edit-btn:hover { background:#0056b3; }

        .delete-btn { background:#dc3545; }
        .delete-btn:hover { background:#a71d2a; }

        .role-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 12px;
            color: white;
        }

        .role-admin { background: #3bb77e; }
        .role-user { background: #6c757d; }

    </style>
</head>
<body>

    <!-- 🔙 BACK BUTTON (Top Left) -->
    <a class="back-btn" href="admin.php">← Back</a>

    <div class="main-content">
        <h2>Manage Users</h2>
        <div class="table-container">
            <table>
                <tr>
                    <th>#</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Actions</th>
                </tr>
                <?php 
                $counter = 1; 
                while ($row = $users->fetch_assoc()): 
                ?>
                <tr>
                    <td><?= $counter ?></td>
                    <td><?= htmlspecialchars($row['username']) ?></td>
                    <td><?= htmlspecialchars($row['email']) ?></td>
                    <td>
                        <?php if ($row['role'] === 'admin'): ?>
                            <span class="role-badge role-admin">Admin</span>
                        <?php else: ?>
                            <span class="role-badge role-user">User</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <a class="action-btn edit-btn" href="edit_user.php?id=<?= $row['id'] ?>">Edit</a>
                        <a class="action-btn delete-btn" 
                           href="admin_users.php?delete_id=<?= $row['id'] ?>" 
                           onclick="return confirm('Are you sure you want to delete this user?')">
                           Delete
                        </a>
                    </td>
                </tr>
                <?php 
                $counter++;
                endwhile; 
                ?>
            </table>
        </div>
    </div>

    <?php $conn->close(); ?>
</body>
</html>
