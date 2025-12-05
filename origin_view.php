<?php
include 'db.php';
session_start();

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

if (!isset($_GET['origin'])) {
    echo "Origin not specified.";
    exit;
}

$origin = $_GET['origin'];

// Fetch fishes with this origin (allow partial matches like "Thailand" inside "Thailand, Laos")
$stmt = $conn->prepare("SELECT * FROM fishes WHERE LOWER(origin) LIKE LOWER(?)");
$search = "%" . $origin . "%";
$stmt->bind_param("s", $search);
$stmt->execute();
$result = $stmt->get_result();
$totalFishes = $result->num_rows;

$conn->close();

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Fishes from <?php echo htmlspecialchars($origin); ?></title>
  <link rel="stylesheet" href="origin.css?v=<?= time() ?>">
    <!-- Font Awesome for icons -->
    <link rel="icon" href="uploads/logo-16.png" sizes="16x16" type="image/png">
<link rel="icon" href="uploads/logo-32.png" sizes="32x32" type="image/png">
<link rel="icon" href="uploads/logo-48.png" sizes="48x48" type="image/png">
<link rel="icon" href="uploads/logo-512.png" sizes="512x512" type="image/png">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css?family=Roboto:400,700|Montserrat:400,700&display=swap" rel="stylesheet">
    </head>
<body><style> 
</style>

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
    
<!-- Hero -->
<div class="hero">
     <div class="hero-overlay"></div>
  <div class="hero-content">
    <h1><?php echo htmlspecialchars($origin); ?> - List of Fishes</h1>
    <p>Discover the freshwater species found in <?php echo htmlspecialchars($origin); ?>.</p>
  </div>
</div>

<!-- Species count -->
<div class="species-count">
  <?php echo $totalFishes; ?> species available for research.
</div>

<!-- Fish grid -->
<div class="fish-grid">
  <?php if ($totalFishes > 0): ?>
    <?php while ($fish = $result->fetch_assoc()): ?>
      <div class="fish-card" onclick="location.href='fish_view.php?id=<?php echo $fish['id']; ?>'">
        <img src="<?php echo htmlspecialchars($fish['image_url']); ?>" alt="<?php echo htmlspecialchars($fish['name']); ?>">
        <div class="fish-info">
          <p><?php echo htmlspecialchars($fish['name']); ?></p>
          <h3><em><?php echo htmlspecialchars($fish['scientific_name']); ?></em></h3>
        </div>
      </div>
    <?php endwhile; ?>
  <?php else: ?>
    <p style="grid-column:1/-1;text-align:center;color:#ccc;">No fishes found from this origin.</p>
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
<?php include 'footer.php'; ?>
</body>
</html>
