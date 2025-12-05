<?php
include 'db.php';
session_start();

// Handle delete request
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM aquarium_sizes WHERE id = $id");
    header("Location: admin_aquarium_sizes.php");
    exit();
}

// Fetch all aquarium size entries
$result = $conn->query("
    SELECT a.id, f.name AS fish_name, a.tank_size, a.info, a.fish_id 
    FROM aquarium_sizes a 
    JOIN fishes f ON f.id = a.fish_id
    ORDER BY a.id ASC
");

// Fetch all fishes for dropdown / assignment
$fishes = $conn->query("SELECT id, name FROM fishes ORDER BY name ASC");
$fishes_array = [];
while ($f = $fishes->fetch_assoc()) {
    $fishes_array[] = $f;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Manage Aquarium Sizes</title>
<style>
body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: linear-gradient(135deg, #e0f7fa, #f1f8f9);
    margin: 0; padding: 40px;
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
    color: #00796b;
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
    background: #00bcd4;
    color: white;
}
table tr:nth-child(even) {
    background: #f9f9f9;
}
a.delete-btn, a.edit-btn, button.assign-btn {
    padding: 6px 12px;
    border-radius: 6px;
    text-decoration: none;
    font-size: 14px;
    cursor: pointer;
}
a.delete-btn { background: #e53935; color: #fff; }
a.delete-btn:hover { background: #c62828; }
a.edit-btn { background: #1976d2; color: #fff; margin-right: 5px; }
a.edit-btn:hover { background: #0d47a1; }
button.assign-btn { background: #ff9800; color: #fff; border: none; margin-right: 5px; }
button.assign-btn:hover { background: #f57c00; }

.back-button {
    display: inline-block;
    margin-bottom: 15px;
    background-color: #00796b;
    color: white;
    text-decoration: none;
    padding: 10px 16px;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
}
.back-button:hover { background-color: #004d40; }

/* Modal styles */
.modal {
    display: none;
    position: fixed;
    z-index: 9999;
    left: 0; top: 0;
    width: 100%; height: 100%;
    overflow: auto;
    background-color: rgba(0,0,0,0.5);
}
.modal-content {
    background: #fff;
    margin: 10% auto;
    padding: 20px;
    border-radius: 12px;
    width: 400px;
    max-width: 90%;
}
.close {
    color: #aaa;
    float: right;
    font-size: 24px;
    font-weight: bold;
    cursor: pointer;
}
.close:hover { color: #000; }
form input[type="text"], form select, form textarea {
    width: 100%;
    padding: 10px;
    margin-top: 5px;
    border-radius: 8px;
    border: 1px solid #cfd8dc;
}
</style>
</head>
<body>
<div class="container">
    <a href="admin.php" class="back-button">← Back to Admin</a>
    <h2>Manage Aquarium Sizes</h2>

    <table>
        <tr>
            <th>ID</th>
            <th>Fish</th>
            <th>Tank Size</th>
            <th>Info</th>
            <th>Action</th>
        </tr>
        <?php while($row = $result->fetch_assoc()): ?>
        <tr>
            <td><?= $row['id'] ?></td>
            <td><?= htmlspecialchars($row['fish_name']) ?></td>
            <td><?= htmlspecialchars($row['tank_size']) ?></td>
            <td><?= htmlspecialchars(substr($row['info'],0,50)) ?>...</td>
            <td>
                <a href="edit_aquarium_size.php?id=<?= $row['id'] ?>" class="edit-btn">Edit</a>
                <a href="admin_aquarium_sizes.php?delete=<?= $row['id'] ?>" class="delete-btn" onclick="return confirm('Delete this entry?');">Delete</a>
            </td>
        </tr>
        <?php endwhile; ?>
    </table>
</div>
</body>
</html>
