<?php
include 'db.php';
session_start();

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

$similarFishes = [];
$matchedFishId = null;
$targetPath = null;
$relativePath = null;

// Helper to normalize web paths for images
function webPath($path) {
    if (!$path) return '';
    if (strpos($path, 'uploads/') === 0) return $path;
    return 'uploads/' . basename($path);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Handle uploaded image
    if (isset($_FILES['fish_image']) && $_FILES['fish_image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . '/uploads/';
        if (!file_exists($uploadDir)) mkdir($uploadDir, 0755, true);

        $uploadedFile = $_FILES['fish_image'];
        $uniqueName = time() . '_' . basename($uploadedFile['name']);
        $targetPath = $uploadDir . $uniqueName;
        $relativePath = 'uploads/' . $uniqueName;

        if (!move_uploaded_file($uploadedFile['tmp_name'], $targetPath)) {
            echo "Failed to move uploaded file.";
            exit();
        }

    // Handle external image
    } elseif (isset($_POST['image_path']) && !empty($_POST['image_path'])) {
        $externalPath = $_POST['image_path'];
        if (!file_exists($externalPath)) {
            echo "File does not exist: $externalPath";
            exit();
        }

        $uploadDir = __DIR__ . '/uploads/';
        if (!file_exists($uploadDir)) mkdir($uploadDir, 0755, true);

        $uniqueName = time() . '_' . basename($externalPath);
        $targetPath = $uploadDir . $uniqueName;
        $relativePath = 'uploads/' . $uniqueName;

        if (!copy($externalPath, $targetPath)) {
            echo "Failed to copy external image.";
            exit();
        }

    } else {
        echo "No image provided.";
        exit();
    }

    // Insert into user_uploads
    $user_id = $_SESSION['user']['id'];
    $stmtInsert = $conn->prepare("INSERT INTO user_uploads (user_id, image_path) VALUES (?, ?)");
    $stmtInsert->bind_param("is", $user_id, $relativePath);
    $stmtInsert->execute();
    $uploadId = $stmtInsert->insert_id;
    $stmtInsert->close();

    // Identify fish via Python
    identifyFish($conn, $targetPath, $similarFishes, $matchedFishId);

    // Update matched_fish_id
    if ($matchedFishId) {
        $stmtUpdate = $conn->prepare("UPDATE user_uploads SET matched_fish_id = ? WHERE id = ?");
        $stmtUpdate->bind_param("ii", $matchedFishId, $uploadId);
        $stmtUpdate->execute();
        $stmtUpdate->close();
    }

} else {
    echo "Invalid request.";
    exit();
}

// ✅ Function to call Python script and parse JSON safely
function identifyFish($conn, $filePath, &$similarFishes, &$matchedFishId) {
    $python = 'C:/Windows/py.exe'; // Adjust if necessary
    $script = 'C:/xampp/htdocs/aquawiki/reserve_identify_fish.py';
    $absFile = str_replace('\\','/',$filePath);

    if (!file_exists($absFile) || !file_exists($script)) return;

    // Suppress TensorFlow logs
    $command = "set TF_CPP_MIN_LOG_LEVEL=3 && $python -3.12 \"$script\" \"$absFile\" 2>&1";
    $output = shell_exec($command);
    if (!$output) return;

    // Only parse last line (JSON) to avoid warnings/logs
    $lines = explode("\n", trim($output));
    $json_line = end($lines);

    $result = json_decode($json_line, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("JSON decode error: " . json_last_error_msg() . " Output: $json_line");
        return;
    }

    if (isset($result['matched_fish']) && $result['matched_fish']) {
        $fish = $result['matched_fish'];
        if (isset($fish['id'])) $matchedFishId = $fish['id'];
        $similarFishes[] = $fish;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Fish Identification Results</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .result-section { max-width: 900px; margin: 40px auto; text-align: center; }
        .result-section img.uploaded { width: 300px; border: 3px solid #4CAF50; border-radius: 10px; margin-bottom: 30px; }
        .fish-card { width: 220px; border: 1px solid #ccc; padding: 12px; margin: 10px; border-radius: 10px; display: inline-block; vertical-align: top; background-color: #f9f9f9; box-shadow: 2px 2px 8px #aaa; }
        .fish-card img { width: 100%; height: 150px; object-fit: cover; border-radius: 6px; }
        .fish-card h4 { margin: 10px 0 5px 0; }
        .match-type { font-size: 14px; color: #555; margin-bottom: 8px; }
        .back-btn { display: inline-block; margin-top: 30px; padding: 10px 20px; background-color: #4CAF50; color: white; text-decoration: none; border-radius: 6px; }
        .no-match { font-size: 20px; color: #f44336; }
    </style>
</head>
<body>

<div class="result-section">
    <h2>Uploaded Image:</h2>
    <img src="<?php echo htmlspecialchars(webPath($relativePath ?? $targetPath)); ?>" class="uploaded" alt="Uploaded Fish Image">

    <h2>Matching Fish:</h2>

    <?php if (!empty($similarFishes)): ?>
        <?php foreach ($similarFishes as $fish): ?>
            <div class="fish-card">
                <img src="<?php echo htmlspecialchars(webPath($fish['matched_image_url'] ?? '')); ?>" alt="<?php echo htmlspecialchars($fish['name']); ?>">
                <h4><?php echo htmlspecialchars($fish['name']); ?></h4>
                <?php if (isset($fish['match_type'])): ?>
                    <div class="match-type">Matched as: <b><?php echo ucfirst($fish['match_type']); ?></b></div>
                <?php endif; ?>
                <a href="fish_view.php?id=<?php echo $fish['id']; ?>">View Details</a>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p class="no-match">No similar fish found in the database.</p>
    <?php endif; ?>

    <br><br>
    <a href="home.php" class="back-btn">← Back to Home</a>
</div>

</body>
</html>
