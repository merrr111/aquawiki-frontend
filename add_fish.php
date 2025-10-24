<?php
include 'db.php';
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Form data
    $name = $_POST['name'];
    $description = $_POST['description'];
    $female_description = $_POST['female_description'];
    $male_description = $_POST['male_description'];
    $scientific_name = $_POST['scientific_name'];
    $year_discovered = $_POST['year_discovered'];
    $origin = $_POST['origin'];
    $country = isset($_POST['country']) ? $_POST['country'] : "";
    $invasive_country = isset($_POST['invasive_country']) ? $_POST['invasive_country'] : "";
    $type = $_POST['type'];
    $sexual_difference = $_POST['sexual_difference'];
    $temp_range = $_POST['temp_range'];
    $ph_range = $_POST['ph_range'];
    $hardness_range = $_POST['hardness_range'];
    $natural_habitat = $_POST['natural_habitat'];
    $breeding = $_POST['breeding'];
    $sociability = $_POST['sociability'];
    $territorial = $_POST['territorial'];
    $way_of_living = $_POST['way_of_living'];
    $diet_feeding = $_POST['diet_feeding'];
    $compatible_fishes = isset($_POST['compatible_fishes']) ? $_POST['compatible_fishes'] : [];

    $upload_dir = 'uploads/';
    $image_url = "";
    $image_male_url = "";
    $image_hash = null;
    $image_male_hash = null;

    // Upload female image
    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        if (in_array(strtolower($ext), ['jpg','jpeg','png','gif'])) {
            $new_name = uniqid() . '.' . $ext;
            $path = $upload_dir . $new_name;
            if (move_uploaded_file($_FILES['image']['tmp_name'], $path)) {
                $image_url = $path;
                $hash = shell_exec("python3 compute_hash.py " . escapeshellarg($image_url));
                if ($hash) $image_hash = trim($hash);
            }
        }
    }

    // Upload male image (optional)
    if (isset($_FILES['image_male']) && $_FILES['image_male']['error'] === 0) {
        $ext = pathinfo($_FILES['image_male']['name'], PATHINFO_EXTENSION);
        if (in_array(strtolower($ext), ['jpg','jpeg','png','gif'])) {
            $new_name = uniqid() . '.' . $ext;
            $path = $upload_dir . $new_name;
            if (move_uploaded_file($_FILES['image_male']['tmp_name'], $path)) {
                $image_male_url = $path;
                $hash = shell_exec("python3 compute_hash.py " . escapeshellarg($image_male_url));
                if ($hash) $image_male_hash = trim($hash);
            }
        }
    }

    if ($image_url) {
        $stmt = $conn->prepare("INSERT INTO fishes 
            (name, description, female_description, male_description, scientific_name, year_discovered, origin, country, invasive_country, type, 
             image_url, image_male_url, sexual_difference, temp_range, ph_range, hardness_range, natural_habitat, 
             breeding, sociability, territorial, way_of_living, diet_feeding, image_hash, image_male_hash)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssssssssssssssssssssss", 
            $name, $description, $female_description, $male_description, $scientific_name, $year_discovered, $origin, $country, $invasive_country, $type, 
            $image_url, $image_male_url, $sexual_difference, $temp_range, $ph_range, $hardness_range, $natural_habitat, 
            $breeding, $sociability, $territorial, $way_of_living, $diet_feeding, $image_hash, $image_male_hash);
        $stmt->execute();

        $new_fish_id = $stmt->insert_id;

        if (!empty($compatible_fishes)) {
            $compat_stmt = $conn->prepare("INSERT INTO fish_compatibility (fish_id, compatible_with_id) VALUES (?, ?)");
            foreach ($compatible_fishes as $compatible_id) {
                $compat_stmt->bind_param("ii", $new_fish_id, $compatible_id);
                $compat_stmt->execute();
            }
            $compat_stmt->close();
        }

        header("Location: admin.php");
        exit();
    }
}

// Fetch all fishes for the compatibility dropdown
$fishes_result = $conn->query("SELECT id, name FROM fishes ORDER BY name ASC");
?>

<style>
    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        background: linear-gradient(135deg, #e0f7fa, #f2f6fc);
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
        height: 1350px;
        width: 100%;
        max-width: 1200px;
        position: relative;
        border: 1px solid #dce7f3;
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
        color: #006064;
        font-size: 26px;
        font-weight: bold;
    }

    form {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 20px;
    }

    form input[type="text"],
    form input[type="file"],
    form textarea,
    form select {
        width: 100%;
        padding: 12px 14px;
        border: 1px solid #cfd8dc;
        border-radius: 10px;
        font-size: 15px;
        box-sizing: border-box;
        transition: border 0.3s ease, box-shadow 0.2s ease;
        background-color: #fafafa;
    }

    form input[type="file"] {
        padding: 8px;
        background-color: #fff;
    }

    form input[type="text"]:focus,
    form input[type="file"]:focus,
    form textarea:focus,
    form select:focus {
        border-color: #00bcd4;
        box-shadow: 0 0 6px rgba(0, 188, 212, 0.3);
        outline: none;
    }

    textarea {
        resize: vertical;
        min-height: 120px;
    }

    .full-width {
        grid-column: 1 / -1;
    }

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
        background: linear-gradient(135deg, #0097a7, #006064);
        transform: translateY(-2px);
    }

    @media (max-width: 768px) {
        body {
            padding: 20px;
        }

        .container {
            padding: 20px;
        }
    }
</style>

<div class="container">
    <a href="admin.php" class="back-button">← Back</a>
    <h2>Add New Fish</h2>
    <form method="POST" action="" enctype="multipart/form-data">
        <input type="text" name="name" placeholder="Fish Name" required>
        <input type="text" name="scientific_name" placeholder="Scientific Name" required>

        <textarea name="description" placeholder="Description" class="full-width" required></textarea>
        <textarea name="female_description" placeholder="Female Description" class="full-width"></textarea>
        <textarea name="male_description" placeholder="Male Description" class="full-width"></textarea>

        <input type="text" name="year_discovered" placeholder="Year Discovered" required>

        <!-- 🗺️ Origin & Country -->
        <input type="text" name="origin" placeholder="Origin (e.g., Mekong River Basin)" required>

        <!-- ✅ Country changed to text input -->
        <input type="text" name="country" placeholder="Country (e.g., Thailand, Vietnam)" required>

        <!-- ✅ Invasive Country changed to text input -->
        <input type="text" name="invasive_country" placeholder="Invasive Country (e.g., Philippines, Malaysia)">

        <input type="text" name="type" placeholder="Type (e.g. Betta, Tetra)" required>

        <input type="file" name="image" required>
        <input type="file" name="image_male">

        <textarea name="sexual_difference" placeholder="Sexual Difference" required></textarea>
        <textarea name="natural_habitat" placeholder="Natural Habitat" required></textarea>

        <input type="text" name="temp_range" placeholder="Temperature Range (e.g. 22-28°C)" required>
        <input type="text" name="ph_range" placeholder="pH Range (e.g. 6.0 - 7.5)" required>
        <input type="text" name="hardness_range" placeholder="Hardness Range (e.g. 5 - 20 dGH)" required>

        <textarea name="breeding" placeholder="Breeding" required></textarea>
        <input type="text" name="sociability" placeholder="Sociability (e.g. Solitary, Schooling)" required>
        <input type="text" name="territorial" placeholder="Territorial (Yes/No)" required>
        <input type="text" name="way_of_living" placeholder="Way of Living (e.g. Nocturnal, Diurnal)" required>
        <textarea name="diet_feeding" placeholder="Diet & Feeding" class="full-width" required></textarea>

        <!-- ✅ Added section for compatibility -->
        <div class="full-width">
            <label><strong>Compatible with:</strong></label>
            <select name="compatible_fishes[]" multiple style="width:100%; padding:10px; border-radius:10px; border:1px solid #ccc;">
                <?php while ($f = $fishes_result->fetch_assoc()): ?>
                    <option value="<?= $f['id'] ?>"><?= htmlspecialchars($f['name']) ?></option>
                <?php endwhile; ?>
            </select>
            <small>Select multiple fish using Ctrl (Windows) or Cmd (Mac)</small>
        </div>

        <div class="full-width">
            <button type="submit">Add Fish</button>
        </div>
    </form>
</div>
