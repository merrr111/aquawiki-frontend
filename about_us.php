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
  <title>About Us</title>
  <link rel="icon" href="uploads/logo-16.png" sizes="16x16" type="image/png">
<link rel="icon" href="uploads/logo-32.png" sizes="32x32" type="image/png">
<link rel="icon" href="uploads/logo-48.png" sizes="48x48" type="image/png">
<link rel="icon" href="uploads/logo-512.png" sizes="512x512" type="image/png">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css?family=Roboto:400,700|Montserrat:400,700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="about.css?v=<?= time() ?>"> <!-- your existing CSS -->
  <style>
    .about-section {
      text-align: center;
      padding: 50px 20px;
      max-width: 1000px;
      margin: 0 auto;
    }

    .about-section h1 {
      color: #00c2cb;
      font-size: 2rem;
      margin-bottom: 20px;
    }

    .about-section p {
      font-size: 1rem;
      color: #adb5bd;
      margin-bottom: 40px;
      line-height: 1.6;
    }

    .team-container {
      display: flex;
      flex-wrap: wrap;
      justify-content: center;
      gap: 25px;
    }

    .member-card {
      background: #1b263b;
      border-radius: 12px;
      width: 220px;
      text-align: center;
      padding: 15px;
      box-shadow: 0 3px 6px rgba(0,0,0,0.2);
      transition: transform 0.3s;
    }

    .member-card:hover {
      transform: scale(1.05);
    }

    .member-card img {
      width: 100%;
      height: 200px;
      object-fit: cover;
      border-radius: 10px;
    }

    .member-card h3 {
      color: #ffffff;
      margin-top: 12px;
      font-size: 1.1rem;
    }

    .member-card p {
      color: #adb5bd;
      font-size: 0.9rem;
    }

    footer {
      background-color: #0d1b2a;
      text-align: center;
      padding: 30px;
      color: #adb5bd;
      font-size: 0.9rem;
      margin-top: 50px;
    }

    .back-link {
      display: inline-block;
      margin-top: 20px;
      color: #74c0fc;
      text-decoration: none;
      font-weight: 500;
    }

    .back-link:hover {
      color: #a5d8ff;
    }

    @media (max-width: 600px) {
      .member-card {
        width: 100%;
        max-width: 300px;
      }
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


<section class="about-section">
  <h1>Meet the Developers</h1>
  <p>
    We are a group of passionate students behind <strong>AquaWiki</strong> — a platform built to provide 
    freshwater aquarium enthusiasts with accurate fish information, care guides, and community contributions.
  </p>

  <div class="team-container">
    <div class="member-card">
      <img src="uploads/Herrera.jpg" alt="Giemer Herrera">
      <h3>Giemer Herrera</h3>
      <p>Developer</p>
    </div>
    <div class="member-card">
      <img src="uploads/Ilagan.jpg" alt="Member 2">
      <h3>Joseph Matthew Ilagan</h3>
      <p>Research & Documentation</p>
    </div>
    <div class="member-card">
      <img src="uploads/Fulgencio.jpg" alt="Member 4">
      <h3>Kenneth Fulgencio</h3>
      <p>Research & Documentation</p>
    </div>
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

<?php include 'footer.php'; ?>
</body>
</html>
