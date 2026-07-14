<?php
require_once '../config.php';

if (!isLoggedIn() || !isClient()) {
    redirect('../index.php');
}

$user_id = $_SESSION['user_id'];

// Get saved freelancers
$saved = $pdo->query("
    SELECT u.id, u.name, u.email
    FROM saved_freelancers s
    JOIN users u ON s.freelancer_id = u.id
    WHERE s.client_id = $user_id
    ORDER BY s.created_at DESC
")->fetchAll();

// Save freelancer
if (isset($_GET['save']) && is_numeric($_GET['save'])) {
    $freelancer_id = $_GET['save'];
    try {
        $stmt = $pdo->prepare("INSERT INTO saved_freelancers (client_id, freelancer_id) VALUES (?, ?)");
        $stmt->execute([$user_id, $freelancer_id]);
        redirect('savedLancer_client.php?success=saved');
    } catch (PDOException $e) {
        redirect('savedLancer_client.php?error=exists');
    }
}

// Remove saved
if (isset($_GET['remove']) && is_numeric($_GET['remove'])) {
    $stmt = $pdo->prepare("DELETE FROM saved_freelancers WHERE client_id = ? AND freelancer_id = ?");
    $stmt->execute([$user_id, $_GET['remove']]);
    redirect('savedLancer_client.php?success=removed');
}

// Get all freelancers (to add)
$allFreelancers = $pdo->query("SELECT id, name FROM users WHERE role = 'freelancer' ORDER BY name")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Saved Freelancers</title>
    <link rel="stylesheet" href="client-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        .saved-list-panel { padding: 28px; display: flex; flex-direction: column; gap: 24px; }
        .saved-freelancer-list { display: flex; flex-direction: column; gap: 16px; }
        .saved-freelancer-card { display: flex; justify-content: space-between; align-items: center; gap: 18px; padding: 22px; background: #fff; border: 1px solid #e5e7eb; border-radius: 22px; }
        .freelancer-card-left { display: flex; align-items: center; gap: 18px; min-width: 0; }
        .avatar-circle { width: 68px; height: 68px; border-radius: 50%; object-fit: cover; flex-shrink: 0; background: #eef2ff; }
        .freelancer-details { display: flex; flex-direction: column; gap: 10px; min-width: 0; }
        .freelancer-name { font-size: 17px; font-weight: 700; }
        .action-buttons { display: flex; gap: 10px; flex-shrink: 0; }
        .btn-secondary { background: #eef2ff; color: #4338ca; border: none; border-radius: 12px; padding: 12px 18px; font-weight: 700; cursor: pointer; }
        .btn-secondary:hover { background: #dbeafe; }
        .btn-primary { background: #6366f1; color: white; border: none; border-radius: 12px; padding: 12px 18px; font-weight: 700; cursor: pointer; }
        .btn-primary:hover { background: #4f46e5; }
        .btn-danger { background: #fee2e2; color: #dc2626; border: none; border-radius: 12px; padding: 12px 18px; font-weight: 700; cursor: pointer; }
        .btn-danger:hover { background: #fecaca; }
        .alert { padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; }
        .alert-success { background: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0; }
        .alert-danger { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }
        .panel-header-row { display: flex; justify-content: space-between; align-items: center; gap: 18px; flex-wrap: wrap; }
        .panel-subtitle-text { font-size: 13px; color: #6b7280; margin-top: 6px; }
        .profile-search-bar { display: flex; align-items: center; gap: 10px; background: #f3f0ff; border: 1px solid #e5e7eb; border-radius: 14px; padding: 12px 16px; width: min(100%, 320px); }
        .profile-search-bar input { border: none; outline: none; background: transparent; width: 100%; font-size: 14px; }
        .add-freelancer-select { padding: 8px 14px; border: 1px solid #e5e7eb; border-radius: 10px; font-size: 14px; background: white; }
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
                <a href="savedLancer_client.php" class="menu-item active"><i class="fa-solid fa-star"></i> Saved Freelancers</a>
                <a href="review_client.php" class="menu-item"><i class="fa-solid fa-star-half-stroke"></i> Reviews</a>
                <a href="profile_client.php" class="menu-item"><i class="fa-solid fa-user"></i> Profile</a>
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

            <section class="workspace-panel-card saved-list-panel">
                <div class="panel-header-row">
                    <div>
                        <h3 class="panel-title-text">Saved Freelancers</h3>
                        <p class="panel-subtitle-text">Bookmark freelancers for easy hiring</p>
                    </div>
                    <div style="display:flex;gap:12px;align-items:center;">
                        <form method="GET" style="display:flex;gap:8px;align-items:center;">
                            <select name="save" class="add-freelancer-select">
                                <option value="">Add freelancer...</option>
                                <?php 
                                $saved_ids = array_column($saved, 'id');
                                foreach ($allFreelancers as $f): 
                                    if (!in_array($f['id'], $saved_ids)):
                                ?>
                                    <option value="<?php echo $f['id']; ?>"><?php echo escape($f['name']); ?></option>
                                <?php endif; endforeach; ?>
                            </select>
                            <button type="submit" class="btn-primary" style="padding:10px 16px;">Add</button>
                        </form>
                    </div>
                </div>

                <?php if (isset($_GET['success']) && $_GET['success'] == 'saved'): ?>
                    <div class="alert alert-success">✅ Freelancer saved successfully!</div>
                <?php endif; ?>
                <?php if (isset($_GET['success']) && $_GET['success'] == 'removed'): ?>
                    <div class="alert alert-success">✅ Freelancer removed from saved!</div>
                <?php endif; ?>
                <?php if (isset($_GET['error']) && $_GET['error'] == 'exists'): ?>
                    <div class="alert alert-danger">⚠️ Freelancer already saved!</div>
                <?php endif; ?>

                <div class="saved-freelancer-list">
                    <?php foreach ($saved as $f): ?>
                    <article class="saved-freelancer-card">
                        <div class="freelancer-card-left">
                            <img src="https://api.dicebear.com/7.x/avataaars/svg?seed=<?php echo urlencode($f['name']); ?>" alt="" class="avatar-circle">
                            <div class="freelancer-details">
                                <div class="freelancer-title-row">
                                    <h4 class="freelancer-name"><?php echo escape($f['name']); ?></h4>
                                </div>
                                <span class="freelancer-role"><?php echo escape($f['email']); ?></span>
                            </div>
                        </div>
                        <div class="action-buttons">
                            <a href="message_client.php" class="btn-secondary"><i class="fa-regular fa-comment"></i> Message</a>
                            <a href="savedLancer_client.php?remove=<?php echo $f['id']; ?>" class="btn-danger" onclick="return confirm('Remove from saved?')"><i class="fa-regular fa-bookmark"></i> Remove</a>
                            <a href="#" class="btn-primary" onclick="alert('Hire feature coming soon!')"><i class="fa-solid fa-handshake"></i> Hire Now</a>
                        </div>
                    </article>
                    <?php endforeach; ?>
                </div>
            </section>
        </main>
    </div>
</body>
</html>