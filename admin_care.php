<?php
include 'db.php';
session_start();

// Redirect if not logged in
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['id'])) {
    $fish_id = intval($_GET['id']);

    // Collect form inputs
    $care_guidelines = $_POST['care_guidelines'];
    $temp_range = $_POST['temp_range'];
    $ph_range = $_POST['ph_range'];
    $hardness_range = $_POST['hardness_range'];
    $natural_habitat = $_POST['natural_habitat'];
    $breeding = $_POST['breeding'];
    $diet_feeding = $_POST['diet_feeding'];

    // Update database
    $stmt = $conn->prepare("UPDATE fishes SET care_guidelines = ?, temp_range = ?, ph_range = ?, hardness_range = ?, natural_habitat = ?, breeding = ?, diet_feeding = ? WHERE id = ?");
    $stmt->bind_param("sssssssi", $care_guidelines, $temp_range, $ph_range, $hardness_range, $natural_habitat, $breeding, $diet_feeding, $fish_id);
    $stmt->execute();
    $stmt->close();

    header("Location: admin_care_list.php?success=1");
    exit;
}

// Fetch fish data
if (isset($_GET['id'])) {
    $fish_id = intval($_GET['id']);
    $stmt = $conn->prepare("SELECT name, care_guidelines, temp_range, ph_range, hardness_range, natural_habitat, breeding, diet_feeding FROM fishes WHERE id = ?");
    $stmt->bind_param("i", $fish_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $fish = $result->fetch_assoc();
    $stmt->close();

    if (!$fish) {
        header("Location: admin_care_list.php");
        exit;
    }
} else {
    header("Location: admin_care_list.php");
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Care Guidelines</title>
    <style>
        body { font-family: Arial, sans-serif; }
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            color: #333;
            display: flex;
        }
        .sidebar {
            width: 200px;
            background-color: #4CAF50;
            padding: 20px 0;
            height: 100vh;
            position: fixed;
        }
        .sidebar h2 {
            color: white;
            text-align: center;
            margin-bottom: 20px;
        }
        .sidebar a {
            display: block;
            padding: 10px 20px;
            color: white;
            text-decoration: none;
            margin-bottom: 5px;
        }
        .sidebar a:hover {
            background-color: #45a049;
        }
        .main-content { margin-left: 200px; padding: 20px; }
        .care-form { margin: 20px 0; }
        textarea, input[type="text"] { width: 100%; padding: 10px; margin-bottom: 10px; font-size: 14px; }
        .submit-btn { background-color: green; color: white; padding: 10px 20px; font-size: 16px; border: none; cursor: pointer; }
        label { font-weight: bold; }
    </style>
</head>
<body>

<div class="sidebar">
    <h2>🐟 Admin</h2>
    <a href="admin.php">Dashboard</a>
    <a href="add_fish.php">Add Fish</a>
    <a href="admin_feedback.php">Feedbacks</a>
    <a href="admin_comments.php">Comments</a>
    <a href="admin_compatibility.php">Compatibility</a>
    <a href="admin_care_list.php">Care Guidelines</a>
    <a href="browse.php">Browse (User)</a>
    <a href="logout.php">Logout</a>
</div>

<div class="main-content">
    <h2>Edit Care Guidelines for "<?php echo htmlspecialchars($fish['name']); ?>"</h2>

    <form class="care-form" method="POST" action="admin_care.php?id=<?php echo $fish_id; ?>">
        <label for="care_guidelines">Care Guidelines:</label>
        <textarea name="care_guidelines" id="care_guidelines"><?php echo htmlspecialchars($fish['care_guidelines']); ?></textarea>

        <label for="temp_range">Temperature Range:</label>
        <input type="text" name="temp_range" id="temp_range" value="<?php echo htmlspecialchars($fish['temp_range']); ?>">

        <label for="ph_range">pH Range:</label>
        <input type="text" name="ph_range" id="ph_range" value="<?php echo htmlspecialchars($fish['ph_range']); ?>">

        <label for="hardness_range">Hardness Range:</label>
        <input type="text" name="hardness_range" id="hardness_range" value="<?php echo htmlspecialchars($fish['hardness_range']); ?>">

        <label for="natural_habitat">Natural Habitat:</label>
        <textarea name="natural_habitat" id="natural_habitat"><?php echo htmlspecialchars($fish['natural_habitat']); ?></textarea>

        <label for="breeding">Breeding:</label>
        <textarea name="breeding" id="breeding"><?php echo htmlspecialchars($fish['breeding']); ?></textarea>

        <label for="diet_feeding">Diet and Feeding:</label>
        <textarea name="diet_feeding" id="diet_feeding"><?php echo htmlspecialchars($fish['diet_feeding']); ?></textarea>

        <button type="submit" class="submit-btn">Save Changes</button>
    </form>
</div>

</body>
</html>
