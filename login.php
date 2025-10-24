<?php
session_start();
include 'db.php';

$loginSuccess = false;

if (isset($_POST['login'])) {
    $loginInput = $_POST['username']; // can be username OR email
    $password = $_POST['password'];

    // ✅ allow login with either username OR email
    $sql = "SELECT * FROM users WHERE username = ? OR email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $loginInput, $loginInput);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        if (password_verify($password, $row['password'])) {
            $_SESSION['user'] = [
                'id' => $row['id'],
                'username' => $row['username']
            ];
            $_SESSION['role'] = $row['role'];

            $loginSuccess = true;
            $redirectTo = $row['role'] === 'admin' ? "admin.php" : "home.php";

            // ✅ redirect after login
            header("Location: $redirectTo");
            exit;
        } else {
            $error = "Invalid password.";
        }
    } else {
        $error = "User not found.";
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Aquatic Login</title>

  <!-- Font Awesome -->
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

  <style>
    :root{
      --aqua-1: #00c6ff;
      --aqua-2: #0072ff;
      --card-bg: rgba(255,255,255,0.08);
      --card-border: rgba(255,255,255,0.12);
      --text-soft: rgba(255,255,255,0.92);
      --muted: rgba(255,255,255,0.75);
    }

    /* layout & background */
    html,body{height:100%;margin:0;padding:0;font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;}
    body{
      min-height:100vh;
      display:flex;
      align-items:center;
      justify-content:center;
      background: url('uploads/bg2.jpg') center/cover no-repeat fixed;
      position:relative; /* needed so overlay positioning is clear */
    }

    /* fixed dim overlay so card reads well */
    .overlay{
      position:fixed;
      inset:0;
      background: linear-gradient(180deg, rgba(2,30,45,0.55), rgba(2,40,60,0.60));
      z-index:1;
      pointer-events:none;
      backdrop-filter: blur(2px);
    }

    /* card */
    .card {
  position:relative;
  z-index:2;
  width: 420px;
  max-width: calc(100% - 30px); /* shrink on small screens */
  border-radius:14px;
  padding: 28px 24px;
  background: linear-gradient(180deg, rgba(255,255,255,0.06), rgba(255,255,255,0.04));
  border: 1px solid var(--card-border);
  box-shadow:
    0 10px 30px rgba(2,20,30,0.45),
    inset 0 1px 0 rgba(255,255,255,0.02);
  backdrop-filter: blur(10px) saturate(120%);
  color: var(--text-soft);
  text-align:center;
}



    /* compact header */
    .card .avatar {
      width:64px;height:64px;border-radius:50%;
      display:inline-flex;align-items:center;justify-content:center;
      background: linear-gradient(135deg,var(--aqua-1),var(--aqua-2));
      color:#fff;font-size:30px;margin: 0 auto 10px;
      box-shadow: 0 6px 18px rgba(0,0,0,0.25);
    }

    .site-title{
      font-size:20px;font-weight:700;
      margin: 6px 0 4px;
      background: linear-gradient(90deg,var(--aqua-1),var(--aqua-2));
      -webkit-background-clip:text;-webkit-text-fill-color:transparent;
    }

    .subtitle{
      margin:8px 0 20px;color:var(--muted);font-size:15px;
    }

  .input-box {
  position: relative;
  margin-bottom: 14px;
  width: 100%; /* always fit inside card */
  box-sizing: border-box;
}

.input-box input {
  width: 100%;
  padding: 12px 44px; /* enough space left+right for icons */
  border-radius: 10px;
  background: rgba(255,255,255,0.03);
  border: 1px solid rgba(255,255,255,0.08);
  color: var(--text-soft);
  font-size: 15px;
  outline: none;
  transition: all .18s ease;
  box-sizing: border-box; /* keep padding inside */
  box-shadow: 0 1px 0 rgba(255,255,255,0.02) inset;
}

/* left icon */
.left-icon {
  position: absolute;
  left: 12px;
  top: 50%;
  transform: translateY(-50%);
  color: var(--aqua-1);
  font-size: 16px;
  pointer-events: none;
}

/* toggle eye on right (no fixed px anymore) */
.toggle-password {
  position: absolute;
  right: 12px;   /* stick to right side */
  top: 50%;
  transform: translateY(-50%);
  color: var(--aqua-1);
  font-size: 16px;
  cursor: pointer;
  transition: color .12s ease;
}
    .toggle-password:hover{ color: var(--aqua-2); }

    /* login button */
    .btn-login{
      margin-top:10px;
      width:100%;
      padding:12px;
      border-radius:10px;
      border: none;
      background: linear-gradient(90deg,var(--aqua-1),var(--aqua-2));
      color:#fff;
      font-weight:700;
      font-size:16px;
      cursor:pointer;
      box-shadow: 0 8px 20px rgba(0,110,160,0.14);
      transition: transform .12s ease, box-shadow .12s ease;
    }
    .btn-login:hover{ transform: translateY(-2px); box-shadow: 0 12px 28px rgba(0,110,160,0.18); }

    .register-line{ margin-top:14px; font-size:14px; color:var(--muted); }
    .register-line a{ color:var(--aqua-1); text-decoration:none; font-weight:600; }
    .register-line a:hover{ color:var(--aqua-2); text-decoration:underline; }

    /* error */
    .error{ color:#ff7b7b; font-weight:700; margin-bottom:12px; font-size:14px; }

    /* smaller screens */
    @media (max-width:420px){
      .card{ padding:22px; width: min(80%, 420px); }
      .avatar{ width:56px;height:56px;font-size:26px; }
    }
  </style>
</head>
<body>
  <div class="overlay"></div>

  <div class="card" role="main" aria-labelledby="loginTitle">
    <div class="avatar" aria-hidden="true"><i class="fa fa-user"></i></div>

    <div id="loginTitle" class="site-title">Aquatic Login</div>
    <div class="subtitle">Welcome Back</div>

    <?php if (!empty($error)) : ?>
      <div class="error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="post" action="" autocomplete="off">
      <div class="input-box">
        <i class="fa fa-user left-icon" aria-hidden="true"></i>
       <input name="username" type="text" placeholder="Username or Email" required autocomplete="username">
      </div>

      <div class="input-box">
        <i class="fa fa-lock left-icon" aria-hidden="true"></i>
        <input id="password" name="password" type="password" placeholder="Password" required autocomplete="current-password">
        <i class="fa fa-eye toggle-password" id="togglePwd" title="Show / hide password" onclick="togglePassword()"></i>
      </div>

      <button type="submit" name="login" class="btn-login">Login</button>
    </form>

    <div class="register-line">
      Don’t have an account? <a href="register.php">Register</a>
    </div>
  </div>

  <script>
    // eye toggle: also swap icons for clarity
    function togglePassword(){
      const p = document.getElementById('password');
      const t = document.getElementById('togglePwd');

      if (p.type === 'password'){
        p.type = 'text';
        t.classList.remove('fa-eye');
        t.classList.add('fa-eye-slash');
      } else {
        p.type = 'password';
        t.classList.remove('fa-eye-slash');
        t.classList.add('fa-eye');
      }
    }

    // ensure the toggle shows initial eye icon
    (function initToggleIcon(){
      const t = document.getElementById('togglePwd');
      if (t){
        t.classList.remove('fa-eye-slash');
        t.classList.add('fa-eye');
      }
    })();
  </script>
</body>
</html>
