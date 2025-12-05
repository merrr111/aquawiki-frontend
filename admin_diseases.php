<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Log errors to a file in the same directory
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error_log.txt');
include 'db.php';
session_start();

// Handle delete request
if (isset($_GET['delete'])) {
    $disease_id = intval($_GET['delete']);

    // Delete from mapping table first
    $conn->query("DELETE FROM fish_diseases WHERE disease_id = $disease_id");

    // Delete from diseases table
    $conn->query("DELETE FROM diseases WHERE id = $disease_id");

    // Reset auto-increment
    $conn->query("ALTER TABLE diseases AUTO_INCREMENT = 1");

    header("Location: admin_diseases.php");
    exit();
}

// Handle assignment
if (isset($_POST['assign_fish'])) {
    $disease_id = intval($_POST['disease_id']);
    $fish_ids = $_POST['fish_ids'] ?? [];

    // Delete old assignments
    $conn->query("DELETE FROM fish_diseases WHERE disease_id = $disease_id");

    // Insert new assignments
    foreach ($fish_ids as $fish_id) {
    $fid = intval($fish_id);
    $conn->query("INSERT INTO fish_diseases (disease_id, fish_id) VALUES ($disease_id, $fid)");
}

    header("Location: admin_diseases.php");
    exit();
}

// Fetch all diseases
$result = $conn->query("SELECT * FROM diseases ORDER BY id ASC");

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
    <title>Manage Diseases</title>
    <style>
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

/* ===== Headings ===== */
h2 {
    text-align: center;
    color: #555; /* gray color */
    margin-bottom: 20px;
}

/* ===== Back Button ===== */
.back-button {
    display: inline-block;
    margin-bottom: 15px;
    background-color: #999; /* gray */
    color: white;
    text-decoration: none;
    padding: 10px 16px;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    transition: background 0.3s ease;
}
.back-button:hover {
    background-color: #777;
}

/* ===== Table Styles ===== */
table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 15px;
}

table th,
table td {
    padding: 12px;
    border: 1px solid #ddd;
    text-align: left;
    vertical-align: middle;
}

table th {
    background: #999; /* gray */
    color: white;
}

table tr:nth-child(even) {
    background: #f9f9f9;
}

/* ===== Table Buttons ===== */
a.delete-btn,
a.edit-btn,
button.assign-btn {
    padding: 6px 12px;
    border-radius: 6px;
    text-decoration: none;
    font-size: 14px;
    cursor: pointer;
    margin-right: 5px; /* gap between buttons */
    display: inline-block;
}

a.delete-btn {
    background: #e53935;
    color: #fff;
}
a.delete-btn:hover {
    background: #c62828;
}

a.edit-btn {
    background: #555; /* gray */
    color: #fff;
}
a.edit-btn:hover {
    background: #777; /* darker gray */
}

button.assign-btn {
    background: #ff9800;
    color: #fff;
    border: none;
}
button.assign-btn:hover {
    background: #f57c00;
}

/* Remove margin-right for last button in row */
td > a:last-child,
td > button:last-child {
    margin-right: 0;
}

/* ===== Modal Styles ===== */
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

/* ===== Form Inside Modal ===== */
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
textarea { min-height: 80px; resize: vertical; }

button[type=submit] {
    background: #555; /* gray */
    color: #fff;
    border: none;
    padding: 10px;
    border-radius: 6px;
    cursor: pointer;
}
button[type=submit]:hover {
    background: #777;
}

/* ===== Responsive Adjustments ===== */
@media (max-width: 600px) {
    td, th {
        font-size: 13px;
        padding: 8px;
    }
    a.delete-btn,
    a.edit-btn,
    button.assign-btn {
        display: block;
        margin-bottom: 5px;
    }
}
</style>

    </style>
</head>
<body>
<div class="container">
    <a href="admin.php" class="back-button">← Back to Admin</a>
    <h2>Manage Fish Diseases</h2>
    <table>
        <tr>
            <th>ID</th>
            <th>Disease Name</th>
            <th>Description</th>
            <th>Prevention</th>
            <th>Treatment</th>
            <th>Image</th>
            <th>Action</th>
        </tr>
        <?php while ($row = $result->fetch_assoc()): ?>
        <tr>
            <td><?php echo $row['id']; ?></td>
            <td><?php echo htmlspecialchars($row['name']); ?></td>
            <td><?php echo htmlspecialchars(substr($row['description'], 0, 50)) . '...'; ?></td>
            <td><?php echo htmlspecialchars(substr($row['prevention'], 0, 50)) . '...'; ?></td>
            <td><?php echo htmlspecialchars(substr($row['treatment'], 0, 50)) . '...'; ?></td>
            <td>
                <?php if ($row['image_url']): ?>
                    <img src="<?php echo $row['image_url']; ?>" alt="Disease Image" width="80" height="60">
                <?php endif; ?>
            </td>
            <td>
                <a href="disease_edit.php?id=<?php echo $row['id']; ?>" class="edit-btn">Edit</a>
                <a href="admin_diseases.php?delete=<?php echo $row['id']; ?>" 
                   class="delete-btn"
                   onclick="return confirm('Are you sure you want to delete this disease?');">
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
        <h3>Assign Disease to Fish</h3>
        <form method="POST" id="assignForm">
            <input type="hidden" name="disease_id" id="modalDiseaseId">
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
    function openAssignModal(diseaseId) {
        document.getElementById('modalDiseaseId').value = diseaseId;
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
