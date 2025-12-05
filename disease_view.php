<?php
include 'db.php';
session_start();

if (!isset($_GET['id'])) {
    echo "Disease not found.";
    exit;
}

$id = intval($_GET['id']);

// Determine logged-in user
$userId = isset($_SESSION['user']['id']) ? $_SESSION['user']['id'] : 0;

// Notification count (only if user logged in)
$notifCount = 0;
if ($userId) {
    $notifQuery = $conn->prepare("
        SELECT COUNT(*) 
        FROM notifications 
        WHERE user_id = ? AND is_read = 0
    ");
    $notifQuery->bind_param("i", $userId);
    $notifQuery->execute();
    $notifQuery->bind_result($notifCount);
    $notifQuery->fetch();
    $notifQuery->close();
}

// Fetch disease details
$stmt = $conn->prepare("SELECT * FROM diseases WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if (!$row = $result->fetch_assoc()) {
    echo "Disease not found.";
    exit;
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="icon" href="uploads/logo-16.png" sizes="16x16" type="image/png">
<link rel="icon" href="uploads/logo-32.png" sizes="32x32" type="image/png">
<link rel="icon" href="uploads/logo-48.png" sizes="48x48" type="image/png">
<link rel="icon" href="uploads/logo-512.png" sizes="512x512" type="image/png">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css?family=Roboto:400,700|Montserrat:400,700&display=swap" rel="stylesheet">
<title><?php echo htmlspecialchars($row['name']); ?> - Disease Details</title>
<link rel="stylesheet" href="plant.css?v=<?= time() ?>">
<style>
.hero {
  position: relative;
  width: 100%;
  height: 450px;
  background: url('<?php echo htmlspecialchars($row['image_url']); ?>') center/cover no-repeat;
  display: flex;
  align-items: center;
  justify-content: flex-start;
  color: #fff;
  text-align: left;
  padding-left: 60px;
  margin-top: 70px;
}

.hero h1 {
  font-size: 3rem;
  text-shadow: 2px 2px 6px rgba(0,0,0,0.6);
  margin: 0;
  color: #ff4444;
}

@media (max-width: 1024px) {
  .hero { height: 380px; padding-left: 40px; }
  .hero h1 { font-size: 2.4rem; }
}

@media (max-width: 768px) {
  .hero { height: 300px; justify-content: center; text-align: center; padding: 0 20px; }
  .hero h1 { font-size: 2rem; }
}

@media (max-width: 480px) {
  .hero { height: 240px; padding: 0 10px; margin-top: 60px; }
  .hero h1 { font-size: 1.6rem; }
}

.disease-container {
  display: flex;
  flex-wrap: wrap;
  padding: 40px;
  gap: 30px;
  max-width: 1000px;
  margin: auto;
}

.disease-image img {
  max-width: 400px;
  width: 100%;
  border-radius: 15px;
  box-shadow: 0 8px 25px rgba(0,0,0,0.1);
}

.disease-details {
  flex: 1;
  min-width: 300px;
  color: #555;
}

.disease-details h2 {
  color: #d32f2f;
  margin-bottom: 20px;
}

.disease-details p {
  margin-bottom: 12px;
  color: #fff;
}

.detail-label {
  font-weight: bold;
  color: #FF00FF;
}
</style>
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
        <?php if (isset($_SESSION['user'])): ?>
        <a href="profile.php">Profile</a>
          <a href="upload_history.php">Uploads</a>
          <a href="logout.php">Logout</a>
        <?php else: ?>
          <a href="login.php">Login</a>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class="auth">
    <?php if ($userId): ?>
      <a href="notification.php" id="notifBtn">
        <i class="fas fa-bell"></i>
        <span id="notifCount" class="<?= $notifCount > 0 ? '' : 'hidden' ?>"><?= (int)$notifCount ?></span>
      </a>
    <?php endif; ?>
  </div>
</nav>

<!-- HERO -->
<div class="hero">
  <h1><?php echo htmlspecialchars($row['name']); ?></h1>
</div>

<!-- DISEASE DETAILS -->
<div class="disease-container">
  <div class="disease-image">
    <img src="<?php echo htmlspecialchars($row['image_url']); ?>" alt="<?php echo htmlspecialchars($row['name']); ?>">
  </div>
  <div class="disease-details">
    <h2><?php echo htmlspecialchars($row['name']); ?></h2>
    <p><span class="detail-label">Scientific Name:</span> <?php echo htmlspecialchars($row['scientific_name']); ?></p>
    <p><span class="detail-label">Description:</span><br><?php echo nl2br(htmlspecialchars($row['description'])); ?></p>
    <p><span class="detail-label">Prevention:</span><br><?php echo nl2br(htmlspecialchars($row['prevention'])); ?></p>
    <p><span class="detail-label">Treatment:</span><br><?php echo nl2br(htmlspecialchars($row['treatment'])); ?></p>
    <p><span class="detail-label">Added On:</span> <?php echo htmlspecialchars($row['created_at']); ?></p>
  </div>
</div>

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

  setInterval(checkNotifications, 5000);
  checkNotifications();

  $("#notifBtn").on("click", function(){
    $.post("mark_notifications_read.php", function(){
      $("#notifCount").fadeOut(300);
    });
  });
});
</script>

</body>
</html>
