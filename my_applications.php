<?php
require_once 'config.php';

if (!isLoggedIn() || !isFreelancer()) {
    redirect('index.php');
}

$user_id = $_SESSION['user_id'];

// Get all applications with job and client details
$applications = $pdo->query("
    SELECT a.*, 
           j.title as job_title, 
           j.category,
           j.budget_min,
           j.budget_max,
           j.status as job_status,
           u.name as client_name,
           u.email as client_email
    FROM applications a
    JOIN jobs j ON a.job_id = j.id
    JOIN users u ON j.client_id = u.id
    WHERE a.freelancer_id = $user_id
    ORDER BY a.applied_at DESC
")->fetchAll();

// Withdraw application (only if pending)
if (isset($_GET['withdraw']) && is_numeric($_GET['withdraw'])) {
    $app_id = $_GET['withdraw'];
    $stmt = $pdo->prepare("DELETE FROM applications WHERE id = ? AND freelancer_id = ? AND status = 'pending'");
    $stmt->execute([$app_id, $user_id]);
    redirect('my_applications.php?success=withdrawn');
}

// Count by status
$pending = $pdo->query("SELECT COUNT(*) FROM applications WHERE freelancer_id = $user_id AND status = 'pending'")->fetchColumn();
$accepted = $pdo->query("SELECT COUNT(*) FROM applications WHERE freelancer_id = $user_id AND status = 'accepted'")->fetchColumn();
$rejected = $pdo->query("SELECT COUNT(*) FROM applications WHERE freelancer_id = $user_id AND status = 'rejected'")->fetchColumn();
$inProgress = $pdo->query("SELECT COUNT(*) FROM applications WHERE freelancer_id = $user_id AND status = 'in_progress'")->fetchColumn();
$completed = $pdo->query("SELECT COUNT(*) FROM applications WHERE freelancer_id = $user_id AND status = 'completed'")->fetchColumn();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Applications</title>
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

        .stats-mini {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 16px;
            margin-bottom: 20px;
        }
        .stat-card {
            background: white; padding: 16px; border-radius: 12px; border: 1px solid #e5e7eb; text-align: center;
        }
        .stat-card .number { font-size: 24px; font-weight: 700; color: #0f172a; }
        .stat-card .label { font-size: 13px; color: #94a3b8; }
        .stat-card .pending { color: #6b7280; }
        .stat-card .accepted { color: #10b981; }
        .stat-card .rejected { color: #ef4444; }
        .stat-card .in-progress { color: #d97706; }
        .stat-card .completed { color: #3b82f6; }

        .panel {
            background: white;
            border-radius: 12px;
            border: 1px solid #e5e7eb;
            padding: 24px;
        }
        .panel h2 { font-size: 16px; font-weight: 700; color: #0f172a; margin-bottom: 16px; }
        .panel h2 i { color: #6366f1; }

        .app-card {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px 0;
            border-bottom: 1px solid #f1f5f9;
            gap: 16px;
            flex-wrap: wrap;
        }
        .app-card:last-child { border-bottom: none; }
        .app-info { flex: 1; min-width: 150px; }
        .app-info .job-title { font-weight: 700; font-size: 15px; color: #0f172a; }
        .app-info .client-name { font-size: 13px; color: #6b7280; }
        .app-info .budget { font-size: 13px; font-weight: 600; color: #0f172a; margin-top: 4px; }
        .app-status-badge {
            padding: 4px 12px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 600;
        }
        .app-status-badge.pending { background: #f3f4f6; color: #6b7280; }
        .app-status-badge.accepted { background: #d1fae5; color: #047857; }
        .app-status-badge.rejected { background: #fee2e2; color: #dc2626; }
        .app-status-badge.in_progress { background: #fef3c7; color: #d97706; }
        .app-status-badge.completed { background: #dbeafe; color: #1d4ed8; }
        
        .app-actions { display: flex; gap: 8px; }
        .btn-withdraw {
            background: #fee2e2; color: #dc2626; border: none; padding: 6px 14px;
            border-radius: 6px; font-weight: 600; font-size: 12px; cursor: pointer;
            text-decoration: none; display: inline-block;
        }
        .btn-withdraw:hover { background: #fecaca; }
        .btn-message {
            background: #eef2ff; color: #4338ca; border: none; padding: 6px 14px;
            border-radius: 6px; font-weight: 600; font-size: 12px; cursor: pointer;
            text-decoration: none; display: inline-block;
        }
        .btn-message:hover { background: #dbeafe; }

        .alert { padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; font-weight: 600; font-size: 14px; }
        .alert-success { background: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0; }
        .empty { text-align: center; padding: 40px; color: #94a3b8; }
        .empty i { font-size: 40px; color: #d1d5db; display: block; margin-bottom: 12px; }
        .empty p { font-size: 14px; }

        .job-status-pill {
            font-size: 11px;
            padding: 2px 10px;
            border-radius: 999px;
            font-weight: 600;
            background: #f3f4f6;
            color: #6b7280;
            display: inline-block;
        }
        .job-status-pill.active { background: #d1fae5; color: #047857; }
        .job-status-pill.completed { background: #dbeafe; color: #1d4ed8; }
        .job-status-pill.in_progress { background: #fef3c7; color: #d97706; }
        .job-status-pill.cancelled { background: #fee2e2; color: #dc2626; }

        @media (max-width: 1024px) {
            .stats-mini { grid-template-columns: repeat(3, 1fr); }
        }
        @media (max-width: 768px) {
            .sidebar { width: 200px; padding: 16px 12px; }
            .main-content { padding: 20px; }
            .stats-mini { grid-template-columns: 1fr 1fr; }
            .hero-banner .emoji { display: none; }
            .hero-banner h1 { font-size: 24px; }
            .hero-banner p { font-size: 14px; }
            .app-card { flex-direction: column; align-items: stretch; }
            .app-actions { justify-content: flex-end; }
        }
        @media (max-width: 480px) {
            .sidebar { width: 100%; height: auto; position: relative; border-right: none; border-bottom: 1px solid #e5e7eb; }
            .dashboard-container { flex-direction: column; }
            .sidebar-menu { flex-direction: row; flex-wrap: wrap; }
            .sidebar-menu a { padding: 8px 12px; font-size: 13px; }
            .sidebar-menu .logout { margin-top: 0; border-top: none; padding-top: 0; }
            .stats-mini { grid-template-columns: 1fr; }
            .hero-banner { padding: 24px; }
            .hero-banner h1 { font-size: 22px; }
        }
    </style>
</head>
<body>

<div class="dashboard-container">

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
            <a href="my_applications.php" class="active"><i class="fa-solid fa-file-lines"></i> My Applications</a>
            <a href="messages.php"><i class="fa-solid fa-comment-dots"></i> Messages</a>
            <a href="portfolio.php"><i class="fa-solid fa-folder-open"></i> Portfolio</a>
            <a href="earnings.php"><i class="fa-solid fa-wallet"></i> Earnings</a>
            <a href="profile.php"><i class="fa-solid fa-user"></i> Profile</a>
            <a href="settings_freelancer.php"><i class="fa-solid fa-gear"></i> Settings</a>
            <a href="index.php?logout=1" class="logout" onclick="return confirm('Are you sure you want to logout?')">
                <i class="fa-solid fa-right-from-bracket"></i> Log out
            </a>
        </nav>
    </aside>

    <main class="main-content">

        <div class="hero-banner">
            <div>
                <h1>📋 My Applications</h1>
                <p>Track the status of all your job applications.</p>
            </div>
            <div class="emoji">📊</div>
        </div>

        <?php if (isset($_GET['success']) && $_GET['success'] == 'withdrawn'): ?>
            <div class="alert alert-success">✅ Application withdrawn successfully!</div>
        <?php endif; ?>

        <div class="stats-mini">
            <div class="stat-card"><div class="number pending"><?php echo $pending; ?></div><div class="label">Pending</div></div>
            <div class="stat-card"><div class="number in-progress"><?php echo $inProgress; ?></div><div class="label">In Progress</div></div>
            <div class="stat-card"><div class="number accepted"><?php echo $accepted; ?></div><div class="label">Accepted</div></div>
            <div class="stat-card"><div class="number completed"><?php echo $completed; ?></div><div class="label">Completed</div></div>
            <div class="stat-card"><div class="number rejected"><?php echo $rejected; ?></div><div class="label">Rejected</div></div>
        </div>

        <div class="panel">
            <h2><i class="fa-regular fa-file-lines"></i> All Applications</h2>
            <?php if (count($applications) > 0): ?>
                <?php foreach ($applications as $app): ?>
                <div class="app-card">
                    <div class="app-info">
                        <div class="job-title"><?php echo escape($app['job_title']); ?></div>
                        <div class="client-name">Client: <?php echo escape($app['client_name']); ?></div>
                        <div class="budget">RM <?php echo number_format($app['budget_min'], 2); ?></div>
                        <div style="margin-top:4px;display:flex;gap:6px;flex-wrap:wrap;">
                            <span class="job-status-pill <?php echo $app['job_status']; ?>">
                                Job: <?php echo ucfirst(str_replace('_', ' ', $app['job_status'])); ?>
                            </span>
                            <span style="font-size:11px;color:#94a3b8;">Applied: <?php echo date('d M Y', strtotime($app['applied_at'])); ?></span>
                        </div>
                    </div>
                    <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
                        <span class="app-status-badge <?php echo $app['status']; ?>">
                            <?php echo ucfirst(str_replace('_', ' ', $app['status'])); ?>
                        </span>
                        <div class="app-actions">
                            <?php if ($app['status'] == 'pending'): ?>
                                <a href="my_applications.php?withdraw=<?php echo $app['id']; ?>" class="btn-withdraw" onclick="return confirm('Withdraw this application?')">Withdraw</a>
                            <?php endif; ?>
                            <?php if ($app['status'] == 'accepted' || $app['status'] == 'in_progress'): ?>
                                <a href="messages.php" class="btn-message"><i class="fa-regular fa-comment"></i> Message Client</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty">
                    <i class="fa-regular fa-file"></i>
                    <p>No applications yet. <a href="browse_jobs.php" style="color:#6366f1;font-weight:600;">Browse jobs</a> and apply now!</p>
                </div>
            <?php endif; ?>
        </div>

    </main>
</div>

</body>
</html>