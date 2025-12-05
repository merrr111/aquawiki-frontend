<?php 
include 'db.php'; 
session_start();

// ✅ Initialize variable to prevent undefined error
$notifCount = 0;

// ✅ Only fetch notifications if user is logged in
if (isset($_SESSION['user']['id'])) {
    $userId = $_SESSION['user']['id'];

    // ✅ Fetch unread notification count
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>About - AquaWiki</title>
  <link rel="icon" href="uploads/logo-16.png" sizes="16x16" type="image/png">
<link rel="icon" href="uploads/logo-32.png" sizes="32x32" type="image/png">
<link rel="icon" href="uploads/logo-48.png" sizes="48x48" type="image/png">
<link rel="icon" href="uploads/logo-512.png" sizes="512x512" type="image/png">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css?family=Roboto:400,700|Montserrat:400,700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="about.css?v=<?= time() ?>"> <!-- your existing CSS -->
  <style>
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



<!-- HEADER SECTION -->
<section class="about-header">
  <h1>About AquaWiki</h1>
  <p>Your trusted source for freshwater aquarium fish and plant information.</p>
</section>

<!-- MAIN CONTENT -->
<section class="about-content">
  <h2>Our Mission</h2>
  <p>
    AquaWiki is a web-based encyclopedia dedicated to helping aquarium enthusiasts learn about 
    freshwater fish species and aquatic plants. Our goal is to provide accessible, accurate, and 
    organized information for beginners and experts alike. Whether you’re identifying a fish, 
    learning care guidelines, or exploring new aquatic species, AquaWiki is here to guide you.
  </p>

  <h2>Educational Purpose</h2>
  <p>
    All content on AquaWiki is intended for educational and non-commercial use. The platform 
    compiles public data and community contributions to create a collective resource for fish 
    keepers and aquatic hobbyists worldwide.
  </p>

  <div class="credits">
    <h3>Credits & Acknowledgements</h3>
    <ul>
      <li>Images and data are sourced from verified aquarium resources and public databases.</li>
      <li>Scientific information is contributed by hobbyists and referenced from credible sources.</li>
      <li>AquaWiki does not claim ownership of any photo or copyrighted material used herein.</li>
    </ul>
  </div>
</section>
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

<!-- FOOTER -->
<?php include 'footer.php'; ?>


</body>
</html>
