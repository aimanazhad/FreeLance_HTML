<?php
require_once '../config.php';

// 1. Handle Logout if triggered
if (isset($_GET['logout']) && $_GET['logout'] == 1) {
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

// 2. If request explicitly forces admin login, clear any existing admin session first
if (isset($_GET['force_login']) && $_GET['force_login'] == '1') {
    unset($_SESSION['admin_logged_in'], $_SESSION['admin_id'], $_SESSION['admin_username'], $_SESSION['admin_email'], $_SESSION['admin_full_name'], $_SESSION['admin_role']);
}

// 3. If already logged in as admin, skip login page
if (isAdminLoggedIn()) {
    redirect('dashboard_admin.php');
    exit;
}

$error = '';

// 3. Process Login Form Submission - GUNA FUNGSI DARI CONFIG
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['admin_login'])) {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($email) || empty($password)) {
        $error = 'Sila isi semua butiran.';
    } else {
        // GUNA FUNGSI loginAdmin() dari config.php
        $admin = loginAdmin($email, $password);
        
        if ($admin) {
            // Set session variables for admin
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_username'] = $admin['username'];
            $_SESSION['admin_email'] = $admin['email'];
            $_SESSION['admin_full_name'] = $admin['full_name'];
            $_SESSION['admin_role'] = $admin['role'];
            
            // Update last login - GUNA FUNGSI dari config.php
            updateAdminLastLogin($admin['id']);
            
            // Redirect ke dashboard admin
            header("Location: dashboard_admin.php");
            exit;
        } else {
            $error = 'Kredensial admin tidak sah.';
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #0b0f19;
            padding: 20px;
        }
        .auth-page { width: 100%; max-width: 960px; }
        .auth-card {
            display: grid;
            grid-template-columns: 1fr 1fr;
            background: #141b2b;
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 25px 60px rgba(0,0,0,0.5);
            border: 1px solid #2a2f3e;
        }
        .brand-panel {
            background: linear-gradient(145deg, #1a2236, #0f1625);
            padding: 48px 36px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            border-right: 1px solid #2a2f3e;
        }
        .brand-identity {
            display: flex;
            align-items: center;
            gap: 14px;
            margin-bottom: 28px;
        }
        .brand-mark {
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            width: 52px;
            height: 52px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 18px;
            color: white;
            flex-shrink: 0;
        }
        .brand-text .title-main {
            font-size: 20px;
            font-weight: 700;
            color: white;
            letter-spacing: -0.3px;
        }
        .brand-text .title-sub {
            font-size: 12px;
            color: #8b8fa8;
            letter-spacing: 0.3px;
            text-transform: uppercase;
        }
        .brand-panel .eyebrow {
            font-size: 12px;
            font-weight: 700;
            color: #6366f1;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            margin-bottom: 12px;
        }
        .brand-panel h1 {
            font-size: 32px;
            font-weight: 800;
            color: white;
            line-height: 1.2;
            margin-bottom: 14px;
        }
        .brand-panel .lead {
            font-size: 15px;
            color: #a0a8be;
            line-height: 1.7;
        }
        .form-panel {
            padding: 48px 36px;
            background: #141b2b;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .form-header {
            margin-bottom: 28px;
        }
        .form-header .eyebrow {
            font-size: 12px;
            font-weight: 700;
            color: #ff5c5c;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            margin-bottom: 8px;
        }
        .form-header h2 {
            font-size: 28px;
            font-weight: 800;
            color: white;
            margin-bottom: 6px;
        }
        .form-header .muted {
            font-size: 14px;
            color: #8b8fa8;
        }
        .input-group {
            margin-bottom: 18px;
        }
        .input-group label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #c8cee0;
            margin-bottom: 6px;
        }
        .input-group input {
            width: 100%;
            padding: 14px 16px;
            background: #0f1625;
            border: 1px solid #2a2f3e;
            border-radius: 10px;
            color: white;
            font-size: 14px;
            transition: border-color 0.2s;
            outline: none;
        }
        .input-group input:focus {
            border-color: #6366f1;
        }
        .input-group input::placeholder {
            color: #5a607a;
        }
        .password-wrapper {
            position: relative;
        }
        .password-wrapper .input-icon {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #5a607a;
        }
        .password-wrapper input {
            padding-left: 44px;
        }
        .btn-submit {
            width: 100%;
            padding: 16px;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 700;
            color: white;
            cursor: pointer;
            transition: all 0.2s;
        }
        .btn-submit:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }
        .login-redirect {
            text-align: center;
            margin-top: 22px;
            font-size: 14px;
            color: #8b8fa8;
        }
        .login-redirect a {
            color: #6366f1;
            text-decoration: none;
            font-weight: 600;
        }
        .login-redirect a:hover {
            text-decoration: underline;
        }
        .error-msg {
            margin-bottom: 12px;
            padding: 10px 14px;
            border-radius: 8px;
            background: rgba(239, 68, 68, 0.15);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #f87171;
            font-size: 13px;
        }
        @media (max-width: 768px) {
            .auth-card { grid-template-columns: 1fr; }
            .brand-panel {
                border-right: none;
                border-bottom: 1px solid #2a2f3e;
                padding: 32px 24px;
            }
            .form-panel { padding: 32px 24px; }
            .brand-panel h1 { font-size: 26px; }
        }
    </style>
</head>
<body>
    <div class="auth-page">
        <div class="auth-card">
            <div class="brand-panel">
                <div class="brand-identity">
                    <div class="brand-mark"><span>FM</span></div>
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
                    <p class="eyebrow"><i class="fa-solid fa-lock"></i> Security Clearance</p>
                    <h2>Verify credentials</h2>
                    <p class="muted">Please provide your authorized official staff ID/email address and system passphrase.</p>
                </div>

                <?php if ($error): ?>
                    <div class="error-msg"><i class="fa-solid fa-triangle-exclamation"></i> <?php echo escape($error); ?></div>
                <?php endif; ?>

                <form method="POST" action="adminlogin.php">
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
                    <button type="submit" name="admin_login" class="btn-submit" style="margin-top: 14px; background: linear-gradient(135deg, #ff416c, #ff4b2b); box-shadow: 0 14px 30px rgba(255, 75, 43, 0.25);">
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