<?php
require_once '../config.php';

if (!isLoggedIn() || !isClient()) {
    redirect('../index.php');
}

$user = getUserById($_SESSION['user_id']);
$error = '';
$success = '';

$avatarOptions = [
    'Oliver' => 'Oliver',
    'Liam' => 'Liam',
    'Ethan' => 'Ethan',
    'Noah' => 'Noah',
    'Ava' => 'Ava',
    'Mia' => 'Mia',
    'Emma' => 'Emma',
    'Sophia' => 'Sophia',
];

$selectedAvatarSeed = $user['avatar_seed'] ?? '';
$currentAvatarSeed = !empty($selectedAvatarSeed) ? $selectedAvatarSeed : $user['name'];

if (isset($_POST['update_profile'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone'] ?? '');
    $bio = trim($_POST['bio'] ?? '');
    $avatarSeed = trim($_POST['avatar_seed'] ?? $selectedAvatarSeed);
    
    if (empty($name) || empty($email)) {
        $error = '⚠️ Name and email are required.';
    } else {
        $avatarSeed = $avatarSeed !== '' ? $avatarSeed : null;
        $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, phone = ?, bio = ?, avatar_seed = ? WHERE id = ?");
        if ($stmt->execute([$name, $email, $phone, $bio, $avatarSeed, $_SESSION['user_id']])) {
            $_SESSION['name'] = $name;
            $_SESSION['email'] = $email;
            $_SESSION['avatar_seed'] = $avatarSeed;
            $success = '✅ Profile updated successfully!';
            $user = getUserById($_SESSION['user_id']);
            $selectedAvatarSeed = $user['avatar_seed'] ?? '';
            $currentAvatarSeed = !empty($selectedAvatarSeed) ? $selectedAvatarSeed : $user['name'];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Freelance Marketplace</title>
    <link rel="stylesheet" href="client-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        .profile-header-section { padding: 32px 0; border-bottom: 1px solid #e5e7eb; margin-bottom: 32px; }
        .profile-container { padding: 0 32px 32px 32px; }
        .profile-main-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 24px; padding: 40px; margin-bottom: 32px; }
        .profile-content-wrapper { display: grid; grid-template-columns: 200px 1fr; gap: 60px; }
        .profile-left-section { display: flex; flex-direction: column; gap: 28px; }
        .profile-large-avatar { width: 160px; height: 160px; border-radius: 50%; border: 8px solid #eeebff; object-fit: cover; }
        .info-item { display: flex; flex-direction: column; gap: 4px; }
        .info-item label { font-size: 12px; font-weight: 700; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px; }
        .info-item p { font-size: 15px; font-weight: 600; color: #1f2937; }
        .profile-right-section { display: flex; flex-direction: column; gap: 24px; }
        .metrics-container { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; }
        .metric-box { padding: 20px 16px; border-radius: 14px; text-align: center; color: white; font-weight: 700; }
        .metric-box.green { background: #22c55e; }
        .metric-box.blue { background: #3b82f6; }
        .metric-box.yellow { background: #eab308; color: #000; }
        .metric-label { display: block; font-size: 12px; font-weight: 600; margin-bottom: 8px; opacity: 0.9; }
        .metric-number { font-size: 32px; margin: 0; }
        .skill-tag { background: #f3f0ff; color: #6366f1; padding: 10px 16px; border-radius: 20px; font-size: 13px; font-weight: 600; border: 1px solid #e9e5ff; display: inline-block; }
        .profile-additional-cards { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; }
        .settings-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 16px; padding: 24px; }
        .settings-card.danger-zone-card { grid-column: span 2; border-color: #fee2e2; background: #fef2f2; }
        .settings-title { font-size: 16px; font-weight: 700; color: #1f2937; margin: 0 0 20px 0; display: flex; align-items: center; gap: 10px; }
        .settings-title i { color: #6366f1; }
        .settings-content { display: flex; flex-direction: column; gap: 16px; }
        .setting-row { display: flex; justify-content: space-between; align-items: center; padding: 12px 0; border-bottom: 1px solid #e5e7eb; font-size: 14px; font-weight: 500; }
        .setting-row:last-of-type { border-bottom: none; }
        .setting-row span:first-child { color: #6b7280; }
        .setting-row span:last-child { color: #1f2937; font-weight: 600; }
        .badge { display: inline-block; padding: 6px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; text-transform: uppercase; }
        .badge-active { background: #dcfce7; color: #22c55e; }
        .btn-action { padding: 10px 16px; background: #6366f1; color: white; border: none; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer; width: 100%; margin-top: 8px; }
        .btn-action:hover { background: #4f46e5; }
        .btn-deactivate { background: #fbbf24; color: #78350f; padding: 12px 20px; border: none; border-radius: 8px; font-size: 14px; font-weight: 700; cursor: pointer; width: 100%; }
        .btn-deactivate:hover { background: #f59e0b; }
        .btn-delete { background: #ef4444; color: white; padding: 12px 20px; border: none; border-radius: 8px; font-size: 14px; font-weight: 700; cursor: pointer; width: 100%; }
        .btn-delete:hover { background: #dc2626; }
        .danger-actions { display: flex; flex-direction: column; gap: 12px; }
        .alert { padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; }
        .alert-success { background: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0; }
        .alert-danger { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }
        @media (max-width: 1024px) { .profile-content-wrapper { grid-template-columns: 1fr; } .profile-additional-cards { grid-template-columns: 1fr; } .settings-card.danger-zone-card { grid-column: span 1; } }
        @media (max-width: 768px) { .profile-main-card { padding: 20px; } .metrics-container { grid-template-columns: 1fr; } .profile-large-avatar { width: 120px; height: 120px; } }
        .form-group { margin-bottom: 16px; }
        .form-group label { display: block; font-size: 13px; font-weight: 600; color: #1f2937; margin-bottom: 6px; }
        .form-group input, .form-group textarea { width: 100%; padding: 10px 14px; border: 1px solid #e5e7eb; border-radius: 10px; font-size: 14px; background: white; }
        .form-group textarea { min-height: 80px; resize: vertical; }
        .btn-save { background: #6366f1; color: white; border: none; padding: 10px 24px; border-radius: 10px; font-weight: 600; cursor: pointer; }
        .btn-save:hover { background: #4f46e5; }
        .avatar-container { display: flex; justify-content: center; }
        .avatar-picker { display: flex; align-items: center; gap: 20px; margin-bottom: 24px; }
        .avatar-display { position: relative; width: 120px; height: 120px; }
        .avatar-display img { width: 120px; height: 120px; border-radius: 50%; object-fit: cover; border: 4px solid #eef2ff; }
        .avatar-edit-icon { position: absolute; right: 0; bottom: 0; width: 36px; height: 36px; border-radius: 50%; background: #6366f1; display: flex; align-items: center; justify-content: center; color: #fff; border: 2px solid #fff; box-shadow: 0 10px 20px rgba(0,0,0,0.12); }
        .avatar-selection { margin-bottom: 20px; }
        .section-label { display: block; font-size: 13px; font-weight: 700; color: #1f2937; margin-bottom: 10px; }
        .avatar-options { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 12px; }
        .avatar-option { cursor: pointer; border: 2px solid transparent; border-radius: 16px; padding: 8px; transition: all 0.2s ease; }
        .avatar-option.selected { border-color: #6366f1; background: #eef2ff; }
        .avatar-option img { width: 100%; border-radius: 14px; }
        .avatar-option input { display: none; }
        .freelancers-list { display: flex; flex-direction: column; gap: 14px; }
        .freelancer-item { display: flex; align-items: center; gap: 12px; }
        .freelancer-avatar { width: 40px; height: 40px; border-radius: 50%; object-fit: cover; }
        .freelancer-name { font-size: 14px; font-weight: 600; color: #1f2937; }
        .recent-freelancers-card { background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 16px; padding: 20px; }
        .card-subtitle { font-size: 14px; font-weight: 700; color: #1f2937; margin: 0 0 16px 0; }
        .skills-card { display: flex; gap: 10px; flex-wrap: wrap; }
        .notification-settings { display: flex; flex-direction: column; gap: 12px; }
        .notification-item { display: flex; justify-content: space-between; align-items: center; padding: 14px; background: #f9fafb; border-radius: 10px; border: 1px solid #e5e7eb; }
        .notification-info h4 { font-size: 13px; font-weight: 600; color: #1f2937; margin: 0 0 2px 0; }
        .notification-info p { font-size: 12px; color: #6b7280; margin: 0; }
        .toggle-switch { width: 40px; height: 24px; cursor: pointer; accent-color: #6366f1; }
    </style>
</head>
<body class="client-dashboard-body">
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
                <a href="dashboard_client.php" class="menu-item"><i class="fa-solid fa-house"></i> Dashboard</a>
                <a href="jobs_client.php" class="menu-item"><i class="fa-solid fa-circle-plus"></i> Post a Job</a>
                <a href="myjob_client.php" class="menu-item"><i class="fa-solid fa-file-lines"></i> My Jobs</a>
                <a href="message_client.php" class="menu-item"><i class="fa-solid fa-comment-dots"></i> Messages</a>
                <a href="payment_client.php" class="menu-item"><i class="fa-solid fa-credit-card"></i> Payments</a>
                <a href="savedLancer_client.php" class="menu-item"><i class="fa-solid fa-star"></i> Saved Freelancers</a>
                <a href="review_client.php" class="menu-item"><i class="fa-solid fa-star-half-stroke"></i> Reviews</a>
                <a href="profile_client.php" class="menu-item active"><i class="fa-solid fa-user"></i> Profile</a>
                <a href="settings_client.php" class="menu-item"><i class="fa-solid fa-gear"></i> Settings</a>
                <a href="../index.php?logout=1" class="menu-item" style="margin-top: 20px; border-top: 1px solid var(--border-line); padding-top: 16px;"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
            </nav>
        </aside>
        <main class="main-content">
            <header class="content-header">
                <div class="header-controls-left"></div>
                <div class="header-controls-right">
                    <button class="notification-btn"><i class="fa-regular fa-bell"></i></button>
                    <div class="profile-dropdown-trigger">
                        <img src="https://api.dicebear.com/7.x/avataaars/svg?seed=<?php echo urlencode($currentAvatarSeed); ?>" alt="User Avatar" class="profile-avatar">
                        <div class="user-info-text">
                            <span class="profile-name"><?php echo escape($_SESSION['name']); ?></span>
                            <span class="profile-role">Client</span>
                        </div>
                        <i class="fa-solid fa-chevron-down dropdown-arrow"></i>
                    </div>
                </div>
            </header>

            <section class="profile-header-section">
                <span class="welcome-tag">Your Account</span>
                <h1 class="welcome-heading">My Profile</h1>
            </section>

            <div class="profile-container">
                <div class="profile-main-card">
                    <div class="profile-content-wrapper">
                        <div class="profile-left-section">
                            <div class="avatar-container">
                                <img src="https://api.dicebear.com/7.x/avataaars/svg?seed=<?php echo urlencode($currentAvatarSeed); ?>" alt="Profile Avatar" class="profile-large-avatar">
                            </div>
                            <div class="profile-info-section">
                                <div class="info-item"><label>Full Name:</label><p><?php echo escape($user['name']); ?></p></div>
                                <div class="info-item"><label>Email:</label><p><?php echo escape($user['email']); ?></p></div>
                                <div class="info-item"><label>Phone:</label><p><?php echo escape($user['phone'] ?? '-'); ?></p></div>
                                <div class="info-item"><label>Bio:</label><p><?php echo escape($user['bio'] ?? '-'); ?></p></div>
                            </div>
                        </div>
                        <div class="profile-right-section">
                            <div class="metrics-container">
                                <div class="metric-box green"><span class="metric-label">Jobs Posted</span><h3 class="metric-number"><?php echo $pdo->query("SELECT COUNT(*) FROM jobs WHERE client_id = ".$_SESSION['user_id'])->fetchColumn(); ?></h3></div>
                                <div class="metric-box blue"><span class="metric-label">Active Contracts</span><h3 class="metric-number"><?php echo $pdo->query("SELECT COUNT(*) FROM jobs WHERE client_id = ".$_SESSION['user_id']." AND status = 'active'")->fetchColumn(); ?></h3></div>
                                <div class="metric-box yellow"><span class="metric-label">Completed</span><h3 class="metric-number"><?php echo $pdo->query("SELECT COUNT(*) FROM jobs WHERE client_id = ".$_SESSION['user_id']." AND status = 'completed'")->fetchColumn(); ?></h3></div>
                            </div>
                            <div class="skills-card">
                                <span class="skill-tag">Client Account</span>
                                <span class="skill-tag">Active</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="profile-additional-cards">
                    <div class="settings-card">
                        <?php if ($success): ?>
                            <div class="alert alert-success"><?php echo escape($success); ?></div>
                        <?php endif; ?>
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo escape($error); ?></div>
                        <?php endif; ?>
                        <h2 class="settings-title"><i class="fa-solid fa-user-pen"></i> Edit Profile</h2>
                        <form method="post">
                            <div class="avatar-selection">
                                <span class="section-label">Choose your profile avatar</span>
                                <div class="avatar-picker">
                                    <div class="avatar-display">
                                        <img src="https://api.dicebear.com/7.x/avataaars/svg?seed=<?php echo urlencode($currentAvatarSeed); ?>" alt="Selected avatar">
                                        <div class="avatar-edit-icon"><i class="fa-solid fa-pencil"></i></div>
                                    </div>
                                    <div class="avatar-options">
                                        <?php foreach ($avatarOptions as $seed => $label): ?>
                                            <label class="avatar-option <?php echo $selectedAvatarSeed === $seed ? 'selected' : ''; ?>">
                                                <input type="radio" name="avatar_seed" value="<?php echo escape($seed); ?>" <?php echo $selectedAvatarSeed === $seed ? 'checked' : ''; ?>>
                                                <img src="https://api.dicebear.com/7.x/avataaars/svg?seed=<?php echo urlencode($seed); ?>" alt="<?php echo escape($label); ?> avatar">
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="name">Full Name</label>
                                <input type="text" id="name" name="name" value="<?php echo escape($user['name']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="email">Email</label>
                                <input type="email" id="email" name="email" value="<?php echo escape($user['email']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="phone">Phone</label>
                                <input type="text" id="phone" name="phone" value="<?php echo escape($user['phone'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label for="bio">Bio</label>
                                <textarea id="bio" name="bio"><?php echo escape($user['bio'] ?? ''); ?></textarea>
                            </div>
                            <button type="submit" name="update_profile" class="btn-save">Save Changes</button>
                        </form>
                    </div>

                    <div class="settings-card">
                        <h2 class="settings-title"><i class="fa-solid fa-lock"></i> Account Security</h2>
                        <div class="settings-content">
                            <div class="setting-row"><span>Account Status</span><span class="badge badge-active">Active</span></div>
                            <div class="setting-row"><span>Member Since</span><span><?php echo date('d M Y', strtotime($user['created_at'])); ?></span></div>
                            <button class="btn-action" onclick="alert('Change password feature coming soon!')">Change Password</button>
                        </div>
                    </div>

                    <div class="settings-card">
                        <h2 class="settings-title"><i class="fa-solid fa-bell"></i> Notification Preferences</h2>
                        <div class="notification-settings">
                            <div class="notification-item"><div class="notification-info"><h4>Email Notifications</h4><p>Receive updates via email</p></div><input type="checkbox" class="toggle-switch" checked></div>
                            <div class="notification-item"><div class="notification-info"><h4>Job Updates</h4><p>Get notified about your job posts</p></div><input type="checkbox" class="toggle-switch" checked></div>
                        </div>
                    </div>

                    <div class="settings-card">
                        <h2 class="settings-title"><i class="fa-solid fa-credit-card"></i> Payment & Billing</h2>
                        <div class="settings-content">
                            <div class="setting-row"><span>Payment Method</span><span>E-Wallet / Bank Transfer</span></div>
                            <button class="btn-action" onclick="alert('Manage payment methods coming soon!')">Manage Payment Methods</button>
                        </div>
                    </div>

                    <div class="settings-card danger-zone-card">
                        <div class="danger-actions">
                            <button class="btn-deactivate" onclick="if(confirm('Deactivate account?')) alert('Account deactivated!')">Deactivate Account</button>
                            <button class="btn-delete" onclick="if(confirm('Delete account permanently? This cannot be undone!')) alert('Account deleted!')">Delete Account Permanently</button>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>