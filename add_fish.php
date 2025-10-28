<?php
include 'db.php';
require 'vendor/autoload.php';
session_start();

use Cloudinary\Configuration\Configuration;
use Cloudinary\Api\Upload\UploadApi;

// Cloudinary configuration (replace with your credentials)
Configuration::instance([
  'cloud' => [
    'cloud_name' => 'YOUR_CLOUD_NAME',   // 🔁 replace with yours
    'api_key'    => 'YOUR_API_KEY',
    'api_secret' => 'YOUR_API_SECRET'
  ],
  'url' => ['secure' => true]
]);

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action']) && $_POST['action'] === "insert") {
    // Form data
    $name = $_POST['name'];
    $description = $_POST['description'];
    $female_description = $_POST['female_description'];
    $male_description = $_POST['male_description'];
    $average_size = $_POST['average_size'] ?? '';
    $max_size = $_POST['max_size'] ?? '';
    $longevity = $_POST['longevity'] ?? '';
    $shape = $_POST['shape'] ?? '';
    $scientific_name = $_POST['scientific_name'];
    $family = $_POST['family'] ?? '';
    $year_discovered = $_POST['year_discovered'];
    $origin = $_POST['origin'];
    $country = $_POST['country'] ?? "";
    $invasive_country = $_POST['invasive_country'] ?? "";
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
    $compatible_fishes = $_POST['compatible_fishes'] ?? [];

    // Image URLs & hashes
    $image_url = "";
    $image_male_url = "";
    $image_hash = null;
    $image_male_hash = null;

    // Upload female image to Cloudinary
    if (!empty($_FILES['image']['name']) && $_FILES['image']['error'] === 0) {
        try {
            $upload = (new UploadApi())->upload($_FILES['image']['tmp_name'], [
                'folder' => 'aquawiki_fishes'
            ]);
            $image_url = $upload['secure_url'];

            // Optional hash (if your compute_hash.py handles URLs)
            $hash = shell_exec("python3 compute_hash.py " . escapeshellarg($image_url));
            if ($hash) $image_hash = trim($hash);
        } catch (Exception $e) {
            echo "❌ Image upload failed: " . $e->getMessage();
        }
    }

    // Upload male image to Cloudinary
    if (!empty($_FILES['image_male']['name']) && $_FILES['image_male']['error'] === 0) {
        try {
            $upload = (new UploadApi())->upload($_FILES['image_male']['tmp_name'], [
                'folder' => 'aquawiki_fishes'
            ]);
            $image_male_url = $upload['secure_url'];

            $hash = shell_exec("python3 compute_hash.py " . escapeshellarg($image_male_url));
            if ($hash) $image_male_hash = trim($hash);
        } catch (Exception $e) {
            echo "❌ Male image upload failed: " . $e->getMessage();
        }
    }

    // Insert into DB
    if ($image_url) {
        $stmt = $conn->prepare("INSERT INTO fishes 
            (name, description, female_description, male_description, scientific_name, year_discovered, origin, country, invasive_country, type, 
             image_url, image_male_url, sexual_difference, temp_range, ph_range, hardness_range, natural_habitat, 
             breeding, sociability, territorial, way_of_living, diet_feeding, image_hash, image_male_hash)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssssssssssssssssssssss", 
            $name, $description, $female_description, $male_description, $scientific_name, $year_discovered, 
            $origin, $country, $invasive_country, $type, $image_url, $image_male_url, $sexual_difference, 
            $temp_range, $ph_range, $hardness_range, $natural_habitat, $breeding, $sociability, 
            $territorial, $way_of_living, $diet_feeding, $image_hash, $image_male_hash);
        $stmt->execute();

        $new_fish_id = $stmt->insert_id;

        // Insert compatibility
        if (!empty($compatible_fishes)) {
            $compat_stmt = $conn->prepare("INSERT INTO fish_compatibility (fish_id, compatible_with_id) VALUES (?, ?)");
            foreach ($compatible_fishes as $compatible_id) {
                $compat_stmt->bind_param("ii", $new_fish_id, $compatible_id);
                $compat_stmt->execute();
            }
            $compat_stmt->close();
        }

        // ✅ Trigger Python backend to generate embeddings & hashes
        $backend_url = "https://your-render-service-name.onrender.com/update_fish_data"; // 🔁 replace with your Render backend URL
        $postData = json_encode([
            "fish_id" => (int)$new_fish_id,
            "image_url" => $image_url,
            "image_male_url" => $image_male_url
        ]);

        $ch = curl_init($backend_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        $response = curl_exec($ch);
        curl_close($ch);

        header("Location: admin.php");
        exit();
    }
}

// Fetch all fishes for compatibility list
$fishes_result = $conn->query("SELECT id, name FROM fishes ORDER BY name ASC");
?>

<div class="container">
    <a href="admin.php" class="back-button">← Back</a>
    <h2>Add New Fish</h2>
    <form method="POST" action="" enctype="multipart/form-data">
        <input type="hidden" name="action" value="insert">

        <input type="text" name="name" placeholder="Fish Name" required>
        <input type="text" name="scientific_name" placeholder="Scientific Name" required>

        <textarea name="description" placeholder="Description" class="full-width" required></textarea>
        <textarea name="female_description" placeholder="Female Description" class="full-width"></textarea>
        <textarea name="male_description" placeholder="Male Description" class="full-width"></textarea>

        <input type="text" name="year_discovered" placeholder="Year Discovered" required>
        <input type="text" name="origin" placeholder="Origin (e.g., Mekong River Basin)" required>
        <input type="text" name="country" placeholder="Country (e.g., Thailand, Vietnam)" required>
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
