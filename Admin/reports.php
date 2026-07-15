<?php
require_once '../config.php';

// Restrict page to logged in admins only
if (!isAdminLoggedIn()) {
     redirect('adminlogin.php');
}

// Overall platform stats
$totalUsers = getTotalUsers();
$totalFreelancers = getTotalFreelancers();
$totalClients = getTotalClients();
$totalJobs = $pdo->query("SELECT COUNT(*) FROM jobs")->fetchColumn();
$totalApplications = $pdo->query("SELECT COUNT(*) FROM applications")->fetchColumn();
$totalRevenue = $pdo->query("SELECT SUM(amount) FROM payments WHERE status = 'paid' AND amount > 0")->fetchColumn() ?? 0;

// Jobs grouped by category (for simple bar chart)
$jobsByCategory = $pdo->query("
    SELECT category, COUNT(*) as total
    FROM jobs
    GROUP BY category
    ORDER BY total DESC
")->fetchAll();
$maxCategoryCount = 0;
foreach ($jobsByCategory as $c) {
    if ($c['total'] > $maxCategoryCount) $maxCategoryCount = $c['total'];
}

// Applications grouped by status
$appsByStatus = $pdo->query("
    SELECT status, COUNT(*) as total
    FROM applications
    GROUP BY status
")->fetchAll();

// Recent reviews / ratings
$reviews = $pdo->query("
    SELECT r.*, u1.name as reviewer_name, u2.name as reviewee_name
    FROM reviews r
    JOIN users u1 ON r.reviewer_id = u1.id
    JOIN users u2 ON r.reviewee_id = u2.id
    ORDER BY r.created_at DESC
    LIMIT 10
")->fetchAll();

$avgRating = $pdo->query("SELECT AVG(rating) FROM reviews")->fetchColumn() ?? 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - Admin</title>
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

        /* Top stat cards */
        .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 28px; }
        .stat-card { background: #ffffff; padding: 20px 24px; border-radius: 16px; border: 1px solid #e5e7eb; }
        .stat-card .stat-label { font-size: 13px; font-weight: 500; color: #6b7280; }
        .stat-card .stat-number { font-size: 26px; font-weight: 800; color: #1f2937; margin-top: 4px; }

        /* Report sections */
        .report-split { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 28px; }
        .panel { background: #ffffff; border-radius: 16px; border: 1px solid #e5e7eb; padding: 24px; }
        .panel h3 { font-size: 16px; font-weight: 700; margin-bottom: 20px; }

        /* Simple bar chart for categories */
        .bar-row { margin-bottom: 14px; }
        .bar-row-top { display: flex; justify-content: space-between; font-size: 13px; margin-bottom: 6px; }
        .bar-track { width: 100%; height: 10px; background: #f3f4f6; border-radius: 6px; overflow: hidden; }
        .bar-fill { height: 100%; background: #6366f1; border-radius: 6px; }

        /* Status pill summary */
        .status-summary { display: flex; flex-direction: column; gap: 12px; }
        .status-summary-row { display: flex; justify-content: space-between; align-items: center; padding: 10px 14px; background: #f9fafb; border-radius: 10px; }
        .status-summary-row .lbl { font-size: 13px; font-weight: 600; text-transform: capitalize; }
        .status-summary-row .val { font-size: 14px; font-weight: 700; color: #6366f1; }

        /* Reviews table */
        .review-table { width: 100%; border-collapse: collapse; font-size: 13px; }
        .review-table th { text-align: left; padding: 10px 8px; border-bottom: 2px solid #e5e7eb; color: #6b7280; font-size: 12px; text-transform: uppercase; }
        .review-table td { padding: 12px 8px; border-bottom: 1px solid #f3f4f6; }
        .stars { color: #f59e0b; }
        .fm-empty { text-align: center; padding: 30px; color: #6b7280; }

        @media (max-width: 1024px) { .stats-grid { grid-template-columns: repeat(2, 1fr); } .report-split { grid-template-columns: 1fr; } }
        @media (max-width: 768px) { .sidebar { width: 200px; padding: 16px 12px; } .main-content { padding: 20px; } .stats-grid { grid-template-columns: 1fr; } }
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
                <a href="reports.php" class="active"><i class="fa-solid fa-chart-pie"></i> Reports</a>
                <a href="messages.php"><i class="fa-solid fa-comment-dots"></i> Messages</a>
                <a href="settings.php"><i class="fa-solid fa-gear"></i> Settings</a>
                <a href="profile.php"><i class="fa-solid fa-user"></i> Profile</a>
                <a href="adminlogin.php?logout=1" class="logout" onclick="return confirm('Are you sure you want to logout?')">
                    <i class="fa-solid fa-right-from-bracket"></i> Logout
                </a>
            </nav>
        </aside>

        <!-- Main content -->
        <main class="main-content">

            <div class="page-header">
                <h1>Reports</h1>
                <p>Platform performance overview and statistics.</p>
            </div>

            <!-- Top level stats -->
            <div class="stats-grid">
                <div class="stat-card"><div class="stat-label">Total Users</div><div class="stat-number"><?php echo $totalUsers; ?></div></div>
                <div class="stat-card"><div class="stat-label">Total Jobs</div><div class="stat-number"><?php echo $totalJobs; ?></div></div>
                <div class="stat-card"><div class="stat-label">Total Applications</div><div class="stat-number"><?php echo $totalApplications; ?></div></div>
                <div class="stat-card"><div class="stat-label">Total Revenue</div><div class="stat-number">RM <?php echo number_format($totalRevenue, 2); ?></div></div>
            </div>

            <div class="report-split">

                <!-- Jobs by category bar chart -->
                <div class="panel">
                    <h3>Jobs by Category</h3>
                    <?php if (count($jobsByCategory) > 0): ?>
                        <?php foreach ($jobsByCategory as $c): ?>
                        <div class="bar-row">
                            <div class="bar-row-top">
                                <span><?php echo escape($c['category']); ?></span>
                                <span><?php echo $c['total']; ?></span>
                            </div>
                            <div class="bar-track">
                                <div class="bar-fill" style="width: <?php echo $maxCategoryCount > 0 ? round(($c['total'] / $maxCategoryCount) * 100) : 0; ?>%"></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="fm-empty">No job data yet.</div>
                    <?php endif; ?>
                </div>

                <!-- Applications by status -->
                <div class="panel">
                    <h3>Applications by Status</h3>
                    <?php if (count($appsByStatus) > 0): ?>
                        <div class="status-summary">
                            <?php foreach ($appsByStatus as $s): ?>
                            <div class="status-summary-row">
                                <span class="lbl"><?php echo str_replace('_', ' ', $s['status']); ?></span>
                                <span class="val"><?php echo $s['total']; ?></span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="fm-empty">No application data yet.</div>
                    <?php endif; ?>
                </div>

            </div>

            <!-- Recent reviews -->
            <div class="panel">
                <h3>Recent Reviews (Average Rating: <?php echo number_format($avgRating, 1); ?> / 5)</h3>
                <?php if (count($reviews) > 0): ?>
                <table class="review-table">
                    <thead>
                        <tr><th>Reviewer</th><th>Reviewee</th><th>Rating</th><th>Comment</th><th>Date</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reviews as $r): ?>
                        <tr>
                            <td><?php echo escape($r['reviewer_name']); ?></td>
                            <td><?php echo escape($r['reviewee_name']); ?></td>
                            <td class="stars"><?php echo str_repeat('★', $r['rating']) . str_repeat('☆', 5 - $r['rating']); ?></td>
                            <td><?php echo escape(substr($r['comment'] ?? '', 0, 60)); ?></td>
                            <td><?php echo date('d M Y', strtotime($r['created_at'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                    <div class="fm-empty">No reviews yet.</div>
                <?php endif; ?>
            </div>

        </main>
    </div>

</body>
</html>
