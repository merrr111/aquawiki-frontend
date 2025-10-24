<?php 
session_start(); 
if (!isset($_SESSION['user'])) header("Location: login.php"); 
include 'db.php';

$userId = $_SESSION['user']['id'];
$latestImage = null;
$fishName = null;
$fishDescription = null;
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

// ✅ Fetch latest uploaded fish info
$stmt = $conn->prepare("
    SELECT u.image_path, f.name, f.description
    FROM user_uploads u
    LEFT JOIN fishes f ON u.matched_fish_id = f.id
    WHERE u.user_id = ?
    ORDER BY u.upload_time DESC 
    LIMIT 1
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$stmt->bind_result($imagePath, $name, $description);
if ($stmt->fetch()) {
    $latestImage = $imagePath;
    $fishName = $name;
    $fishDescription = $description;
}
$stmt->close();

// ✅ Fetch fish types
$fishTypes = [];
$sql = "
    SELECT type, MIN(id) as sample_id, MIN(name) as sample_name, 
           MIN(description) as description, MIN(image_url) as image
    FROM fishes
    WHERE status = 1
    GROUP BY type
    ORDER BY type ASC
";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $fishTypes[] = $row;
    }
}

// ✅ Fetch fishes grouped by origin
$originsData = [];
$sql = "
    SELECT origin, id, name, scientific_name, description, image_url
    FROM fishes
    WHERE status = 1 AND origin IS NOT NULL AND origin != ''
    ORDER BY origin, name
";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $originsData[$row['origin']][] = $row;
    }
}

// ✅ Fetch plants
$plantsData = [];
$sql = "
    SELECT id, name, scientific_name, description, image_url
    FROM plants
    ORDER BY name ASC
    LIMIT 12
"; 
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $plantsData[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>AquaWiki - Home</title>
  <link rel="stylesheet" href="home.css?v=<?= time() ?>">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css?family=Roboto:400,700|Montserrat:400,700&display=swap" rel="stylesheet">
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
  </div>
</div>

<!-- OPTIONAL: keep this small JS for mobile tap dropdown support -->
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

<!-- Hero Section -->
<div class="hero">
  <div class="hero-overlay"></div>
  <div class="hero-content">
    <h1>Discover Freshwater Fish</h1>
  </div>
</div>

<h1>Welcome to AquaWiki, <?= htmlspecialchars($_SESSION['user']['username']); ?>!</h1>

<div class="upload-section">
  <h2><i class="fas fa-upload"></i> Upload a Fish Photo to Identify</h2>
  <form method="POST" action="identify_fish.php" enctype="multipart/form-data">
    <input type="file" name="fish_image" accept="image/*" required><br><br>
    <button type="submit"><i class="fas fa-search"></i> Identify Fish</button>
  </form>
</div>

<h2 class="all-fishes-title">All Fishes</h2>
<div class="fish-types">
  <?php foreach ($fishTypes as $fish): ?>
  <div class="fish-card" onclick="location.href='browse.php?type=<?= urlencode($fish['type']); ?>'">
    <div class="fish-image">
      <img src="<?= htmlspecialchars($fish['image']); ?>" alt="<?= htmlspecialchars($fish['sample_name']); ?>">
    </div>
    <div class="fish-info">
      <h3><?= strtoupper(htmlspecialchars($fish['type'])); ?></h3>
      <p><?= htmlspecialchars(substr($fish['description'], 0, 100)); ?>...</p>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<section class="feature-hero">
    <div class="hero-overlay"></div>
    <div class="hero-content">
        <h1>Discover Freshwater Fish & Plants of Asia</h1>
        <p>Explore aquarium species from different Asian countries and aquatic plants that bring life to your tank.</p>
       <div class="origin-links">
    <a href="origin_view.php?origin=Thailand">Thailand</a>
    <a href="origin_view.php?origin=Malaysia">Malaysia</a>
    <a href="origin_view.php?origin=Indonesia">Indonesia</a>
    <a href="origin_view.php?origin=Philippines">Philippines</a>
    <a href="origin_view.php?origin=India">India</a>
    <a href="origin_view.php?origin=China">China</a>
    <a href="origin_view.php?origin=Vietnam">Vietnam</a>
    <a href="browse_plants.php">Aquatic Plants</a>
</div>
    </div>
</section>

<!-- DISCOVER BY ORIGIN -->
<?php foreach ($originsData as $origin => $fishes): ?>
    <div class="origin-section">
        <h2 class="origin-title">The Fish of <?php echo htmlspecialchars($origin); ?></h2>
        <p class="origin-description">
            Explore freshwater species that come from <?php echo htmlspecialchars($origin); ?>.
        </p>

        <div class="fish-carousel-wrapper">
            <button class="carousel-arrow left">&#10094;</button>
            <div class="origin-fish-row">
                <?php foreach ($fishes as $fish): ?>
                    <div class="origin-fish-card" onclick="location.href='fish_view.php?id=<?php echo $fish['id']; ?>'">
                        <img src="<?php echo htmlspecialchars($fish['image_url']); ?>" 
                            alt="<?php echo htmlspecialchars($fish['name']); ?>">
                        <div class="origin-fish-info">
                            <h3><?php echo htmlspecialchars($fish['name']); ?></h3>
                            <p><?php echo htmlspecialchars($fish['scientific_name']); ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <button class="carousel-arrow right">&#10095;</button>
        </div>
    </div>
<?php endforeach; ?>

<?php if (!empty($plantsData)): ?>
    <div class="origin-section">
        <h2 class="origin-title">Discover Aquatic Plants</h2>
        <p class="origin-description">
            Explore beautiful aquatic plants that enhance your aquarium’s ecosystem.
        </p>
        <div class="origin-fish-row">
            <?php foreach ($plantsData as $plant): ?>
                <div class="origin-fish-card" onclick="location.href='plant_view.php?id=<?php echo $plant['id']; ?>'">
                    <img src="<?php echo htmlspecialchars($plant['image_url']); ?>" 
                         alt="<?php echo htmlspecialchars($plant['name']); ?>">

                    <!-- overlay with plant name + scientific name -->
                    <div class="origin-fish-info">
                        <h3><?php echo htmlspecialchars($plant['name']); ?></h3>
                        <p><?php echo htmlspecialchars($plant['scientific_name']); ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>

<!-- ✅ Notification auto-refresh + fade effect -->
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

<script>
document.querySelectorAll('.fish-carousel-wrapper').forEach(wrapper => {
  const group = wrapper.querySelector('.origin-fish-row');
  const leftBtn = wrapper.querySelector('.carousel-arrow.left');
  const rightBtn = wrapper.querySelector('.carousel-arrow.right');

  leftBtn.addEventListener('click', () => {
    group.scrollBy({ left: -group.clientWidth / 2, behavior: 'smooth' });
  });

  rightBtn.addEventListener('click', () => {
    group.scrollBy({ left: group.clientWidth / 2, behavior: 'smooth' });
  });
});
</script>

</body>
</html>
