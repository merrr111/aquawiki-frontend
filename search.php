<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
include 'db.php';

// Ensure connection uses the right charset
$conn->set_charset("utf8mb4");
$conn->query("SET collation_connection = 'utf8mb4_general_ci'");

$q = trim($_GET['q'] ?? '');
$filter = $_GET['filter'] ?? 'all'; // 🔥 Added filter support

if ($q === '') { header("Location: home.php"); exit; }

// Only allow valid filter values
$allowedFilters = ['all','fish','plant','type'];
if (!in_array($filter, $allowedFilters)) $filter = 'all';

$results = [];
$notifCount = 0; // prevent undefined

// Only fetch notification count if user is logged in
if (isset($_SESSION['user'])) {
    $userId = $_SESSION['user']['id']; // make sure your session stores user id
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

// 🔧 Filter-aware query
$sqlParts = [];
$params = [];
$types = '';

if ($filter === 'all' || $filter === 'type') {
    $sqlParts[] = "
        SELECT 'type' AS category,
               MIN(id) AS id,
               type AS name,
               MIN(image_url) AS image_url
        FROM fishes
        WHERE type COLLATE utf8mb4_general_ci LIKE CONCAT('%', ?, '%')
        GROUP BY type
    ";
    $params[] = $q;
    $types .= "s";
}

if ($filter === 'all' || $filter === 'fish') {
    $sqlParts[] = "
        SELECT 'fish' AS category, id, name, image_url
        FROM fishes
        WHERE name COLLATE utf8mb4_general_ci LIKE CONCAT('%', ?, '%')
           OR scientific_name COLLATE utf8mb4_general_ci LIKE CONCAT('%', ?, '%')
    ";
    $params[] = $q; $params[] = $q;
    $types .= "ss";
}

if ($filter === 'all' || $filter === 'plant') {
    $sqlParts[] = "
        SELECT 'plant' AS category, id, name, image_url
        FROM plants
        WHERE name COLLATE utf8mb4_general_ci LIKE CONCAT('%', ?, '%')
           OR scientific_name COLLATE utf8mb4_general_ci LIKE CONCAT('%', ?, '%')
    ";
    $params[] = $q; $params[] = $q;
    $types .= "ss";
}

// Combine queries only if there are valid parts
if (!empty($sqlParts)) {
    $query = implode(" UNION ", $sqlParts) . " LIMIT 50";
    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) $results[] = $row;
    $stmt->close();
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Search Results - AquaWiki</title>
  <link rel="stylesheet" href="origin.css?v=<?= time() ?>">
  <link rel="icon" href="uploads/logo-16.png" sizes="16x16" type="image/png">
  <link rel="icon" href="uploads/logo-32.png" sizes="32x32" type="image/png">
  <link rel="icon" href="uploads/logo-48.png" sizes="48x48" type="image/png">
  <link rel="icon" href="uploads/logo-512.png" sizes="512x512" type="image/png">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css?family=Roboto:400,700|Montserrat:400,700&display=swap" rel="stylesheet">
</head>
<body>
<style>
    .search-results {
      max-width: 1200px;
      margin: 100px auto;
      padding: 20px;
      text-align: center;
    }

    .search-results h2 {
      font-family: "Merriweather", serif;
      color: #00c2cb;
      margin-bottom: 20px;
    }
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

<div class="search-results">
  <h2>Search Results for “<?= htmlspecialchars($q) ?>”</h2>

  <div class="fish-grid">
    <?php if (count($results) > 0): ?>
      <?php foreach ($results as $fish): ?>
        <div class="fish-card" onclick="location.href='<?php
            if ($fish['category'] === 'fish') echo 'fish_view.php?id=' . $fish['id'];
            else if ($fish['category'] === 'plant') echo 'plant_view.php?id=' . $fish['id'];
            else if ($fish['category'] === 'type') echo 'fish_type.php?type=' . urlencode($fish['name']);
        ?>'">
          <img src="<?= htmlspecialchars($fish['image_url']) ?>" alt="<?= htmlspecialchars($fish['name']) ?>">
          <div class="fish-info">
            <p><?= htmlspecialchars($fish['name']) ?></p>
            <?php if ($fish['category'] === 'fish' && !empty($fish['scientific_name'])): ?>
              <h3><em><?= htmlspecialchars($fish['scientific_name']) ?></em></h3>
            <?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>
    <?php else: ?>
      <p style="grid-column:1/-1;text-align:center;color:#ccc;">No results found.</p>
    <?php endif; ?>
  </div>
</div>


<?php if (isset($_SESSION['user'])): ?>
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
<?php endif; ?>
<?php include 'footer.php'; ?>
</body>
</html>
