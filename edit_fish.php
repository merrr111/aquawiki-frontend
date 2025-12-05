<?php
include 'db.php';
session_start();

require 'vendor/autoload.php';

use Cloudinary\Configuration\Configuration;
use Cloudinary\Api\Upload\UploadApi;

// ✅ Proper Cloudinary configuration
Configuration::instance([
  'cloud' => [
    'cloud_name' => 'dcsiuylpy',
    'api_key'    => '386119783617198',
    'api_secret' => 'Xgus7r3i4TgoPcL_3zfVAAiHLZI'
  ],
  'url' => ['secure' => true]
]);

if (!isset($_GET['id'])) {
    echo "Fish not found.";
    exit;
}

$id = intval($_GET['id']);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'];
    $description = $_POST['description'];
    $female_description = $_POST['female_description'];
    $male_description = $_POST['male_description'];
    $average_size = $_POST['average_size'];
    $max_size = $_POST['max_size'];
    $longevity = $_POST['longevity'];
    $shape = $_POST['shape'];
    $scientific_name = $_POST['scientific_name'];
    $family = $_POST['family'];
    $year_discovered = $_POST['year_discovered'];
    $origin = $_POST['origin'];
    $type = $_POST['type'];
    $sexual_difference = $_POST['sexual_difference'];
    $temp_range = $_POST['temp_range'];
    $ph_range = $_POST['ph_range'];
    $hardness_range = $_POST['hardness_range'];
    $natural_habitat = $_POST['natural_habitat'];
    $breeding = $_POST['breeding'];
    $diet_feeding = $_POST['diet_feeding'];
    $sociability = $_POST['sociability'];
    $territorial = $_POST['territorial'];
    $way_of_living = $_POST['way_of_living'];

    // Handle countries
    $countries = isset($_POST['country']) ? trim($_POST['country']) : "";
    $invasive_countries = isset($_POST['invasive_country']) ? trim($_POST['invasive_country']) : "";

    // ✅ Handle female image upload
    $image_url = $_POST['existing_image']; // default to existing
    if (!empty($_FILES['image_file']['name']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
        try {
            $uploadResult = (new UploadApi())->upload($_FILES['image_file']['tmp_name'], [
                'folder' => 'aquawiki/fishes/female'
            ]);
            $image_url = $uploadResult['secure_url'];
        } catch (Exception $e) {
            echo "❌ Female image upload failed: " . $e->getMessage();
        }
    }

    // ✅ Handle male image upload
    $male_image_url = $_POST['existing_male_image']; // default to existing
    if (!empty($_FILES['male_image_file']['name']) && $_FILES['male_image_file']['error'] === UPLOAD_ERR_OK) {
        try {
            $uploadResultMale = (new UploadApi())->upload($_FILES['male_image_file']['tmp_name'], [
                'folder' => 'aquawiki/fishes/male'
            ]);
            $male_image_url = $uploadResultMale['secure_url'];
        } catch (Exception $e) {
            echo "❌ Male image upload failed: " . $e->getMessage();
        }
    }

    // ✅ Update fish info
    $stmt = $conn->prepare("UPDATE fishes 
        SET name=?, description=?, female_description=?, male_description=?, average_size=?, max_size=?, longevity=?, shape=?, scientific_name=?, family=?, year_discovered=?, origin=?, country=?, invasive_country=?, type=?, image_url=?, image_male_url=?, sexual_difference=?, temp_range=?, ph_range=?, hardness_range=?, natural_habitat=?, breeding=?, sociability=?, territorial=?, way_of_living=?, diet_feeding=? 
        WHERE id=?");

    $stmt->bind_param(
        "sssssssssssssssssssssssssssi",
        $name,
        $description,
        $female_description,
        $male_description,
        $average_size,
        $max_size,
        $longevity,
        $shape,
        $scientific_name,
        $family,
        $year_discovered,
        $origin,
        $countries,
        $invasive_countries,
        $type,
        $image_url,
        $male_image_url,
        $sexual_difference,
        $temp_range,
        $ph_range,
        $hardness_range,
        $natural_habitat,
        $breeding,
        $sociability,
        $territorial,
        $way_of_living,
        $diet_feeding,
        $id
    );
    $stmt->execute();

    // ✅ Update compatibility
    $conn->query("DELETE FROM fish_compatibility WHERE fish_id = $id");
    if (!empty($_POST['compatible_with'])) {
        foreach ($_POST['compatible_with'] as $compatible_id) {
            $conn->query("INSERT INTO fish_compatibility (fish_id, compatible_with_id) VALUES ($id, $compatible_id)");
        }
    }

    header("Location: admin.php");
    exit();
}

// Fetch fish info
$stmt = $conn->prepare("SELECT * FROM fishes WHERE id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$fish = $result->fetch_assoc();

// Fetch selected compatible fishes
$selected = [];
$res = $conn->query("SELECT compatible_with_id FROM fish_compatibility WHERE fish_id = $id");
while ($r = $res->fetch_assoc()) {
    $selected[] = $r['compatible_with_id'];
}

// Prepare location for map
$origin_location = $fish['origin'];
$country_list = explode(',', $fish['country']);
$first_country = trim($country_list[0] ?? '');
$location_query = urlencode($origin_location . ', ' . $first_country);
?>


<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Edit Profile</title>
  <link rel="stylesheet" href="edit_fish.css?v=<?= time() ?>">
  <link href="https://fonts.googleapis.com/css?family=Roboto:400,700|Montserrat:400,700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <title>Edit Fish</title>
</head>
<body>

<a class="back" href="admin.php">← Back to Dashboard</a>
<h2>Edit Fish Information</h2>

<form method="POST" enctype="multipart/form-data">
    <label>Name</label>
    <input type="text" name="name" value="<?php echo htmlspecialchars($fish['name']); ?>" required>

    <label>Description</label>
    <textarea name="description" rows="4" required><?php echo htmlspecialchars($fish['description']); ?></textarea>

    <label>Female Description</label>
    <textarea name="female_description" rows="4" required><?php echo htmlspecialchars($fish['female_description']); ?></textarea>

    <label>Male Description</label>
    <textarea name="male_description" rows="4" required><?php echo htmlspecialchars($fish['male_description']); ?></textarea>

    <!-- ✅ New fields -->
    <label>Average Size</label>
    <input type="text" name="average_size" value="<?php echo htmlspecialchars($fish['average_size']); ?>" placeholder="e.g. 5 cm">

    <label>Maximum Size</label>
    <input type="text" name="max_size" value="<?php echo htmlspecialchars($fish['max_size']); ?>" placeholder="e.g. 10 cm">

    <label>Longevity</label>
    <input type="text" name="longevity" value="<?php echo htmlspecialchars($fish['longevity']); ?>" placeholder="e.g. 5 years">

    <label>Shape</label>
    <input type="text" name="shape" value="<?php echo htmlspecialchars($fish['shape']); ?>" placeholder="e.g. Elongated, Rounded">

    <label>Scientific Name</label>
    <input type="text" name="scientific_name" value="<?php echo htmlspecialchars($fish['scientific_name']); ?>" required>

    <label>Family</label>
    <input type="text" name="family" value="<?php echo htmlspecialchars($fish['family']); ?>" placeholder="e.g. Cichlidae" required>

    <label>Year Discovered</label>
    <input type="text" name="year_discovered" value="<?php echo htmlspecialchars($fish['year_discovered']); ?>" required>

    <label>Origin</label>
    <input type="text" name="origin" value="<?php echo htmlspecialchars($fish['origin']); ?>" required>

    <!-- ✅ Country as text field -->
    <label for="country">Country</label>
    <input type="text" name="country" id="country"
           value="<?php echo htmlspecialchars($fish['country']); ?>"
           placeholder="Enter countries (comma-separated)">
    <p class="note">Example: Philippines, Indonesia, Thailand</p>

    <!-- ✅ Country (Invasive) as text field -->
    <label for="invasive_country">Country (Invasive)</label>
    <input type="text" name="invasive_country" id="invasive_country"
           value="<?php echo htmlspecialchars($fish['invasive_country']); ?>"
           placeholder="Enter invasive countries (comma-separated)">
    <p class="note">Example: Philippines, Malaysia</p>

    <label>Type</label>
    <input type="text" name="type" value="<?php echo htmlspecialchars($fish['type']); ?>" required>

    <div class="image-pair">
        <div>
            <label>Current Image (Female)</label>
            <img src="<?php echo htmlspecialchars($fish['image_url']); ?>" alt="Current Image">
            <input type="file" name="image_file" accept="image/*">
            <input type="hidden" name="existing_image" value="<?php echo htmlspecialchars($fish['image_url']); ?>">
        </div>
        <div>
            <label>Current Image (Male)</label>
            <?php if (!empty($fish['image_male_url'])): ?>
                <img src="<?php echo htmlspecialchars($fish['image_male_url']); ?>" alt="Current Male Image">
            <?php else: ?>
                <p style="color:gray;">No male image uploaded.</p>
            <?php endif; ?>
            <input type="file" name="male_image_file" accept="image/*">
            <input type="hidden" name="existing_male_image" value="<?php echo htmlspecialchars($fish['image_male_url']); ?>">
        </div>
    </div>

    <label>Sexual Difference</label>
    <textarea name="sexual_difference" rows="3" required><?php echo htmlspecialchars($fish['sexual_difference']); ?></textarea>

    <label>Temperature Range</label>
    <input type="text" name="temp_range" value="<?php echo htmlspecialchars($fish['temp_range']); ?>" required>

    <label>pH Range</label>
    <input type="text" name="ph_range" value="<?php echo htmlspecialchars($fish['ph_range']); ?>" required>

    <label>Hardness Range</label>
    <input type="text" name="hardness_range" value="<?php echo htmlspecialchars($fish['hardness_range']); ?>" required>

    <label>Natural Habitat</label>
    <textarea name="natural_habitat" rows="3"><?php echo htmlspecialchars($fish['natural_habitat']); ?></textarea>

    <label>Breeding</label>
    <textarea name="breeding" rows="3"><?php echo htmlspecialchars($fish['breeding']); ?></textarea>

    <!-- ✅ New fields -->
    <label>Sociability</label>
    <input type="text" name="sociability" value="<?php echo htmlspecialchars($fish['sociability']); ?>" placeholder="e.g. Peaceful, Aggressive, Schooling">

    <label>Territorial</label>
    <select name="territorial">
        <option value="Yes" <?php if ($fish['territorial'] == 'Yes') echo 'selected'; ?>>Yes</option>
        <option value="No" <?php if ($fish['territorial'] == 'No') echo 'selected'; ?>>No</option>
    </select>

    <label>Way of Living</label>
    <select name="way_of_living">
        <option value="Nocturnal" <?php if ($fish['way_of_living'] == 'Nocturnal') echo 'selected'; ?>>Nocturnal</option>
        <option value="Diurnal" <?php if ($fish['way_of_living'] == 'Diurnal') echo 'selected'; ?>>Diurnal</option>
    </select>

    <label>Diet & Feeding</label>
    <textarea name="diet_feeding" rows="3"><?php echo htmlspecialchars($fish['diet_feeding']); ?></textarea>

    <!-- Compatibility Section -->
    <div class="compatibility-section">
        <label>Compatible With</label>
        <select name="compatible_with[]" multiple>
            <?php
            $fishesList = $conn->query("SELECT id, name, scientific_name FROM fishes WHERE id != $id");
            while ($compatFish = $fishesList->fetch_assoc()):
                $display_name = htmlspecialchars($compatFish['name']);
                if (!empty($compatFish['scientific_name'])) {
                    $display_name .= " (" . htmlspecialchars($compatFish['scientific_name']) . ")";
                }
            ?>
                <option value="<?php echo $compatFish['id']; ?>" <?php echo in_array($compatFish['id'], $selected) ? 'selected' : ''; ?>>
                    <?php echo $display_name; ?>
                </option>
            <?php endwhile; ?>
        </select>
        <p class="note">Hold <strong>Ctrl</strong> (Windows) or <strong>Cmd</strong> (Mac) to select multiple fish.</p>
    </div>

    <!-- Map Preview -->
    <label>Origin Map Preview</label>
    <iframe 
        class="map"
        src="https://www.google.com/maps?q=<?php echo $location_query; ?>&output=embed">
    </iframe>

    <button type="submit">Update Fish</button>
</form>

</body>
</html>
