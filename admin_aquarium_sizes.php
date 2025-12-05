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

// Fetch all aquarium size entries with fish name
$result = $conn->query("
    SELECT a.id, a.fish_id, a.tank_size, a.info, a.cleaning_frequency, f.name AS fish_name
    FROM aquarium_sizes a
    JOIN fishes f ON a.fish_id = f.id
    ORDER BY a.id ASC
");

// Fetch all fishes for edit dropdown
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
    background: #e0f7fa;
    margin: 0;
    padding: 40px;
}

/* ===== CONTAINER ===== */
.container {
    background: #fff;
    padding: 30px 25px;
    border-radius: 15px;
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
    max-width: 1100px;
    width: 100%;
    margin: auto;
    box-sizing: border-box;
}

/* ===== TITLE ===== */
h2 {
    text-align: center;
    color: #00796b;
    margin-bottom: 25px;
}

/* ===== BACK BUTTON ===== */
.back-button {
    display: inline-block;
    margin-bottom: 15px;
    background: #00bcd4;
    color: #fff;
    text-decoration: none;
    padding: 10px 16px;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    transition: background 0.3s ease, transform 0.2s ease;
}
.back-button:hover {
    background: #0097a7;
    transform: translateY(-2px);
}

/* ===== TABLE STYLES ===== */
table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
    table-layout: fixed;
    word-wrap: break-word;
}

table th, table td {
    padding: 12px;
    border: 1px solid #ddd;
    text-align: left;
    vertical-align: top;
}

table th {
    background: #00bcd4;
    color: white;
    font-weight: 600;
}

table tr:nth-child(even) {
    background: #f9f9f9;
}

/* ===== ACTION BUTTONS ===== */
a.delete-btn,
a.edit-btn {
    padding: 6px 12px;
    border-radius: 6px;
    text-decoration: none;
    font-size: 14px;
    cursor: pointer;
    display: inline-block;
    margin-right: 5px;
}

a.delete-btn {
    background: #e53935;
    color: #fff;
}
a.delete-btn:hover {
    background: #c62828;
}

a.edit-btn {
    background: #1976d2;
    color: #fff;
}
a.edit-btn:hover {
    background: #0d47a1;
}

/* ===== FORMS & MODAL ===== */
form input[type=text],
form textarea,
form select {
    width: 100%;
    padding: 10px;
    margin-bottom: 10px;
    border-radius: 6px;
    border: 1px solid #cfd8dc;
    box-sizing: border-box;
}

textarea {
    min-height: 80px;
    resize: vertical;
}

button[type=submit] {
    background: #00bcd4;
    color: #fff;
    border: none;
    padding: 10px 14px;
    border-radius: 6px;
    cursor: pointer;
    font-weight: 600;
    transition: background 0.3s ease, transform 0.2s ease;
}
button[type=submit]:hover {
    background: #0097a7;
    transform: translateY(-2px);
}

/* ===== MODAL ===== */
.modal {
    display: none;
    position: fixed;
    z-index: 9999;
    left: 0; top: 0;
    width: 100%; height: 100%;
    overflow: auto;
    background-color: rgba(0, 0, 0, 0.5);
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
.close:hover {
    color: #000;
}

/* ===== RESPONSIVE ===== */
@media (max-width: 900px) {
    table th, table td {
        font-size: 14px;
        padding: 8px;
    }
}

@media (max-width: 600px) {
    table, tbody, tr, th, td {
        display: block;
        width: 100%;
    }
    tr {
        margin-bottom: 15px;
        border-bottom: 2px solid #f1f1f1;
    }
    th {
        background: #0097a7;
        color: #fff;
        padding: 10px;
    }
    td {
        border: none;
        padding: 10px 5px;
        position: relative;
        text-align: left;
    }
    td::before {
        content: attr(data-label);
        font-weight: 600;
        display: block;
        margin-bottom: 5px;
        color: #00796b;
    }
}
</style>
</head>
<body>
<div class="container">
    <a href="admin.php" class="back-button">← Back to Admin</a>
    <h2>Manage Aquarium Sizes</h2>
    <table>
        <tr>

            <th>Fish Name</th>
            <th>Tank Size</th>
            <th>Info</th>
            <th>Cleaning Frequency</th>
            <th>Action</th>
        </tr>
        <?php while ($row = $result->fetch_assoc()): ?>
        <tr>
            
            <td><?= htmlspecialchars($row['fish_name']); ?></td>
            <td><?= htmlspecialchars($row['tank_size']); ?></td>
            <td><?= htmlspecialchars($row['info']); ?></td>
            <td><?= htmlspecialchars($row['cleaning_frequency']); ?></td>
            <td>
                <a href="edit_aquarium_size.php?id=<?= $row['id']; ?>" class="edit-btn">Edit</a>
                <a href="admin_aquarium_sizes.php?delete=<?= $row['id']; ?>" 
                   class="delete-btn" onclick="return confirm('Are you sure?');">Delete</a>
            </td>
        </tr>
        <?php endwhile; ?>
    </table>
</div>
</body>
</html>
