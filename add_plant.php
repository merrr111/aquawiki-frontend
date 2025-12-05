<?php
include 'db.php';
session_start();

// Fetch fishes for dropdown
$fishes = $conn->query("SELECT id, name FROM fishes ORDER BY name ASC");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Form data
    $name = $_POST['name'];
    $scientific_name = $_POST['scientific_name'];
    $description = $_POST['description'];
    $type = $_POST['type'];
    $growth_rate = $_POST['growth_rate'];
    $lighting = $_POST['lighting'];
    $co2_requirement = $_POST['co2_requirement'];
    $fish_ids = $_POST['fish_ids']; // multiple fishes

    $upload_dir = 'uploads/';
    $image_url = "";

    // Upload plant image
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

    if ($image_url) {
        // Insert plant first (with extra fields)
        $stmt = $conn->prepare("INSERT INTO plants (name, scientific_name, description, type, growth_rate, lighting, co2_requirement, image_url) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssss", $name, $scientific_name, $description, $type, $growth_rate, $lighting, $co2_requirement, $image_url);
        $stmt->execute();

        $plant_id = $conn->insert_id; // Get the new plant ID

        // Insert into fish_plants (many-to-many mapping)
        if (!empty($fish_ids)) {
            foreach ($fish_ids as $fish_id) {
                $stmt2 = $conn->prepare("INSERT INTO fish_plants (fish_id, plant_id) VALUES (?, ?)");
                $stmt2->bind_param("ii", $fish_id, $plant_id);
                $stmt2->execute();
            }
        }

        header("Location: admin.php");
        exit();
    }
}
?>

<style>
 /* ===== GENERAL STYLES ===== */
body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: #f5f5f5; /* light gray background */
    margin: 0;
    padding: 20px;
    display: flex;
    justify-content: center;
}

.container {
    background-color: #fff;
    padding: 30px 20px; /* slightly smaller horizontal padding */
    border-radius: 15px;
    box-shadow: 0 8px 25px rgba(0,0,0,0.12);
    width: 100%;
    max-width: 1000px; /* wider for large screens */
    box-sizing: border-box; /* ensures padding included in width */
}

/* ===== BACK BUTTON ===== */
.back-button {
    position: absolute;
    top: 20px;
    left: 20px;
    background-color: #aaa; /* gray button */
    color: #fff;
    text-decoration: none;
    padding: 10px 18px;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    transition: background 0.3s ease, transform 0.2s ease;
}
.back-button:hover {
    background-color: #888;
    transform: translateY(-2px);
}

/* ===== TITLE ===== */
h2 {
    text-align: center;
    margin-bottom: 30px;
    color: #555; /* gray title */
    font-size: 26px;
    font-weight: bold;
}

/* ===== FORM ELEMENTS ===== */
form {
    display: grid;
    grid-template-columns: 1fr; /* single column to fit all components */
    gap: 15px;
}

form input[type="text"],
form input[type="file"],
form textarea,
form select {
    width: 100%;
    padding: 12px 14px;
    border: 1px solid #ccc;
    border-radius: 10px;
    font-size: 15px;
    box-sizing: border-box;
    background-color: #fafafa;
    transition: border 0.3s ease, box-shadow 0.2s ease;
}

form input[type="file"] {
    padding: 8px;
    background-color: #fff;
}

form input[type="text"]:focus,
form input[type="file"]:focus,
form textarea:focus,
form select:focus {
    border-color: #aaa;
    box-shadow: 0 0 6px rgba(150,150,150,0.3);
    outline: none;
}

textarea {
    resize: vertical;
    min-height: 120px;
}

.full-width {
    grid-column: 1 / -1;
}

/* ===== BUTTON ===== */
button[type="submit"] {
    background: #aaa; /* gray button */
    color: #fff;
    border: none;
    padding: 14px;
    font-size: 16px;
    font-weight: 600;
    border-radius: 10px;
    cursor: pointer;
    width: 100%;
    transition: background 0.3s ease, transform 0.2s ease;
    margin-top: 10px;
}

button[type="submit"]:hover {
    background: #888;
    transform: translateY(-2px);
}

/* ===== MULTIPLE SELECT ===== */
select[multiple] {
    height: 150px;
}

/* ===== SMALL HELP TEXT ===== */
small {
    font-size: 13px;
    color: #666;
}

/* ===== IMAGE UPLOAD PREVIEW ===== */
img {
    margin-top: 10px;
    max-width: 100%;
    border-radius: 10px;
}

/* ===== RESPONSIVE ===== */
@media (max-width: 768px) {
    .container { padding: 20px; }
    input, textarea, select, button { font-size: 14px; padding: 10px; }
}

</style>

<div class="container">
    <a href="admin.php" class="back-button">← Back</a>
    <h2>Add New Plant</h2>
    <form method="POST" action="" enctype="multipart/form-data">
        <input type="text" name="name" placeholder="Plant Name" required>
        <input type="text" name="scientific_name" placeholder="Scientific Name" required>

        <textarea name="description" placeholder="Description" class="full-width" required></textarea>

        <!-- NEW: plant details -->
        <input type="text" name="type" placeholder="Plant Type (e.g. Stem, Rosette)" required>
        <input type="text" name="growth_rate" placeholder="Growth Rate (e.g. Slow, Medium, Fast)" required>
        <input type="text" name="lighting" placeholder="Lighting Needs (e.g. Low, Medium, High)" required>
        <input type="text" name="co2_requirement" placeholder="CO₂ Requirement (e.g. Low, Medium, High)" required>

        <!-- multiple fish selection -->
        <select name="fish_ids[]" multiple required>
            <?php while ($fish = $fishes->fetch_assoc()): ?>
                <option value="<?php echo $fish['id']; ?>">
                    <?php echo htmlspecialchars($fish['name']); ?>
                </option>
            <?php endwhile; ?>
        </select>
        <small style="grid-column: 1 / -1; color:#666;">Hold Ctrl (Windows) or Command (Mac) to select multiple fishes</small>

        <input type="file" name="image" required>

        <div class="full-width">
            <button type="submit">Add Plant</button>
        </div>
    </form>
</div>
