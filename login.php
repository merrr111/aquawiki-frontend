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
  <link rel="icon" href="uploads/logo-16.png" sizes="16x16" type="image/png">
  <link rel="icon" href="uploads/logo-32.png" sizes="32x32" type="image/png">
  <link rel="icon" href="uploads/logo-48.png" sizes="48x48" type="image/png">
  <link rel="icon" href="uploads/logo-512.png" sizes="512x512" type="image/png">
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
html, body {
  height: 100%;
  margin: 0;
  padding: 0;
  font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
}

body {
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: 100vh;
  background: url('uploads/bg2.jpg') center/cover no-repeat fixed;
  position: relative;
}

.overlay {
  position: fixed;
  inset: 0;
  background: linear-gradient(180deg, rgba(2,30,45,0.65), rgba(2,40,60,0.70));
  z-index: 1;
  pointer-events: none;
  backdrop-filter: blur(2px);
}

/* card */
.card {
  position: relative;
  z-index: 2;
  width: 90%;
  max-width: 420px;
  background: linear-gradient(180deg, rgba(255,255,255,0.06), rgba(255,255,255,0.04));
  margin: 0 auto;
  padding: 28px 24px;
  border-radius: 14px;
  border: 1px solid var(--card-border);
  box-shadow: 0 10px 30px rgba(2,20,30,0.45), inset 0 1px 0 rgba(255,255,255,0.02);
  backdrop-filter: blur(10px) saturate(120%);
  color: var(--text-soft);
  text-align: center;
  box-sizing: border-box;
}

/* avatar */
.card .avatar {
  width: 64px;
  height: 64px;
  border-radius: 50%;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, var(--aqua-1), var(--aqua-2));
  color: #fff;
  font-size: 30px;
  margin: 0 auto 10px;
  box-shadow: 0 6px 18px rgba(0,0,0,0.25);
}

/* titles */
.site-title {
  font-size: 20px;
  font-weight: 700;
  margin: 6px 0 4px;
  background: linear-gradient(90deg, var(--aqua-1), var(--aqua-2));
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
}

.subtitle {
  margin: 8px 0 20px;
  color: var(--muted);
  font-size: 15px;
}

/* input boxes */
.input-box {
  position: relative;
  margin-bottom: 14px;
  width: 100%;
  box-sizing: border-box;
}

.input-box input {
  width: 100%;
  padding: 12px 44px;
  border-radius: 10px;
  background: rgba(255,255,255,0.03);
  border: 1px solid rgba(255,255,255,0.08);
  color: var(--text-soft);
  font-size: 15px;
  outline: none;
  transition: all .18s ease;
  box-sizing: border-box;
  box-shadow: 0 1px 0 rgba(255,255,255,0.02) inset;
}

.input-box input::placeholder {
  color: rgba(255,255,255,0.6);
}

.input-box input:focus {
  border-color: rgba(0,198,255,0.95);
  background: rgba(255,255,255,0.045);
  box-shadow: 0 6px 18px rgba(0,140,180,0.08);
}

/* icons */
.left-icon {
  position: absolute;
  left: 12px;
  top: 50%;
  transform: translateY(-50%);
  color: var(--aqua-1);
  font-size: 16px;
  pointer-events: none;
}

.toggle-password {
  position: absolute;
  right: 12px;
  top: 50%;
  transform: translateY(-50%);
  color: var(--aqua-1);
  font-size: 16px;
  cursor: pointer;
  transition: color .12s ease;
}

.toggle-password:hover {
  color: var(--aqua-2);
}

/* login buttons */
.btn-login {
  margin-top: 10px;
  width: 100%;
  padding: 12px;
  border-radius: 10px;
  border: none;
  background: linear-gradient(90deg, var(--aqua-1), var(--aqua-2));
  color: #fff;
  font-weight: 700;
  font-size: 16px;
  cursor: pointer;
  box-shadow: 0 8px 20px rgba(0,110,160,0.14);
  transition: transform .12s ease, box-shadow .12s ease;
}

.btn-login:hover {
  transform: translateY(-2px);
  box-shadow: 0 12px 28px rgba(0,110,160,0.18);
}

/* register link */
.register-line {
  margin-top: 14px;
  font-size: 14px;
  color: var(--muted);
}

.register-line a {
  color: var(--aqua-1);
  text-decoration: none;
  font-weight: 600;
}

.register-line a:hover {
  color: var(--aqua-2);
  text-decoration: underline;
}

/* error */
.error {
  color: #ff7b7b;
  font-weight: 700;
  margin-bottom: 12px;
  font-size: 14px;
}

/* toggle buttons at top */
.login-toggle {
  display: flex;
  justify-content: center;
  gap: 10px;
  margin-bottom: 20px;
}

.login-toggle .btn-login {
  flex: 1;
  width: auto;
  padding: 10px;
  font-size: 14px;
}

/* responsive */
@media (max-width: 420px) {
  .card {
    padding: 22px;
    width: 92%;
  }
  .avatar {
    width: 56px;
    height: 56px;
    font-size: 26px;
  }
}
  </style>
</head>
<body>
<div class="overlay"></div>
<div class="card" role="main" aria-labelledby="loginTitle">
  <div class="avatar"><i class="fa fa-user"></i></div>
  <div id="loginTitle" class="site-title">Aquatic Login</div>
  <div class="subtitle">Welcome Back</div>

  <?php if (!empty($error)) : ?>
    <div class="error"><?php echo htmlspecialchars($error); ?></div>
  <?php endif; ?>

  <!-- Toggle Buttons at Top -->
  <div class="login-toggle">
    <button type="button" id="modePasswordBtn" class="btn-login">Login with Password</button>
    <button type="button" id="modeCodeBtn" class="btn-login">Login with Code</button>
  </div>

  <!-- Code Login Form -->
  <form id="loginCodeForm" style="display:none;" autocomplete="off">
    <div class="input-box">
      <i class="fa fa-envelope left-icon"></i>
      <input type="email" name="email" placeholder="Enter your email" required>
    </div>
    <button type="button" id="getCodeBtn" class="btn-login">Get Code</button>

    <div id="codeInputContainer" style="display:none; margin-top:12px;">
      <div class="input-box">
        <i class="fa fa-key left-icon"></i>
        <input type="text" name="code" placeholder="Enter code" required>
      </div>
      <button type="submit" class="btn-login">Login with Code</button>
    </div>

    <p id="codeMsg" style="color:#00c2cb; margin-top:8px;"></p>
  </form>

  <!-- Password Login Form -->
  <form method="post" action="" autocomplete="off" id="loginPasswordForm">
    <div class="input-box">
      <i class="fa fa-user left-icon"></i>
      <input name="username" type="text" placeholder="Username or Email" required autocomplete="username">
    </div>
    <div class="input-box">
      <i class="fa fa-lock left-icon"></i>
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
function togglePassword(){
  const p = document.getElementById('password');
  const t = document.getElementById('togglePwd');
  if(p.type === 'password'){ p.type='text'; t.classList.replace('fa-eye','fa-eye-slash'); }
  else { p.type='password'; t.classList.replace('fa-eye-slash','fa-eye'); }
}
(function(){ const t = document.getElementById('togglePwd'); if(t){ t.classList.replace('fa-eye-slash','fa-eye'); }})();

const modeCodeBtn = document.getElementById('modeCodeBtn');
const modePasswordBtn = document.getElementById('modePasswordBtn');
const loginPasswordForm = document.getElementById('loginPasswordForm');
const loginCodeForm = document.getElementById('loginCodeForm');
const codeInputContainer = document.getElementById('codeInputContainer');
const codeMsg = document.getElementById('codeMsg');
const getCodeBtn = document.getElementById('getCodeBtn');

// Toggle forms
modeCodeBtn.addEventListener('click', () => { 
  loginCodeForm.style.display='block'; 
  loginPasswordForm.style.display='none'; 
});
modePasswordBtn.addEventListener('click', () => { 
  loginCodeForm.style.display='none'; 
  loginPasswordForm.style.display='block'; 
});

// Get Code
getCodeBtn.addEventListener('click', () => {
  const email = loginCodeForm.email.value;
  if(!email) return alert('Enter your email.');

  // Show code input and message immediately
  codeInputContainer.style.display = 'block';
  codeMsg.style.color = '#00c2cb';
  codeMsg.textContent = 'Code sent! Check your email.';
  
  // Disable Get Code button to prevent multiple clicks
  getCodeBtn.disabled = true;
  getCodeBtn.style.opacity = 0.6;
  getCodeBtn.style.cursor = 'not-allowed';

  // Send code in background
  fetch('send_login_code.php', {
    method:'POST', 
    headers:{'Content-Type':'application/x-www-form-urlencoded'},
    body:`email=${encodeURIComponent(email)}`
  })
  .then(res => res.text())
  .then(data => {
    if(data.trim() !== 'success'){
      // If error, re-enable button and show error
      getCodeBtn.disabled = false;
      getCodeBtn.style.opacity = 1;
      getCodeBtn.style.cursor = 'pointer';
      codeMsg.style.color = '#ff7b7b';
      codeMsg.textContent = 'Error sending code. Try again.';
    }
  })
  .catch(err => {
    getCodeBtn.disabled = false;
    getCodeBtn.style.opacity = 1;
    getCodeBtn.style.cursor = 'pointer';
    codeMsg.style.color = '#ff7b7b';
    codeMsg.textContent = 'Network error. Try again.';
  });
});

// Submit Code Login
loginCodeForm.addEventListener('submit', e=>{
  e.preventDefault();
  const email = loginCodeForm.email.value;
  const code = loginCodeForm.code.value;

  fetch('verify_login_code.php', {
    method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'},
    body:`email=${encodeURIComponent(email)}&code=${encodeURIComponent(code)}`
  })
  .then(res => res.text())
  .then(data => {
    if(data.trim() === 'success'){ 
      window.location.href='home.php'; 
    } else {
      codeMsg.style.color='#ff7b7b';
      codeMsg.textContent='Invalid code.';
      loginCodeForm.code.value='';
    }
  });
});
</script>

</body>
</html>
