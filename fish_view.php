<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'db.php';
session_start();

// Set UTF-8 collation to prevent host-specific issues
$conn->set_charset("utf8mb4");
$conn->query("SET collation_connection = 'utf8mb4_general_ci'");

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

if (!isset($_GET['id'])) {
    echo "Fish not found.";
    exit;
}

$id = intval($_GET['id']);

// Fetch fish details
$stmt = $conn->prepare("SELECT * FROM fishes WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if (!$row = $result->fetch_assoc()) {
    echo "Fish not found.";
    exit;
}

// Fetch aquarium sizes for this fish (ascending numeric order)
$aquaStmt = $conn->prepare("
    SELECT tank_size, info, cleaning_frequency
    FROM aquarium_sizes
    WHERE fish_id = ?
    ORDER BY CAST(SUBSTRING_INDEX(tank_size, ' ', 1) AS DECIMAL(5,2)) ASC
");
$aquaStmt->bind_param("i", $id);
$aquaStmt->execute();
$aquaResult = $aquaStmt->get_result();

// Handle comment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_comment']) && !empty(trim($_POST['comment'])) && isset($_SESSION['user'])) {
    $comment = trim($_POST['comment']);
    $user = $_SESSION['user']['username'];
    $ins = $conn->prepare("INSERT INTO comments (fish_id, username, comment, status) VALUES (?, ?, ?, 1)");
    $ins->bind_param("iss", $id, $user, $comment);
    $ins->execute();
    header("Location: fish_view.php?id=$id");
    exit;
}

// Fetch approved comments
$cm = $conn->prepare("SELECT username, comment, created_at FROM comments WHERE fish_id = ? AND status = 1 ORDER BY created_at DESC");
$cm->bind_param("i", $id);
$cm->execute();
$comments = $cm->get_result();

// Fetch recommended plants
$plantsStmt = $conn->prepare("
    SELECT p.id, p.name, p.scientific_name, p.image_url
    FROM plants p
    INNER JOIN fish_plants fp ON p.id = fp.plant_id
    WHERE fp.fish_id = ?
");
$plantsStmt->bind_param("i", $id);
$plantsStmt->execute();
$plants = $plantsStmt->get_result();

// Fetch diseases including scientific name
$diseasesStmt = $conn->prepare("
    SELECT d.id, d.name, d.scientific_name, d.image_url
    FROM diseases d
    INNER JOIN fish_diseases fd ON d.id = fd.disease_id
    WHERE fd.fish_id = ?
");
$diseasesStmt->bind_param("i", $id);
$diseasesStmt->execute();
$diseases = $diseasesStmt->get_result();

// Fetch compatible fishes
$compatStmt = $conn->prepare("
    SELECT f.id, f.name, f.scientific_name, f.image_url
    FROM fish_compatibility fc
    JOIN fishes f ON fc.compatible_with_id = f.id
    WHERE fc.fish_id = ?
");
$compatStmt->bind_param("i", $id);
$compatStmt->execute();
$compatibleFishes = $compatStmt->get_result();

$origin_location = trim($row['origin']);
$country_list = array_map('trim', explode(',', $row['country']));
$invasive_country_list = array_map('trim', explode(',', $row['invasive_country'] ?? ''));

$origin_js = json_encode($origin_location);
$countries_js = json_encode($country_list);
$invasive_js = json_encode($invasive_country_list);

?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
<link rel="stylesheet" href="fish_view.css?v=<?= time() ?>">
<title><?php echo htmlspecialchars($row['name']); ?> - Details</title>
<link rel="icon" href="uploads/logo-16.png" sizes="16x16" type="image/png">
<link rel="icon" href="uploads/logo-32.png" sizes="32x32" type="image/png">
<link rel="icon" href="uploads/logo-48.png" sizes="48x48" type="image/png">
<link rel="icon" href="uploads/logo-512.png" sizes="512x512" type="image/png">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css?family=Roboto:400,700|Montserrat:400,700&display=swap" rel="stylesheet">
</head>
<body>
<style>
/* ===== HERO SECTION ===== */
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
}

.hero-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
}

.hero-content {
    position: relative;
    z-index: 2;
    color: #fff;
    height: 100%;
    display: flex;
    flex-direction: column;
    justify-content: flex-start;
    padding-top: 200px;
}

.hero-content h1 {
    font-size: 3em;
    border-left: 4px solid #00bcd4;
    padding-left: 10px;
    margin: 0 0 20px 0;
    margin-top: 0;
}

.hero-content .subtitle {
    margin-top: 10px;
    font-size: 1.2em;
    opacity: 0.85;
}

.quick-info {
    display: flex;
    flex-wrap: wrap; 
    gap: 30px;      
    margin-top: 20px;
    margin-left: 20px;
}

.quick-info div {
    display: flex;
    flex-direction: column;
    justify-content: flex-start;
    text-align: left;
    min-width: 100px; 
}


.quick-info div span {
    display: block;
    font-weight: bold;
    font-size: 1.1em;
    color: #00bcd4;
}

.family-link {
  color: #00bcd4;
  text-decoration: underline;
  font-weight: bold;
  transition: color 0.3s ease;
}

.family-link:hover {
  color: #00eaff;
  text-decoration: underline;
}

.scientific-name {
    font-style: italic;
    font-size: 1.4em;
    color: #ccc;
    margin-top: -20px;
    margin-bottom: 20px;
    padding-left: 14px;
}

.hero::after {
    content: "";
    position: absolute;
    bottom: 0;
    left: 0;
    width: 100%;
    height: 120px;
    background: linear-gradient(to bottom, rgba(0, 0, 0, 0) 0%, #0d1117 100%);
    pointer-events: none;
}

/* ===== RESPONSIVE ===== */
@media (max-width: 1200px) {
    .hero {
        height: 400px;
        padding-left: 40px;
    }
    .hero-content {
        padding-top: 160px;
    }
    .hero-content h1 {
        font-size: 2.5em;
    }
    .hero-content .subtitle {
        font-size: 1.1em;
    }
    .quick-info {
        gap: 120px;
    }
    .scientific-name {
        font-size: 1.3em;
    }
}

@media (max-width: 900px) {
    .hero {
        height: 350px;
        padding-left: 30px;
    }
    .hero-content {
        padding-top: 140px;
    }
    .hero-content h1 {
        font-size: 2.2em;
        margin-top: 20px;
    }
    .hero-content .subtitle {
        font-size: 1em;
    }
    .quick-info {
        gap: 20px;
        margin-left: 15px;
    }
    .quick-info div span {
        font-size: 1em;
    }
    .scientific-name {
        font-size: 1.2em;
    }
}

@media (max-width: 600px) {
   .hero {
        height: 250px; /* smaller hero */
        padding-left: 15px;
    }
    .hero-content {
        padding-top: 80px; /* less padding so content starts higher */
    }
    .hero-content h1 {
        font-size: 1.8em;
        border-left-width: 3px;
        padding-left: 8px;
        margin-top: 30px;
    }
    .hero-content .subtitle {
        font-size: 0.95em;
    }
    .quick-info {
        gap: 15px;
        margin-left: 10px;
        flex-wrap: nowrap; /* keep them in one line and scroll if needed */
        overflow-x: auto;  /* allow horizontal scroll on very small screens */
        -webkit-overflow-scrolling: touch;
    }
    .quick-info div {
        min-width: 80px;
    }
    .quick-info div span {
        font-size: 0.95em;
    }
    .scientific-name {
        font-size: 1em;
        padding-left: 10px;
        margin-top: -15px;
        margin-bottom: 15px;
    }
}

.aquarium-info {
    margin-bottom: 20px;
    padding: 10px;
    border-radius: 8px;
}

.aquarium-info .tank-size {
    font-size: 1.1rem;  /* bigger text */
    font-weight: bold;
    color: #00bcd4;     /* matches your accent color */
    margin-bottom: 5px;
}

.aquarium-info .tank-info {
    margin-bottom: 5px;
    color: #fff;
}

.aquarium-info .cleaning-frequency {
    color: #ccc;
    font-style: italic;
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

<div class="hero">
  <div class="hero-overlay"></div>
  <div class="hero-content">
    <h1><?= htmlspecialchars($row['name']); ?></h1>
    <div class="scientific-name"><em><?= htmlspecialchars($row['scientific_name']); ?></em></div>

   <div class="quick-info">
  <?php if (!empty($row['family'])): ?>
    <div>
      Family:
      <a href="family_view.php?family=<?= urlencode($row['family']); ?>" class="family-link">
        <?= htmlspecialchars($row['family']); ?>
      </a>
    </div>
  <?php endif; ?>

  <div>Year: <span><?= htmlspecialchars($row['year_discovered']); ?></span></div>
  <div>Origin: <span><?= htmlspecialchars($row['origin']); ?></span></div>
</div>
  </div>
</div>

<div class="content">
  <div class="section">
    <div class="section-title">Description</div>
    <p><?php echo nl2br(htmlspecialchars($row['description'])); ?></p>
  </div>

  <?php if (!empty($row['male_description']) || !empty($row['image_male_url'])): ?>
  <div class="section">
    <div class="section-title">Male vs Female Comparison</div>
    <div class="comparison-container">
      <div class="comparison-box">
        <h4>Female</h4>
        <?php if (!empty($row['image_url'])): ?>
        <div class="image-hover">
          <img src="<?php echo htmlspecialchars($row['image_url']); ?>" alt="Female Fish">
          <div class="overlay">
            <p><?php echo nl2br(htmlspecialchars($row['female_description'])); ?></p>
          </div>
        </div>
        <?php endif; ?>
      </div>
      <div class="comparison-box">
        <h4>Male</h4>
        <?php if (!empty($row['image_male_url'])): ?>
        <div class="image-hover">
          <img src="<?php echo htmlspecialchars($row['image_male_url']); ?>" alt="Male Fish">
          <div class="overlay">
            <p><?php echo nl2br(htmlspecialchars($row['male_description'])); ?></p>
          </div>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <?php if (!empty($row['sexual_difference'])): ?>
  <div class="section">
    <div class="section-title">Sexual Difference</div>
    <p><?php echo nl2br(htmlspecialchars($row['sexual_difference'])); ?></p>
  </div>
  <?php endif; ?>

  <?php if (!empty($row['average_size']) || !empty($row['max_size']) || !empty($row['longevity']) || !empty($row['shape'])): ?>
  <div class="section behavior-section">
    <div class="section-title">Physical Information</div>
    <div class="behavior-grid">

      <?php if (!empty($row['average_size'])): ?>
      <div class="behavior-item">
        <i class="fas fa-arrows-alt-h"></i>
        <div>
          <strong>Average Size</strong>
          <p><?= htmlspecialchars($row['average_size']) ?> cm</p>
        </div>
      </div>
      <?php endif; ?>

      <?php if (!empty($row['max_size'])): ?>
      <div class="behavior-item">
        <i class="fas fa-ruler-horizontal"></i>
        <div>
          <strong>Maximum Size</strong>
          <p><?= htmlspecialchars($row['max_size']) ?> cm</p>
        </div>
      </div>
      <?php endif; ?>

      <?php if (!empty($row['longevity'])): ?>
      <div class="behavior-item">
        <i class="fas fa-birthday-cake"></i>
        <div>
          <strong>Longevity</strong>
          <p><?= htmlspecialchars($row['longevity']) ?> year<?= ($row['longevity'] > 1 ? 's' : '') ?></p>
        </div>
      </div>
      <?php endif; ?>

      <?php if (!empty($row['shape'])): ?>
      <div class="behavior-item">
        <i class="fas fa-shapes"></i>
        <div>
          <strong>Shape</strong>
          <p><?= htmlspecialchars($row['shape']) ?></p>
        </div>
      </div>
      <?php endif; ?>

    </div>
  </div>
<?php endif; ?>

  <div class="section">
    <div class="section-title">Water Parameters</div>
    <div class="quick-info-grid">
      <div class="info-item">
        <i class="fas fa-thermometer-half"></i>
        <div>
          <strong>Temperature</strong>
          <p><?php echo htmlspecialchars($row['temp_range']); ?></p>
        </div>
      </div>
      <div class="info-item">
        <i class="fas fa-tint"></i>
        <div>
          <strong>pH (Acidity)</strong>
          <p><?php echo htmlspecialchars($row['ph_range']); ?></p>
        </div>
      </div>
      <div class="info-item">
        <i class="fas fa-water"></i>
        <div>
          <strong>GH/KH (Hardness)</strong>
          <p><?php echo htmlspecialchars($row['hardness_range']); ?></p>
        </div>
      </div>
    </div>
  </div>
  
<?php if ($aquaResult->num_rows > 0): ?>
<div class="section">
  <div class="section-title">Aquarium Recommendations</div>

  <?php while ($aqua = $aquaResult->fetch_assoc()): ?>
    <div class="aquarium-info">
      <div class="tank-size"><?= htmlspecialchars($aqua['tank_size']); ?></div>
      <div class="tank-info"><?= htmlspecialchars($aqua['info']); ?></div>
      <div class="cleaning-frequency"><em>Cleaning Frequency: <?= htmlspecialchars($aqua['cleaning_frequency']); ?></em></div>
    </div>
  <?php endwhile; ?>
</div>
<?php endif; ?>


  <?php if (!empty($row['natural_habitat'])): ?>
  <div class="section">
    <div class="section-title">Natural Habitat</div>
    <p><?php echo nl2br(htmlspecialchars($row['natural_habitat'])); ?></p>
  </div>
  <?php endif; ?>

  <?php if (!empty($row['breeding'])): ?>
  <div class="section">
    <div class="section-title">Breeding</div>
    <p><?php echo nl2br(htmlspecialchars($row['breeding'])); ?></p>
  </div>
  <?php endif; ?>
<?php if (!empty($row['diet_feeding']) || !empty($row['sociability']) || !empty($row['territorial']) || !empty($row['way_of_living'])): ?>
  <div class="section behavior-section">
    <div class="section-title">Behaviour & Life Cycle</div>
    <div class="behavior-grid">

      <?php if (!empty($row['diet_feeding'])): ?>
      <div class="behavior-item">
        <i class="fas fa-utensils"></i>
        <div>
          <strong>Diet</strong>
          <p><?php echo htmlspecialchars($row['diet_feeding']); ?></p>
        </div>
      </div>
      <?php endif; ?>

      <?php if (!empty($row['sociability'])): ?>
      <div class="behavior-item">
        <i class="fas fa-fish"></i>
        <div>
          <strong>Sociability</strong>
          <p><?php echo htmlspecialchars($row['sociability']); ?></p>
        </div>
      </div>
      <?php endif; ?>

      <?php if (!empty($row['territorial'])): ?>
      <div class="behavior-item">
        <i class="fas fa-exclamation-circle"></i>
        <div>
          <strong>Territorial</strong>
          <p><?php echo htmlspecialchars($row['territorial']); ?></p>
        </div>
      </div>
      <?php endif; ?>

      <?php if (!empty($row['way_of_living'])): ?>
      <div class="behavior-item">
        <i class="fas fa-sun"></i>
        <div>
          <strong>Way of Living</strong>
          <p><?php echo htmlspecialchars($row['way_of_living']); ?></p>
        </div>
      </div>
      <?php endif; ?>

    </div>
  </div>
<?php endif; ?>

<?php if (!empty($row['origin'])): ?>
  <div class="section origin-section">
    <div class="section-title">Origin and Distribution</div>
    <div class="origin-map-container">
      <div id="origin-map"></div>
      <div class="legend">
        <span><i class="dot natural"></i> Natural Range</span>
        <span><i class="dot invasive"></i> Native Countries</span>
        <span><i class="dot mixed"></i> Invasive Range</span>
      </div>
    </div>
  </div>

  <!-- Leaflet Library -->
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

  <script>
  document.addEventListener("DOMContentLoaded", () => {
      const map = L.map('origin-map').setView([0, 0], 2);

      L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
        attribution: '&copy; OpenStreetMap contributors'
      }).addTo(map);

      const origin = <?php echo $origin_js; ?>;
      const countries = <?php echo $countries_js; ?>;
      const invasiveCountries = <?php echo json_encode(array_map('trim', explode(',', $row['invasive_country'] ?? ''))); ?>;

      const addMarker = (query, color) => {
          fetch(`geocode.php?q=${encodeURIComponent(query)}`)
          .then(res => res.json())
          .then(data => {
              if (data && data.length > 0) {
                  const lat = data[0].lat;
                  const lon = data[0].lon;
                  L.circleMarker([lat, lon], {
                      radius: 8,
                      color: color,
                      fillColor: color,
                      fillOpacity: 0.6
                  })
                  .addTo(map)
                  .bindPopup(`<b>${query}</b>`);
              }
          })
          .catch(err => console.error("Map error:", err));
      };

      // Natural (Origin)
      if (origin) addMarker(origin, '#00ff88'); // matches .legend .natural

      // Native countries
      if (Array.isArray(countries)) {
          countries.forEach(c => {
              if (c) addMarker(c, '#3399ff'); // matches .legend .mixed
          });
      }

      // Invasive range
      if (Array.isArray(invasiveCountries)) {
          invasiveCountries.forEach(c => {
              if (c) addMarker(c, '#ff4444'); // matches .legend .invasive
          });
      }
  });
  </script>
<?php endif; ?>

  <?php if ($compatibleFishes->num_rows > 0): ?>
  <div class="section">
    <div class="section-title">Compatible Tankmates</div>

    <div class="compat-carousel-wrapper">
      <button class="carousel-arrow left">&#10094;</button>
      <div class="compat-group">
        <?php while ($cf = $compatibleFishes->fetch_assoc()): ?>
          <a href="fish_view.php?id=<?php echo $cf['id']; ?>" class="compat-card">
            <img src="<?php echo htmlspecialchars($cf['image_url']); ?>" alt="<?php echo htmlspecialchars($cf['name']); ?>">
            <div class="compat-overlay">
              <h4><?php echo htmlspecialchars($cf['name']); ?></h4>
              <p><em><?php echo htmlspecialchars($cf['scientific_name']); ?></em></p>
            </div>
          </a>
        <?php endwhile; ?>
      </div>
      <button class="carousel-arrow right">&#10095;</button>
    </div>
  </div>
<?php endif; ?>

<?php if ($diseases->num_rows > 0): ?>
  <div class="section">
    <div class="section-title">Common Fish Diseases</div>

   <div class="plant-carousel-wrapper">
  <button class="carousel-arrow left">&#10094;</button>
  <div class="plant-group">
    <?php while ($disease = $diseases->fetch_assoc()): ?>
      <a href="disease_view.php?id=<?php echo $disease['id']; ?>" class="plant-card">
        <img src="<?php echo htmlspecialchars($disease['image_url']); ?>" alt="<?php echo htmlspecialchars($disease['name']); ?>">
        <div class="plant-overlay">
          <h4><?php echo htmlspecialchars($disease['name']); ?></h4>
          <p><em><?php echo htmlspecialchars($disease['scientific_name']); ?></em></p>
        </div>
      </a>
    <?php endwhile; ?>
  </div>
  <button class="carousel-arrow right">&#10095;</button>
</div>
  </div>
<?php endif; ?>

<?php if ($plants->num_rows > 0): ?>
  <div class="section">
    <div class="section-title">Recommended Plants</div>

    <div class="plant-carousel-wrapper">
      <button class="carousel-arrow left">&#10094;</button>
      <div class="plant-group">
        <?php while ($plant = $plants->fetch_assoc()): ?>
          <a href="plant_view.php?id=<?php echo $plant['id']; ?>" class="plant-card">
            <img src="<?php echo htmlspecialchars($plant['image_url']); ?>" alt="<?php echo htmlspecialchars($plant['name']); ?>">
            <div class="plant-overlay">
              <h4><?php echo htmlspecialchars($plant['name']); ?></h4>
              <p><em><?php echo htmlspecialchars($plant['scientific_name']); ?></em></p>
            </div>
          </a>
        <?php endwhile; ?>
      </div>
      <button class="carousel-arrow right">&#10095;</button>
    </div>
  </div>
<?php endif; ?>

  <div class="comments-section">
    <h3>Share Your Experience</h3>

    <?php if (isset($_SESSION['user'])): ?>
    <form class="comment-form" method="POST">
      <textarea name="comment" placeholder="How was your experience caring for this fish?" required></textarea>
      <button type="submit" name="submit_comment">Post Comment</button>
    </form>
    <?php else: ?>
    <p><a href="login.php">Log in</a> to share your experience.</p>
    <?php endif; ?>

    <div class="comment-list">
      <?php if ($comments->num_rows): ?>
        <?php while ($c = $comments->fetch_assoc()): ?>
        <div class="comment">
          <div class="comment-user"><?php echo htmlspecialchars($c['username']); ?></div>
          <div class="comment-time"><?php echo htmlspecialchars($c['created_at']); ?></div>
          <div class="comment-text"><?php echo nl2br(htmlspecialchars($c['comment'])); ?></div>
        </div>
        <?php endwhile; ?>
      <?php else: ?>
        <p>No comments yet. Be the first to share!</p>
      <?php endif; ?>
    </div>
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

<script>
document.querySelectorAll('.compat-carousel-wrapper, .plant-carousel-wrapper').forEach(wrapper => {
    const group = wrapper.querySelector('.compat-group, .plant-group');
    const leftBtn = wrapper.querySelector('.carousel-arrow.left');
    const rightBtn = wrapper.querySelector('.carousel-arrow.right');

    leftBtn.addEventListener('click', () => {
        group.scrollBy({ left: -group.clientWidth, behavior: 'smooth' });
    });
    rightBtn.addEventListener('click', () => {
        group.scrollBy({ left: group.clientWidth, behavior: 'smooth' });
    });
});
</script>
<?php include 'footer.php'; ?>
</body>
</html>
