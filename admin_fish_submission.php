<?php
include 'db.php';
session_start();

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

$conn->query("UPDATE fish_submissions SET viewed = 1 WHERE viewed = 0");

// Fetch all fish submissions
$result = $conn->query("
    SELECT fs.id, fs.fish_name, fs.fish_info, fs.created_at, u.username
    FROM fish_submissions fs
    LEFT JOIN users u ON fs.user_id = u.id
    ORDER BY fs.created_at DESC
");
?>
<!DOCTYPE html>
<html>
<head>
    <title>User Fish Submissions</title>
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

        h2 {
            margin: 20px 0 10px;
            font-size: 24px;
            color: #2d8659;
            text-align: left;
            padding-left: 15px;
        }

        .notif-count {
            background: red;
            color: white;
            padding: 2px 6px;
            border-radius: 50%;
            font-size: 12px;
            vertical-align: super;
            margin-left: 5px;
        }

        .table-container {
            overflow-x: auto;
            margin: 25px;
            border-radius: 8px;
            background: white;
            padding: 10px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.05);
        }

        table {
            border-collapse: collapse;
            width: 100%;
            min-width: 900px;
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
            vertical-align: top;
            max-width: 160px;
            word-wrap: break-word;
        }

        tr:nth-child(even) {
            background: #f9fdfb;
        }
    </style>
</head>
<body>

    <!-- 🔙 Back Button -->
    <a class="back-btn" href="admin.php">← Back</a>

    <h2>User Fish Submissions</h2>

    <div class="table-container">
        <table>
            <tr>
                <th>ID</th>
                <th>Fish Name</th>
                <th>Information</th>
                <th>Submitted By</th>
                <th>Created At</th>
            </tr>

            <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?= $row['id'] ?></td>
                <td><?= htmlspecialchars($row['fish_name']) ?></td>
                <td><?= nl2br(htmlspecialchars($row['fish_info'])) ?></td>
                <td><?= htmlspecialchars($row['username'] ?? 'Guest') ?></td>
                <td><?= $row['created_at'] ?></td>
            </tr>
            <?php endwhile; ?>

        </table>
    </div>

</body>
</html>
