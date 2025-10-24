<?php
include 'db.php';
session_start();

if (!isset($_GET['id'])) {
    echo "Plant not found.";
    exit;
}

$id = intval($_GET['id']);

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

// Fetch plant details
$stmt = $conn->prepare("SELECT * FROM plants WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if (!$row = $result->fetch_assoc()) {
    echo "Plant not found.";
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css?family=Roboto:400,700|Montserrat:400,700&display=swap" rel="stylesheet">
  <title><?php echo htmlspecialchars($row['name']); ?> - Plant Details</title>
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
  margin-top: 70px; /* pushes hero below navbar */
}

.hero h1 {
  font-size: 3rem;
  text-shadow: 2px 2px 6px rgba(0,0,0,0.6);
  margin: 0;
  color: #00c2cb;
}

/* ===== RESPONSIVE HERO ===== */
@media (max-width: 1024px) {
  .hero {
    height: 380px;
    padding-left: 40px;
  }

  .hero h1 {
    font-size: 2.4rem;
  }
}

@media (max-width: 768px) {
  .hero {
    height: 300px;
    justify-content: center;
    text-align: center;
    padding: 0 20px;
  }

  .hero h1 {
    font-size: 2rem;
  }
}

@media (max-width: 480px) {
  .hero {
    height: 240px;
    padding: 0 10px;
    margin-top: 60px; /* smaller offset under navbar */
  }

  .hero h1 {
    font-size: 1.6rem;
  }
}

  </style>
</head>
<body>

<!-- NAVBAR -->
<div class="navbar">
    <div class="logo"><i class="fas fa-water"></i> AquaWiki</div>

    <div class="menu">
        <a href="home.php">Home</a>

        <div class="dropdown">
            <a href="browse.php" class="dropbtn">Browse<i class="fas fa-caret-down"></i></a>
            <div class="dropdown-content">
                <a href="browse.php">Browse Fish</a>
                <a href="browse_plants.php">Aquatic Plants</a>
            </div>
        </div>

        <a href="community.php">Community</a>

        <div class="dropdown">
            <a href="profile.php" class="dropbtn">Profile <i class="fas fa-caret-down"></i></a>
            <div class="dropdown-content">
                <a href="upload_history.php">Upload History</a>
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
            <a href="notification.php" id="notifBtn" style="position:relative; margin-right:8px;">
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
            <a href="login.php" style="display:inline-flex; align-items:center; gap:4px;">
                <i class="fas fa-user"></i> Login
            </a>
        <?php endif; ?>
    </div>
</div>

<script>
document.querySelectorAll('.dropdown > .dropbtn').forEach(btn => {
  let firstTapTime = 0;
  
  btn.addEventListener('click', e => {
    if (window.innerWidth <= 900) {
      const dropdown = btn.parentElement;
      const now = Date.now();

      // If dropdown is not open yet → open it, prevent navigation
      if (!dropdown.classList.contains('open')) {
        e.preventDefault();
        dropdown.classList.add('open');
        firstTapTime = now;
      } 
      // If tapped again quickly (within 1.5s) → follow link
      else if (now - firstTapTime < 1500) {
        window.location.href = btn.getAttribute('href');
      } 
      // Otherwise → reset timer (prevents getting stuck)
      else {
        e.preventDefault();
        firstTapTime = now;
      }
    }
  });
});
</script>

  <div class="hero">
    <h1><?php echo htmlspecialchars($row['name']); ?></h1>
  </div>

  <div class="plant-container">
    <div class="plant-image">
      <img src="<?php echo htmlspecialchars($row['image_url']); ?>" alt="<?php echo htmlspecialchars($row['name']); ?>">
    </div>
    <div class="plant-details">
      <h2><?php echo htmlspecialchars($row['name']); ?></h2>
      <p><span class="detail-label">Scientific Name:</span> <?php echo htmlspecialchars($row['scientific_name']); ?></p>
      <p><span class="detail-label">Type:</span> <?php echo htmlspecialchars($row['type']); ?></p>
      <p><span class="detail-label">Growth Rate:</span> <?php echo htmlspecialchars($row['growth_rate']); ?></p>
      <p><span class="detail-label">Lighting Needs:</span> <?php echo htmlspecialchars($row['lighting']); ?></p>
      <p><span class="detail-label">CO₂ Requirement:</span> <?php echo htmlspecialchars($row['co2_requirement']); ?></p>
      <p><span class="detail-label">Description:</span><br><?php echo nl2br(htmlspecialchars($row['description'])); ?></p>
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
</body>
</html>
