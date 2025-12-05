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

//delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['notif_id']) && isset($_SESSION['user'])) {
    $notifId = intval($_POST['notif_id']);
    $userId = $_SESSION['user']['id'];

    $delStmt = $conn->prepare("DELETE FROM notifications WHERE id = ? AND user_id = ?");
    $delStmt->bind_param("ii", $notifId, $userId);
    $delStmt->execute();
    $delStmt->close();

    // Refresh to update list and prevent form resubmission
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

function timeAgo($datetime) {
    $timestamp = strtotime($datetime);
    $diff = time() - $timestamp;

    if ($diff < 60) return $diff . "s ago";
    $minutes = floor($diff / 60);
    if ($minutes < 60) return $minutes . "m ago";
    $hours = floor($minutes / 60);
    if ($hours < 24) return $hours . "h ago";
    $days = floor($hours / 24);
    if ($days < 7) return $days . "d ago";
    $weeks = floor($days / 7);
    if ($weeks < 4) return $weeks . "w ago";
    $months = floor($weeks / 4);
    if ($months < 12) return $months . "mo ago";
    $years = floor($months / 12);
    return $years . "y ago";
}

// If a notification is clicked
if (isset($_GET['open'])) {
    $notif_id = (int)$_GET['open'];

    $stmt = $conn->prepare("SELECT post_id FROM notifications WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $notif_id, $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $notif = $res->fetch_assoc();

    if ($notif) {
        // mark that notification as read
        $conn->query("UPDATE notifications SET is_read = 1 WHERE id = $notif_id");
        // redirect to the post
        header("Location: community.php?post=" . $notif['post_id']);
        exit;
    }
}

// Fetch all notifications (with post info + actor username)
$stmt = $conn->prepare("
    SELECT n.*, u.username, u.avatar, cp.title
    FROM notifications n
    LEFT JOIN users u ON n.actor_id = u.id
    LEFT JOIN community_posts cp ON n.post_id = cp.id
    WHERE n.user_id = ?
    ORDER BY n.created_at DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$conn->close();

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Notifications</title>
  <link rel="stylesheet" href="notification.css?v=<?= time() ?>">
  <link rel="icon" href="uploads/logo-16.png" sizes="16x16" type="image/png">
<link rel="icon" href="uploads/logo-32.png" sizes="32x32" type="image/png">
<link rel="icon" href="uploads/logo-48.png" sizes="48x48" type="image/png">
<link rel="icon" href="uploads/logo-512.png" sizes="512x512" type="image/png">
  <link href="https://fonts.googleapis.com/css?family=Roboto:400,700|Montserrat:400,700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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


<div class="notif-container">
  <h2>Notifications</h2>
  <?php if ($result->num_rows > 0): ?>
    <?php while($row = $result->fetch_assoc()): ?>
      <div class="notif <?= $row['is_read'] ? '' : 'unread' ?>">
     <img 
    src="assets/avatar/<?= htmlspecialchars($row['avatar']) ?>" 
     alt="<?= htmlspecialchars($row['username']) ?>" 
    class="notif-avatar"/>

        <div class="notif-body">
          <a href="community.php?post=<?= (int)$row['post_id'] ?>#post-<?= (int)$row['post_id'] ?>">
            <p>
              <strong><?= htmlspecialchars($row['username']) ?></strong>
              <?php if ($row['type'] === 'like'): ?>
                liked your post: <em><?= htmlspecialchars($row['title'] ?: 'Untitled post') ?></em>
              <?php elseif ($row['type'] === 'comment'): ?>
                commented on your post: <em><?= htmlspecialchars($row['title'] ?: 'Untitled post') ?></em>
              <?php elseif ($row['type'] === 'reply'): ?>
                replied to your comment on: <em><?= htmlspecialchars($row['title'] ?: 'your post') ?></em>
              <?php else: ?>
                did something
              <?php endif; ?>
            </p>
      <small><?= timeAgo($row['created_at']) ?></small>
          </a>
        </div>
        <!-- Delete notification button -->
       <form method="POST" class="notif-delete-form">
  <input type="hidden" name="notif_id" value="<?= (int)$row['id'] ?>">
  <button type="submit" class="notif-delete" title="Delete notification">
    <i class="fas fa-trash"></i>
  </button>
      </form>
      </div>
    <?php endwhile; ?>
  <?php else: ?>
    <p>No notifications yet.</p>
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
document.querySelectorAll('.notif-delete-form').forEach(form => {
  form.addEventListener('submit', e => {
    if (!confirm('Are you sure you want to delete this notification?')) {
      e.preventDefault();
    }
  });
});
</script>
<?php include 'footer.php'; ?>
</body>
</html>
