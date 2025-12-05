<?php
include 'db.php';
session_start();

if (!isset($_GET['id'])) {
    header("Location: admin_aquarium_sizes.php");
    exit();
}

$id = intval($_GET['id']);

// Fetch aquarium size entry
$stmt = $conn->prepare("SELECT * FROM aquarium_sizes WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$aquarium = $result->fetch_assoc();

if (!$aquarium) {
    header("Location: admin_aquarium_sizes.php");
    exit();
}

// Fetch all fishes for dropdown
$fishes = $conn->query("SELECT id, name FROM fishes ORDER BY name ASC");
$fishes_array = [];
while ($f = $fishes->fetch_assoc()) {
    $fishes_array[] = $f;
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $fish_id = intval($_POST['fish_id']);
    $tank_size = trim($_POST['tank_size']);
    $info = trim($_POST['info']);
    $cleaning_frequency = trim($_POST['cleaning_frequency']);

    $stmt = $conn->prepare("UPDATE aquarium_sizes SET fish_id = ?, tank_size = ?, info = ?, cleaning_frequency = ? WHERE id = ?");
    $stmt->bind_param("isssi", $fish_id, $tank_size, $info, $cleaning_frequency, $id);
    $stmt->execute();

    header("Location: admin_aquarium_sizes.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Edit Aquarium Size</title>
<style>
body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: linear-gradient(135deg, #e0f7fa, #f1f8f9);
    margin: 0;
    padding: 40px;
    display: flex;
    justify-content: center;
}
.container {
    background-color: #ffffff;
    padding: 30px 40px;
    border-radius: 15px;
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.12);
    width: 100%;
    max-width: 600px;
    position: relative;
    border: 1px solid #b2ebf2;
}
.back-button {
    position: absolute;
    top: 20px;
    left: 20px;
    background-color: #00bcd4;
    color: white;
    text-decoration: none;
    padding: 10px 18px;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    transition: background 0.3s ease, transform 0.2s ease;
}
.back-button:hover {
    background-color: #0097a7;
    transform: translateY(-2px);
}
h2 {
    text-align: center;
    margin-bottom: 30px;
    color: #00796b;
    font-size: 26px;
    font-weight: bold;
}
form {
    display: grid;
    gap: 20px;
}
form label {
    font-weight: 600;
    color: #00796b;
}
form input[type="text"],
form textarea,
form select {
    width: 100%;
    padding: 12px 14px;
    border: 1px solid #cfd8dc;
    border-radius: 10px;
    font-size: 15px;
    box-sizing: border-box;
    background-color: #fafafa;
}
form input[type="text"]:focus,
form textarea:focus,
form select:focus {
    border-color: #00bcd4;
    box-shadow: 0 0 6px rgba(0, 188, 212, 0.3);
    outline: none;
}
textarea { resize: vertical; min-height: 100px; }
button[type="submit"] {
    background: linear-gradient(135deg, #00bcd4, #0097a7);
    color: #fff;
    border: none;
    padding: 14px;
    font-size: 16px;
    font-weight: 600;
    border-radius: 10px;
    cursor: pointer;
    width: 100%;
    transition: background 0.3s ease, transform 0.2s ease;
}
button[type="submit"]:hover {
    background: linear-gradient(135deg, #0097a7, #00796b);
    transform: translateY(-2px);
}
</style>
</head>
<body>
<div class="container">
    <a href="admin_aquarium_sizes.php" class="back-button">← Back</a>
    <h2>Edit Aquarium Size</h2>
    <form method="POST" action="">
        <label for="fish_id">Select Fish</label>
        <select name="fish_id" id="fish_id" required>
            <?php foreach ($fishes_array as $fish): ?>
                <option value="<?= $fish['id']; ?>" <?= $fish['id'] == $aquarium['fish_id'] ? 'selected' : ''; ?>>
                    <?= htmlspecialchars($fish['name']); ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label for="tank_size">Tank Size</label>
        <input type="text" name="tank_size" id="tank_size" value="<?= htmlspecialchars($aquarium['tank_size']); ?>" required>

        <label for="info">Tank Info / Recommendations</label>
        <textarea name="info" id="info" required><?= htmlspecialchars($aquarium['info']); ?></textarea>

        <label for="cleaning_frequency">Cleaning Frequency</label>
        <input type="text" name="cleaning_frequency" id="cleaning_frequency" value="<?= htmlspecialchars($aquarium['cleaning_frequency']); ?>" placeholder="e.g. Weekly, Every 2 Weeks" required>

        <button type="submit">Update Aquarium Size</button>
    </form>
</div>
</body>
</html>
