<?php
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
include 'db.php';
session_start();

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

$user_id = (int)$_SESSION['user']['id'];

function timeAgo($datetime) {
    $time = strtotime($datetime);
    $diff = time() - $time;

    if ($diff < 60) {
        return 'Just now';
    } elseif ($diff < 3600) {
        $mins = floor($diff / 60);
        return $mins . ' min' . ($mins > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 604800) {
        $days = floor($diff / 86400);
        return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 2592000) {
        $weeks = floor($diff / 604800);
        return $weeks . ' week' . ($weeks > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 31536000) {
        $months = floor($diff / 2592000);
        return $months . ' month' . ($months > 1 ? 's' : '') . ' ago';
    } else {
        $years = floor($diff / 31536000);
        return $years . ' year' . ($years > 1 ? 's' : '') . ' ago';
    }
}

function intValSafe($v){ return (int)$v; }

$focusPostId = isset($_GET['post']) ? (int)$_GET['post'] : null;

/* ---------- notification count (initial) ---------- */
$stmtNcount = $conn->prepare("SELECT COUNT(*) AS cnt FROM notifications WHERE user_id = ? AND is_read = 0");
$stmtNcount->bind_param("i", $user_id);
$stmtNcount->execute();
$notifCount = $stmtNcount->get_result()->fetch_assoc()['cnt'] ?? 0;
$stmtNcount->close();

//delete post
if (isset($_POST['delete_post'], $_SESSION['user']['id'])) {
    $post_id = (int)$_POST['post_id'];
    $uid = $_SESSION['user']['id'];

    $check = $conn->prepare("SELECT id FROM community_posts WHERE id = ? AND user_id = ?");
    $check->bind_param("ii", $post_id, $uid);
    $check->execute();
    $res = $check->get_result();

    if ($res->num_rows > 0) {
        $del = $conn->prepare("DELETE FROM community_posts WHERE id = ?");
        $del->bind_param("i", $post_id);
        $del->execute();
        echo "deleted";
    } else {
        echo "unauthorized";
    }
    exit;
}

//edit post
if (isset($_POST['edit_post'], $_SESSION['user']['id'])) {
    $post_id = (int)$_POST['post_id'];
    $uid = $_SESSION['user']['id'];
    $title = trim($_POST['title']);
    $body = trim($_POST['body']);

    $check = $conn->prepare("SELECT id FROM community_posts WHERE id = ? AND user_id = ?");
    $check->bind_param("ii", $post_id, $uid);
    $check->execute();
    $res = $check->get_result();

    if ($res->num_rows > 0) {
        $upd = $conn->prepare("UPDATE community_posts SET title = ?, body = ? WHERE id = ?");
        $upd->bind_param("ssi", $title, $body, $post_id);
        $upd->execute();
        echo "updated";
    } else {
        echo "unauthorized";
    }
    exit;
}


/* ---------- Handle new post ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_post'])) {
    $title = trim($_POST['title'] ?? '');
    $body  = trim($_POST['body'] ?? '');
    $image_url = null;

    if (!empty($_FILES['image']['name'])) {
        $targetDir = "uploads/community/";
        if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);

        $filename = time() . "_" . basename($_FILES['image']['name']);
        $targetFile = $targetDir . $filename;

        if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
            $image_url = $targetFile;
        }
    }

    $stmt = $conn->prepare("INSERT INTO community_posts (user_id, title, body, image_url) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $user_id, $title, $body, $image_url);
    $stmt->execute();
    $stmt->close();

    // redirect to avoid resubmission
    header("Location: community.php");
    exit;
}

/* ---------- Handle Like / Unlike Toggle ---------- */
if (isset($_POST['like_post'])) {
    $actor = $user_id;
    $post_id = intValSafe($_POST['post_id'] ?? 0);
    if ($actor && $post_id) {
        // Check if user already liked
        $stmt = $conn->prepare("SELECT 1 FROM community_likes WHERE user_id = ? AND post_id = ?");
        $stmt->bind_param("ii", $actor, $post_id);
        $stmt->execute();
        $alreadyLiked = $stmt->get_result()->num_rows > 0;
        $stmt->close();

        if ($alreadyLiked) {
            // Unlike → remove the like
            $stmt = $conn->prepare("DELETE FROM community_likes WHERE user_id = ? AND post_id = ?");
            $stmt->bind_param("ii", $actor, $post_id);
            $stmt->execute();
            $stmt->close();
            echo "unliked";
        } else {
            // Like → add record
            $stmt = $conn->prepare("INSERT INTO community_likes (user_id, post_id) VALUES (?, ?)");
            $stmt->bind_param("ii", $actor, $post_id);
            $stmt->execute();
            $stmt->close();
            echo "liked";

            // Notify post owner (unless actor is owner)
            $stmtU = $conn->prepare("SELECT user_id FROM community_posts WHERE id = ?");
            $stmtU->bind_param("i", $post_id);
            $stmtU->execute();
            $owner = $stmtU->get_result()->fetch_assoc();
            $stmtU->close();

            if ($owner && ((int)$owner['user_id']) !== $actor) {
                $stmtN = $conn->prepare("INSERT INTO notifications (user_id, actor_id, post_id, type, is_read, created_at)
                                         VALUES (?, ?, ?, 'like', 0, NOW())");
                $stmtN->bind_param("iii", $owner['user_id'], $actor, $post_id);
                $stmtN->execute();
                $stmtN->close();
            }
        }
    }
    exit;
}

/* ---------- Handle Add Comment ---------- */
if (isset($_POST['add_comment'])) {
    $actor = $user_id;
    $post_id = intValSafe($_POST['post_id'] ?? 0);
    $comment = trim($_POST['comment'] ?? '');
    if ($actor && $post_id && $comment !== '') {
        $stmt = $conn->prepare("INSERT INTO community_comments (post_id, user_id, comment) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $post_id, $actor, $comment);
        $stmt->execute();
        $stmt->close();

        // notify post owner (unless actor is owner)
        $stmtU = $conn->prepare("SELECT user_id FROM community_posts WHERE id = ?");
        $stmtU->bind_param("i", $post_id);
        $stmtU->execute();
        $owner = $stmtU->get_result()->fetch_assoc();
        $stmtU->close();

        // ✅ fix: skip notification if actor == owner
        if ($owner && ((int)$owner['user_id']) !== $actor) {
            $stmtN = $conn->prepare("INSERT INTO notifications (user_id, actor_id, post_id, type, is_read, created_at) VALUES (?, ?, ?, 'comment', 0, NOW())");
            $stmtN->bind_param("iii", $owner['user_id'], $actor, $post_id);
            $stmtN->execute();
            $stmtN->close();
        }
    }
    exit;
}

/* ---------- Handle Add Reply ---------- */
if (isset($_POST['add_reply'])) {
    $actor = $user_id;
    $comment_id = intValSafe($_POST['comment_id'] ?? 0);
    $reply = trim($_POST['reply'] ?? '');
    if ($actor && $comment_id && $reply !== '') {
        $stmt = $conn->prepare("INSERT INTO community_replies (comment_id, user_id, reply) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $comment_id, $actor, $reply);
        $stmt->execute();
        $stmt->close();

        // find the post owner (via the comment -> post)
       $stmtU = $conn->prepare("
    SELECT c.user_id AS comment_owner, c.post_id
    FROM community_comments c
    WHERE c.id = ?
");
$stmtU->bind_param("i", $comment_id);
$stmtU->execute();
$owner = $stmtU->get_result()->fetch_assoc();
$stmtU->close();

if ($owner && ((int)$owner['comment_owner']) !== $actor) {
    $stmtN = $conn->prepare("
        INSERT INTO notifications (user_id, actor_id, post_id, type, is_read, created_at)
        VALUES (?, ?, ?, 'reply', 0, NOW())
    ");
    $stmtN->bind_param("iii", $owner['comment_owner'], $actor, $owner['post_id']);
    $stmtN->execute();
    $stmtN->close();
}

    }
    exit;
}

/* ---------- Fetch Comments (AJAX) ---------- */
if (isset($_GET['fetch_comments'])) {
    $post_id = intValSafe($_GET['fetch_comments']);
    $stmt = $conn->prepare("
        SELECT c.id, c.comment, c.created_at, c.user_id,
               u.username, u.full_name, u.avatar
        FROM community_comments c
        JOIN users u ON c.user_id = u.id
        WHERE c.post_id = ?
        ORDER BY c.created_at ASC
    ");
    $stmt->bind_param("i", $post_id);
    $stmt->execute();
    $comments = $stmt->get_result();

    if ($comments->num_rows > 0) {
        while ($comment = $comments->fetch_assoc()) {
            $avatar = !empty($comment['avatar']) ? "assets/avatar/" . htmlspecialchars($comment['avatar']) : "assets/avatar/default.png";
            ?>
            <div class="comment">
                <a href="profile.php?user=<?= (int)$comment['user_id'] ?>">
                    <img src="<?= $avatar ?>" alt="avatar" class="post-avatar">
                </a>
                <div class="comment-bubble">
                    <a href="profile.php?user=<?= (int)$comment['user_id'] ?>" style="font-weight:bold; text-decoration:none;">
                        <?= htmlspecialchars($comment['full_name'] ?: $comment['username']) ?>
                    </a><br>
                    <?= nl2br(htmlspecialchars($comment['comment'])) ?>
                   <div style="font-size:12px; color:#555; margin-top:2px;" title="<?= htmlspecialchars($comment['created_at']) ?>">
    <?= timeAgo($comment['created_at']) ?>
</div>
                </div>
            </div>

            <?php
            // fetch replies for this comment
            $stmtR = $conn->prepare("
                SELECT r.id, r.reply, r.created_at, r.user_id,
                       u.username, u.full_name, u.avatar
                FROM community_replies r
                JOIN users u ON r.user_id = u.id
                WHERE r.comment_id = ?
                ORDER BY r.created_at ASC
            ");
            $stmtR->bind_param("i", $comment['id']);
            $stmtR->execute();
            $replies = $stmtR->get_result();

            $replyCount = $replies->num_rows;
            ?>

            <!-- ✅ View Replies Toggle -->
            <?php if ($replyCount > 0): ?>
                <div class="view-replies-toggle" 
                     style="margin-left:50px; font-size:13px; color:#007bff; cursor:pointer;"
                     onclick="toggleReplies(<?= $comment['id'] ?>)">
                    View Replies (<?= $replyCount ?>)
                </div>
            <?php endif; ?>

            <!-- ✅ Replies container (hidden by default) -->
            <div class="reply-section" id="replies-<?= $comment['id'] ?>" style="display:none;">
                <?php
                while ($reply = $replies->fetch_assoc()) {
                    $ravatar = !empty($reply['avatar']) ? "assets/avatar/" . htmlspecialchars($reply['avatar']) : "assets/avatar/default.png";
                    ?>
                    <div class="reply">
                        <a href="profile.php?user=<?= (int)$reply['user_id'] ?>">
                            <img src="<?= $ravatar ?>" alt="avatar" class="post-avatar" style="width:30px; height:30px; margin-left:40px;">
                        </a>
                        <div class="reply-bubble">
                            <a href="profile.php?user=<?= (int)$reply['user_id'] ?>" style="font-weight:bold; text-decoration:none;">
                                <?= htmlspecialchars($reply['full_name'] ?: $reply['username']) ?>
                            </a><br>
                            <?= nl2br(htmlspecialchars($reply['reply'])) ?>
                         <div style="font-size:11px; color:#555; margin-top:2px;" title="<?= htmlspecialchars($reply['created_at']) ?>">
    <?= timeAgo($reply['created_at']) ?>
</div>
                        </div>
                    </div>
                    <?php
                }
                ?>
            </div>

            <!-- reply form -->
            <form class="reply-form" onsubmit="sendReply(event, <?= (int)$comment['id'] ?>, <?= $post_id ?>)">
                <input type="text" name="reply" placeholder="Write a reply..." autocomplete="off" required>
                <button type="submit">➤</button>
            </form>
            <?php
        }
    } else {
        echo "<div style='color:#777; padding:6px;'>No comments yet.</div>";
    }
    exit;
}

/* ---------- Fetch notification count (AJAX) ---------- */
if (isset($_GET['fetch_notifications'])) {
    $stmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    echo (int)$result['cnt'];
    exit;
}

/* ---------- Fetch like count (AJAX) ---------- */
if (isset($_GET['fetch_like_count'])) {
    $post_id = intValSafe($_GET['fetch_like_count']);
    $res = $conn->query("SELECT COUNT(*) AS cnt FROM community_likes WHERE post_id = " . $post_id)->fetch_assoc()['cnt'];
    echo (int)$res;
    exit;
}

/* ---------- Fetch comment count (AJAX) ---------- */
if (isset($_GET['fetch_comment_count'])) {
    $post_id = intValSafe($_GET['fetch_comment_count']);
    $res = $conn->query("SELECT COUNT(*) AS cnt FROM community_comments WHERE post_id = " . $post_id)->fetch_assoc()['cnt'];
    echo (int)$res;
    exit;
}

$posts = $conn->query("
    SELECT p.*, u.id AS user_id, u.username, u.full_name, u.avatar,
    (SELECT COUNT(*) FROM community_likes WHERE post_id = p.id) as like_count,
    (SELECT COUNT(*) FROM community_comments WHERE post_id = p.id) as comment_count,
    (SELECT COUNT(*) FROM community_likes WHERE post_id = p.id AND user_id = {$user_id}) as liked_by_me
    FROM community_posts p
    JOIN users u ON p.user_id = u.id
    ORDER BY p.created_at DESC
");

$conn->close();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Community — AquaWiki</title>
    <link rel="stylesheet" href="community.css?v=<?= time() ?>">
    <link rel="icon" href="uploads/logo-16.png" sizes="16x16" type="image/png">
    <link rel="icon" href="uploads/logo-32.png" sizes="32x32" type="image/png">
    <link rel="icon" href="uploads/logo-48.png" sizes="48x48" type="image/png">
    <link rel="icon" href="uploads/logo-512.png" sizes="512x512" type="image/png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
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

    <!-- POST FORM -->
    <div class="post-form">
        <form method="post" enctype="multipart/form-data">
            <input type="text" name="title" placeholder="Post title (optional)">
            <textarea name="body" rows="3" placeholder="Share something..."></textarea>
            <input type="file" name="image">
            <button type="submit" name="new_post">Post</button>
        </form>
    </div>

    <!-- POSTS FEED -->
    <div class="feed">
        <?php while ($post = $posts->fetch_assoc()):
            $pavatar = !empty($post['avatar']) ? "assets/avatar/" . htmlspecialchars($post['avatar']) : "assets/avatar/default.png";
        ?>
       <div class="post" id="post-<?= (int)$post['id'] ?>" data-post-id="<?= (int)$post['id'] ?>">
            <div class="post-header">
                <a href="profile.php?user=<?= (int)$post['user_id'] ?>">
                    <img src="<?= $pavatar ?>" alt="avatar" class="post-avatar">
                </a>
                <div>
                    <a href="profile.php?user=<?= (int)$post['user_id'] ?>" style="text-decoration:none; font-weight:bold;">
                        <?= htmlspecialchars($post['full_name'] ?: $post['username']) ?>
                    </a>
                <div style="font-size:12px; color:#555;" title="<?= htmlspecialchars($post['created_at']) ?>">
    <?= timeAgo($post['created_at']) ?>
</div>
                </div>
            </div>

            <?php if (!empty($post['title'])): ?>
                <h3><?= htmlspecialchars($post['title']) ?></h3>
            <?php endif; ?>

            <?php if (!empty($post['body'])): ?>
                <p><?= nl2br(htmlspecialchars($post['body'])) ?></p>
            <?php endif; ?>

            <?php if (!empty($post['image_url'])): ?>
                <img src="<?= htmlspecialchars($post['image_url']) ?>" alt="Post image">
            <?php endif; ?>

            <div class="post-actions">
               <a href="#" class="like-btn <?= $post['liked_by_me'] ? 'liked' : '' ?>" 
   data-id="<?= (int)$post['id'] ?>">
    <i class="fa-regular fa-thumbs-up"></i>
    Like (<span class="likes-count"><?= (int)$post['like_count'] ?></span>)
</a>
                <a href="#" class="comment-link" data-id="<?= (int)$post['id'] ?>">
                    <i class="fa-regular fa-comment"></i>
                    Comment (<span class="comments-count"><?= (int)$post['comment_count'] ?></span>)
                </a>
            </div>

            <!-- Comment Section -->
            <div class="comment-section" id="comments-<?= (int)$post['id'] ?>"></div>

            <!-- Comment Form -->
            <form method="post" class="comment-form" onsubmit="sendComment(event, <?= (int)$post['id'] ?>)">
                <input type="text" name="comment" placeholder="Write a comment..." autocomplete="off" required>
                <button type="submit">➤</button>
            </form>
        </div>
        <?php endwhile; ?>
    </div>

<script>
$(function(){
    // Toggle comments
    $(document).on('click', '.comment-link', function(e){
        e.preventDefault();
        let postId = $(this).data('id');
        let section = $('#comments-' + postId);
        if (section.is(':visible') && section.children().length) {
            section.slideToggle(150);
            return;
        }
        section.html('<div style="color:#777; padding:8px;">Loading comments...</div>');
        $.get('community.php', { fetch_comments: postId }, function(data){
            section.html(data).slideDown(150);
        });
    });

    // Like - send request then refresh like count from server
    $(document).on('click', '.like-btn', function(e){
    e.preventDefault();
    let btn = $(this), postId = btn.data('id');
    btn.prop('disabled', true);

    $.post('community.php', { like_post: 1, post_id: postId }, function(response){
        // Toggle class depending on action
        if (response.trim() === "liked") {
            btn.addClass('liked');
        } else if (response.trim() === "unliked") {
            btn.removeClass('liked');
        }

        // Update like count
        $.get('community.php', { fetch_like_count: postId }, function(count){
            btn.find('.likes-count').text(count);
            btn.prop('disabled', false);
        });
    }).fail(function(){
        btn.prop('disabled', false);
    });
});

    // Poll notifications every 5s
    function checkNotifications() {
        $.get('community.php', { fetch_notifications: 1 }, function(count){
            let c = parseInt(count) || 0;
            if (c > 0) {
                $("#notifCount").text(c).show();
            } else {
                $("#notifCount").hide();
            }
        });
    }
    setInterval(checkNotifications, 5000);
    checkNotifications();

    // 🔹 Highlight post if opened from notification
    const urlParams = new URLSearchParams(window.location.search);
    const focusPost = urlParams.get("post");
    if (focusPost) {
        let target = $("#post-" + focusPost);
        if (target.length) {
            $('html, body').animate({
                scrollTop: target.offset().top - 100
            }, 600);
            target.css("background", "#fff3cd"); // highlight yellow
            setTimeout(() => target.css("background", ""), 3000);
        }
    }
});

// Send comment
function sendComment(e, postId) {
    e.preventDefault();
    let form = $(e.target);
    let input = form.find("input[name='comment']");
    let comment = input.val().trim();
    if (!comment) return;
    $.post('community.php', { add_comment: 1, post_id: postId, comment: comment }, function(){
        $('#comments-' + postId).load('community.php?fetch_comments=' + postId);
        input.val('');
        $.get('community.php', { fetch_comment_count: postId }, function(cnt){
            $('.post[data-post-id="'+postId+'"]').find('.comments-count').text(cnt);
        });
    }).fail(function(){
        alert('Could not post comment. Try again.');
    });
}

// Send reply
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
$(function(){
  // When user clicks bell icon, mark notifications as read
  $("#notifBtn").on("click", function(e){
    e.preventDefault();
    $.post("mark_notifications_read.php", function(){
      $("#notifCount").fadeOut(300);
    });
    // Optionally navigate to notifications page
    window.location.href = "notification.php";
  });
});
</script>

<script>
function toggleReplies(commentId) {
    const section = $("#replies-" + commentId);
    const toggle = $(".view-replies-toggle[data-id='" + commentId + "']");

    if (section.is(":visible")) {
        section.slideUp(200);
        toggle.text(toggle.text().replace("Hide", "View"));
    } else {
        section.slideDown(200);
        toggle.text(toggle.text().replace("View", "Hide"));
    }
}
</script>

<?php include 'footer.php'; ?>

</body>
</html>
