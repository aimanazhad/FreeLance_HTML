<?php
require_once '../config.php';

if (!isLoggedIn() || !isFreelancer()) {
    redirect('../index.php');
}

$user_id = $_SESSION['user_id'];

// Get all applications (FIXED: Removed broken ORDER BY clause causing SQL error)
$applications = $pdo->query("
    SELECT a.*, j.title as job_title, j.category, u.name as client_name 
    FROM applications a
    JOIN jobs j ON a.job_id = j.id
    JOIN users u ON j.client_id = u.id
    WHERE a.freelancer_id = $user_id
")->fetchAll();

// Withdraw application
if (isset($_GET['withdraw']) && is_numeric($_GET['withdraw'])) {
    $stmt = $pdo->prepare("DELETE FROM applications WHERE id = ? AND freelancer_id = ?");
    $stmt->execute([$_GET['withdraw'], $user_id]);
    redirect('my_applications.php?success=withdrawn');
}

// Get status counts
$pending = $pdo->query("SELECT COUNT(*) FROM applications WHERE freelancer_id = $user_id AND status = 'pending'")->fetchColumn();
$underReview = $pdo->query("SELECT COUNT(*) FROM applications WHERE freelancer_id = $user_id AND status = 'under_review'")->fetchColumn();
$accepted = $pdo->query("SELECT COUNT(*) FROM applications WHERE freelancer_id = $user_id AND status = 'accepted'")->fetchColumn();
$rejected = $pdo->query("SELECT COUNT(*) FROM applications WHERE freelancer_id = $user_id AND status = 'rejected'")->fetchColumn();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Applications - Freelancer</title>
    <!-- FIXED: Added ../ path to point to the correct CSS folder location -->
    <link rel="stylesheet" href="../freelancer.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        .fm-table { width: 100%; border-collapse: collapse; }
        .fm-table th { text-align: left; font-size: 12px; color: #6b7280; padding: 10px 8px; border-bottom: 1px solid #e5e7eb; }
        .fm-table td { padding: 14px 8px; border-bottom: 1px solid #e5e7eb; font-size: 13px; vertical-align: middle; }
        .fm-badge { padding: 4px 12px; border-radius: 999px; font-size: 11px; font-weight: 600; }
        .fm-badge-inprogress { background: #fdf3e0; color: #eab308; }
        .fm-badge-underreview { background: #e9f1fe; color: #3b82f6; }
        .fm-badge-rejected { background: #fceaea; color: #ef4444; }
        .fm-badge-accepted { background: #eaf6ef; color: #10b981; }
        .fm-badge-pending { background: #f3f4f6; color: #6b7280; }
        .btn-danger { background: #fee2e2; color: #dc2626; border: none; padding: 6px 12px; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 12px; }
        .btn-danger:hover { background: #fecaca; }
        .alert { padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; }
        .alert-success { background: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0; }
        .fm-empty { text-align: center; padding: 40px; color: #6b7280; }
        .fm-panel { background: #fff; border-radius: 16px; padding: 24px; }
        .stats-mini { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 20px; }
        .stat-card { background: white; padding: 16px; border-radius: 12px; border: 1px solid #e5e7eb; text-align: center; }
        .stat-number { font-size: 24px; font-weight: 700; color: #1f2937; }
        .stat-label { font-size: 12px; color: #6b7280; }
        .stat-number.pending { color: #6b7280; }
        .stat-number.under-review { color: #3b82f6; }
        .stat-number.accepted { color: #10b981; }
        .stat-number.rejected { color: #ef4444; }
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
                <a href="my_applications.php" class="menu-item active"><i class="fa-solid fa-file-lines"></i> My Applications</a>
                <a href="messages.php" class="menu-item"><i class="fa-solid fa-comment-dots"></i> Messages</a>
                <a href="portfolio.php" class="menu-item"><i class="fa-solid fa-folder-open"></i> Portfolio</a>
                <a href="earnings.php" class="menu-item"><i class="fa-solid fa-wallet"></i> Earnings</a>
                <a href="profile.php" class="menu-item"><i class="fa-solid fa-user"></i> Profile</a>
                <a href="settings_freelancer.php" class="menu-item"><i class="fa-solid fa-gear"></i> Settings</a>
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
                <div class="fm-hero-eyebrow">Track</div>
                <h1>My Applications</h1>
                <p>Status of all your job applications.</p>
            </div>

            <?php if (isset($_GET['success']) && $_GET['success'] == 'withdrawn'): ?>
                <div class="alert alert-success">✅ Application withdrawn successfully!</div>
            <?php endif; ?>

            <div class="stats-mini">
                <div class="stat-card"><div class="stat-number pending"><?php echo $pending; ?></div><div class="stat-label">Pending</div></div>
                <div class="stat-card"><div class="stat-number under-review"><?php echo $underReview; ?></div><div class="stat-label">Under Review</div></div>
                <div class="stat-card"><div class="stat-number accepted"><?php echo $accepted; ?></div><div class="stat-label">Accepted</div></div>
                <div class="stat-card"><div class="stat-number rejected"><?php echo $rejected; ?></div><div class="stat-label">Rejected</div></div>
            </div>

            <div class="fm-panel">
                <?php if (count($applications) > 0): ?>
                <table class="fm-table">
                    <thead>
                        <tr>
                            <th>Job</th>
                            <th>Client</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($applications as $app): ?>
                        <tr>
                            <td><strong><?php echo escape($app['job_title']); ?></strong></td>
                            <td><?php echo escape($app['client_name']); ?></td>
                            <!-- FIXED: Fallback to current time if date format column is empty -->
                            <td><?php echo isset($app['applied_at']) ? date('d M Y', strtotime($app['applied_at'])) : 'N/A'; ?></td>
                            <td><span class="fm-badge fm-badge-<?php echo $app['status']; ?>"><?php echo ucfirst(str_replace('_', ' ', $app['status'])); ?></span></td>
                            <td>
                                <?php if ($app['status'] == 'pending' || $app['status'] == 'under_review'): ?>
                                    <a href="my_applications.php?withdraw=<?php echo $app['id']; ?>" class="btn-danger" onclick="return confirm('Withdraw this application?')">Withdraw</a>
                                <?php else: ?>
                                    <span style="color:#6b7280;font-size:12px;">-</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                    <div class="fm-empty">No applications yet. Start browsing jobs!</div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>