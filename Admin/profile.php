<?php
require_once '../config.php';

// Restrict page to logged in admins only
if (!isAdminLoggedIn()) {
    redirect('adminlogin.php');
}

// Get admin data from admins table
$admin_id = $_SESSION['admin_id'];
$admin = getAdminById($admin_id);
$error = '';
$success = '';

// Update profile info - GUNA TABLE ADMINS
if (isset($_POST['update_profile'])) {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone'] ?? '');
    $username = trim($_POST['username'] ?? '');

    if (empty($full_name) || empty($email)) {
        $error = 'Name and email are required.';
    } else {
        $stmt = $pdo->prepare("UPDATE admins SET full_name = ?, email = ?, phone = ?, username = ? WHERE id = ?");
        $stmt->execute([$full_name, $email, $phone, $username, $admin_id]);
        
        // Update session
        $_SESSION['admin_full_name'] = $full_name;
        $_SESSION['admin_email'] = $email;
        $_SESSION['admin_username'] = $username;
        
        $success = 'Profile updated successfully.';
        $admin = getAdminById($admin_id);
    }
}

// Change password - GUNA TABLE ADMINS
if (isset($_POST['change_password'])) {
    $currentPassword = trim($_POST['current_password']);
    $newPassword = trim($_POST['new_password']);
    $confirmPassword = trim($_POST['confirm_password']);

    if ($currentPassword !== $admin['password']) {
        $error = 'Current password is incorrect.';
    } elseif (strlen($newPassword) < 6) {
        $error = 'New password must be at least 6 characters.';
    } elseif ($newPassword !== $confirmPassword) {
        $error = 'New passwords do not match.';
    } else {
        $stmt = $pdo->prepare("UPDATE admins SET password = ? WHERE id = ?");
        $stmt->execute([$newPassword, $admin_id]);
        $success = 'Password changed successfully.';
        $admin = getAdminById($admin_id);
    }
}

// Basic activity counts
$totalManagedUsers = getTotalUsers();
$totalManagedJobs = $pdo->query("SELECT COUNT(*) FROM jobs")->fetchColumn();
$totalAdmins = countAdmins();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - Admin</title>
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

        /* Stat mini cards */
        .profile-stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-bottom: 24px; }
        .profile-stat { background: #fff; padding: 16px; border-radius: 12px; border: 1px solid #e5e7eb; text-align: center; }
        .profile-stat .number { font-size: 24px; font-weight: 700; color: #1f2937; }
        .profile-stat .label { font-size: 12px; color: #6b7280; }

        .panel { background: #fff; border-radius: 16px; border: 1px solid #e5e7eb; padding: 24px; margin-bottom: 24px; }
        .avatar-row { display: flex; align-items: center; gap: 16px; margin-bottom: 20px; }
        .avatar-row img { width: 72px; height: 72px; border-radius: 50%; object-fit: cover; }
        .avatar-row .badge-admin-role { background: #fef2f2; color: #ef4444; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; }

        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        .field { display: flex; flex-direction: column; gap: 6px; margin-bottom: 16px; }
        .field label { font-size: 12px; font-weight: 600; color: #6b7280; }
        .field input { border: 1px solid #e5e7eb; border-radius: 10px; padding: 10px 14px; font-size: 13px; }
        .btn-primary { background: #6366f1; color: #fff; border: none; padding: 10px 24px; border-radius: 10px; font-weight: 600; cursor: pointer; }
        .btn-primary:hover { background: #4f46e5; }
        .alert { padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; font-size: 13px; }
        .alert-success { background: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0; }
        .alert-danger { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }

        @media (max-width: 768px) { .sidebar { width: 200px; padding: 16px 12px; } .main-content { padding: 20px; } .form-grid { grid-template-columns: 1fr; } .profile-stats { grid-template-columns: 1fr; } }
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
                <a href="settings.php"><i class="fa-solid fa-gear"></i> Settings</a>
                <a href="profile.php" class="active"><i class="fa-solid fa-user"></i> Profile</a>
                <a href="adminlogin.php?logout=1" class="logout" onclick="return confirm('Are you sure you want to logout?')">
                    <i class="fa-solid fa-right-from-bracket"></i> Logout
                </a>
            </nav>
        </aside>

        <!-- Main content -->
        <main class="main-content">

            <div class="page-header">
                <h1>Profile</h1>
                <p>Manage your admin account information.</p>
            </div>

            <?php if ($error): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php endif; ?>
            <?php if ($success): ?><div class="alert alert-success"><?php echo $success; ?></div><?php endif; ?>

            <div class="profile-stats">
                <div class="profile-stat"><div class="number"><?php echo $totalManagedUsers; ?></div><div class="label">Total Users Managed</div></div>
                <div class="profile-stat"><div class="number"><?php echo $totalManagedJobs; ?></div><div class="label">Total Jobs Managed</div></div>
                <div class="profile-stat"><div class="number"><?php echo $totalAdmins; ?></div><div class="label">Total Admins</div></div>
            </div>

            <!-- Profile info form - GUNA TABLE ADMINS -->
            <div class="panel">
                <div class="avatar-row">
                    <img src="https://api.dicebear.com/7.x/avataaars/svg?seed=<?php echo urlencode($admin['full_name'] ?? $admin['username']); ?>" alt="Avatar">
                    <div>
                        <div style="font-weight:700;font-size:16px;"><?php echo escape($admin['full_name'] ?? $admin['username']); ?></div>
                        <span class="badge-admin-role"><?php echo ucfirst($admin['role'] ?? 'Administrator'); ?></span>
                        <div style="font-size:12px;color:#6b7280;margin-top:4px;">@<?php echo escape($admin['username']); ?></div>
                    </div>
                </div>

                <form method="POST">
                    <div class="form-grid">
                        <div class="field">
                            <label>Full Name</label>
                            <input type="text" name="full_name" value="<?php echo escape($admin['full_name'] ?? ''); ?>" required>
                        </div>
                        <div class="field">
                            <label>Username</label>
                            <input type="text" name="username" value="<?php echo escape($admin['username'] ?? ''); ?>" required>
                        </div>
                    </div>
                    <div class="form-grid">
                        <div class="field">
                            <label>Email</label>
                            <input type="email" name="email" value="<?php echo escape($admin['email'] ?? ''); ?>" required>
                        </div>
                        <div class="field">
                            <label>Phone</label>
                            <input type="text" name="phone" value="<?php echo escape($admin['phone'] ?? ''); ?>" placeholder="+6012 345 6789">
                        </div>
                    </div>
                    <button type="submit" name="update_profile" class="btn-primary">Save Changes</button>
                </form>
            </div>

            <!-- Change password form - GUNA TABLE ADMINS -->
            <div class="panel">
                <h3 style="margin-bottom:16px;">Change Password</h3>
                <form method="POST">
                    <div class="field">
                        <label>Current Password</label>
                        <input type="password" name="current_password" required>
                    </div>
                    <div class="form-grid">
                        <div class="field">
                            <label>New Password</label>
                            <input type="password" name="new_password" required>
                        </div>
                        <div class="field">
                            <label>Confirm New Password</label>
                            <input type="password" name="confirm_password" required>
                        </div>
                    </div>
                    <button type="submit" name="change_password" class="btn-primary">Update Password</button>
                </form>
            </div>

        </main>
    </div>

</body>
</html>