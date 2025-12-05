<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}
include 'db.php';

$userId = $_SESSION['user']['id'];
$notifCount = 0; // prevent undefined

// ✅ Fetch unread notification count (your exact table)
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

$query = "SELECT u.id, u.image_path, u.upload_time, 
                 f.id AS fish_id, f.name AS fish_name 
          FROM user_uploads u
          LEFT JOIN fishes f ON u.matched_fish_id = f.id
          WHERE u.user_id = ?
          ORDER BY u.upload_time DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

$uploads = [];
while ($row = $result->fetch_assoc()) {
    $uploads[] = $row;
}

$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload History</title>
     <link rel="stylesheet" href="upload_history.css?v=<?= time() ?>">
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
    <?php if (isset($_SESSION['user'])): ?>
      <!-- Show notification bell only for logged-in users -->
      <a href="notification.php" id="notifBtn" style="position:relative;">
        <i class="fas fa-bell"></i>
        <span id="notifCount" style="
          background:red;
          color:white;
          border-radius:50%;
          padding:2px 6px;
          font-size:12px;
          position:absolute;
          top:-6px;
          right:-10px;
          <?= $notifCount > 0 ? '' : 'display:none;' ?>
        "><?= (int)$notifCount ?></span>
      </a>
    <?php else: ?>
      <!-- Show user icon/login if not logged in -->
      <a href="login.php" style="display:inline-flex; align-items:center; gap:4px;">
        <i class="fas fa-user"></i>
      </a>
    <?php endif; ?>
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

<div class="main-content">
<h1>Your Upload History</h1>

<div class="upload-container">
    <?php if (empty($uploads)): ?>
        <p class="empty">You haven't uploaded any fish photos yet.</p>
    <?php else: ?>
        <?php foreach ($uploads as $upload): ?>
            <div class="upload-item">
                <img src="<?php echo htmlspecialchars($upload['image_path']); ?>" alt="Uploaded Fish">
                <div>
                    <p><strong>Uploaded:</strong> <?php echo date('F j, Y, g:i a', strtotime($upload['upload_time'])); ?></p>
                    <p><strong>Identified as:</strong>
                        <?php if ($upload['fish_name']): ?>
                            <a href="fish_view.php?id=<?php echo $upload['fish_id']; ?>">
                                <?php echo htmlspecialchars($upload['fish_name']); ?>
                            </a>
                        <?php else: ?>
                            Unknown
                        <?php endif; ?>
                    </p>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
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
</body>
</html>
