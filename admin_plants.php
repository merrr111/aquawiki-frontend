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

// Handle assignment
if (isset($_POST['assign_fish'])) {
    $plant_id = intval($_POST['plant_id']);
    $fish_ids = $_POST['fish_ids'] ?? [];

    // Delete old assignments
    $conn->query("DELETE FROM fish_plants WHERE plant_id = $plant_id");

    // Insert new assignments
    foreach ($fish_ids as $fish_id) {
        $fid = intval($fish_id);
        $conn->query("INSERT INTO fish_plants (plant_id, fish_id) VALUES ($plant_id, $fid)");
    }

    header("Location: admin_plants.php");
    exit();
}

// Fetch all plants
$result = $conn->query("SELECT * FROM plants ORDER BY id ASC");

// Fetch all fishes for assignment dropdown
$fishes = $conn->query("SELECT id, name FROM fishes ORDER BY name ASC");
$fishes_array = [];
while($f = $fishes->fetch_assoc()) {
    $fishes_array[] = $f;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Plants</title>
    <style>
body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: #f5f5f5; /* light gray background */
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
    color: #555; /* gray title */
    margin-bottom: 20px;
}

/* ===== TABLE STYLES ===== */
table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 15px;
}

table th, table td {
    padding: 12px;
    border: 1px solid #ddd;
    text-align: left;
    vertical-align: middle;
}

table th {
    background: #ccc; /* gray header */
    color: #333;
}

table tr:nth-child(even) {
    background: #f9f9f9;
}

img {
    width: 80px;
    height: 60px;
    object-fit: cover;
    border-radius: 4px;
}

/* ===== BUTTONS ===== */
a.delete-btn, a.edit-btn, button.assign-btn {
    padding: 6px 12px;
    border-radius: 6px;
    text-decoration: none;
    font-size: 14px;
    cursor: pointer;
    margin-right: 6px;   /* horizontal gap */
    margin-bottom: 4px;  /* vertical gap if wrap */
    display: inline-block;
}

a.delete-btn { 
    background: #b44; 
    color: #fff; 
}
a.delete-btn:hover { 
    background: #922; 
}

a.edit-btn { 
    background: #888; 
    color: #fff; 
}
a.edit-btn:hover { 
    background: #666; 
}

button.assign-btn { 
    background: #aaa; 
    color: #fff; 
    border: none; 
}
button.assign-btn:hover { 
    background: #888; 
}

/* ===== BACK BUTTON ===== */
.back-button {
    display: inline-block;
    margin-bottom: 15px;
    background-color: #aaa; /* gray */
    color: white;
    text-decoration: none;
    padding: 10px 16px;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    transition: background 0.3s ease;
}
.back-button:hover {
    background-color: #888;
}

/* ===== MODAL ===== */
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

/* ===== RESPONSIVE ===== */
@media (max-width:768px) {
    table { font-size:12px; }
    h2 { font-size:18px; }
    a.delete-btn, a.edit-btn, button.assign-btn { font-size:12px; padding:5px 10px; }
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
                    <a href="plant_edit.php?id=<?php echo $row['id']; ?>" class="edit-btn">Edit</a>
                    <a href="admin_plants.php?delete=<?php echo $row['id']; ?>" 
                       class="delete-btn"
                       onclick="return confirm('Are you sure you want to delete this plant?');">
                        Delete
                    </a>
                    <button class="assign-btn" onclick="openAssignModal(<?php echo $row['id']; ?>)">Assign to Fish</button>
                </td>
            </tr>
            <?php endwhile; ?>
        </table>
    </div>

    <!-- Modal -->
    <div id="assignModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeAssignModal()">&times;</span>
            <h3>Assign Plant to Fish</h3>
            <form method="POST" id="assignForm">
                <input type="hidden" name="plant_id" id="modalPlantId">
                <?php foreach ($fishes_array as $fish): ?>
                    <div>
                        <label>
                            <input type="checkbox" name="fish_ids[]" value="<?php echo $fish['id']; ?>">
                            <?php echo htmlspecialchars($fish['name']); ?>
                        </label>
                    </div>
                <?php endforeach; ?>
                <br>
                <button type="submit" name="assign_fish" style="padding:8px 16px; border:none; background:#4caf50; color:#fff; border-radius:6px; cursor:pointer;">Assign</button>
            </form>
        </div>
    </div>

    <script>
        function openAssignModal(plantId) {
            document.getElementById('modalPlantId').value = plantId;
            document.getElementById('assignModal').style.display = 'block';
        }

        function closeAssignModal() {
            document.getElementById('assignModal').style.display = 'none';
        }

        window.onclick = function(event) {
            const modal = document.getElementById('assignModal');
            if (event.target == modal) modal.style.display = "none";
        }
    </script>
</body>
</html>
