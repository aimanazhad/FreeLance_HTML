<?php
require_once 'config.php';

if (!isLoggedIn() || !isFreelancer()) {
    redirect('../index.php');
}

$user = getUserById($_SESSION['user_id']);
$error = '';
$success = '';

if (isset($_POST['update_profile'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone'] ?? '');
    $bio = trim($_POST['bio'] ?? '');
    $skills = trim($_POST['skills'] ?? '');
    
    if (empty($name) || empty($email)) {
        $error = '⚠️ Name and email are required.';
    } else {
        $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, phone = ?, bio = ?, skills = ? WHERE id = ?");
        if ($stmt->execute([$name, $email, $phone, $bio, $skills, $_SESSION['user_id']])) {
            $_SESSION['name'] = $name;
            $_SESSION['email'] = $email;
            $success = '✅ Profile updated successfully!';
            $user = getUserById($_SESSION['user_id']);
        }
    }
}

// Get stats
$totalApplications = $pdo->query("SELECT COUNT(*) FROM applications WHERE freelancer_id = ".$_SESSION['user_id'])->fetchColumn();
$acceptedApplications = $pdo->query("SELECT COUNT(*) FROM applications WHERE freelancer_id = ".$_SESSION['user_id']." AND status = 'accepted'")->fetchColumn();
$totalEarnings = $pdo->query("SELECT SUM(amount) FROM payments WHERE user_id = ".$_SESSION['user_id']." AND status = 'paid'")->fetchColumn() ?? 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - Freelancer</title>
    <link rel="stylesheet" href="freelancer.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        .fm-hero { background: linear-gradient(120deg, #ede7ff, #f7e9f5); border-radius: 16px; padding: 32px 36px; margin-bottom: 24px; }
        .fm-panel { background: #fff; border-radius: 16px; padding: 24px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
        .fm-avatar-row { display: flex; align-items: center; gap: 16px; margin-bottom: 20px; }
        .fm-avatar-row img { width: 80px; height: 80px; border-radius: 50%; object-fit: cover; }
        .fm-form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        .fm-field { display: flex; flex-direction: column; gap: 6px; margin-bottom: 16px; }
        .fm-field label { font-size: 12px; font-weight: 600; color: #6b7280; }
        .fm-field input, .fm-field textarea { border: 1px solid #e5e7eb; border-radius: 10px; padding: 10px 14px; font-family: inherit; font-size: 13px; width: 100%; }
        .fm-field textarea { min-height: 80px; resize: vertical; }
        .fm-btn { background: #6366f1; color: #fff; border: none; padding: 10px 24px; border-radius: 10px; font-weight: 600; cursor: pointer; }
        .fm-btn:hover { background: #4f46e5; }
        .fm-skill-tag { background: #f2eeff; color: #6c4ce0; padding: 5px 12px; border-radius: 999px; font-size: 12px; font-weight: 600; display: inline-flex; align-items: center; gap: 6px; }
        .alert { padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; }
        .alert-success { background: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0; }
        .alert-danger { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }
        .profile-stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-bottom: 20px; }
        .profile-stat { background: #f9fafb; padding: 16px; border-radius: 12px; text-align: center; border: 1px solid #e5e7eb; }
        .profile-stat .number { font-size: 28px; font-weight: 700; color: #1f2937; }
        .profile-stat .label { font-size: 12px; color: #6b7280; }
        .fm-field input:focus, .fm-field textarea:focus { border-color: #6366f1; outline: none; box-shadow: 0 0 0 3px rgba(99,102,241,0.1); }
        @media (max-width: 768px) { .fm-form-grid { grid-template-columns: 1fr; } .profile-stats { grid-template-columns: 1fr; } }
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
                <a href="profile.php" class="menu-item active"><i class="fa-solid fa-user"></i> Profile</a>
                <a href="settings.php" class="menu-item"><i class="fa-solid fa-gear"></i> Settings</a>
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
                <div class="fm-hero-eyebrow">You</div>
                <h1>Profile</h1>
                <p>Update your profile information.</p>
            </div>

            <?php if ($error): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php endif; ?>
            <?php if ($success): ?><div class="alert alert-success"><?php echo $success; ?></div><?php endif; ?>

            <div class="profile-stats">
                <div class="profile-stat"><div class="number"><?php echo $totalApplications; ?></div><div class="label">Applications</div></div>
                <div class="profile-stat"><div class="number"><?php echo $acceptedApplications; ?></div><div class="label">Accepted</div></div>
                <div class="profile-stat"><div class="number">RM <?php echo number_format($totalEarnings, 2); ?></div><div class="label">Earnings</div></div>
            </div>

            <div class="fm-panel">
                <div class="fm-avatar-row">
                    <img src="https://api.dicebear.com/7.x/avataaars/svg?seed=<?php echo urlencode($user['name']); ?>" alt="">
                    <div class="fm-field" style="margin:0;flex:1;">
                        <label>Avatar URL</label>
                        <input class="fm-input" id="avatar-url" value="https://api.dicebear.com/7.x/avataaars/svg?seed=<?php echo urlencode($user['name']); ?>" disabled>
                    </div>
                </div>

                <form method="POST">
                    <div class="fm-form-grid">
                        <div class="fm-field">
                            <label>Full Name</label>
                            <input name="name" value="<?php echo escape($user['name']); ?>" required>
                        </div>
                        <div class="fm-field">
                            <label>Email</label>
                            <input name="email" type="email" value="<?php echo escape($user['email']); ?>" required>
                        </div>
                    </div>
                    <div class="fm-field">
                        <label>Phone</label>
                        <input name="phone" value="<?php echo escape($user['phone'] ?? ''); ?>" placeholder="+6012 345 6789">
                    </div>
                    <div class="fm-field">
                        <label>Bio</label>
                        <textarea name="bio" rows="3"><?php echo escape($user['bio'] ?? ''); ?></textarea>
                    </div>
                    <div class="fm-field">
                        <label>Skills (comma separated)</label>
                        <input name="skills" value="<?php echo escape($user['skills'] ?? ''); ?>" placeholder="HTML, CSS, JavaScript, Figma">
                    </div>
                    <button type="submit" name="update_profile" class="fm-btn"><i class="fa-regular fa-floppy-disk"></i> Save Changes</button>
                </form>
            </div>
        </main>
    </div>
</body>
</html>