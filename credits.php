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
  <title>Credits & Acknowledgments — AquaWiki</title>
   <link rel="icon" href="uploads/logo-16.png" sizes="16x16" type="image/png">
    <link rel="icon" href="uploads/logo-32.png" sizes="32x32" type="image/png">
    <link rel="icon" href="uploads/logo-48.png" sizes="48x48" type="image/png">
    <link rel="icon" href="uploads/logo-512.png" sizes="512x512" type="image/png">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    body {
      font-family: 'Roboto', sans-serif;
      background-color: #f5f7fa;
      margin: 0;
      padding: 0;
    }
    /* ===== NAVBAR ===== */
.navbar {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: var(--nav-height);
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 30px;
  background: linear-gradient(to bottom, rgba(6, 12, 18, 0.25), rgba(6, 12, 18, 0.35));
  background-color: var(--glass-bg);
  -webkit-backdrop-filter: blur(8px) saturate(1.05);
  backdrop-filter: blur(8px) saturate(1.05);
  border-bottom: 1px solid rgba(255, 255, 255, 0.06);
  box-shadow: 0 6px 20px rgba(2, 8, 12, 0.45);
  transition: background 0.25s ease, box-shadow 0.25s ease;
  z-index: 3000;
  box-sizing: border-box;
}

/* --- LOGO LEFT --- */
.navbar .logo {
  display: flex;
  align-items: center;
  height: 100%;
}

.navbar .logo img {
  height: 70px;
  width: auto;
  display: block;
  object-fit: contain;
  filter: drop-shadow(0 2px 5px rgba(0, 194, 203, 0.5));
  transition: transform 0.25s ease, filter 0.25s ease;
}

.navbar .logo img:hover {
  transform: scale(1.08);
  filter: drop-shadow(0 0 10px rgba(0, 194, 203, 0.8));
}

/* --- MENU CENTER --- */
.navbar .menu {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 24px;
  flex-wrap: nowrap;
}

.navbar .menu a {
  color: rgba(255, 255, 255, 0.92);
  text-decoration: none;
  font-weight: 700;
  letter-spacing: 0.8px;
  text-transform: uppercase;
  font-size: 13px;
  padding: 6px 8px;
  transition: color 180ms ease, transform 120ms ease;
}

.navbar .menu a:hover,
.navbar .menu a:focus {
  color: var(--accent);
  transform: translateY(-1px);
}

/* --- AUTH RIGHT (Notification) --- */
.navbar .auth {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: 10px;
}

#notifBtn {
  position: relative;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  color: #fff; /* white icon */
  font-size: 22px;
  text-decoration: none;
  padding: 0;
  border-radius: 0;
  background: none;
  transition: color 180ms ease;
}

#notifBtn:hover {
  color: #000; /* black on hover */
}

#notifCount {
  display: none; /* hide the background badge */
}

.navbar .auth a {
  color: var(--accent);
  text-decoration: none;
  font-weight: 700;
  padding: 6px 10px;
  border-radius: 8px;
  background: rgba(255, 255, 255, 0.02);
  transition: background 180ms ease, color 180ms ease;
}

.navbar .auth a:hover {
  background: rgba(255, 255, 255, 0.06);
  color: #fff;
}

/* ===== DROPDOWN ===== */
.dropdown {
  position: relative;
  z-index: 3300;
}

.dropbtn {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  color: rgba(255, 255, 255, 0.92);
  text-decoration: none;
  font-weight: 700;
  letter-spacing: 0.8px;
  text-transform: uppercase;
  font-size: 13px;
  padding: 6px 8px;
  cursor: pointer;
}

.dropdown-content {
  position: absolute;
  top: 100%;
  left: 0;
  min-width: max-content;
  background: rgba(10, 15, 20, 0.97);
  backdrop-filter: blur(10px);
  border-radius: 6px;
  overflow: hidden;
  z-index: 3500;
  opacity: 0;
  visibility: hidden;
  transform: translateY(8px);
  transition: opacity 0.25s ease, transform 0.25s ease, visibility 0.25s;
  pointer-events: none;
  border: 1px solid rgba(0, 194, 203, 0.2);
  box-shadow: 0 4px 25px rgba(0, 194, 203, 0.35);
}

.dropdown:hover .dropdown-content {
  opacity: 1;
  visibility: visible;
  transform: translateY(0);
  pointer-events: auto;
}

.dropdown-content a {
  display: block;
  padding: 10px 14px;
  color: rgba(255, 255, 255, 0.88);
  text-decoration: none;
  font-weight: 500;
  font-size: 13px;
  transition: background 0.25s ease, color 0.25s ease;
}

.dropdown-content a:hover {
  background: rgba(0, 194, 203, 0.15);
  color: var(--accent);
}

/* Prevent disappearing gap */
.dropdown::after {
  content: "";
  position: absolute;
  top: 100%;
  left: 0;
  width: 100%;
  height: 8px;
}

/* Tablet (≤900px) */
@media (max-width: 900px) {
  .navbar {
    flex-direction: row;
    align-items: center;
    justify-content: space-between;
    padding: 8px 16px;
    height: auto;
    gap: 0;
  }

  .navbar .logo img {
    height: 42px;
  }

  .navbar .menu {
    gap: 12px;
    flex: 1;
    justify-content: center;
  }

  .navbar .menu a,
  .dropbtn {
    font-size: 12px;
    padding: 5px 6px;
  }

  .navbar .auth {
    gap: 6px;
  }

  #notifBtn {
    font-size: 18px;
  }

  .navbar .auth a {
    font-size: 12px;
    padding: 5px 8px;
  }
}

/* Mobile (≤600px) */
@media (max-width: 600px) {
  .navbar {
    padding: 6px 10px;
    height: auto;
  }

  .navbar .logo img {
    height: 36px;
  }

  .navbar .menu {
    gap: 6px;
  }

  .navbar .menu a,
  .dropbtn {
    font-size: 11px;
    padding: 4px 5px;
  }

  .navbar .auth {
    gap: 4px;
  }

  #notifBtn {
    font-size: 16px;
  }

  .navbar .auth a {
    font-size: 11px;
    padding: 4px 6px;
  }
}

/* ===== ONE-LINE MOBILE NAVBAR FIX ===== */
@media (max-width: 768px) {
  .navbar {
    display: flex;
    flex-direction: row;
    align-items: center;
    justify-content: space-between;
    padding: 6px 10px;
    height: 55px;
  }

  .navbar .logo img {
    height: 30px;
  }

  .navbar .menu {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 8px;
    flex: 1;
  }

  .navbar .menu a,
  .dropbtn {
    font-size: 11px;
    padding: 4px 5px;
  }

  .navbar .auth {
    display: flex;
    justify-content: flex-end;
    align-items: center;
    gap: 6px;
  }

  #notifBtn {
    font-size: 14px;
    padding: 4px;
    border-radius: 6px;
  }

  #notifCount {
    display: none; /* hide the background badge */
  }

  .navbar .auth a {
    font-size: 11px;
    padding: 4px 6px;
  }
}
.credits-container {
  max-width: 1000px;
  margin: 100px auto;
  padding: 30px;
  background: #ffffff;
  border-radius: 15px;
  box-shadow: 0 8px 25px rgba(0,0,0,0.1);
}

h1 {
  text-align: center;
  color: #0d1b2a;
  margin-bottom: 30px;
}

.credits-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); /* desktop multi-column */
  gap: 25px;
}

.credit-card {
  background-color: #e9ecef;
  border-radius: 12px;
  padding: 20px;
  text-align: center;
  transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.credit-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 6px 15px rgba(0,0,0,0.1);
}

.credit-card img {
  width: 100px;
  height: 100px;
  border-radius: 50%;
  object-fit: cover;
  margin-bottom: 15px;
  border: 3px solid #00c2cb;
}

.credit-card h3 {
  color: #0d1b2a;
  font-size: 1.1rem;
  margin-bottom: 8px;
}

.credit-card p {
  color: #495057;
  font-size: 0.9rem;
  margin-bottom: 12px;
}

.credit-card a {
  color: #00c2cb;
  text-decoration: none;
  font-weight: 600;
  transition: color 0.3s;
}

.credit-card a:hover {
  color: #009aa0;
}

.fb-icon {
  color: #1877F2;
  font-size: 1.2em;
  vertical-align: middle;
  margin-right: 6px;
}

@media (max-width: 600px) {
  .credits-container {
    margin: 80px 15px;
    padding: 15px; /* slightly smaller padding */
    max-height: 90vh; 
    overflow-y: auto; 
  }

  .credits-grid {
    grid-template-columns: repeat(2, 1fr); /* 2 cards per row */
    gap: 12px; /* smaller gap */
  }

  .credit-card {
    padding: 12px; /* reduce padding inside cards */
  }

  .credit-card img {
    width: 70px;   /* smaller image */
    height: 70px;
    margin-bottom: 10px;
  }

  .credit-card h3 {
    font-size: 1rem;
    margin-bottom: 5px;
  }

  .credit-card p {
    font-size: 0.8rem;
    margin-bottom: 8px;
  }

  .credit-card a {
    font-size: 0.85rem;
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

<div class="credits-container">
  <h1>Credits & Acknowledgments</h1>
  <div class="credits-grid">

    <div class="credit-card">
      <img src="uploads/1.jpg" alt="FB Shop 1">
      <h3>PDERT Farm</h3>
      <p>Provided information and photos for several freshwater species.</p>
      <a href="https://www.facebook.com/share/17TY4ypYPd/" target="_blank">
        <i class="fa-brands fa-facebook fb-icon"></i> Visit Page
      </a>
    </div>

    <div class="credit-card">
      <img src="uploads/2.jpg" alt="FB Shop 2">
      <h3>Zoe Fishes</h3>
      <p>Provided information and photos for several freshwater species.<p>
      <a href="https://www.facebook.com/share/19wounWe2E/" target="_blank">
        <i class="fa-brands fa-facebook fb-icon"></i> Visit Page
      </a>
    </div>
    
     <div class="credit-card">
      <img src="uploads/3.jpg" alt="FB Shop 2">
      <h3>Kamp BJ Aquatics</h3>
      <p>Provided information and photos for several freshwater species.</p>
      <a href="https://www.facebook.com/share/1BN2LMieux/" target="_blank">
        <i class="fa-brands fa-facebook fb-icon"></i> Visit Page
      </a>
    </div>
    
     <div class="credit-card">
      <img src="uploads/4.jpg" alt="FB Shop 2">
      <h3>Quinta Aquatica</h3>
      <p>Provided information and photos for several freshwater species.</p>
      <a href="https://www.facebook.com/share/1HZ7XKiDQG/" target="_blank">
        <i class="fa-brands fa-facebook fb-icon"></i> Visit Page
      </a>
    </div>
    
     <div class="credit-card">
      <img src="uploads/5.jpg" alt="FB Shop 2">
      <h3>Vince Aquatics</h3>
      <p>Provided information and photos for several freshwater species.</p>
      <a href="https://www.facebook.com/share/17kQa62QAz/" target="_blank">
        <i class="fa-brands fa-facebook fb-icon"></i> Visit Page
      </a>
    </div>
    
     <div class="credit-card">
      <img src="uploads/6.jpg" alt="FB Shop 2">
      <h3>Guppy Meal</h3>
      <p>Provided information and photos for several freshwater species.</p>
      <a href="https://www.facebook.com/share/17x1gDJkWf/" target="_blank">
        <i class="fa-brands fa-facebook fb-icon"></i> Visit Page
      </a>
    </div>
    
        <div class="credit-card">
      <img src="uploads/7.jpg" alt="FB Shop 2">
      <h3>FishBase</h3>
      <p>Provided information for several freshwater species.</p>
      <a href="https://www.fishbase.se/search.php" target="_blank">Visit Page</a>
    </div>
    
        <div class="credit-card">
      <img src="uploads/8.jpg" alt="FB Shop 2">
      <h3>Aqueon</h3>
      <p>Provided Care Guidelines and Disease Prevention.</p>
      <a href="https://www.aqueon.com/" target="_blank">Visit Page</a>
    </div>
    
        <div class="credit-card">
      <img src="uploads/9.png" alt="FB Shop 2">
      <h3>Live Aquaria</h3>
      <p>Provided information for several freshwater species.</p>
      <a href="https://www.liveaquaria.com/" target="_blank">Visit Page</a>
    </div>
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
