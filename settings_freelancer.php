<?php
require_once 'config.php';

if (!isLoggedIn() || !isFreelancer()) {
    redirect('../index.php');
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
    <title>Settings - Freelancer</title>
    <link rel="stylesheet" href="freelancer.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        .fm-hero { background: linear-gradient(120deg, #ede7ff, #f7e9f5); border-radius: 16px; padding: 32px 36px; margin-bottom: 24px; }
        .fm-panel { background: #fff; border-radius: 16px; padding: 24px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
        .fm-two-col { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; }
        .fm-toggle-row { display: flex; justify-content: space-between; align-items: center; padding: 14px 0; border-bottom: 1px solid #e5e7eb; }
        .fm-toggle-row:last-child { border-bottom: none; }
        .fm-toggle-title { font-size: 13px; font-weight: 600; }
        .fm-toggle-sub { font-size: 12px; color: #6b7280; }
        .fm-switch { position: relative; width: 42px; height: 24px; background: #dedcea; border-radius: 999px; border: none; flex-shrink: 0; cursor: pointer; }
        .fm-switch::after { content: ""; position: absolute; top: 3px; left: 3px; width: 18px; height: 18px; background: #fff; border-radius: 50%; transition: .15s; }
        .fm-switch.on { background: #6366f1; }
        .fm-switch.on::after { left: 21px; }
        .fm-btn { background: #ef4444; color: #fff; border: none; padding: 10px 24px; border-radius: 10px; font-weight: 600; cursor: pointer; }
        .fm-btn:hover { background: #dc2626; }
        .alert { padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; }
        .alert-success { background: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0; }
        @media (max-width: 768px) { .fm-two-col { grid-template-columns: 1fr; } }
    </style>
</head>
<body class="freelancer-dashboard-body">
    <div class="dashboard-container">
        <aside class="sidebar">
            <div class="sidebar-brand">
                <div class="brand-logo">
                    <img src="https://i.postimg.co/N0Mszp57/F-Logo.png" alt="Logo" class="logo-img" onerror="this.style.display='none';">
                    <div class="brand-text-group">
                        <span class="brand-title">Freelance</span>
                        <span class="brand-subtitle">Marketplace</span>
                    </div>
                </div>
            </div>
            <nav class="sidebar-menu">
                <a href="dashboard_freelancer.php" class="menu-item"><i class="fa-solid fa-house"></i> Dashboard</a>
                <a href="browse_jobs.php" class="menu-item"><i class="fa-solid fa-briefcase"></i> Browse Jobs</a>
                <a href="my_applications.php" class="menu-item"><i class="fa-solid fa-file-lines"></i> My Applications</a>
                <a href="messages.php" class="menu-item"><i class="fa-solid fa-comment-dots"></i> Messages</a>
                <a href="portfolio.php" class="menu-item"><i class="fa-solid fa-folder-open"></i> Portfolio</a>
                <a href="earnings.php" class="menu-item"><i class="fa-solid fa-wallet"></i> Earnings</a>
                <a href="profile.php" class="menu-item"><i class="fa-solid fa-user"></i> Profile</a>
                <a href="settings.php" class="menu-item active"><i class="fa-solid fa-gear"></i> Settings</a>
                <a href="../index.php?logout=1" class="menu-item" style="margin-top: 20px; border-top: 1px solid var(--color-border-line); padding-top: 16px;"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
            </nav>
        </aside>
        <main class="main-content">
            <header class="content-header">
                <div class="header-controls-left"></div>
                <div class="header-controls-right">
                    <button class="notification-btn"><i class="fa-regular fa-bell"></i></button>
                    <div class="profile-dropdown-trigger">
                        <img src="https://api.dicebear.com/7.x/avataaars/svg?seed=<?php echo urlencode($_SESSION['name']); ?>" alt="User Avatar" class="profile-avatar">
                        <div class="user-info-text">
                            <span class="profile-name"><?php echo escape($_SESSION['name']); ?></span>
                            <span class="profile-role">Freelancer</span>
                        </div>
                        <i class="fa-solid fa-chevron-down dropdown-arrow"></i>
                    </div>
                </div>
            </header>

            <div class="fm-hero">
                <div class="fm-hero-eyebrow">Configuration</div>
                <h1>Settings</h1>
                <p>Manage your account preferences.</p>
            </div>

            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>

            <div class="fm-two-col">
                <div class="fm-panel">
                    <h2>Notifications</h2>
                    <form method="POST">
                        <div class="fm-toggle-row">
                            <div><div class="fm-toggle-title">Email Notifications</div><div class="fm-toggle-sub">Receive updates via email</div></div>
                            <button class="fm-switch on" data-key="email" type="button"></button>
                        </div>
                        <div class="fm-toggle-row">
                            <div><div class="fm-toggle-title">SMS Notifications</div><div class="fm-toggle-sub">Receive updates via SMS</div></div>
                            <button class="fm-switch" data-key="sms" type="button"></button>
                        </div>
                        <div class="fm-toggle-row">
                            <div><div class="fm-toggle-title">Push Notifications</div><div class="fm-toggle-sub">Receive app notifications</div></div>
                            <button class="fm-switch on" data-key="push" type="button"></button>
                        </div>
                        <button type="submit" name="save_settings" class="fm-btn" style="background:#6366f1;margin-top:16px;">Save Settings</button>
                    </form>
                </div>

                <div class="fm-panel">
                    <h2>Account</h2>
                    <p style="margin:0 0 16px; color:#64748b;">Use the button below to sign out from this workspace.</p>
                    <a href="../index.php?logout=1" class="fm-btn" style="background:#ef4444; text-decoration:none; display:inline-block;">Log Out</a>
                </div>
            </div>
        </main>
    </div>

    <script>
        document.querySelectorAll('.fm-switch').forEach(btn => {
            btn.addEventListener('click', function() {
                this.classList.toggle('on');
            });
        });
    </script>
</body>
</html>