<?php 

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Log errors to a file in the same directory
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error_log.txt');

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
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="google-site-verification" content="WjPwQFe-kZEagpC8R9K9PwW_M4YZervYwmzBhWD10IQ" />
  <title>AquaWiki - Home</title>
  <link rel="stylesheet" href="home.css?v=<?= time() ?>">
  <link rel="icon" href="uploads/logo-16.png" sizes="16x16" type="image/png">
  <link rel="icon" href="uploads/logo-32.png" sizes="32x32" type="image/png">
  <link rel="icon" href="uploads/logo-48.png" sizes="48x48" type="image/png">
  <link rel="icon" href="uploads/logo-512.png" sizes="512x512" type="image/png">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css?family=Roboto:400,700|Montserrat:400,700&display=swap" rel="stylesheet">
</head>
<body>
    
<div id="loadingOverlay" class="loading-overlay">
  <div class="spinner"></div>
  <p>Identifying fish, please wait...</p>
</div>

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


<!-- Hero Section -->
<div class="hero">
  <div class="hero-overlay"></div>
  <div class="hero-content">
    <h1>Discover Freshwater Fish</h1>
  </div>
</div>

<div class="hero-search">
<form id="searchForm" action="search.php" method="get" autocomplete="off" class="search-wrapper">
    
    <input type="text" id="searchInput" name="q" placeholder="Search..." required>

    <!-- Filter Icon + Dropdown -->
    <div class="filter-box">
        <i class="fas fa-filter"></i>
        <select id="searchFilter" name="filter">
            <option value="all">All</option>
            <option value="fish">Fish</option>
            <option value="plant">Plants</option>
        </select>
    </div>

    <button type="submit"><i class="fas fa-search"></i></button>
</form>
  <div id="searchResults"></div> 
</div>

<h1>Welcome to AquaWiki, <?= htmlspecialchars($_SESSION['user']['username']); ?>!</h1>

<div class="upload-section"> <h2><i class="fas fa-upload"></i> Upload a Fish Photo to Identify</h2> 
<form method="POST" action="identify_fish.php" enctype="multipart/form-data"> 
<input type="file" name="fish_image" accept="image/*" required><br><br> 
<button type="submit"><i class="fas fa-search"></i> Identify Fish</button> </form> </div>

<h2 class="all-fishes-title">All Fishes</h2>
<div class="fish-types">
  <?php foreach ($fishTypes as $fish): ?>
  <div class="fish-card" onclick="location.href='fish_type.php?type=<?= urlencode($fish['type']); ?>'">
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

        <!-- ✅ Add carousel wrapper + arrows like the fish section -->
        <div class="fish-carousel-wrapper">
            <button class="carousel-arrow left"><i class="fas fa-chevron-left"></i></button>

            <div class="origin-fish-row">
                <?php foreach ($plantsData as $plant): ?>
                    <div class="origin-fish-card" onclick="location.href='plant_view.php?id=<?php echo $plant['id']; ?>'">
                        <img src="<?php echo htmlspecialchars($plant['image_url']); ?>" 
                             alt="<?php echo htmlspecialchars($plant['name']); ?>">

                        <div class="origin-fish-info">
                            <h3><?php echo htmlspecialchars($plant['name']); ?></h3>
                            <p><?php echo htmlspecialchars($plant['scientific_name']); ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <button class="carousel-arrow right"><i class="fas fa-chevron-right"></i></button>
        </div>
    </div>
<?php endif; ?>


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

<script>
let scrollTimer;
window.addEventListener('scroll', () => {
  const searchBar = document.querySelector('.hero-search');
  if (!searchBar) return;

  // hide immediately while scrolling
  searchBar.classList.add('hidden');

  // show again after user stops scrolling
  clearTimeout(scrollTimer);
  scrollTimer = setTimeout(() => {
    searchBar.classList.remove('hidden');
  }, 500);
});
</script>


<script>
const input = document.getElementById('searchInput');
const resultsBox = document.getElementById('searchResults');
const filterSelect = document.getElementById('searchFilter');
let searchTimer;

input.addEventListener('input', function() {
  clearTimeout(searchTimer);
  const query = this.value.trim();

  if (query.length < 2) {
    resultsBox.style.display = 'none';
    return;
  }

  searchTimer = setTimeout(() => {
    const filter = (filterSelect && filterSelect.value) ? filterSelect.value : 'all';

    fetch(`search_suggest.php?q=${encodeURIComponent(query)}&filter=${encodeURIComponent(filter)}`)
      .then(res => res.json())
      .then(data => {
        if (data.length > 0) {
          resultsBox.innerHTML = data.map(item => `
            <div class="search-item" onclick="goToSearch('${item.id}', '${item.category}', '${item.name.replace(/'/g,"\\'")}')">
              <img src="${item.image_url}" alt="${item.name}">
              <div>
                <strong>${item.name}</strong><br>
                <small>${
                  item.category === 'fish' ? 'Fish' :
                  item.category === 'plant' ? 'Plant' :
                  'Type'
                }</small>
              </div>
            </div>
          `).join('');
          resultsBox.style.display = 'block';
        } else {
          resultsBox.innerHTML = '<div class="search-item">No results found</div>';
          resultsBox.style.display = 'block';
        }
      })
      .catch(err => console.error('Search suggest error:', err));
  }, 300);
});

document.addEventListener('click', e => {
  if (!e.target.closest('.hero-search')) resultsBox.style.display = 'none';
});

function goToSearch(idOrName, category) {
  const filter = (filterSelect && filterSelect.value) ? filterSelect.value : 'all';

  if (category === 'fish') {
    window.location.href = `fish_view.php?id=${encodeURIComponent(idOrName)}`;
  } else if (category === 'plant') {
    window.location.href = `plant_view.php?id=${encodeURIComponent(idOrName)}`;
  } else if (category === 'type') {
    window.location.href = `fish_type.php?type=${encodeURIComponent(idOrName)}`;
  }
}
</script>


<script>
document.addEventListener("DOMContentLoaded", () => {
  const identifyForm = document.querySelector('.upload-section form');
  const loadingOverlay = document.getElementById('loadingOverlay');

  if (identifyForm && loadingOverlay) {
    identifyForm.addEventListener('submit', () => {
      // 🧠 Allow the form to start submitting, then show overlay right after
      setTimeout(() => {
        loadingOverlay.classList.add('active');
      }, 0);
    });
  }
});
</script>



<?php include 'footer.php'; ?>

</body>
</html>
