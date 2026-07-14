<?php
require_once '../config.php';

if (!isLoggedIn() || !isClient()) {
    redirect('../index.php');
}

$user_id = $_SESSION['user_id'];
$user = getUserById($user_id);

$error = '';
$success = '';

// ============================================
// UPDATE PROFILE
// ============================================
if (isset($_POST['update_profile'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $bio = trim($_POST['bio']);
    
    // Validation
    if (empty($name) || empty($email)) {
        $error = '⚠️ Name and email are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = '⚠️ Please enter a valid email address.';
    } else {
        $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, phone = ?, bio = ? WHERE id = ?");
        if ($stmt->execute([$name, $email, $phone, $bio, $user_id])) {
            $_SESSION['name'] = $name;
            $_SESSION['email'] = $email;
            $success = '✅ Profile updated successfully!';
            $user = getUserById($user_id);
        } else {
            $error = '❌ Failed to update profile.';
        }
    }
}

// ============================================
// CHANGE PASSWORD
// ============================================
if (isset($_POST['change_password'])) {
    $current_password = trim($_POST['current_password']);
    $new_password = trim($_POST['new_password']);
    $confirm_password = trim($_POST['confirm_password']);
    
    // Validation
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error = '⚠️ Please fill in all password fields.';
    } elseif (md5($current_password) !== $user['password']) {
        $error = '⚠️ Current password is incorrect.';
    } elseif (strlen($new_password) < 6) {
        $error = '⚠️ New password must be at least 6 characters.';
    } elseif ($new_password !== $confirm_password) {
        $error = '⚠️ New passwords do not match.';
    } else {
        $hashed_password = md5($new_password);
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        if ($stmt->execute([$hashed_password, $user_id])) {
            $success = '✅ Password changed successfully!';
        } else {
            $error = '❌ Failed to change password.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Client Dashboard</title>
    <link rel="stylesheet" href="client-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        /* ============================================
           SETTINGS PAGE STYLES
        ============================================ */
        .settings-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
        }

        .settings-card {
            background: #fff;
            border-radius: 16px;
            border: 1px solid #e5e7eb;
            padding: 24px;
        }

        .settings-card .card-title {
            font-size: 18px;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .settings-card .card-title i {
            color: #6366f1;
        }

        .form-group {
            margin-bottom: 16px;
        }

        .form-group label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 4px;
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 10px 14px;
            border: 1px solid #d1d5db;
            border-radius: 10px;
            font-size: 14px;
            color: #1f2937;
            background: #f9fafb;
            outline: none;
            transition: all 0.2s ease;
            font-family: inherit;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            border-color: #6366f1;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
            background: white;
        }

        .form-group textarea {
            min-height: 80px;
            resize: vertical;
        }

        .form-group .helper-text {
            font-size: 12px;
            color: #6b7280;
            margin-top: 4px;
        }

        .btn-save {
            background: #6366f1;
            color: white;
            border: none;
            padding: 10px 24px;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            width: 100%;
        }

        .btn-save:hover {
            background: #4f46e5;
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
        }

        .btn-danger {
            background: #ef4444;
            color: white;
            border: none;
            padding: 10px 24px;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            width: 100%;
        }

        .btn-danger:hover {
            background: #dc2626;
        }

        .alert {
            padding: 12px 16px;
            border-radius: 10px;
            margin-bottom: 16px;
            font-weight: 600;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-success {
            background: #f0fdf4;
            color: #16a34a;
            border: 1px solid #bbf7d0;
        }

        .alert-danger {
            background: #fef2f2;
            color: #dc2626;
            border: 1px solid #fecaca;
        }

        .profile-avatar-section {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 1px solid #e5e7eb;
        }

        .profile-avatar-section img {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #eef2ff;
        }

        .profile-avatar-section .avatar-info h4 {
            font-size: 16px;
            font-weight: 700;
            color: #1f2937;
        }

        .profile-avatar-section .avatar-info p {
            font-size: 13px;
            color: #6b7280;
        }

        /* Notification Toggles */
        .notification-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid #f1f5f9;
        }

        .notification-item:last-child {
            border-bottom: none;
        }

        .notification-item .info h4 {
            font-size: 14px;
            font-weight: 600;
            color: #1f2937;
        }

        .notification-item .info p {
            font-size: 12px;
            color: #6b7280;
        }

        .toggle-switch {
            width: 44px;
            height: 24px;
            background: #d1d5db;
            border-radius: 999px;
            border: none;
            cursor: pointer;
            position: relative;
            transition: all 0.3s ease;
            flex-shrink: 0;
        }

        .toggle-switch.active {
            background: #6366f1;
        }

        .toggle-switch::after {
            content: '';
            position: absolute;
            top: 2px;
            left: 2px;
            width: 20px;
            height: 20px;
            background: white;
            border-radius: 50%;
            transition: all 0.3s ease;
            box-shadow: 0 1px 3px rgba(0,0,0,0.2);
        }

        .toggle-switch.active::after {
            left: 22px;
        }

        .danger-zone {
            margin-top: 24px;
            padding: 20px;
            border: 2px solid #fecaca;
            border-radius: 12px;
            background: #fef2f2;
        }

        .danger-zone h4 {
            font-size: 16px;
            font-weight: 700;
            color: #dc2626;
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 8px;
        }

        .danger-zone p {
            font-size: 13px;
            color: #6b7280;
            margin-bottom: 12px;
        }

        .danger-zone .btn-danger {
            width: auto;
            padding: 8px 20px;
        }

        .sidebar-menu .logout {
            margin-top: 20px;
            border-top: 1px solid var(--border-line);
            padding-top: 16px;
            color: #ef4444;
        }

        .sidebar-menu .logout:hover {
            background: #fef2f2;
            color: #dc2626;
        }

        @media (max-width: 768px) {
            .settings-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body class="client-dashboard-body">
    <div class="dashboard-container">
        
        <!-- ==========================================
        SIDEBAR
        ========================================== -->
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
                <a href="dashboard_client.php" class="menu-item"><i class="fa-solid fa-house"></i> Dashboard</a>
                <a href="jobs_client.php" class="menu-item"><i class="fa-solid fa-circle-plus"></i> Post a Job</a>
                <a href="myjob_client.php" class="menu-item"><i class="fa-solid fa-file-lines"></i> My Jobs</a>
                <a href="message_client.php" class="menu-item"><i class="fa-solid fa-comment-dots"></i> Messages</a>
                <a href="payment_client.php" class="menu-item"><i class="fa-solid fa-credit-card"></i> Payment</a>
                <a href="savedLancer_client.php" class="menu-item"><i class="fa-solid fa-star"></i> Saved Freelancers</a>
                <a href="review_client.php" class="menu-item"><i class="fa-solid fa-star-half-stroke"></i> Reviews</a>
                <a href="profile_client.php" class="menu-item"><i class="fa-solid fa-user"></i> Profile</a>
                <a href="settings_client.php" class="menu-item active"><i class="fa-solid fa-gear"></i> Settings</a>
                <a href="../index.php?logout=1" class="menu-item logout" onclick="return confirm('Are you sure you want to logout?')">
                    <i class="fa-solid fa-right-from-bracket"></i> Logout
                </a>
            </nav>
        </aside>

        <!-- ==========================================
        MAIN CONTENT
        ========================================== -->
        <main class="main-content">
            
            <header class="content-header">
                <div class="header-controls-left"></div>
                <div class="header-controls-right">
                    <button class="notification-btn"><i class="fa-regular fa-bell"></i></button>
                    <div class="profile-dropdown-trigger">
                        <img src="https://api.dicebear.com/7.x/avataaars/svg?seed=<?php echo urlencode($_SESSION['name']); ?>" alt="User Avatar" class="profile-avatar">
                        <div class="user-info-text">
                            <span class="profile-name"><?php echo escape($_SESSION['name']); ?></span>
                            <span class="profile-role">Client</span>
                        </div>
                        <i class="fa-solid fa-chevron-down dropdown-arrow"></i>
                    </div>
                </div>
            </header>

            <section class="welcome-text-section">
                <span class="welcome-tag">Welcome back,</span>
                <h1 class="welcome-heading"><?php echo escape($_SESSION['name']); ?>! 👋</h1>
            </section>

            <!-- ==========================================
            MESSAGES
            ========================================== -->
            <?php if ($error): ?>
                <div class="alert alert-danger"><i class="fa-solid fa-circle-exclamation"></i> <?php echo $error; ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> <?php echo $success; ?></div>
            <?php endif; ?>

            <!-- ==========================================
            SETTINGS GRID
            ========================================== -->
            <div class="settings-grid">

                <!-- ==========================================
                CARD 1: UPDATE PROFILE
                ========================================== -->
                <div class="settings-card">
                    <div class="card-title">
                        <i class="fa-regular fa-id-card"></i> Update Profile
                    </div>

                    <!-- Avatar -->
                    <div class="profile-avatar-section">
                        <img src="https://api.dicebear.com/7.x/avataaars/svg?seed=<?php echo urlencode($user['name']); ?>" alt="Avatar">
                        <div class="avatar-info">
                            <h4><?php echo escape($user['name']); ?></h4>
                            <p><?php echo escape($user['email']); ?></p>
                        </div>
                    </div>

                    <form method="POST">
                        <div class="form-group">
                            <label for="name">Full Name</label>
                            <input type="text" id="name" name="name" value="<?php echo escape($user['name']); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <input type="email" id="email" name="email" value="<?php echo escape($user['email']); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="phone">Phone Number</label>
                            <input type="text" id="phone" name="phone" value="<?php echo escape($user['phone'] ?? ''); ?>" placeholder="+6012 345 6789">
                        </div>

                        <div class="form-group">
                            <label for="bio">Bio</label>
                            <textarea id="bio" name="bio" placeholder="Tell us about yourself..."><?php echo escape($user['bio'] ?? ''); ?></textarea>
                            <span class="helper-text">Brief description about yourself (max 200 characters)</span>
                        </div>

                        <button type="submit" name="update_profile" class="btn-save">
                            <i class="fa-regular fa-floppy-disk"></i> Update Profile
                        </button>
                    </form>
                </div>

                <!-- ==========================================
                CARD 2: CHANGE PASSWORD & NOTIFICATIONS
                ========================================== -->
                <div class="settings-card">
                    <!-- Change Password -->
                    <div class="card-title" style="margin-bottom: 16px;">
                        <i class="fa-solid fa-key"></i> Change Password
                    </div>

                    <form method="POST">
                        <div class="form-group">
                            <label for="current_password">Current Password</label>
                            <input type="password" id="current_password" name="current_password" placeholder="Enter current password" required>
                        </div>

                        <div class="form-group">
                            <label for="new_password">New Password</label>
                            <input type="password" id="new_password" name="new_password" placeholder="Enter new password (min 6 chars)" required>
                        </div>

                        <div class="form-group">
                            <label for="confirm_password">Confirm New Password</label>
                            <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm new password" required>
                        </div>

                        <button type="submit" name="change_password" class="btn-save" style="background: #ef4444;">
                            <i class="fa-solid fa-key"></i> Change Password
                        </button>
                    </form>

                    <!-- Divider -->
                    <hr style="margin: 24px 0; border: none; border-top: 1px solid #e5e7eb;">

                    <!-- Notifications -->
                    <div class="card-title" style="margin-bottom: 12px;">
                        <i class="fa-regular fa-bell"></i> Notifications
                    </div>

                    <div class="notification-item">
                        <div class="info">
                            <h4>Email Notifications</h4>
                            <p>Receive updates via email</p>
                        </div>
                        <button class="toggle-switch active" onclick="toggleSwitch(this)"></button>
                    </div>

                    <div class="notification-item">
                        <div class="info">
                            <h4>SMS Notifications</h4>
                            <p>Receive updates via SMS</p>
                        </div>
                        <button class="toggle-switch" onclick="toggleSwitch(this)"></button>
                    </div>

                    <div class="notification-item">
                        <div class="info">
                            <h4>Job Updates</h4>
                            <p>Get notified about your job posts</p>
                        </div>
                        <button class="toggle-switch active" onclick="toggleSwitch(this)"></button>
                    </div>

                    <div class="notification-item">
                        <div class="info">
                            <h4>New Messages</h4>
                            <p>Receive notifications for new messages</p>
                        </div>
                        <button class="toggle-switch active" onclick="toggleSwitch(this)"></button>
                    </div>
                </div>

                <!-- ==========================================
                CARD 3: DANGER ZONE (Full Width)
                ========================================== -->
                <div class="settings-card" style="grid-column: 1 / -1;">
                    <div class="card-title">
                        <i class="fa-solid fa-triangle-exclamation" style="color: #dc2626;"></i> Account Actions
                    </div>

                    <div style="display: flex; gap: 12px; flex-wrap: wrap;">
                        <button class="btn-save" style="background: #f59e0b; width: auto;" onclick="if(confirm('Are you sure you want to deactivate your account?')) alert('Account deactivated!')">
                            <i class="fa-solid fa-pause"></i> Deactivate Account
                        </button>
                        <button class="btn-danger" style="width: auto;" onclick="if(confirm('Delete account permanently? This cannot be undone!')) alert('Account deleted!')">
                            <i class="fa-solid fa-trash-can"></i> Delete Account
                        </button>
                    </div>
                </div>

            </div>

        </main>
    </div>

    <!-- ==========================================
    JAVASCRIPT
    ========================================== -->
    <script>
        // Toggle Switch
        function toggleSwitch(btn) {
            btn.classList.toggle('active');
        }
    </script>

</body>
</html>