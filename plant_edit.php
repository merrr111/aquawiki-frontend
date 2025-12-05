<?php
include 'db.php';
session_start();

if (!isset($_GET['id'])) {
    echo "No plant selected.";
    exit();
}

$plant_id = intval($_GET['id']);

// Fetch plant details
$stmt = $conn->prepare("SELECT * FROM plants WHERE id = ?");
$stmt->bind_param("i", $plant_id);
$stmt->execute();
$result = $stmt->get_result();
$plant = $result->fetch_assoc();

if (!$plant) {
    echo "Plant not found.";
    exit();
}

// Update logic
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'];
    $scientific_name = $_POST['scientific_name'];
    $description = $_POST['description'];
    $type = $_POST['type'];
    $growth_rate = $_POST['growth_rate'];
    $lighting = $_POST['lighting'];
    $co2_requirement = $_POST['co2_requirement'];

    $upload_dir = 'uploads/';
    $image_url = $plant['image_url']; // keep old image by default

    // Handle new image upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        if (in_array(strtolower($ext), ['jpg','jpeg','png','gif'])) {
            $new_name = uniqid() . '.' . $ext;
            $path = $upload_dir . $new_name;
            if (move_uploaded_file($_FILES['image']['tmp_name'], $path)) {
                $image_url = $path;
            }
        }
    }

    // Update plant record
    $stmt = $conn->prepare("UPDATE plants SET name=?, scientific_name=?, description=?, type=?, growth_rate=?, lighting=?, co2_requirement=?, image_url=? WHERE id=?");
    $stmt->bind_param("ssssssssi", $name, $scientific_name, $description, $type, $growth_rate, $lighting, $co2_requirement, $image_url, $plant_id);
    $stmt->execute();

    header("Location: admin_plants.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Plant</title>
    <style>
/* ===== GENERAL STYLES ===== */
body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: #f5f5f5; /* light gray background */
    margin: 0;
    padding: 40px;
    display: flex;
    justify-content: center;
}

.container {
    background-color: #fff;
    padding: 30px 40px;
    border-radius: 15px;
    box-shadow: 0 8px 25px rgba(0,0,0,0.12);
    width: 100%;
    max-width: 700px;
}

h2 {
    text-align: center;
    margin-bottom: 20px;
    color: #555; /* gray title */
}

/* ===== FORM ELEMENTS ===== */
form {
    display: grid;
    grid-template-columns: 1fr;
    gap: 15px;
}

input, textarea, select {
    width: 100%;
    padding: 12px;
    border: 1px solid #ccc;
    border-radius: 8px;
    font-size: 14px;
}

textarea {
    min-height: 100px;
}

select {
    appearance: none;
    -webkit-appearance: none;
    -moz-appearance: none;
    background: #fff url("data:image/svg+xml;charset=US-ASCII,%3Csvg%20width%3D'10'%20height%3D'5'%20xmlns%3D'http://www.w3.org/2000/svg'%3E%3Cpath%20d%3D'M0%200l5%205%205-5z'%20fill%3D'%23666'%2F%3E%3C%2Fsvg%3E") no-repeat right 12px center;
    background-size: 10px 5px;
}

/* ===== BUTTONS ===== */
button, .back-button {
    border: none;
    padding: 12px;
    border-radius: 8px;
    font-size: 14px;
    cursor: pointer;
    display: inline-block;
    text-decoration: none;
    text-align: center;
    transition: background 0.3s ease;
}

button {
    background: #aaa; /* gray */
    color: #fff;
    margin-top: 8px;
}

button:hover {
    background: #888;
}

.back-button {
    background: #aaa;
    color: #fff;
    margin-bottom: 15px;
}

.back-button:hover {
    background: #888;
}

/* ===== IMAGE PREVIEW ===== */
img {
    margin-top: 10px;
    max-width: 150px;
    border-radius: 10px;
}

/* ===== RESPONSIVE ===== */
@media (max-width: 768px) {
    .container { padding: 20px; }
    input, textarea, select, button { font-size: 13px; padding: 10px; }
}

    </style>
</head>
<body>
<div class="container">
    <a href="admin_plants.php" class="back-button">← Back</a>
    <h2>Edit Plant</h2>
    <form method="POST" action="" enctype="multipart/form-data">
        <input type="text" name="name" value="<?php echo htmlspecialchars($plant['name']); ?>" placeholder="Plant Name" required>
        <input type="text" name="scientific_name" value="<?php echo htmlspecialchars($plant['scientific_name']); ?>" placeholder="Scientific Name" required>

        <textarea name="description" class="full-width" required><?php echo htmlspecialchars($plant['description']); ?></textarea>

        <!-- Type -->
        <select name="type" required>
            <option value="">Select Type</option>
            <option value="Foreground" <?php if($plant['type']=="Foreground") echo "selected"; ?>>Foreground</option>
            <option value="Midground" <?php if($plant['type']=="Midground") echo "selected"; ?>>Midground</option>
            <option value="Background" <?php if($plant['type']=="Background") echo "selected"; ?>>Background</option>
            <option value="Floating" <?php if($plant['type']=="Floating") echo "selected"; ?>>Floating</option>
        </select>

        <!-- Growth Rate -->
        <select name="growth_rate" required>
            <option value="">Select Growth Rate</option>
            <option value="Slow" <?php if($plant['growth_rate']=="Slow") echo "selected"; ?>>Slow</option>
            <option value="Medium" <?php if($plant['growth_rate']=="Medium") echo "selected"; ?>>Medium</option>
            <option value="Fast" <?php if($plant['growth_rate']=="Fast") echo "selected"; ?>>Fast</option>
        </select>

        <!-- Lighting -->
        <select name="lighting" required>
            <option value="">Select Lighting Needs</option>
            <option value="Low" <?php if($plant['lighting']=="Low") echo "selected"; ?>>Low</option>
            <option value="Medium" <?php if($plant['lighting']=="Medium") echo "selected"; ?>>Medium</option>
            <option value="High" <?php if($plant['lighting']=="High") echo "selected"; ?>>High</option>
        </select>

        <!-- CO₂ Requirement -->
        <select name="co2_requirement" required>
            <option value="">Select CO₂ Requirement</option>
            <option value="Not Required" <?php if($plant['co2_requirement']=="Not Required") echo "selected"; ?>>Not Required</option>
            <option value="Low" <?php if($plant['co2_requirement']=="Low") echo "selected"; ?>>Low</option>
            <option value="Medium" <?php if($plant['co2_requirement']=="Medium") echo "selected"; ?>>Medium</option>
            <option value="High" <?php if($plant['co2_requirement']=="High") echo "selected"; ?>>High</option>
            <option value="Required" <?php if($plant['co2_requirement']=="Required") echo "selected"; ?>>Required</option>
        </select>

        <!-- Image Upload -->
        <input type="file" name="image">
        <?php if ($plant['image_url']): ?>
            <p>Current Image:</p>
            <img src="<?php echo $plant['image_url']; ?>" alt="Plant Image" width="100">
        <?php endif; ?>

        <div class="full-width">
            <button type="submit">Update Plant</button>
        </div>
    </form>
</div>

</body>
</html>
