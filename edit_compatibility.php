<?php
include 'db.php';
session_start();

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

if (!isset($_GET['id'])) {
    header("Location: admin_compatibility.php");
    exit;
}

$fish_id = intval($_GET['id']);

// Handle adding a new compatible fish
if (isset($_POST['new_fish_name']) && !empty(trim($_POST['new_fish_name']))) {
    $new_name = trim($_POST['new_fish_name']);

    // Handle image upload (optional)
    $image_url = null;
    if (!empty($_FILES['new_fish_image']['name'])) {
        $target_dir = "uploads/"; // Ensure this folder exists and is writable
        $filename = basename($_FILES["new_fish_image"]["name"]);
        $target_file = $target_dir . uniqid() . "_" . $filename;

        if (move_uploaded_file($_FILES["new_fish_image"]["tmp_name"], $target_file)) {
            $image_url = $target_file;
        }
    }

    // Insert into fishes table
    $stmt = $conn->prepare("INSERT INTO fishes (name, image_url) VALUES (?, ?)");
    $stmt->bind_param("ss", $new_name, $image_url);
    $stmt->execute();
    $new_fish_id = $stmt->insert_id;
    $stmt->close();

    // Link as compatible fish
    $stmt = $conn->prepare("INSERT INTO fish_compatibility (fish_id, compatible_with_id) VALUES (?, ?)");
    $stmt->bind_param("ii", $fish_id, $new_fish_id);
    $stmt->execute();
    $stmt->close();

    header("Location: edit_compatibility.php?id=$fish_id");
    exit;
}

// Fetch all other fish for selection
$stmt = $conn->prepare("SELECT id, name FROM fishes WHERE id != ? ORDER BY name ASC");
$stmt->bind_param("i", $fish_id);
$stmt->execute();
$all_fish_result = $stmt->get_result();
$all_fishes = $all_fish_result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Fetch current compatibility
$stmt = $conn->prepare("SELECT compatible_with_id FROM fish_compatibility WHERE fish_id = ?");
$stmt->bind_param("i", $fish_id);
$stmt->execute();
$result = $stmt->get_result();
$current_compat_ids = array_column($result->fetch_all(MYSQLI_ASSOC), 'compatible_with_id');
$stmt->close();

// Handle checkbox form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['compatible_ids'])) {
    $stmt = $conn->prepare("DELETE FROM fish_compatibility WHERE fish_id = ?");
    $stmt->bind_param("i", $fish_id);
    $stmt->execute();
    $stmt->close();

    $stmt = $conn->prepare("INSERT INTO fish_compatibility (fish_id, compatible_with_id) VALUES (?, ?)");
    foreach ($_POST['compatible_ids'] as $compatible_id) {
        $compatible_id = intval($compatible_id);
        if ($compatible_id !== $fish_id) {
            $stmt->bind_param("ii", $fish_id, $compatible_id);
            $stmt->execute();
        }
    }
    $stmt->close();

    header("Location: admin_compatibility.php");
    exit;
}

// Get fish name
$stmt = $conn->prepare("SELECT name FROM fishes WHERE id = ?");
$stmt->bind_param("i", $fish_id);
$stmt->execute();
$result = $stmt->get_result();
$fish = $result->fetch_assoc();
$stmt->close();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Edit Compatibility</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; display: flex; }
        .sidebar {
            width: 200px;
            background: #4CAF50;
            height: 100vh;
            color: #fff;
            position: fixed;
            padding-top: 20px;
        }
        .sidebar h2 { text-align: center; margin-bottom: 20px; }
        .sidebar a {
            display: block;
            color: #fff;
            padding: 10px 20px;
            text-decoration: none;
        }
        .sidebar a:hover { background: #45a049; }
        .main { margin-left: 200px; padding: 20px; flex: 1; }
        .form-section { max-width: 600px; }
        .submit-btn {
            background: green;
            color: white;
            padding: 10px 20px;
            font-size: 16px;
            border: none;
            cursor: pointer;
            margin-top: 10px;
        }
        label { display: block; margin: 10px 0 5px; }
        form input[type="text"], form input[type="file"] {
            margin-bottom: 10px;
            display: block;
        }
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
    <a href="browse.php">Browse (User)</a>
    <a href="logout.php">Logout</a>
</div>

<div class="main">
    <h2>Set Compatibility for "<?php echo htmlspecialchars($fish['name']); ?>"</h2>

    <form method="POST">
        <div class="form-section">
            <label>Select Compatible Fish:</label>
            <?php foreach ($all_fishes as $other_fish): ?>
                <div>
                    <input 
                        type="checkbox" 
                        name="compatible_ids[]" 
                        value="<?php echo $other_fish['id']; ?>" 
                        <?php echo in_array($other_fish['id'], $current_compat_ids) ? 'checked' : ''; ?>>
                    <?php echo htmlspecialchars($other_fish['name']); ?>
                </div>
            <?php endforeach; ?>
            <button type="submit" class="submit-btn">Save Compatibility</button>
        </div>
    </form>

    <hr>
    <h3>Add New Compatible Fish</h3>
    <form method="POST" enctype="multipart/form-data">
        <label>Fish Name:</label>
        <input type="text" name="new_fish_name" required>

        <label>Image (optional):</label>
        <input type="file" name="new_fish_image" accept="image/*">

        <button type="submit" class="submit-btn">Add & Link Fish</button>
    </form>
</div>

</body>
</html>
