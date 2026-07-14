<?php
require_once 'config.php';

if (!isLoggedIn() || !isFreelancer()) {
    redirect('index.php');
}

$user = getUserById($_SESSION['user_id']);
$error = '';
$success = '';

if (isset($_POST['save_settings'])) {
    $notifications = isset($_POST['notifications']) ? 1 : 0;
    $stmt = $pdo->prepare("UPDATE users SET notifications = ? WHERE id = ?");
    $stmt->execute([$notifications, $_SESSION['user_id']]);
    $success = '✅ Settings saved successfully!';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings</title>
    <link rel="stylesheet" href="freelancer.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body { background: #f3f0ff; min-height: 100vh; }

        .dashboard-container { display: flex; min-height: 100vh; }

        .sidebar {
            width: 260px; background: #ffffff; border-right: 1px solid #e5e7eb; padding: 24px 16px;
            flex-shrink: 0; position: sticky; top: 0; height: 100vh; overflow-y: auto;
        }
        .sidebar-brand { display: flex; align-items: center; gap: 12px; margin-bottom: 32px; padding: 0 8px; }
        .sidebar-brand .logo-icon { font-size: 28px; color: #6366f1; }
        .sidebar-brand .brand-title { font-size: 18px; font-weight: 700; color: #1f2937; }
        .sidebar-brand .brand-sub { font-size: 12px; color: #6b7280; display: block; margin-top: -2px; }
        .sidebar-menu { display: flex; flex-direction: column; gap: 4px; }
        .sidebar-menu a {
            display: flex; align-items: center; gap: 12px; padding: 10px 14px; border-radius: 10px;
            color: #6b7280; text-decoration: none; font-size: 14px; font-weight: 500; transition: all 0.2s ease;
        }
        .sidebar-menu a:hover { background: #f5f3ff; color: #6366f1; }
        .sidebar-menu a.active { background: #eef2ff; color: #6366f1; font-weight: 600; }
        .sidebar-menu a i { width: 20px; font-size: 16px; }
        .sidebar-menu .logout {
            margin-top: 20px; border-top: 1px solid #e5e7eb; padding-top: 16px; color: #ef4444;
        }
        .sidebar-menu .logout:hover { background: #fef2f2; color: #dc2626; }

        .main-content { flex: 1; padding: 32px 40px 60px; overflow-y: auto; }

        .hero-banner {
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            border-radius: 16px; padding: 32px 40px; color: white; margin-bottom: 28px;
            position: relative; overflow: hidden;
        }
        .hero-banner::after {
            content: ''; position: absolute; right: -60px; top: -60px;
            width: 200px; height: 200px; border-radius: 50%; background: rgba(255,255,255,0.05);
        }
        .hero-banner h1 { font-size: 28px; font-weight: 800; }
        .hero-banner p { font-size: 15px; opacity: 0.85; margin-top: 4px; }
        .hero-banner .emoji { position: absolute; right: 40px; top: 50%; transform: translateY(-50%); font-size: 48px; z-index: 1; }

        .settings-grid {
            display: grid; grid-template-columns: 1fr 1fr; gap: 24px;
        }
        .settings-panel {
            background: white; border-radius: 12px; border: 1px solid #e5e7eb; padding: 24px;
        }
        .settings-panel h2 {
            font-size: 16px; font-weight: 700; color: #0f172a; margin-bottom: 4px;
        }
        .settings-panel .subtitle { font-size: 14px; color: #94a3b8; margin-bottom: 16px; }

        .toggle-row {
            display: flex; justify-content: space-between; align-items: center;
            padding: 14px 0; border-bottom: 1px solid #f1f5f9;
        }
        .toggle-row:last-child { border-bottom: none; }
        .toggle-title { font-size: 14px; font-weight: 600; color: #0f172a; }
        .toggle-sub { font-size: 13px; color: #94a3b8; }

        .toggle-switch {
            position: relative; width: 42px; height: 24px; background: #e2e8f0;
            border-radius: 999px; border: none; flex-shrink: 0; cursor: pointer; transition: background 0.2s ease;
        }
        .toggle-switch::after {
            content: ""; position: absolute; top: 3px; left: 3px;
            width: 18px; height: 18px; background: #fff; border-radius: 50%;
            transition: transform 0.2s ease; box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .toggle-switch.on { background: #6366f1; }
        .toggle-switch.on::after { transform: translateX(18px); }

        .btn-save {
            background: #6366f1; color: #fff; border: none; padding: 10px 24px; border-radius: 8px;
            font-weight: 600; font-size: 14px; cursor: pointer; transition: background 0.2s ease;
            width: 100%; margin-top: 16px;
        }
        .btn-save:hover { background: #4f46e5; }

        .btn-danger {
            background: #ef4444; color: #fff; border: none; padding: 10px 24px; border-radius: 8px;
            font-weight: 600; font-size: 14px; cursor: pointer; transition: background 0.2s ease;
            text-decoration: none; display: inline-block; text-align: center; width: 100%;
        }
        .btn-danger:hover { background: #dc2626; }

        .alert { padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; font-size: 14px; font-weight: 600; }
        .alert-success { background: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0; }
        hr { border: none; border-top: 1px solid #f1f5f9; margin: 12px 0; }

        @media (max-width: 768px) {
            .sidebar { width: 200px; padding: 16px 12px; }
            .main-content { padding: 20px; }
            .settings-grid { grid-template-columns: 1fr; }
            .hero-banner .emoji { display: none; }
            .hero-banner h1 { font-size: 24px; }
            .hero-banner p { font-size: 14px; }
        }
        @media (max-width: 480px) {
            .sidebar { width: 100%; height: auto; position: relative; border-right: none; border-bottom: 1px solid #e5e7eb; }
            .dashboard-container { flex-direction: column; }
            .sidebar-menu { flex-direction: row; flex-wrap: wrap; }
            .sidebar-menu a { padding: 8px 12px; font-size: 13px; }
            .sidebar-menu .logout { margin-top: 0; border-top: none; padding-top: 0; }
            .hero-banner { padding: 24px; }
            .hero-banner h1 { font-size: 22px; }
        }
    </style>
</head>
<body>

<div class="dashboard-container">

    <!-- SIDEBAR -->
    <aside class="sidebar">
        <div class="sidebar-brand">
            <i class="fa-solid fa-chart-line logo-icon"></i>
            <div>
                <span class="brand-title">Freelance</span>
                <span class="brand-sub">Marketplace</span>
            </div>
        </div>

        <nav class="sidebar-menu">
            <a href="dashboard_freelancer.php"><i class="fa-solid fa-house"></i> Dashboard</a>
            <a href="browse_jobs.php"><i class="fa-solid fa-briefcase"></i> Browse Jobs</a>
            <a href="my_applications.php"><i class="fa-solid fa-file-lines"></i> My Applications</a>
            <a href="messages.php"><i class="fa-solid fa-comment-dots"></i> Messages</a>
            <a href="portfolio.php"><i class="fa-solid fa-folder-open"></i> Portfolio</a>
            <a href="earnings.php"><i class="fa-solid fa-wallet"></i> Earnings</a>
            <a href="profile.php"><i class="fa-solid fa-user"></i> Profile</a>
            <a href="settings_freelancer.php" class="active"><i class="fa-solid fa-gear"></i> Settings</a>
            <a href="index.php?logout=1" class="logout" onclick="return confirm('Are you sure you want to logout?')">
                <i class="fa-solid fa-right-from-bracket"></i> Log out
            </a>
        </nav>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="main-content">

        <div class="hero-banner">
            <div>
                <h1>⚙️ Settings</h1>
                <p>Manage your account preferences and notifications.</p>
            </div>
            <div class="emoji">🔧</div>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <div class="settings-grid">

            <!-- NOTIFICATIONS -->
            <div class="settings-panel">
                <h2><i class="fa-regular fa-bell" style="color:#6366f1;"></i> Notifications</h2>
                <p class="subtitle">Choose how you want to receive updates.</p>
                <form method="POST">
                    <div class="toggle-row">
                        <div>
                            <div class="toggle-title">Email Notifications</div>
                            <div class="toggle-sub">Receive updates via email</div>
                        </div>
                        <button class="toggle-switch on" type="button"></button>
                    </div>
                    <div class="toggle-row">
                        <div>
                            <div class="toggle-title">SMS Notifications</div>
                            <div class="toggle-sub">Receive updates via SMS</div>
                        </div>
                        <button class="toggle-switch" type="button"></button>
                    </div>
                    <div class="toggle-row">
                        <div>
                            <div class="toggle-title">Push Notifications</div>
                            <div class="toggle-sub">Receive app notifications</div>
                        </div>
                        <button class="toggle-switch on" type="button"></button>
                    </div>
                    <button type="submit" name="save_settings" class="btn-save">
                        <i class="fa-regular fa-floppy-disk"></i> Save Settings
                    </button>
                </form>
            </div>

            <!-- ACCOUNT -->
            <div class="settings-panel">
                <h2><i class="fa-regular fa-user" style="color:#6366f1;"></i> Account</h2>
                <p class="subtitle">Manage your account and security.</p>

                <div class="toggle-row">
                    <div>
                        <div class="toggle-title">Two-Factor Authentication</div>
                        <div class="toggle-sub">Add an extra layer of security</div>
                    </div>
                    <button class="toggle-switch" type="button"></button>
                </div>
                <div class="toggle-row">
                    <div>
                        <div class="toggle-title">Session Management</div>
                        <div class="toggle-sub">View active sessions</div>
                    </div>
                    <span style="font-size:13px;color:#94a3b8;">1 active</span>
                </div>

                <hr>

                <a href="index.php?logout=1" class="btn-danger" onclick="return confirm('Are you sure you want to logout?')">
                    <i class="fa-solid fa-right-from-bracket"></i> Log Out
                </a>
            </div>

        </div>

    </main>
</div>

<script>
    document.querySelectorAll('.toggle-switch').forEach(btn => {
        btn.addEventListener('click', function() {
            this.classList.toggle('on');
        });
    });
</script>

</body>
</html>