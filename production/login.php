<?php
// ============================================================
// LOGIN PAGE
// File: production/login.php
// ============================================================
session_start();
require_once('db.php');

if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$errorMsg = '';

if (isset($_POST['btnLogin'])) {
    $username = trim($_POST['txtUsername'] ?? '');
    $password = $_POST['txtPassword'] ?? '';

    if ($username === '' || $password === '') {
        $errorMsg = 'Please enter your username and password.';
    } else {
        $stmt = mysqli_prepare($conn, "SELECT id, full_name, password, role, status FROM users WHERE username = ?");
        mysqli_stmt_bind_param($stmt, 's', $username);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user   = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        if ($user && password_verify($password, $user['password'])) {
            if ($user['status'] !== 'active') {
                $errorMsg = 'Your account is deactivated. Contact the administrator.';
            } else {
                $_SESSION['user_id']   = $user['id'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['role']      = $user['role'];
                $_SESSION['username']  = $username;
                header('Location: dashboard.php');
                exit;
            }
        } else {
            $errorMsg = 'Incorrect username or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Sign In | Clinic Inventory System</title>
<link rel="icon" href="../images/favicon.svg" type="image/svg+xml">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" crossorigin href="../assets/main-v4-DB_ReeJG.css">
<script>(function(){try{var t=localStorage.getItem('theme');var d=window.matchMedia('(prefers-color-scheme: dark)').matches;var theme=t||(d?'dark':'light');document.documentElement.setAttribute('data-theme',theme);}catch(e){}})();</script>
</head>
<body>

<div class="auth-page">
  <div class="auth-card">

    <div class="auth-brand">
      <div class="brand-icon" style="background:var(--teal,#1ABB9C);color:#fff;display:flex;align-items:center;justify-content:center;">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M12 8v8M8 12h8"/></svg>
      </div>
      <div class="brand-name">Clinic IMS <small style="font-weight:400;color:var(--text-muted);font-size:12px;">Inventory System</small></div>
    </div>

    <div class="auth-title">Welcome back</div>
    <div class="auth-subtitle">Sign in to access the clinic inventory system.</div>

    <?php if ($errorMsg): ?>
      <div style="background:#fff0f0;color:#b91c1c;border-left:3px solid #e74c3c;padding:10px 14px;border-radius:6px;margin-bottom:14px;font-size:13.5px;">
        <?php echo htmlspecialchars($errorMsg); ?>
      </div>
    <?php endif; ?>

    <?php if (isset($_GET['logout'])): ?>
      <div style="background:#e6f9f0;color:#1a7a4a;border-left:3px solid #1ABB9C;padding:10px 14px;border-radius:6px;margin-bottom:14px;font-size:13.5px;">
        You have been logged out successfully.
      </div>
    <?php endif; ?>

    <form method="POST" action="login.php">
      <div class="form-group">
        <label class="form-label" for="username">Username</label>
        <div class="input-group">
          <svg class="input-icon" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M8 8a3 3 0 100-6 3 3 0 000 6z"/><path d="M2 14a6 6 0 0112 0"/></svg>
          <input type="text" id="username" name="txtUsername" class="form-control"
                 value="<?php echo htmlspecialchars($_POST['txtUsername'] ?? ''); ?>"
                 placeholder="Enter username" required autofocus>
        </div>
      </div>

      <div class="form-group">
        <label class="form-label" for="password">Password</label>
        <div class="input-group">
          <svg class="input-icon" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="7" width="10" height="7" rx="1.5"/><path d="M5 7V5a3 3 0 016 0v2"/></svg>
          <input type="password" id="password" name="txtPassword"
                 class="form-control" placeholder="••••••••" required>
        </div>
      </div>

      <button type="submit" name="btnLogin" class="btn btn-primary"
              style="width:100%;justify-content:center;height:40px;margin-top:8px;">
        Sign In
      </button>
    </form>

    <div style="margin-top:20px;text-align:center;font-size:12px;color:var(--text-muted);">
      Clinic Inventory Management System &copy; <?php echo date('Y'); ?>
    </div>
  </div>
</div>

<script type="module" src="../js/rolldown-runtime-_5RX-BWT.js"></script>
<script type="module" src="../js/main-v4-5cBbCq9P.js"></script>
</body>
</html>
