<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Log errors to a file in the same directory
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error_log.txt');
include 'db.php';
session_start();

if (isset($_POST['register'])) {
    $username   = trim($_POST['username']);
    $email      = trim($_POST['email']);
    $password   = $_POST['password'];
    $confirmPwd = $_POST['confirm_password'];

    // Validate inputs
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email address.";
    } elseif ($password !== $confirmPwd) {
        $error = "Passwords do not match.";
    } else {
        // Check duplicates
        $stmt = $conn->prepare("SELECT * FROM users WHERE username=? OR email=?");
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $error = "Username or Email already exists.";
        } else {
            $hashedPwd = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, 'user')");
            $stmt->bind_param("sss", $username, $email, $hashedPwd);

            if ($stmt->execute()) {
                $success = "Registration successful! <a href='login.php'>Login now</a>.";
            } else {
                $error = "Something went wrong. Try again.";
            }
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>AquaWiki - Register</title>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
  <link rel="icon" href="uploads/logo-16.png" sizes="16x16" type="image/png">
<link rel="icon" href="uploads/logo-32.png" sizes="32x32" type="image/png">
<link rel="icon" href="uploads/logo-48.png" sizes="48x48" type="image/png">
<link rel="icon" href="uploads/logo-512.png" sizes="512x512" type="image/png">
  <style>
    :root{
      --aqua-1: #00c6ff;
      --aqua-2: #0072ff;
      --card-bg: rgba(255,255,255,0.08);
      --card-border: rgba(255,255,255,0.12);
      --text-soft: rgba(255,255,255,0.92);
      --muted: rgba(255,255,255,0.75);
    }
    html,body{height:100%;margin:0;padding:0;font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;}
    body{
      min-height:100vh;
      display:flex;
      align-items:center;
      justify-content:center;
      background: url('uploads/bg2.jpg') center/cover no-repeat fixed;
      position:relative;
    }
    .overlay{
      position:fixed; inset:0;
      background: linear-gradient(180deg, rgba(2,30,45,0.55), rgba(2,40,60,0.60));
      z-index:1;
      pointer-events:none;
      backdrop-filter: blur(2px);
    }
    .card{
      position:relative;
      z-index:2;
      width: 420px;
      max-width:calc(100% - 40px);
      border-radius:14px;
      padding: 28px 34px;
      background: linear-gradient(180deg, rgba(255,255,255,0.06), rgba(255,255,255,0.04));
      border: 1px solid var(--card-border);
      box-shadow: 0 10px 30px rgba(2,20,30,0.45), inset 0 1px 0 rgba(255,255,255,0.02);
      backdrop-filter: blur(10px) saturate(120%);
      color: var(--text-soft);
      text-align:center;
    }
    .card .avatar{
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
    .subtitle{ margin:8px 0 20px;color:var(--muted);font-size:15px; }
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
    .input-box input::placeholder{ color: rgba(255,255,255,0.6); }
    .input-box input:focus{
      border-color: rgba(0,198,255,0.95);
      background: rgba(255,255,255,0.045);
      box-shadow: 0 6px 18px rgba(0,140,180,0.08);
    }
    .left-icon{
      position:absolute; left:12px; top:50%; transform: translateY(-50%);
      color: var(--aqua-1); font-size:16px; pointer-events:none;
    }
    .btn-login{
      margin-top:10px; width:100%; padding:12px;
      border-radius:10px; border: none;
      background: linear-gradient(90deg,var(--aqua-1),var(--aqua-2));
      color:#fff; font-weight:700; font-size:16px; cursor:pointer;
      box-shadow: 0 8px 20px rgba(0,110,160,0.14);
      transition: transform .12s ease, box-shadow .12s ease;
    }
    .btn-login:hover{ transform: translateY(-2px); box-shadow: 0 12px 28px rgba(0,110,160,0.18); }
    .register-line{ margin-top:14px; font-size:14px; color:var(--muted); }
    .register-line a{ color:var(--aqua-1); text-decoration:none; font-weight:600; }
    .register-line a:hover{ color:var(--aqua-2); text-decoration:underline; }
    .error{ color:#ff7b7b; font-weight:700; margin-bottom:12px; font-size:14px; }
    .success{ color:#00ffae; font-weight:700; margin-bottom:12px; font-size:14px; }
       /* smaller screens */
    @media (max-width:420px){
      .card{ padding:22px; width: min(80%, 420px); }
      .avatar{ width:56px;height:56px;font-size:26px; }
    }
  </style>
</head>
<body>
  <div class="overlay"></div>
  <div class="card" role="main" aria-labelledby="registerTitle">
    <div class="avatar" aria-hidden="true"><i class="fa fa-user-plus"></i></div>
    <div id="registerTitle" class="site-title">Create Account</div>
    <div class="subtitle">Join the AquaWiki Community</div>

    <?php if (!empty($error)) : ?>
      <div class="error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <?php if (!empty($success)) : ?>
      <div class="success"><?php echo $success; ?></div>
    <?php endif; ?>

    <form method="post" action="" autocomplete="off">
      <div class="input-box">
        <i class="fa fa-user left-icon"></i>
        <input name="username" type="text" placeholder="Username" required>
      </div>
      <div class="input-box">
        <i class="fa fa-envelope left-icon"></i>
        <input name="email" type="email" placeholder="Email" required>
      </div>
      <div class="input-box">
        <i class="fa fa-lock left-icon"></i>
        <input name="password" type="password" placeholder="Password" required>
      </div>
      <div class="input-box">
        <i class="fa fa-lock left-icon"></i>
        <input name="confirm_password" type="password" placeholder="Confirm Password" required>
      </div>
      <button type="submit" name="register" class="btn-login">Register</button>
    </form>

    <div class="register-line">
      Already have an account? <a href="login.php">Login</a>
    </div>
  </div>
</body>
</html>
