<?php
include 'db.php';
session_start();

// ✅ Cloudinary imports at the top
require 'vendor/autoload.php';
use Cloudinary\Configuration\Configuration;
use Cloudinary\Api\Upload\UploadApi;

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}
$user_id = $_SESSION['user']['id'];

$similarFishes = [];
$matchedFishId = null;
$targetPath = null;
$relativePath = null;
$notifCount = 0; // prevent undefined

// ✅ Fetch unread notification count (fixed $userId → $user_id)
$notifQuery = $conn->prepare("
    SELECT COUNT(*) 
    FROM notifications 
    WHERE user_id = ? AND is_read = 0
");
$notifQuery->bind_param("i", $user_id);
$notifQuery->execute();
$notifQuery->bind_result($notifCount);
$notifQuery->fetch();
$notifQuery->close();

function webPath($path) {
    if (!$path) return '';
    if (filter_var($path, FILTER_VALIDATE_URL)) return $path; // already a URL
    if (strpos($path, 'uploads/') === 0) return $path;       // local uploads
    return 'uploads/' . basename($path);
}


// ✅ Cloudinary configuration (only once, at the top)
Configuration::instance([
    'cloud' => [
        'cloud_name' => 'dcsiuylpy',
        'api_key'    => '386119783617198',
        'api_secret' => 'Xgus7r3i4TgoPcL_3zfVAAiHLZI'
    ],
    'url' => ['secure' => true]
]);

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    if (isset($_FILES['fish_image']) && $_FILES['fish_image']['error'] === UPLOAD_ERR_OK) {
        $uploadedFile = $_FILES['fish_image'];
        $uploadDir = __DIR__ . '/uploads/';
        if (!file_exists($uploadDir)) mkdir($uploadDir, 0755, true);

        $uniqueName = time() . '_' . basename($uploadedFile['name']);
        $targetPath = $uploadDir . $uniqueName;
        $relativePath = 'uploads/' . $uniqueName;

        if (!move_uploaded_file($uploadedFile['tmp_name'], $targetPath)) {
            echo "Failed to move uploaded file.";
            exit();
        }

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

    // --- Upload to Cloudinary (common for both cases) ---
    try {
        $upload = (new UploadApi())->upload($targetPath, [
            'folder' => 'aquawiki_user_uploads'
        ]);
        $cloudinaryUrl = $upload['secure_url'];
    } catch (Exception $e) {
        echo "❌ Cloudinary upload failed: " . $e->getMessage();
        exit();
    }

    // Use Cloudinary URL if available, fallback to local path
    $displayPath = $cloudinaryUrl ?? $relativePath;

    // Insert into DB (still use local path)
    $stmtInsert = $conn->prepare("INSERT INTO user_uploads (user_id, image_path) VALUES (?, ?)");
    $stmtInsert->bind_param("is", $user_id, $relativePath);
    $stmtInsert->execute();
    $uploadId = $stmtInsert->insert_id;
    $stmtInsert->close();

    // Identify fish
    identifyFish($conn, $targetPath, $similarFishes, $matchedFishId);

    if ($matchedFishId) {
        $stmtUpdate = $conn->prepare("UPDATE user_uploads SET matched_fish_id = ? WHERE id = ?");
        $stmtUpdate->bind_param("ii", $matchedFishId, $uploadId);
        $stmtUpdate->execute();
        $stmtUpdate->close();
    }
}

// ✅ Function moved outside POST block (no other changes)
function identifyFish($conn, $filePath, &$similarFishes, &$matchedFishId) {
    $apiUrl = "https://aquawiki-ai-1bh3.onrender.com/identify";

    $ch = curl_init();
    $cfile = new CURLFile($filePath, mime_content_type($filePath), basename($filePath));

    curl_setopt_array($ch, [
        CURLOPT_URL => $apiUrl,
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POSTFIELDS => ['file' => $cfile],
        CURLOPT_HTTPHEADER => ['Accept: application/json']
    ]);

    $response = curl_exec($ch);
    if (curl_errno($ch)) {
        error_log("CURL error: " . curl_error($ch));
        curl_close($ch);
        return;
    }
    curl_close($ch);

    $result = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("JSON decode error: " . json_last_error_msg() . " Output: $response");
        return;
    }

    // ✅ Add main matched fish
    if (isset($result['matched_fish']) && $result['matched_fish']) {
        $fish = $result['matched_fish'];
        if (isset($fish['id'])) $matchedFishId = $fish['id'];
        $similarFishes[] = $fish;
    }

    // ✅ Add other similar fishes
    if (isset($result['other_similar_fishes']) && is_array($result['other_similar_fishes'])) {
        foreach ($result['other_similar_fishes'] as $otherFish) {
            $similarFishes[] = $otherFish;
        }
    }
}
$conn->close();

?>

<!DOCTYPE html>
<html>
<head>
    <title>Fish Identification Results</title>
     <link rel="stylesheet" href="identify_fish.css?v=<?= time() ?>">
     <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="uploads/logo-16.png" sizes="16x16" type="image/png">
    <link rel="icon" href="uploads/logo-32.png" sizes="32x32" type="image/png">
    <link rel="icon" href="uploads/logo-48.png" sizes="48x48" type="image/png">
    <link rel="icon" href="uploads/logo-512.png" sizes="512x512" type="image/png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css?family=Roboto:400,700|Montserrat:400,700&display=swap" rel="stylesheet">
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar">
  <div class="logo" onclick="location.href='home.php'" style="cursor:pointer;">
    <img src="uploads/logo.png" alt="AquaWiki Logo">
  </div>

  <div class="menu">
    <a href="home.php">Home</a>

    <div class="dropdown">
      <a href="browse.php" class="dropbtn">Browse <i class="fas fa-caret-down"></i></a>
      <div class="dropdown-content">
        <a href="browse.php">Browse Fish</a>
        <a href="browse_plants.php">Aquatic Plants</a>
      </div>
    </div>

    <a href="community.php">Community</a>

    <div class="dropdown">
      <a href="profile.php" class="dropbtn">Profile <i class="fas fa-caret-down"></i></a>
      <div class="dropdown-content">
          <a href="profile.php">Profile</a>
        <a href="upload_history.php">Uploads</a>
        <?php if (isset($_SESSION['user'])): ?>
          <a href="logout.php">Logout</a>
        <?php else: ?>
          <a href="login.php">Login</a>
        <?php endif; ?>
      </div>
    </div>
  </div>

<div class="auth">
  <a href="notification.php" id="notifBtn">
    <i class="fas fa-bell"></i>
    <span id="notifCount" class="<?= $notifCount > 0 ? '' : 'hidden' ?>">
      <?= (int)$notifCount ?>
    </span>
  </a>
</div>
</nav>


<!-- OPTIONAL: small JS for mobile tap dropdown support -->
<script>
document.querySelectorAll('.dropdown > .dropbtn').forEach(btn => {
  let firstTapTime = 0;
  
  btn.addEventListener('click', e => {
    if (window.innerWidth <= 900) {
      const dropdown = btn.parentElement;
      const now = Date.now();

      if (!dropdown.classList.contains('open')) {
        e.preventDefault();
        dropdown.classList.add('open');
        firstTapTime = now;
      } else if (now - firstTapTime < 1500) {
        window.location.href = btn.getAttribute('href');
      } else {
        e.preventDefault();
        firstTapTime = now;
      }
    }
  });
});
</script>


<div class="result-section">
    <h2>Uploaded Image:</h2>
   <img src="<?= htmlspecialchars($displayPath) ?>" class="uploaded-img" alt="Uploaded Fish Image">

    <h2>Matching Fish:</h2>
<?php if (!empty($similarFishes)): ?>

    <!-- BEST MATCH -->
    <h2>Best Match:</h2>
    <div class="fish-grid">
        <?php $bestFish = $similarFishes[0]; ?>
        <div class="fish-card best-match">
            <img src="<?= htmlspecialchars(webPath($bestFish['matched_image_url'] ?? '')) ?>" class="img" alt="<?= htmlspecialchars($bestFish['name']) ?>">
            <h4><?= htmlspecialchars($bestFish['name']) ?></h4>
            <p class="fish-description"><?= htmlspecialchars($bestFish['description'] ?? 'No description available.') ?></p>
            <?php if (isset($bestFish['match_type'])): ?>
                <div class="match-type">Matched as: <b><?= ucfirst($bestFish['match_type']) ?></b></div>
            <?php endif; ?>
            <a href="fish_view.php?id=<?= $bestFish['id'] ?>">View Details</a>
        </div>
    </div>

    <!-- OTHER SIMILAR FISHES -->
    <?php if (count($similarFishes) > 1): ?>
        <h2>Other Similar Fishes:</h2>
        <div class="fish-grid">
            <?php foreach (array_slice($similarFishes, 1) as $fish): ?>
                <div class="fish-card">
                    <img src="<?= htmlspecialchars(webPath($fish['matched_image_url'] ?? '')) ?>" class="img" alt="<?= htmlspecialchars($fish['name']) ?>">
                    <h4><?= htmlspecialchars($fish['name']) ?></h4>
                    <p class="fish-description"><?= htmlspecialchars($fish['description'] ?? 'No description available.') ?></p>
                    <?php if (isset($fish['match_type'])): ?>
                        <div class="match-type">Matched as: <b><?= ucfirst($fish['match_type']) ?></b></div>
                    <?php endif; ?>
                    <a href="fish_view.php?id=<?= $fish['id'] ?>">View Details</a>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

<?php else: ?>
    <p class="no-match">No similar fish found in the database.</p>
<?php endif; ?>


<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(function(){
  function checkNotifications() {
    $.get('community.php', { fetch_notifications: 1 }, function(count){
      let c = parseInt(count) || 0;
      if (c > 0) {
        if ($("#notifCount").is(":hidden")) {
          $("#notifCount").text(c).fadeIn(300);
        } else {
          $("#notifCount").text(c);
        }
      } else {
        $("#notifCount").fadeOut(300);
      }
    });
  }

  // Auto check every 5s
  setInterval(checkNotifications, 5000);
  checkNotifications();

  // ✅ When user visits notification page, mark all as read
  $("#notifBtn").on("click", function(){
    $.post("mark_notifications_read.php", function(){
      $("#notifCount").fadeOut(300);
    });
  });
});
</script>

<?php include 'footer.php'; ?>

</body>
</html>