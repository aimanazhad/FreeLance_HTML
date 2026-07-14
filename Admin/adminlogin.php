<?php
require_once '../config.php';

// 1. Handle Logout if triggered
if (isset($_GET['logout']) && $_GET['logout'] == 1) {
    // Clear session variables securely
    $_SESSION = array();
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
    header("Location: adminlogin.php");
    exit;
}

// 2. If already logged in as admin, skip login page
if (isLoggedIn() && isAdmin()) {
    redirect('dashboard_admin.php');
}

$error = '';

// 3. Process Login Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['admin_login'])) {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($email) || empty($password)) {
        $error = 'Sila isi semua butiran.';
    } else {
        try {
            // Find the user by email
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
            $stmt->execute(['email' => $email]);
            $user = $stmt->fetch();

            // Verify password and check if they are actually an Admin
            if ($user && password_verify($password, $user['password'])) {
                if ($user['role'] === 'admin') {
                    // Set up session variables
                    $_SESSION['logged_in'] = true;
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_role'] = $user['role'];
                    $_SESSION['user_name'] = $user['name'];
                    $_SESSION['name'] = $user['name'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['role'] = $user['role'];
                    
                    redirect('dashboard_admin.php');
                } else {
                    $error = 'Akses ditolak. Anda tidak mempunyai kebenaran pentadbir.';
                }
            } else {
                $error = 'Kredensial admin tidak sah.';
            }
        } catch (PDOException $e) {
            $error = 'Ralat pangkalan data: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Portal - Freelance Marketplace</title>
  <link rel="stylesheet" href="../style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
  <div class="auth-page">
    <div class="auth-card">
      <div class="brand-panel">
        <div class="brand-identity">
          <div class="brand-mark"><span class="logo-f">FM</span></div>
          <div class="brand-text">
            <div class="title-main">Freelance Market</div>
            <div class="title-sub">Secure Admin Area</div>
          </div>
        </div>
        <p class="eyebrow">Management System</p>
        <h1>Admin Control Portal</h1>
        <p class="lead">Log in to your dashboard to monitor system data, manage platform users, audit pending transactions, and approve active project listings.</p>
      </div>

      <div class="form-panel">
        <div class="form-header">
          <p class="eyebrow" style="color: #ff5c5c;"><i class="fa-solid fa-lock"></i> Security Clearance</p>
          <h2 style="font-size: 1.8rem; font-weight: 800;">Verify credentials</h2>
          <p class="muted">Please provide your authorized official staff ID/email address and system passphrase.</p>
        </div>

        <?php if ($error): ?>
          <div style="margin-bottom: 12px; padding: 10px 12px; border-radius: 8px; background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca;"><?php echo escape($error); ?></div>
        <?php endif; ?>

        <form class="login-form" method="post" action="adminlogin.php">
          <input type="hidden" name="admin_login" value="1">
          <div class="input-group">
            <label for="admin-email">Official Admin Email / Staff ID</label>
            <input type="email" id="admin-email" name="email" placeholder="admin@freelance.uitm.edu.my" required autocomplete="email">
          </div>
          <div class="input-group">
            <label for="admin-password">Security Passphrase</label>
            <div class="password-wrapper">
              <i class="fa-solid fa-key input-icon"></i>
              <input type="password" id="admin-password" name="password" placeholder="Enter secure password" required autocomplete="current-password">
            </div>
          </div>
          <button type="submit" class="btn-submit" style="margin-top: 14px; background: linear-gradient(135deg, #ff416c, #ff4b2b); box-shadow: 0 14px 30px rgba(255, 75, 43, 0.25);">
            <i class="fa-solid fa-right-to-bracket"></i> &nbsp; Authenticate Portal
          </button>
        </form>

        <div class="login-redirect">
          Not an admin? <a href="../index.php">Return to User Login</a>
        </div>
      </div>
    </div>
  </div>
</body>
</html>

```