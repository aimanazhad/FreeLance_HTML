<?php
require_once '../config.php';

// Restrict page to logged in admins only
if (!isLoggedIn() || !isAdmin()) {
    redirect('../index.php');
}

$success = '';

// Save notification preference (reuse users.notifications column)
if (isset($_POST['save_settings'])) {
    $notifications = isset($_POST['notifications']) && $_POST['notifications'] == '1' ? 'on' : 'off';
    $stmt = $pdo->prepare("UPDATE users SET notifications = ? WHERE id = ?");
    $stmt->execute([$notifications, $_SESSION['user_id']]);
    $success = 'Settings saved successfully.';
}

$user = getUserById($_SESSION['user_id']);
$notificationsOn = ($user['notifications'] ?? 'on') === 'on';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Admin</title>
    <link rel="stylesheet" href="admin-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body { background: #f3f0ff; color: #1f2937; min-height: 100vh; }
        .admin-container { display: flex; min-height: 100vh; }

        /* Sidebar (same across all admin pages) */
        .sidebar { width: 240px; background: #ffffff; border-right: 1px solid #e5e7eb; padding: 24px 16px; flex-shrink: 0; position: sticky; top: 0; height: 100vh; overflow-y: auto; }
        .sidebar-brand { display: flex; align-items: center; gap: 12px; margin-bottom: 32px; padding: 0 8px; }
        .sidebar-brand .logo-icon { font-size: 28px; color: #6366f1; }
        .sidebar-brand .brand-title { font-size: 18px; font-weight: 700; color: #1f2937; }
        .sidebar-brand .brand-sub { font-size: 12px; color: #6b7280; display: block; margin-top: -2px; }
        .sidebar-menu { display: flex; flex-direction: column; gap: 4px; }
        .sidebar-menu a { display: flex; align-items: center; gap: 12px; padding: 10px 14px; border-radius: 10px; color: #6b7280; text-decoration: none; font-size: 14px; font-weight: 500; transition: all 0.2s ease; }
        .sidebar-menu a:hover { background: #f5f3ff; color: #6366f1; }
        .sidebar-menu a.active { background: #eef2ff; color: #6366f1; font-weight: 600; }
        .sidebar-menu a i { width: 20px; font-size: 16px; }
        .sidebar-menu .logout { margin-top: 20px; border-top: 1px solid #e5e7eb; padding-top: 16px; color: #ef4444; }
        .sidebar-menu .logout:hover { background: #fef2f2; color: #dc2626; }

        .main-content { flex: 1; padding: 32px 40px 60px; overflow-y: auto; }
        .page-header { margin-bottom: 28px; }
        .page-header h1 { font-size: 28px; font-weight: 800; color: #1f2937; }
        .page-header p { color: #6b7280; font-size: 14px; margin-top: 4px; }

        .two-col { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; }
        .panel { background: #fff; border-radius: 16px; border: 1px solid #e5e7eb; padding: 24px; }
        .panel h2 { font-size: 16px; margin-bottom: 16px; }

        /* Toggle row */
        .toggle-row { display: flex; justify-content: space-between; align-items: center; padding: 14px 0; border-bottom: 1px solid #e5e7eb; }
        .toggle-row:last-child { border-bottom: none; }
        .toggle-title { font-size: 13px; font-weight: 600; }
        .toggle-sub { font-size: 12px; color: #6b7280; }
        .switch { position: relative; width: 42px; height: 24px; background: #dedcea; border-radius: 999px; border: none; flex-shrink: 0; cursor: pointer; }
        .switch::after { content: ""; position: absolute; top: 3px; left: 3px; width: 18px; height: 18px; background: #fff; border-radius: 50%; transition: .15s; }
        .switch.on { background: #6366f1; }
        .switch.on::after { left: 21px; }

        .btn-primary { background: #6366f1; color: #fff; border: none; padding: 10px 24px; border-radius: 10px; font-weight: 600; cursor: pointer; margin-top: 16px; }
        .btn-primary:hover { background: #4f46e5; }
        .btn-danger { background: #ef4444; color: #fff; border: none; padding: 10px 24px; border-radius: 10px; font-weight: 600; cursor: pointer; text-decoration: none; display: inline-block; }
        .btn-danger:hover { background: #dc2626; }
        .alert { padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; font-size: 13px; }
        .alert-success { background: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0; }

        @media (max-width: 1024px) { .two-col { grid-template-columns: 1fr; } }
        @media (max-width: 768px) { .sidebar { width: 200px; padding: 16px 12px; } .main-content { padding: 20px; } }
        @media (max-width: 480px) {
            .sidebar { width: 100%; height: auto; position: relative; border-right: none; border-bottom: 1px solid #e5e7eb; }
            .admin-container { flex-direction: column; }
            .sidebar-menu { flex-direction: row; flex-wrap: wrap; }
            .sidebar-menu a { padding: 8px 12px; font-size: 13px; }
            .sidebar-menu .logout { margin-top: 0; border-top: none; padding-top: 0; }
        }
    </style>
</head>
<body>

    <div class="admin-container">

        <!-- Sidebar navigation -->
        <aside class="sidebar">
            <div class="sidebar-brand">
                <i class="fa-solid fa-chart-line logo-icon"></i>
                <div>
                    <span class="brand-title">Freelance</span>
                    <span class="brand-sub">Marketplace</span>
                </div>
            </div>
            <nav class="sidebar-menu">
                <a href="dashboard_admin.php"><i class="fa-solid fa-house"></i> Dashboard</a>
                <a href="manage_users.php"><i class="fa-solid fa-users"></i> Manage Users</a>
                <a href="manage_jobs.php"><i class="fa-solid fa-briefcase"></i> Manage Jobs</a>
                <a href="reports.php"><i class="fa-solid fa-chart-pie"></i> Reports</a>
                <a href="messages.php"><i class="fa-solid fa-comment-dots"></i> Messages</a>
                <a href="settings.php" class="active"><i class="fa-solid fa-gear"></i> Settings</a>
                <a href="profile.php"><i class="fa-solid fa-user"></i> Profile</a>
                <a href="adminlogin.php?logout=1" class="logout" onclick="return confirm('Are you sure you want to logout?')">
                    <i class="fa-solid fa-right-from-bracket"></i> Logout
                </a>
            </nav>
        </aside>

        <!-- Main content -->
        <main class="main-content">

            <div class="page-header">
                <h1>Settings</h1>
                <p>Manage your admin account preferences.</p>
            </div>

            <?php if ($success): ?><div class="alert alert-success"><?php echo $success; ?></div><?php endif; ?>

            <div class="two-col">

                <!-- Notification settings -->
                <div class="panel">
                    <h2>Notifications</h2>
                    <form method="POST">
                        <div class="toggle-row">
                            <div>
                                <div class="toggle-title">Email Notifications</div>
                                <div class="toggle-sub">Receive platform alerts via email</div>
                            </div>
                            <button type="button" class="switch <?php echo $notificationsOn ? 'on' : ''; ?>" id="notifSwitch"></button>
                            <input type="hidden" name="notifications" id="notifInput" value="<?php echo $notificationsOn ? '1' : '0'; ?>">
                        </div>
                        <button type="submit" name="save_settings" class="btn-primary">Save Settings</button>
                    </form>
                </div>

                <!-- Account actions -->
                <div class="panel">
                    <h2>Account</h2>
                    <p style="color:#6b7280;font-size:13px;margin-bottom:16px;">
                        Manage your admin session below.
                    </p>
                    <a href="adminlogin.php?logout=1" class="btn-danger" onclick="return confirm('Are you sure you want to logout?')">Log Out</a>
                </div>

            </div>

        </main>
    </div>

    <script>
        // Toggle switch visual + hidden input value
        const notifSwitch = document.getElementById('notifSwitch');
        const notifInput = document.getElementById('notifInput');
        notifSwitch.addEventListener('click', function() {
            this.classList.toggle('on');
            notifInput.value = this.classList.contains('on') ? '1' : '0';
        });
    </script>

</body>
</html>
