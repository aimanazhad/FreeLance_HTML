<?php
require_once 'config.php';

if (!isLoggedIn() || !isFreelancer()) {
    redirect('index.php');
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

$totalApplications = $pdo->query("SELECT COUNT(*) FROM applications WHERE freelancer_id = ".$_SESSION['user_id'])->fetchColumn();
$totalEarnings = $pdo->query("SELECT SUM(amount) FROM payments WHERE freelancer_id = ".$_SESSION['user_id']." AND status = 'paid'")->fetchColumn() ?? 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile</title>
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

        .profile-stats {
            display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px; margin-bottom: 20px;
        }
        .profile-stat {
            background: white; padding: 16px; border-radius: 12px; border: 1px solid #e5e7eb; text-align: center;
        }
        .profile-stat .number { font-size: 28px; font-weight: 700; color: #0f172a; }
        .profile-stat .label { font-size: 13px; color: #94a3b8; }

        .panel {
            background: white; border-radius: 12px; border: 1px solid #e5e7eb; padding: 24px;
        }
        .panel .avatar-row {
            display: flex; align-items: center; gap: 16px; margin-bottom: 20px;
        }
        .panel .avatar-row img { width: 80px; height: 80px; border-radius: 50%; object-fit: cover; border: 3px solid #eef2ff; }
        .panel .avatar-row .info h4 { font-size: 16px; font-weight: 700; color: #0f172a; }
        .panel .avatar-row .info p { font-size: 14px; color: #94a3b8; }

        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        .form-group {
            display: flex; flex-direction: column; gap: 6px; margin-bottom: 16px;
        }
        .form-group label { font-size: 13px; font-weight: 600; color: #6b7280; }
        .form-group input, .form-group textarea {
            border: 1px solid #e5e7eb; border-radius: 8px; padding: 10px 14px;
            font-family: inherit; font-size: 14px; width: 100%; background: #f9fafb; outline: none; transition: all 0.2s ease;
        }
        .form-group input:focus, .form-group textarea:focus {
            border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99,102,241,0.1);
        }
        .form-group textarea { min-height: 80px; resize: vertical; }
        .btn-save {
            background: #6366f1; color: white; border: none; padding: 10px 24px; border-radius: 8px;
            font-weight: 600; cursor: pointer; width: 100%; transition: all 0.2s ease; font-size: 14px;
        }
        .btn-save:hover { background: #4f46e5; }

        .alert { padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; font-weight: 600; font-size: 14px; }
        .alert-success { background: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0; }
        .alert-danger { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }

        @media (max-width: 768px) {
            .sidebar { width: 200px; padding: 16px 12px; }
            .main-content { padding: 20px; }
            .profile-stats { grid-template-columns: 1fr; }
            .form-grid { grid-template-columns: 1fr; }
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
            <a href="profile.php" class="active"><i class="fa-solid fa-user"></i> Profile</a>
            <a href="settings_freelancer.php"><i class="fa-solid fa-gear"></i> Settings</a>
            <a href="index.php?logout=1" class="logout" onclick="return confirm('Are you sure you want to logout?')">
                <i class="fa-solid fa-right-from-bracket"></i> Log out
            </a>
        </nav>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="main-content">

        <div class="hero-banner">
            <div>
                <h1>👤 Profile</h1>
                <p>Update your profile information.</p>
            </div>
            <div class="emoji">📝</div>
        </div>

        <?php if ($error): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php endif; ?>
        <?php if ($success): ?><div class="alert alert-success"><?php echo $success; ?></div><?php endif; ?>

        <div class="profile-stats">
            <div class="profile-stat"><div class="number"><?php echo $totalApplications; ?></div><div class="label">Applications</div></div>
            <div class="profile-stat"><div class="number">RM <?php echo number_format($totalEarnings, 2); ?></div><div class="label">Earnings</div></div>
        </div>

        <div class="panel">
            <div class="avatar-row">
                <img src="https://api.dicebear.com/7.x/avataaars/svg?seed=<?php echo urlencode($user['name']); ?>" alt="">
                <div class="info">
                    <h4><?php echo escape($user['name']); ?></h4>
                    <p><?php echo escape($user['email']); ?></p>
                </div>
            </div>

            <form method="POST">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Full Name</label>
                        <input type="text" name="name" value="<?php echo escape($user['name']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" value="<?php echo escape($user['email']); ?>" required>
                    </div>
                </div>
                <div class="form-group">
                    <label>Phone</label>
                    <input type="text" name="phone" value="<?php echo escape($user['phone'] ?? ''); ?>" placeholder="+6012 345 6789">
                </div>
                <div class="form-group">
                    <label>Bio</label>
                    <textarea name="bio" rows="3"><?php echo escape($user['bio'] ?? ''); ?></textarea>
                </div>
                <div class="form-group">
                    <label>Skills (comma separated)</label>
                    <input type="text" name="skills" value="<?php echo escape($user['skills'] ?? ''); ?>" placeholder="HTML, CSS, JavaScript, Figma">
                </div>
                <button type="submit" name="update_profile" class="btn-save">
                    <i class="fa-regular fa-floppy-disk"></i> Save Changes
                </button>
            </form>
        </div>

    </main>
</div>

</body>
</html>