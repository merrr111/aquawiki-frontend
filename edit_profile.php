<?php
include 'db.php';
session_start();

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user']['id'];

$notifCount = 0; // prevent undefined

// ✅ Fetch unread notification count (fixed $userId → $user_id)
$notifQuery = $conn->prepare("
    SELECT COUNT(*) 
    FROM notifications 
    WHERE user_id = ? AND is_read = 0
");
$notifQuery->bind_param("i", $user_id);
$notifQuery->execute();
$notifQuery->bind_result($notifCount);
$notifQuery->fetch();
$notifQuery->close();

// Fetch current user info
$sql = "SELECT * FROM users WHERE id=?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = $_POST['full_name'];
    $bio = $_POST['bio'];
    $avatar = $user['avatar']; // default to current avatar

    // --- handle avatar selection from preset ---
    if (!empty($_POST['avatar'])) {
        $avatar = $_POST['avatar'];
    }

    // --- handle uploaded custom avatar ---
    if (!empty($_FILES['custom_avatar']['name'])) {
        $upload_dir = "assets/avatar/";
        $file_name = basename($_FILES['custom_avatar']['name']);
        $target_file = $upload_dir . uniqid("avatar_") . "_" . $file_name;
        $file_type = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        // Allow only image types
        $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (in_array($file_type, $allowed_types)) {
            if (move_uploaded_file($_FILES['custom_avatar']['tmp_name'], $target_file)) {
                $avatar = basename($target_file);
            }
        }
    }

    // Update user info
    $sql_update = "UPDATE users SET full_name=?, bio=?, avatar=? WHERE id=?";
    $stmt_up = $conn->prepare($sql_update);
    $stmt_up->bind_param("sssi", $full_name, $bio, $avatar, $user_id);
    $stmt_up->execute();

    // Update session
    $_SESSION['user']['avatar'] = $avatar;
    $_SESSION['user']['full_name'] = $full_name;
    $_SESSION['user']['bio'] = $bio;

    header("Location: profile.php");
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
 <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Edit Profile</title>
  <link rel="stylesheet" href="edit_profile.css?v=<?= time() ?>">
  <link href="https://fonts.googleapis.com/css?family=Roboto:400,700|Montserrat:400,700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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

<div class="profile-edit">
    <h2>Edit Profile</h2>
    <form method="post" enctype="multipart/form-data">
        <label>Full Name:</label>
        <input type="text" name="full_name" value="<?= htmlspecialchars($user['full_name']) ?>">

        <label>Bio:</label>
        <textarea name="bio"><?= htmlspecialchars($user['bio']) ?></textarea>

        <label>Choose Avatar:</label>
        <div class="avatar-list">
            <?php
           $avatars = glob("assets/avatar/*.{png,jpg,jpeg,webp,gif}", GLOB_BRACE);
foreach ($avatars as $a):
    $filename = basename($a);
?>
    <label>
        <input type="radio" name="avatar" value="<?= $filename ?>" <?= ($user['avatar'] === $filename) ? 'checked' : '' ?>>
        <img src="assets/avatar/<?= $filename ?>" class="avatar-choice">
    </label>
<?php endforeach; ?>
        </div>

        <div class="custom-upload">
            <label>Or Upload Your Own Avatar:</label>
            <input type="file" name="custom_avatar" accept="image/*">
        </div>

        <button type="submit">Save</button>
    </form>
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
