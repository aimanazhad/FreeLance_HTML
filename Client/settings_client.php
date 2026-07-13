<?php
require_once '../config.php';

if (!isLoggedIn() || !isClient()) {
    redirect('../index.php');
}

$error = '';
$success = '';

if (isset($_POST['save_settings'])) {
    $display_name = trim($_POST['display_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    
    if (empty($display_name) || empty($email)) {
        $error = '⚠️ Name and email are required.';
    } else {
        $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, phone = ? WHERE id = ?");
        if ($stmt->execute([$display_name, $email, $phone, $_SESSION['user_id']])) {
            $_SESSION['name'] = $display_name;
            $_SESSION['email'] = $email;
            $success = '✅ Settings saved successfully!';
        }
    }
}

$user = getUserById($_SESSION['user_id']);
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
        .form-grid { display: grid; gap: 24px; }
        .panel-form-card { background: #fff; border-radius: 20px; padding: 24px; box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04); }
        .field-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        .field-row.single { grid-template-columns: 1fr; }
        label.input-label { display: block; margin-bottom: 10px; font-weight: 700; font-size: 13px; color: #111827; }
        input { width: 100%; border: 1px solid #d1d5db; border-radius: 14px; padding: 14px 16px; font-size: 14px; color: #111827; background: #f8fafc; }
        input:focus { border-color: #6366f1; outline: none; }
        .checkbox-group { display: flex; gap: 28px; margin-top: 12px; }
        .radio-label { display: inline-flex; align-items: center; gap: 10px; cursor: pointer; font-weight: 600; color: #111827; }
        .radio-label input { accent-color: #7c3aed; }
        .button-group { display: flex; gap: 12px; justify-content: flex-end; margin-top: 20px; }
        .btn-secondary { background: transparent; border: 1px solid #c7d2fe; color: #4338ca; padding: 14px 26px; border-radius: 999px; cursor: pointer; font-weight: 700; }
        .btn-primary { background: #7c3aed; color: #fff; border: none; padding: 14px 26px; border-radius: 999px; cursor: pointer; font-weight: 700; }
        .alert { padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; }
        .alert-success { background: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0; }
        .alert-danger { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }
        .workspace-panel-card { background: #fff; border-radius: 20px; padding: 24px; box-shadow: 0 2px 8px rgba(15,23,42,0.06); }
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
                <a href="payment_client.php" class="menu-item"><i class="fa-solid fa-credit-card"></i> Payment</a>
                <a href="savedLancer_client.php" class="menu-item"><i class="fa-solid fa-star"></i> Saved Freelancers</a>
                <a href="review_client.php" class="menu-item"><i class="fa-solid fa-star-half-stroke"></i> Review</a>
                <a href="profile_client.php" class="menu-item"><i class="fa-solid fa-user"></i> Profile</a>
                <a href="settings_client.php" class="menu-item active"><i class="fa-solid fa-gear"></i> Settings</a>
                <a href="../index.php?logout=1" class="menu-item" style="margin-top: 20px; border-top: 1px solid var(--border-line); padding-top: 16px;"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
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

            <section class="workspace-panel-card">
                <div class="panel-header-row">
                    <h3 class="panel-title-text">Account Settings</h3>
                    <p class="panel-subtitle-text">Update your account preferences and profile settings.</p>
                </div>

                <?php if ($error): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php endif; ?>
                <?php if ($success): ?><div class="alert alert-success"><?php echo $success; ?></div><?php endif; ?>

                <div class="form-grid">
                    <div class="panel-form-card">
                        <form method="POST">
                            <div class="field-row single">
                                <div>
                                    <label class="input-label" for="displayName">Display Name</label>
                                    <input id="displayName" name="display_name" type="text" value="<?php echo escape($user['name']); ?>">
                                </div>
                            </div>
                            <div class="field-row two" style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                                <div>
                                    <label class="input-label" for="emailAddress">Email</label>
                                    <input id="emailAddress" name="email" type="text" value="<?php echo escape($user['email']); ?>">
                                </div>
                                <div>
                                    <label class="input-label" for="phoneNumber">Phone</label>
                                    <input id="phoneNumber" name="phone" type="text" value="<?php echo escape($user['phone'] ?? ''); ?>" placeholder="012-3456789">
                                </div>
                            </div>
                            <div class="field-row single">
                                <div>
                                    <label class="input-label">Notifications</label>
                                    <div class="checkbox-group">
                                        <label class="radio-label"><input type="checkbox" checked> Email alerts</label>
                                        <label class="radio-label"><input type="checkbox"> SMS updates</label>
                                    </div>
                                </div>
                            </div>
                            <div class="button-group">
                                <button type="reset" class="btn-secondary">Cancel</button>
                                <button type="submit" name="save_settings" class="btn-primary">Save Changes</button>
                            </div>
                        </form>
                    </div>
                </div>
            </section>
        </main>
    </div>
</body>
</html>