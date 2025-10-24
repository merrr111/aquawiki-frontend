<?php
include 'db.php';
session_start();

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

// Fetch all distinct types
$types = [];
$type_query = $conn->query("SELECT DISTINCT type FROM fishes ORDER BY type ASC");
if ($type_query) {
    while ($row = $type_query->fetch_assoc()) {
        $types[] = $row['type'];
    }
}

// Toggle fish status (Enable/Disable)
if (isset($_GET['toggle_status'], $_GET['id'], $_GET['current_status'])) {
    $fish_id = intval($_GET['id']);
    $current_status = intval($_GET['current_status']);
    $new_status = $current_status === 1 ? 0 : 1;
    $stmt = $conn->prepare("UPDATE fishes SET status = ? WHERE id = ?");
    $stmt->bind_param("ii", $new_status, $fish_id);
    $stmt->execute();
    header("Location: admin.php");
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard</title>
    <style>
        body {
            font-family: "Segoe UI", Arial, sans-serif;
            margin: 0;
            background: #f4f9f6;
            color: #333;
            display: flex;
        }
        .sidebar {
            width: 220px;
            background: linear-gradient(180deg, #3bb77e, #2d8659);
            padding: 20px 0;
            height: 100vh;
            position: fixed;
            box-shadow: 2px 0 6px rgba(0,0,0,0.1);
        }
        .sidebar h2 {
            color: white;
            text-align: center;
            margin-bottom: 25px;
            font-size: 20px;
        }
        .sidebar a {
            display: block;
            padding: 12px 20px;
            color: white;
            text-decoration: none;
            margin: 6px 0;
            font-size: 14px;
            border-radius: 6px;
            transition: all 0.3s ease;
        }
        .sidebar a:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateX(5px);
        }
        .main-content {
            margin-left: 220px;
            padding: 25px;
            flex: 1;
        }
        h2 {
            margin-bottom: 15px;
            font-size: 24px;
            color: #2d8659;
        }
        .add-btn {
            background: #3bb77e;
            color: white;
            padding: 8px 14px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 14px;
            transition: 0.3s;
        }
        .add-btn:hover {
            background: #2d8659;
        }
        h3 {
            margin: 25px 0 10px;
            font-size: 18px;
            color: #3bb77e;
            border-bottom: 2px solid #3bb77e;
            display: inline-block;
            padding-bottom: 3px;
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
        img {
            width: 60px;
            height: 45px;
            object-fit: cover;
            border-radius: 4px;
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
        .toggle-btn { background:#28a745; }
        .toggle-btn:hover { background:#1e7e34; }
        .status-enabled { color:green; font-weight:bold; }
        .status-disabled { color:red; font-weight:bold; }
        .truncate {
            max-width:120px; white-space:nowrap;
            overflow:hidden; text-overflow:ellipsis;
        }
        @media (max-width:768px) {
            table { font-size:12px; min-width:600px; }
            h3 { font-size:16px; }
            .add-btn { font-size:12px; padding:6px 10px; }
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <h2>🐟 Admin</h2>
        <a href="admin.php">Dashboard</a>
        <a href="add_fish.php">Add Fish</a>
        <a href="add_plant.php">Add Plant</a>    
         <a href="admin_plants.php">Manage Plant</a>    
        <a href="admin_user_uploads.php">User Uploads</a>
        <a href="admin_feedback.php">Feedbacks</a>
        <a href="admin_comments.php">Comments</a>
        <a href="admin_compatibility.php">Compatibility</a>
        <a href="admin_care_list.php">Care Guidelines</a>
        <a href="browse.php">Browse (User)</a>
        <a href="logout.php">Logout</a>
    </div>

    <div class="main-content">
        <h2>Admin Dashboard</h2>
        <div style="margin-bottom:15px;">
            <a class="add-btn" href="add_fish.php">➕ Add New Fish</a>
        </div>

        <?php foreach ($types as $type): ?>
            <h3><?php echo htmlspecialchars($type); ?> Fish</h3>
            <div class="table-container">
                <table>
                    <tr>
                        <th>Img</th>
                        <th>Male Img</th>
                        <th>Name</th>
                        <th>Status</th>
                        <th>Scientific</th>
                        <th>Year</th>
                        <th>Origin</th>
                        <th>Description</th>
                        <th>Female Description</th>
                        <th>Male Description</th>
                        <th>Sexual</th>
                        <th>Temp</th>
                        <th>pH</th>
                        <th>Hardness</th>
                        <th>Habitat</th>
                        <th>Breeding</th>
                        <th>Diet</th>
                        <th>Actions</th>
                    </tr>
                    <?php
                    $stmt = $conn->prepare("SELECT * FROM fishes WHERE type = ? ORDER BY name ASC");
                    $stmt->bind_param("s", $type);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    while ($row = $result->fetch_assoc()):
                    ?>
                    <tr>
                        <td><img src="<?php echo htmlspecialchars($row['image_url']); ?>" alt="Female"></td>
                        <td>
                            <?php if (!empty($row['image_male_url'])): ?>
                                <img src="<?php echo htmlspecialchars($row['image_male_url']); ?>" alt="Male">
                            <?php else: ?>
                                <span style="color:gray; font-size:12px;">No Male Img</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($row['name']); ?></td>
                        <td>
                            <?php if ($row['status'] === 1): ?>
                                <span class="status-enabled">Enabled</span>
                            <?php else: ?>
                                <span class="status-disabled">Disabled</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($row['scientific_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['year_discovered']); ?></td>
                        <td><?php echo htmlspecialchars($row['origin']); ?></td>
                        <td class="truncate" title="<?php echo htmlspecialchars($row['description']); ?>">
                            <?php echo htmlspecialchars($row['description']); ?>
                        </td>
                        <td class="truncate" title="<?php echo htmlspecialchars($row['female_description']); ?>">
                            <?php echo htmlspecialchars($row['female_description']); ?>
                        </td>
                        <td class="truncate" title="<?php echo htmlspecialchars($row['male_description']); ?>">
                            <?php echo htmlspecialchars($row['male_description']); ?>
                        </td>
                        <td class="truncate" title="<?php echo htmlspecialchars($row['sexual_difference']); ?>">
                            <?php echo htmlspecialchars($row['sexual_difference']); ?>
                        </td>
                        <td><?php echo htmlspecialchars($row['temp_range']); ?></td>
                        <td><?php echo htmlspecialchars($row['ph_range']); ?></td>
                        <td><?php echo htmlspecialchars($row['hardness_range']); ?></td>
                        <td class="truncate" title="<?php echo htmlspecialchars($row['natural_habitat']); ?>">
                            <?php echo htmlspecialchars($row['natural_habitat']); ?>
                        </td>
                        <td class="truncate" title="<?php echo htmlspecialchars($row['breeding']); ?>">
                            <?php echo htmlspecialchars($row['breeding']); ?>
                        </td>
                        <td class="truncate" title="<?php echo htmlspecialchars($row['diet_feeding']); ?>">
                            <?php echo htmlspecialchars($row['diet_feeding']); ?>
                        </td>
                        <td>
                            <div style="display:flex; flex-direction:column; gap:6px;">
                                <a class="action-btn edit-btn" href="edit_fish.php?id=<?php echo $row['id']; ?>">Edit</a>
                                <a class="action-btn delete-btn" href="delete_fish.php?id=<?php echo $row['id']; ?>" onclick="return confirm('Delete?')">Delete</a>
                                <a class="action-btn toggle-btn"
                                   href="admin.php?toggle_status=1&id=<?php echo $row['id']; ?>&current_status=<?php echo $row['status']; ?>">
                                    <?php echo $row['status'] === 1 ? 'Disable' : 'Enable'; ?>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </table>
            </div>
        <?php endforeach; ?>
    </div>
</body>
</html>
