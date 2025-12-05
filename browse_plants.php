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

// Fetch all plants
$sql = "SELECT * FROM plants ORDER BY name ASC";
$result = $conn->query($sql);
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="icon" href="uploads/logo-16.png" sizes="16x16" type="image/png">
<link rel="icon" href="uploads/logo-32.png" sizes="32x32" type="image/png">
<link rel="icon" href="uploads/logo-48.png" sizes="48x48" type="image/png">
<link rel="icon" href="uploads/logo-512.png" sizes="512x512" type="image/png">
    <link rel="stylesheet" href="browse_plants.css?v=<?= time() ?>">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css?family=Roboto:400,700|Montserrat:400,700&display=swap" rel="stylesheet">
  <title>Browse Plants</title>
    
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

    <!-- Hero Section -->
    <div class="hero">
        <div class="hero-overlay"></div>
        <div class="hero-content">
            <h1>Discover Aquarium Plants</h1>
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

  <h2 style="text-align:center; margin-top:80px; color:#fff;">Browse Aquatic Plants</h2>

  <div class="plant-grid">
    <?php if ($result && $result->num_rows > 0): ?>
      <?php while ($plant = $result->fetch_assoc()): ?>
        <div class="plant-card" onclick="window.location.href='plant_view.php?id=<?php echo $plant['id']; ?>'">
          <img src="<?php echo htmlspecialchars($plant['image_url']); ?>" alt="<?php echo htmlspecialchars($plant['name']); ?>">
          <div class="plant-info">
            <h3><?php echo htmlspecialchars($plant['name']); ?></h3>
            <p><?php echo htmlspecialchars($plant['scientific_name']); ?></p>
          </div>
        </div>
      <?php endwhile; ?>
    <?php else: ?>
      <p style="color:#ccc; text-align:center; grid-column:1/-1;">No plants added yet.</p>
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


<?php include 'footer.php'; ?>

</body>
</html>
