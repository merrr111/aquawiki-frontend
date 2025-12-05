<?php
include 'db.php';
session_start();

// Fetch fishes for dropdown
$fishes = $conn->query("SELECT id, name FROM fishes ORDER BY name ASC");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Form data
    $name = $_POST['name'];
    $scientific_name = $_POST['scientific_name']; // NEW
    $description = $_POST['description'];
    $prevention = $_POST['prevention'];
    $treatment = $_POST['treatment'];
    $fish_ids = $_POST['fish_ids']; // multiple fishes

    $upload_dir = 'uploads/';
    $image_url = "";

    // Upload disease image
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
        // Insert disease
        $stmt = $conn->prepare("INSERT INTO diseases (name, scientific_name, image_url, description, prevention, treatment) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssss", $name, $scientific_name, $image_url, $description, $prevention, $treatment);
        $stmt->execute();

        $disease_id = $conn->insert_id; // Get new disease ID

        // Assign to fishes
        if (!empty($fish_ids)) {
            foreach ($fish_ids as $fish_id) {
                $stmt2 = $conn->prepare("INSERT INTO fish_diseases (fish_id, disease_id) VALUES (?, ?)");
                $stmt2->bind_param("ii", $fish_id, $disease_id);
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
    background: linear-gradient(135deg, #f0f0f0, #fafafa); /* soft gray gradient */
    margin: 0;
    padding: 40px;
    display: flex;
    justify-content: center;
}

.container {
    background-color: #ffffff;
    padding: 30px 30px;
    border-radius: 15px;
    box-shadow: 0 12px 30px rgba(0, 0, 0, 0.08); /* subtle shadow */
    width: 100%;
    max-width: 850px;
    box-sizing: border-box;
    position: relative;
    border: 1px solid #d1d1d1;
}

.back-button {
    position: absolute;
    top: 20px;
    left: 20px;
    background-color: #9e9e9e;
    color: white;
    text-decoration: none;
    padding: 10px 18px;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    transition: background 0.3s ease, transform 0.2s ease;
}

.back-button:hover {
    background-color: #757575;
    transform: translateY(-2px);
}

h2 {
    text-align: center;
    margin-bottom: 30px;
    color: #424242;
    font-size: 28px;
    font-weight: bold;
}

/* ===== FORM LAYOUT ===== */
form {
    display: grid;
    grid-template-columns: 1fr;
    gap: 20px;
}

input[type="text"],
input[type="file"],
textarea,
select {
    width: 100%;
    padding: 12px 14px;
    border: 1px solid #cfcfcf;
    border-radius: 10px;
    font-size: 15px;
    box-sizing: border-box;
    background-color: #f9f9f9;
    transition: border 0.3s ease, box-shadow 0.2s ease, background 0.2s ease;
}

input[type="text"]:focus,
input[type="file"]:focus,
textarea:focus,
select:focus {
    border-color: #757575;
    box-shadow: 0 0 8px rgba(117, 117, 117, 0.3);
    outline: none;
    background-color: #f1f1f1;
}

textarea {
    resize: vertical;
    min-height: 120px;
}

/* ===== FULL WIDTH BUTTON ===== */
button[type="submit"] {
    background: linear-gradient(135deg, #9e9e9e, #616161);
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
    background: linear-gradient(135deg, #757575, #424242);
    transform: translateY(-2px);
}

small {
    font-size: 12px;
    color: #555;
}

/* ===== NAME + SCIENTIFIC NAME + IMAGE ROW ===== */
.name-scientific-image-row {
    display: flex;
    gap: 20px;
    flex-wrap: wrap;
}

.name-scientific-image-row input[type="text"] {
    flex: 1 1 200px;
}

.name-scientific-image-row .image-upload {
    flex: 1 1 150px;
    display: flex;
    flex-direction: column;
    align-items: center;
}

.name-scientific-image-row img {
    margin-top: 10px;
    max-width: 150px;
    max-height: 150px;
    border-radius: 10px;
    object-fit: cover;
    border: 1px solid #cfcfcf;
    display: none; /* hidden until file selected */
}

/* ===== MULTI-FISH SELECT ===== */
select[multiple] {
    min-height: 120px;
    padding: 10px;
    border-radius: 8px;
    border: 1px solid #cfcfcf;
    font-size: 15px;
    background-color: #f9f9f9;
}

/* ===== RESPONSIVE ===== */
@media (max-width: 700px) {
    .name-scientific-image-row {
        flex-direction: column;
    }
    .name-scientific-image-row .image-upload {
        align-items: flex-start;
    }
}

</style>

<div class="container">
    <a href="admin.php" class="back-button">← Back</a>
    <h2>Add New Fish Disease</h2>
    <form method="POST" action="" enctype="multipart/form-data">
        <div class="name-scientific-image-row">
            <input type="text" name="name" placeholder="Disease Name" required>
            <input type="text" name="scientific_name" placeholder="Scientific Name">
            <div class="image-upload">
                <input type="file" name="image" id="imageInput" required>
                <img id="imagePreview" src="" alt="Image Preview">
            </div>
        </div>

        <textarea name="description" placeholder="Description" class="full-width" required></textarea>
        <textarea name="prevention" placeholder="Prevention Methods" class="full-width" required></textarea>
        <textarea name="treatment" placeholder="Treatment / What to Do" class="full-width" required></textarea>

        <!-- multiple fish selection -->
        <select name="fish_ids[]" multiple required>
            <?php while ($fish = $fishes->fetch_assoc()): ?>
                <option value="<?php echo $fish['id']; ?>">
                    <?php echo htmlspecialchars($fish['name']); ?>
                </option>
            <?php endwhile; ?>
        </select>
        <small style="grid-column: 1 / -1; color:#666;">Hold Ctrl (Windows) or Command (Mac) to select multiple fishes</small>

        <div class="full-width">
            <button type="submit">Add Disease</button>
        </div>
    </form>
</div>

