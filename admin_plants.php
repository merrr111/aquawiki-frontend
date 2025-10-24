<?php
include 'db.php';
session_start();

// Handle delete request
if (isset($_GET['delete'])) {
    $plant_id = intval($_GET['delete']);

    // Delete from mapping table first
    $conn->query("DELETE FROM fish_plants WHERE plant_id = $plant_id");

    // Delete from plants table
    $conn->query("DELETE FROM plants WHERE id = $plant_id");

    // Reset auto-increment (IDs start fresh after deletions)
    $conn->query("ALTER TABLE plants AUTO_INCREMENT = 1");

    header("Location: admin_plants.php");
    exit();
}

// Fetch all plants
$result = $conn->query("SELECT * FROM plants ORDER BY id ASC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Plants</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f0f4f7, #e6f7f1);
            margin: 0;
            padding: 40px;
        }
        .container {
            background: #fff;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            max-width: 1000px;
            margin: auto;
        }
        h2 {
            text-align: center;
            color: #2e7d32;
            margin-bottom: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        table th, table td {
            padding: 12px;
            border: 1px solid #ddd;
            text-align: left;
        }
        table th {
            background: #4caf50;
            color: white;
        }
        table tr:nth-child(even) {
            background: #f9f9f9;
        }
        a.delete-btn {
            color: #fff;
            background: #e53935;
            padding: 6px 12px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 14px;
        }
        a.delete-btn:hover {
            background: #c62828;
        }
        a.edit-btn {
    color: #fff;
    background: #1976d2;
    padding: 6px 12px;
    border-radius: 6px;
    text-decoration: none;
    font-size: 14px;
    margin-right: 5px;
}
a.edit-btn:hover {
    background: #0d47a1;
}

        .back-button {
            display: inline-block;
            margin-bottom: 15px;
            background-color: #4caf50;
            color: white;
            text-decoration: none;
            padding: 10px 16px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            transition: background 0.3s ease;
        }
        .back-button:hover {
            background-color: #388e3c;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="admin.php" class="back-button">← Back to Admin</a>
        <h2>Manage Plants</h2>
        <table>
            <tr>
                <th>ID</th>
                <th>Plant Name</th>
                <th>Scientific Name</th>
                <th>Description</th>
                <th>Image</th>
                <th>Action</th>
            </tr>
            <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?php echo $row['id']; ?></td>
                <td><?php echo htmlspecialchars($row['name']); ?></td>
                <td><?php echo htmlspecialchars($row['scientific_name']); ?></td>
                <td><?php echo htmlspecialchars(substr($row['description'], 0, 50)) . '...'; ?></td>
                <td>
                    <?php if ($row['image_url']): ?>
                        <img src="<?php echo $row['image_url']; ?>" alt="Plant Image" width="80" height="60">
                    <?php endif; ?>
                </td>
               <td>
    <a href="plant_edit.php?id=<?php echo $row['id']; ?>" 
       class="edit-btn">
        Edit
    </a>
    <a href="admin_plants.php?delete=<?php echo $row['id']; ?>" 
       class="delete-btn"
       onclick="return confirm('Are you sure you want to delete this plant?');">
        Delete
    </a>
</td>
                
            </tr>
            <?php endwhile; ?>
        </table>
    </div>
</body>
</html>
