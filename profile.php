<?php
ob_start();
session_start();
include 'db.php';
error_reporting(0);

if (isset($_POST['delete_post'])) {
    if (!isset($_SESSION['user']['id'])) {
        echo "unauthorized";
        exit;
    }

    $post_id = (int)$_POST['post_id'];
    $uid = (int)$_SESSION['user']['id'];

    // Check if the post belongs to the logged-in user
    $check = $conn->prepare("SELECT id FROM community_posts WHERE id = ? AND user_id = ?");
    $check->bind_param("ii", $post_id, $uid);
    $check->execute();
    $res = $check->get_result();

    if ($res->num_rows > 0) {
        // 1️⃣ Delete related notifications
        $stmt1 = $conn->prepare("DELETE FROM notifications WHERE post_id = ?");
        $stmt1->bind_param("i", $post_id);
        $stmt1->execute();

        // 2️⃣ Delete related comments
        $stmt2 = $conn->prepare("DELETE FROM community_comments WHERE post_id = ?");
        $stmt2->bind_param("i", $post_id);
        $stmt2->execute();

        // 3️⃣ Delete related likes
        $stmt3 = $conn->prepare("DELETE FROM community_likes WHERE post_id = ?");
        $stmt3->bind_param("i", $post_id);
        $stmt3->execute();

        // 4️⃣ Finally, delete the post
        $del = $conn->prepare("DELETE FROM community_posts WHERE id = ?");
        $del->bind_param("i", $post_id);
        $del->execute();

        echo "deleted";
    } else {
        echo "unauthorized";
    }
    exit;
}


/* ✅ Handle inline post update request */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'], $_POST['title'], $_POST['body'])) {
    header('Content-Type: application/json');

    if (!isset($_SESSION['user'])) {
        echo json_encode(['success' => false, 'error' => 'Not logged in']);
        exit;
    }

    $user_id = $_SESSION['user']['id'];
    $post_id = (int)$_POST['id'];
    $title = trim($_POST['title']);
    $body = trim($_POST['body']);

    // ✅ Update only if post belongs to the logged-in user
    $stmt = $conn->prepare("UPDATE community_posts SET title = ?, body = ? WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ssii", $title, $body, $post_id, $user_id);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Unauthorized or failed update']);
    }
    exit;
}

/* ✅ Regular profile logic below */

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

// Default to logged-in user
$user_id = $_SESSION['user']['id'];

$notifCount = 0; // prevent undefined

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

// If ?user=ID is passed, use that instead
if (isset($_GET['user']) && is_numeric($_GET['user'])) {
    $user_id = (int)$_GET['user'];
}

// Fetch user info
$stmt = $conn->prepare("SELECT id, username, full_name, bio, avatar FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    echo "User not found.";
    exit;
}

// ✅ Determine avatar path (supports Cloudinary + local fallback)
if (!empty($user['avatar'])) {
    if (filter_var($user['avatar'], FILTER_VALIDATE_URL)) {
        // Cloudinary (or any remote URL)
        $avatar_path = $user['avatar'];
    } else {
        // Local avatar (old uploads)
        $local_avatar = "assets/avatar/" . $user['avatar'];
        $avatar_path = file_exists($local_avatar) ? $local_avatar : "assets/avatar/default.png";
    }
} else {
    $avatar_path = "assets/avatar/default.png";
}

// Fetch user's posts from community_posts (include like/comment counts + user liked)
$stmt2 = $conn->prepare("
    SELECT 
        p.id, 
        p.title, 
        p.body, 
        p.image_url, 
        p.created_at,
        (SELECT COUNT(*) FROM community_likes WHERE post_id = p.id) AS like_count,
        (SELECT COUNT(*) FROM community_comments WHERE post_id = p.id) AS comment_count,
        EXISTS(SELECT 1 FROM community_likes WHERE post_id = p.id AND user_id = ?) AS user_liked
    FROM community_posts p
    WHERE p.user_id = ?
    ORDER BY p.created_at DESC
");
$stmt2->bind_param("ii", $_SESSION['user']['id'], $user_id);
$stmt2->execute();
$posts = $stmt2->get_result();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($user['full_name'] ?: $user['username']) ?> — Profile</title>
    <link rel="stylesheet" href="profile.css?v=<?= time() ?>">
<link rel="icon" href="uploads/logo-16.png" sizes="16x16" type="image/png">
<link rel="icon" href="uploads/logo-32.png" sizes="32x32" type="image/png">
<link rel="icon" href="uploads/logo-48.png" sizes="48x48" type="image/png">
<link rel="icon" href="uploads/logo-512.png" sizes="512x512" type="image/png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <style>
      

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


<div class="wrap">
    <div class="profile-card">
        <img src="<?= htmlspecialchars($avatar_path) ?>" alt="Avatar" class="profile-avatar">
        <div class="profile-meta">
            <h1><?= htmlspecialchars($user['full_name'] ?: $user['username']) ?></h1>
            <?php if (!empty($user['bio'])): ?>
                <p><?= nl2br(htmlspecialchars($user['bio'])) ?></p>
            <?php else: ?>
                <p style="color:#888;">No bio yet. <a href="edit_profile.php" style="color:var(--accent,#00c2cb); text-decoration:underline;">Add one</a></p>
            <?php endif; ?>
           <?php if ($user['id'] == $_SESSION['user']['id']): ?>
    <a class="edit-link" href="edit_profile.php">Edit Profile</a>
<?php endif; ?>

        </div>
    </div>
<div class="posts">
  <h2 style="margin:18px 0 8px; color:#00c2cb;">Your Posts</h2>

  <?php if ($posts && $posts->num_rows > 0): ?>
    <?php while ($post = $posts->fetch_assoc()): ?>
      <div class="post" data-post-id="<?= (int)$post['id'] ?>">
        <div class="post-header">
          <img src="<?= htmlspecialchars($avatar_path) ?>" alt="avatar" class="post-avatar">
          <div class="post-user-info">
            <strong style="color:#fff;"><?= htmlspecialchars($user['full_name'] ?: $user['username']) ?></strong>
            <div style="font-size:12px; color:#9aa;"><?= htmlspecialchars($post['created_at']) ?></div>
          </div>

          <?php if ($post['id'] && $user['id'] == $_SESSION['user']['id']): ?>
          <div class="post-menu">
            <i class="fa-solid fa-ellipsis-vertical menu-toggle"></i>
            <div class="menu-dropdown">
              <a href="#" class="edit-inline" data-id="<?= $post['id'] ?>">
                <i class="fa-solid fa-pen-to-square"></i> Edit
              </a>
             <a href="#" class="delete-post" data-id="<?= $post['id'] ?>">
    <i class="fa-solid fa-trash"></i> Delete
</a>
            </div>
          </div>
          <?php endif; ?>
        </div>

        <div class="post-content">
          <?php if (!empty($post['title'])): ?>
            <h3 class="post-title"><?= htmlspecialchars($post['title']) ?></h3>
          <?php endif; ?>

          <?php if (!empty($post['body'])): ?>
            <p class="post-body"><?= nl2br(htmlspecialchars($post['body'])) ?></p>
          <?php endif; ?>

          <!-- Inline Edit Form -->
          <div class="edit-form" style="display:none; margin-top:8px;">
            <input type="text" class="edit-title" value="<?= htmlspecialchars($post['title']) ?>" placeholder="Title">
            <textarea class="edit-body" placeholder="Write something..."><?= htmlspecialchars($post['body']) ?></textarea>
            <div class="edit-actions" style="margin-top:5px;">
              <button class="save-edit" data-id="<?= $post['id'] ?>">Save</button>
              <button class="cancel-edit">Cancel</button>
            </div>
          </div>
        </div>

   <?php if (!empty($post['image_url'])): ?>
  <?php
    $image_url = $post['image_url'];
    if (!preg_match('/^https?:\/\//', $image_url)) {
    }
  ?>
  <img src="<?= htmlspecialchars($image_url) ?>" alt="Post image" style="margin-top:10px; border-radius:8px; max-width:100%;">
<?php endif; ?>


        <small style="color:#9aa;">Posted on <?= htmlspecialchars($post['created_at']) ?></small>

        <div class="post-actions">
          <a href="#" class="like-btn <?= $post['user_liked'] ? 'liked' : '' ?>" data-id="<?= (int)$post['id'] ?>">
            <i class="fa-regular fa-thumbs-up"></i>
            <span class="count">Like</span>
            <span class="count">(<span class="likes-count"><?= (int)$post['like_count'] ?></span>)</span>
          </a>

          <a href="#" class="comment-link" data-id="<?= (int)$post['id'] ?>">
            <i class="fa-regular fa-comment"></i>
            <span class="count">Comment</span>
            <span class="count">(<span class="comments-count"><?= (int)$post['comment_count'] ?></span>)</span>
          </a>
        </div>

        <div class="comment-section" id="comments-<?= (int)$post['id'] ?>"></div>

        <form method="post" class="comment-form" onsubmit="sendComment(event, <?= (int)$post['id'] ?>)">
          <input type="text" name="comment" placeholder="Write a comment..." autocomplete="off" required>
          <button type="submit">➤</button>
        </form>
      </div>
    <?php endwhile; ?>
  <?php else: ?>
    <div class="no-posts">You haven't posted anything yet.</div>
  <?php endif; ?>
    </div>
</div>
<?php if (isset($_SESSION['user'])): ?>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(function(){
  // 🔁 Function to fetch unread notification count
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

  // Auto-check every 5s
  setInterval(checkNotifications, 5000);
  checkNotifications();

  // 🔔 When clicking the bell icon
  $("#notifBtn").on("click", function(e){
    e.preventDefault(); // stop instant navigation

    $.post("mark_notifications_read.php", function(){
      $("#notifCount").fadeOut(300);

      // ⏳ small delay so fadeOut can finish before redirect
      setTimeout(() => {
        window.location.href = "notification.php";
      }, 300);
    });
  });
});
</script>
<?php endif; ?>

<script>
$(function(){
    // Toggle comments section (load via community.php?fetch_comments=postId)
    $(document).on('click', '.comment-link', function(e){
        e.preventDefault();
        let postId = $(this).data('id');
        let section = $('#comments-' + postId);
        if (section.is(':visible') && section.children().length) {
            section.slideToggle(150);
            return;
        }
        // load comments
        section.html('<div style="color:#9aa; padding:8px 0;">Loading comments...</div>');
        $.get('community.php', { fetch_comments: postId }, function(data){
            section.html(data).slideDown(150);
        });
    });

    // Like button (POST to community.php)
    // Like button toggle
$(document).on('click', '.like-btn', function(e){
    e.preventDefault();
    let btn = $(this);
    let postId = btn.data('id');

    $.post('community.php', { like_post: 1, post_id: postId }, function(response){
        response = response.trim();
        let countEl = btn.find('.likes-count');
        let current = parseInt(countEl.text()) || 0;

        if (response === "liked") {
            btn.addClass('liked');
            countEl.text(current + 1);
        } else if (response === "unliked") {
            btn.removeClass('liked');
            countEl.text(Math.max(0, current - 1));
        }
    }).fail(function(){
        alert('Could not like/unlike the post. Try again.');
    });
});

// Send comment via AJAX to community.php and reload comments
function sendComment(e, postId) {
    e.preventDefault();
    let form = $(e.target);
    let input = form.find("input[name='comment']");
    let comment = input.val().trim();
    if (!comment) return;
    $.post('community.php', { add_comment: 1, post_id: postId, comment: comment }, function(){
        // reload comments area
        $('#comments-' + postId).load('community.php?fetch_comments=' + postId);
        // clear input
        input.val('');
        // update comment count in UI
        let countEl = $('.post[data-post-id="'+postId+'"]').find('.comments-count');
        let cur = parseInt(countEl.text()) || 0;
        countEl.text(cur + 1);
    }).fail(function(){
        alert('Could not post comment. Try again.');
    });
}

// Send reply (used by reply forms returned from community.php fetch)
// community.php's reply form calls: sendReply(event, commentId, postId)
function sendReply(e, commentId, postId) {
    e.preventDefault();
    let form = $(e.target);
    let reply = form.find("input[name='reply']").val().trim();
    if (!reply) return;
    $.post('community.php', { add_reply: 1, comment_id: commentId, reply: reply }, function(){
        $('#comments-' + postId).load('community.php?fetch_comments=' + postId);
    }).fail(function(){
        alert('Could not post reply. Try again.');
    });
}
</script>

<script>
document.addEventListener("DOMContentLoaded", () => {
  // Toggle dropdown
  document.querySelectorAll(".menu-toggle").forEach(toggle => {
    toggle.addEventListener("click", e => {
      e.stopPropagation();
      const menu = toggle.nextElementSibling;
      menu.style.display = menu.style.display === "block" ? "none" : "block";
    });
  });

  // Hide dropdowns when clicking outside
  document.addEventListener("click", () => {
    document.querySelectorAll(".menu-dropdown").forEach(m => m.style.display = "none");
  });

  // Edit inline
  document.querySelectorAll(".edit-inline").forEach(btn => {
    btn.addEventListener("click", e => {
      e.preventDefault();
      const post = btn.closest(".post");
      const titleEl = post.querySelector(".post-title");
      const bodyEl  = post.querySelector(".post-body");
      const editForm = post.querySelector(".edit-form");
      if (titleEl) titleEl.style.display = "none";
      if (bodyEl) bodyEl.style.display = "none";
      if (editForm) editForm.style.display = "block";
      // focus first input for convenience
      const firstInput = editForm && editForm.querySelector(".edit-title");
      if (firstInput) firstInput.focus();
    });
  });

  // Cancel edit
  document.addEventListener("click", e => {
    const cancel = e.target.closest(".cancel-edit");
    if (!cancel) return;
    e.preventDefault();
    const post = cancel.closest(".post");
    const editForm = post.querySelector(".edit-form");
    const titleEl = post.querySelector(".post-title");
    const bodyEl  = post.querySelector(".post-body");
    if (editForm) editForm.style.display = "none";
    if (titleEl) titleEl.style.display = "";
    if (bodyEl) bodyEl.style.display = "";
  });

  // Save edit (event delegation)
  document.addEventListener("click", e => {
    const btn = e.target.closest(".save-edit");
    if (!btn) return; // ignore other clicks
    e.preventDefault();

    const post = btn.closest(".post");
    const id = btn.dataset.id;
    const title = post.querySelector(".edit-title").value;
    const body = post.querySelector(".edit-body").value;

    fetch("profile.php", {
      method: "POST",
      headers: {"Content-Type": "application/x-www-form-urlencoded"},
      body: `id=${encodeURIComponent(id)}&title=${encodeURIComponent(title)}&body=${encodeURIComponent(body)}`
    })
    .then(r => r.json())
    .then(data => {
      if (data && data.success) {
        const titleEl = post.querySelector(".post-title");
        const bodyEl  = post.querySelector(".post-body");
        if (titleEl) titleEl.textContent = title;
        if (bodyEl) bodyEl.innerHTML = body.replace(/\n/g, "<br>");
        const editForm = post.querySelector(".edit-form");
        if (editForm) editForm.style.display = "none";
        if (titleEl) titleEl.style.display = "";
        if (bodyEl) bodyEl.style.display = "";
      } else {
        alert("Failed to update post.");
      }
    })
    .catch(() => {
      alert("Failed to update post (network error).");
    });
  });
});
</script>

<script>
$(document).on('click', '.delete-post', function(e){
    e.preventDefault();
    let postId = $(this).data('id');
    if(!confirm("Are you sure you want to delete this post?")) return;
    $.post('profile.php', { delete_post: 1, post_id: postId }, function(resp){
        resp = resp.trim();
        if(resp === "deleted"){
            // Use data-post-id selector instead of #post-id
            $(".post[data-post-id='" + postId + "']").fadeOut();
        } else {
            alert("Failed to delete post. Try again.");
            console.log(resp);
        }
    });
});
</script>


<?php include 'footer.php'; ?>
</body>
</html>
