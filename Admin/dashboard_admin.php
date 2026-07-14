<?php
require_once '../config.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('../index.php');
}

// Get stats from database
$totalUsers = getTotalUsers();
$totalFreelancers = getTotalFreelancers();
$totalClients = getTotalClients();
$totalJobs = $pdo->query("SELECT COUNT(*) FROM jobs")->fetchColumn();

$openJobs = $pdo->query("SELECT COUNT(*) FROM jobs WHERE status = 'active'")->fetchColumn();
$inProgressJobs = $pdo->query("SELECT COUNT(*) FROM jobs WHERE status = 'in_progress'")->fetchColumn();
$completedJobs = $pdo->query("SELECT COUNT(*) FROM jobs WHERE status = 'completed'")->fetchColumn();

$recentUsers = getRecentUsers(5);

// Recent activities
$activities = $pdo->query("
    (SELECT 'job' as type, u.name as user, j.title as detail, j.created_at 
     FROM jobs j JOIN users u ON j.client_id = u.id 
     ORDER BY j.created_at DESC LIMIT 2)
    UNION ALL
    (SELECT 'user' as type, name as user, 'registered as a freelancer' as detail, created_at 
     FROM users WHERE role = 'freelancer' ORDER BY created_at DESC LIMIT 1)
    UNION ALL
    (SELECT 'update' as type, name as user, 'updated their profile' as detail, created_at 
     FROM users ORDER BY created_at DESC LIMIT 1)
    UNION ALL
    (SELECT 'complete' as type, u.name as user, 'completed a project' as detail, j.updated_at 
     FROM jobs j JOIN users u ON j.client_id = u.id 
     WHERE j.status = 'completed' ORDER BY j.updated_at DESC LIMIT 1)
    ORDER BY created_at DESC LIMIT 5
")->fetchAll();

function getPercentage($value, $total) {
    return $total > 0 ? round(($value / $total) * 100) : 0;
}

// Calculate month-over-month growth trend for a given table/condition
function getMonthlyTrend($pdo, $table, $extraWhere = '') {
    $where = $extraWhere !== '' ? "AND $extraWhere" : '';
    $thisMonth = $pdo->query("SELECT COUNT(*) FROM $table WHERE created_at >= DATE_FORMAT(NOW(), '%Y-%m-01') $where")->fetchColumn();
    $lastMonth = $pdo->query("SELECT COUNT(*) FROM $table WHERE created_at >= DATE_FORMAT(NOW() - INTERVAL 1 MONTH, '%Y-%m-01') AND created_at < DATE_FORMAT(NOW(), '%Y-%m-01') $where")->fetchColumn();

    if ($lastMonth == 0) {
        $percent = $thisMonth > 0 ? 100 : 0;
    } else {
        $percent = round((($thisMonth - $lastMonth) / $lastMonth) * 100);
    }
    return ['percent' => $percent, 'up' => $percent >= 0];
}

$usersTrend = getMonthlyTrend($pdo, 'users');
$freelancersTrend = getMonthlyTrend($pdo, 'users', "role = 'freelancer'");
$clientsTrend = getMonthlyTrend($pdo, 'users', "role = 'client'");
$jobsTrend = getMonthlyTrend($pdo, 'jobs');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Freelance Marketplace</title>
    <link rel="stylesheet" href="admin-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body { background: #f3f0ff; color: #1f2937; min-height: 100vh; }
        .admin-container { display: flex; min-height: 100vh; }

        /* ===== SIDEBAR ===== */
        .sidebar {
            width: 240px;
            background: #ffffff;
            border-right: 1px solid #e5e7eb;
            padding: 24px 16px;
            flex-shrink: 0;
            position: sticky;
            top: 0;
            height: 100vh;
            overflow-y: auto;
        }
        .sidebar-brand {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 32px;
            padding: 0 8px;
        }
        .sidebar-brand .logo-icon { font-size: 28px; color: #6366f1; }
        .sidebar-brand .brand-title { font-size: 18px; font-weight: 700; color: #1f2937; }
        .sidebar-brand .brand-sub { font-size: 12px; color: #6b7280; display: block; margin-top: -2px; }
        .sidebar-menu { display: flex; flex-direction: column; gap: 4px; }
        .sidebar-menu a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 14px;
            border-radius: 10px;
            color: #6b7280;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.2s ease;
        }
        .sidebar-menu a:hover { background: #f5f3ff; color: #6366f1; }
        .sidebar-menu a.active { background: #eef2ff; color: #6366f1; font-weight: 600; }
        .sidebar-menu a i { width: 20px; font-size: 16px; }
        .sidebar-menu .logout {
            margin-top: 20px;
            border-top: 1px solid #e5e7eb;
            padding-top: 16px;
            color: #ef4444;
        }
        .sidebar-menu .logout:hover { background: #fef2f2; color: #dc2626; }

        /* ===== MAIN CONTENT ===== */
        .main-content { flex: 1; padding: 32px 40px 60px; overflow-y: auto; }
        .page-header { margin-bottom: 28px; }
        .page-header h1 { font-size: 28px; font-weight: 800; color: #1f2937; }
        .page-header p { color: #6b7280; font-size: 14px; margin-top: 4px; }

        /* ===== STATS GRID ===== */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 28px;
        }
        .stat-card {
            background: #ffffff;
            padding: 20px 24px;
            border-radius: 16px;
            border: 1px solid #e5e7eb;
            position: relative;
            transition: all 0.2s ease;
        }
        .stat-card:hover { transform: translateY(-2px); box-shadow: 0 4px 16px rgba(0,0,0,0.06); }
        .stat-card .stat-label { font-size: 13px; font-weight: 500; color: #6b7280; }
        .stat-card .stat-number { font-size: 28px; font-weight: 800; color: #1f2937; margin: 4px 0 6px; }
        .stat-card .stat-trend {
            font-size: 12px;
            font-weight: 600;
            color: #22c55e;
            display: flex;
            align-items: center;
            gap: 4px;
        }
        .stat-card .stat-trend i { font-size: 10px; }
        .stat-card .stat-trend .period { color: #6b7280; font-weight: 400; }
        .stat-card .stat-icon {
            position: absolute;
            right: 20px;
            top: 20px;
            width: 44px;
            height: 44px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }
        .icon-users { background: #f0fdf4; color: #22c55e; }
        .icon-freelancers { background: #f5f3ff; color: #8b5cf6; }
        .icon-clients { background: #fffbeb; color: #f59e0b; }
        .icon-jobs { background: #fdf2f8; color: #ec4899; }
        .stat-card .card-arrow { position: absolute; right: 20px; bottom: 20px; font-size: 12px; color: #d1d5db; }

        /* ===== WORKSPACE SPLIT ===== */
        .workspace-split {
            display: grid;
            grid-template-columns: 1.1fr 1.9fr;
            gap: 24px;
            margin-bottom: 28px;
        }
        .workspace-card {
            background: #ffffff;
            border-radius: 16px;
            border: 1px solid #e5e7eb;
            padding: 24px;
        }
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .card-header h3 { font-size: 16px; font-weight: 700; color: #1f2937; }
        .card-header a { font-size: 13px; color: #6366f1; font-weight: 600; text-decoration: none; }
        .card-header a:hover { text-decoration: underline; }

        /* ===== ACTIVITY ITEMS ===== */
        .activity-item {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 12px 0;
            border-bottom: 1px solid #f3f4f6;
        }
        .activity-item:last-child { border-bottom: none; }
        .activity-icon {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            flex-shrink: 0;
        }
        .icon-job { background: #fff7ed; color: #ea580c; }
        .icon-user { background: #eff6ff; color: #2563eb; }
        .icon-report { background: #fef2f2; color: #dc2626; }
        .icon-update { background: #f0fdf4; color: #16a34a; }
        .icon-complete { background: #ecfdf5; color: #059669; }
        .activity-details { flex: 1; }
        .activity-details .title { font-size: 13.5px; color: #1f2937; }
        .activity-details .title strong { font-weight: 600; }
        .activity-details .subtitle { display: block; font-size: 12px; color: #6b7280; font-style: italic; margin-top: 2px; }
        .activity-time { font-size: 12px; color: #9ca3af; white-space: nowrap; }

        /* ===== USER TABLE ===== */
        .table-wrapper { overflow-x: auto; }
        .user-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }
        .user-table thead th {
            text-align: left;
            padding: 10px 8px 12px;
            border-bottom: 2px solid #e5e7eb;
            color: #6b7280;
            font-weight: 600;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .user-table tbody td {
            padding: 12px 8px;
            border-bottom: 1px solid #f3f4f6;
            vertical-align: middle;
        }
        .user-table tbody tr:hover td { background: #fafafa; }
        .user-table tbody tr:last-child td { border-bottom: none; }
        .user-meta .name { font-weight: 600; color: #1f2937; }
        .user-meta .email { display: block; font-size: 12px; color: #9ca3af; margin-top: 2px; }
        .badge-role {
            padding: 3px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }
        .badge-freelancer { background: #f5f3ff; color: #8b5cf6; }
        .badge-client { background: #fffbeb; color: #f59e0b; }
        .badge-admin { background: #fef2f2; color: #ef4444; }
        .status-indicator {
            padding: 3px 12px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }
        .status-active { background: #f0fdf4; color: #22c55e; }
        .status-suspended { background: #fef2f2; color: #ef4444; }
        .action-btn { background: none; border: none; color: #9ca3af; cursor: pointer; padding: 4px 8px; border-radius: 6px; }
        .action-btn:hover { background: #f3f4f6; color: #1f2937; }

        /* ===== JOB OVERVIEW ===== */
        .job-overview-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
        }
        .job-progress {
            padding: 16px 20px;
            border-radius: 12px;
        }
        .job-progress .bar-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .job-progress .bar-label {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .job-progress .bar-icon {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
        }
        .job-progress .bar-title { font-size: 12px; color: #6b7280; display: block; }
        .job-progress .bar-value { font-size: 20px; font-weight: 700; color: #1f2937; }
        .job-progress .bar-percent { font-size: 14px; font-weight: 700; color: #6b7280; }
        .job-progress .progress-track {
            width: 100%;
            height: 6px;
            background: rgba(0,0,0,0.06);
            border-radius: 4px;
            margin-top: 12px;
            overflow: hidden;
        }
        .job-progress .progress-fill { height: 100%; border-radius: 4px; transition: width 0.6s ease; }
        .job-progress.bar-open { background: #e0f2fe; }
        .job-progress.bar-progress { background: #fef9c3; }
        .job-progress.bar-completed { background: #dcfce7; }
        .job-progress .box-open { background: #bae6fd; color: #0369a1; }
        .job-progress .box-progress { background: #fef08a; color: #a16207; }
        .job-progress .box-completed { background: #bbf7d0; color: #15803d; }
        .job-progress .fill-open { background: #0284c7; }
        .job-progress .fill-progress { background: #ca8a04; }
        .job-progress .fill-completed { background: #16a34a; }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 1024px) {
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
            .workspace-split { grid-template-columns: 1fr; }
            .job-overview-grid { grid-template-columns: 1fr 1fr; }
        }
        @media (max-width: 768px) {
            .sidebar { width: 200px; padding: 16px 12px; }
            .main-content { padding: 20px; }
            .stats-grid { grid-template-columns: 1fr; }
            .job-overview-grid { grid-template-columns: 1fr; }
            .stat-card .stat-icon { position: relative; right: auto; top: auto; margin-top: 12px; }
            .stat-card { display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; }
            .stat-card .card-arrow { display: none; }
        }
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

        <!-- ==========================================
        SIDEBAR
        ========================================== -->
        <aside class="sidebar">
            <div class="sidebar-brand">
                <i class="fa-solid fa-chart-line logo-icon"></i>
                <div>
                    <span class="brand-title">Freelance</span>
                    <span class="brand-sub">Marketplace</span>
                </div>
            </div>

            <nav class="sidebar-menu">
                <a href="dashboard_admin.php" class="active">
                    <i class="fa-solid fa-house"></i> Dashboard
                </a>
                <a href="manage_users.php">
                    <i class="fa-solid fa-users"></i> Manage Users
                </a>
                <a href="manage_jobs.php">
                    <i class="fa-solid fa-briefcase"></i> Manage Jobs
                </a>
                <a href="reports.php">
                    <i class="fa-solid fa-chart-pie"></i> Reports
                </a>
                <a href="messages.php">
                    <i class="fa-solid fa-comment-dots"></i> Messages
                </a>
                <a href="settings.php">
                    <i class="fa-solid fa-gear"></i> Settings
                </a>
                <a href="profile.php">
                    <i class="fa-solid fa-user"></i> Profile
                </a>
                <a href="adminlogin.php?logout=1" class="logout" onclick="return confirm('Are you sure you want to logout?')">
                    <i class="fa-solid fa-right-from-bracket"></i> Logout
                </a>
            </nav>
        </aside>

        <!-- ==========================================
        MAIN CONTENT
        ========================================== -->
        <main class="main-content">

            <!-- PAGE HEADER -->
            <div class="page-header">
                <h1>Admin Dashboard</h1>
                <p>Welcome back, <?php echo escape($_SESSION['name']); ?>! Here's what's happening on your platform today.</p>
            </div>

            <!-- ==========================================
            STATS GRID
            ========================================== -->
            <div class="stats-grid">

                <div class="stat-card">
                    <div class="stat-info">
                        <div class="stat-label">Total Users</div>
                        <div class="stat-number"><?php echo $totalUsers; ?></div>
                        <div class="stat-trend" style="color: <?php echo $usersTrend['up'] ? '#22c55e' : '#ef4444'; ?>">
                            <i class="fa-solid fa-arrow-<?php echo $usersTrend['up'] ? 'up' : 'down'; ?>"></i> <?php echo abs($usersTrend['percent']); ?>% <span class="period">from last month</span>
                        </div>
                    </div>
                    <div class="stat-icon icon-users">
                        <i class="fa-solid fa-user"></i>
                    </div>
                    <i class="fa-solid fa-arrow-right card-arrow"></i>
                </div>

                <div class="stat-card">
                    <div class="stat-info">
                        <div class="stat-label">Total Freelancers</div>
                        <div class="stat-number"><?php echo $totalFreelancers; ?></div>
                        <div class="stat-trend" style="color: <?php echo $freelancersTrend['up'] ? '#22c55e' : '#ef4444'; ?>">
                            <i class="fa-solid fa-arrow-<?php echo $freelancersTrend['up'] ? 'up' : 'down'; ?>"></i> <?php echo abs($freelancersTrend['percent']); ?>% <span class="period">from last month</span>
                        </div>
                    </div>
                    <div class="stat-icon icon-freelancers">
                        <i class="fa-solid fa-user-tie"></i>
                    </div>
                    <i class="fa-solid fa-arrow-right card-arrow"></i>
                </div>

                <div class="stat-card">
                    <div class="stat-info">
                        <div class="stat-label">Total Clients</div>
                        <div class="stat-number"><?php echo $totalClients; ?></div>
                        <div class="stat-trend" style="color: <?php echo $clientsTrend['up'] ? '#22c55e' : '#ef4444'; ?>">
                            <i class="fa-solid fa-arrow-<?php echo $clientsTrend['up'] ? 'up' : 'down'; ?>"></i> <?php echo abs($clientsTrend['percent']); ?>% <span class="period">from last month</span>
                        </div>
                    </div>
                    <div class="stat-icon icon-clients">
                        <i class="fa-solid fa-handshake"></i>
                    </div>
                    <i class="fa-solid fa-arrow-right card-arrow"></i>
                </div>

                <div class="stat-card">
                    <div class="stat-info">
                        <div class="stat-label">Total Jobs Posted</div>
                        <div class="stat-number"><?php echo $totalJobs; ?></div>
                        <div class="stat-trend" style="color: <?php echo $jobsTrend['up'] ? '#22c55e' : '#ef4444'; ?>">
                            <i class="fa-solid fa-arrow-<?php echo $jobsTrend['up'] ? 'up' : 'down'; ?>"></i> <?php echo abs($jobsTrend['percent']); ?>% <span class="period">from last month</span>
                        </div>
                    </div>
                    <div class="stat-icon icon-jobs">
                        <i class="fa-solid fa-file-invoice"></i>
                    </div>
                    <i class="fa-solid fa-arrow-right card-arrow"></i>
                </div>

            </div>

            <!-- ==========================================
            WORKSPACE SPLIT
            ========================================== -->
            <div class="workspace-split">

                <!-- Recent Activities -->
                <div class="workspace-card">
                    <div class="card-header">
                        <h3>Recent Activities</h3>
                        <a href="activity_log.php">View All</a>
                    </div>

                    <?php foreach ($activities as $activity): ?>
                    <div class="activity-item">
                        <div class="activity-icon <?php 
                            if ($activity['type'] == 'job') echo 'icon-job';
                            elseif ($activity['type'] == 'user') echo 'icon-user';
                            elseif ($activity['type'] == 'report') echo 'icon-report';
                            elseif ($activity['type'] == 'update') echo 'icon-update';
                            else echo 'icon-complete';
                        ?>">
                            <i class="fa-solid <?php 
                                if ($activity['type'] == 'job') echo 'fa-pen-nib';
                                elseif ($activity['type'] == 'user') echo 'fa-user-plus';
                                elseif ($activity['type'] == 'report') echo 'fa-triangle-exclamation';
                                elseif ($activity['type'] == 'update') echo 'fa-user-pen';
                                else echo 'fa-square-check';
                            ?>"></i>
                        </div>
                        <div class="activity-details">
                            <p class="title">
                                <strong><?php echo escape($activity['user']); ?></strong> 
                                <?php 
                                    if ($activity['type'] == 'job') echo 'posted a new job';
                                    elseif ($activity['type'] == 'user') echo 'registered as a freelancer';
                                    elseif ($activity['type'] == 'report') echo 'reported a job post';
                                    elseif ($activity['type'] == 'update') echo 'updated their profile';
                                    else echo 'completed a project';
                                ?>
                            </p>
                            <?php if (isset($activity['detail']) && $activity['type'] != 'update' && $activity['type'] != 'user'): ?>
                                <span class="subtitle">"<?php echo escape($activity['detail']); ?>"</span>
                            <?php endif; ?>
                        </div>
                        <span class="activity-time">
                            <?php 
                                $diff = time() - strtotime($activity['created_at']);
                                if ($diff < 60) echo $diff . ' secs ago';
                                elseif ($diff < 3600) echo floor($diff/60) . ' mins ago';
                                elseif ($diff < 86400) echo floor($diff/3600) . ' hours ago';
                                else echo date('d M Y', strtotime($activity['created_at']));
                            ?>
                        </span>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- User Management Table -->
                <div class="workspace-card">
                    <div class="card-header">
                        <h3>User Management</h3>
                        <a href="manage_users.php">View All</a>
                    </div>

                    <div class="table-wrapper">
                        <table class="user-table">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Role</th>
                                    <th>Status</th>
                                    <th>Joined Date</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentUsers as $user): ?>
                                <tr>
                                    <td>
                                        <div class="user-meta">
                                            <span class="name"><?php echo escape($user['name']); ?></span>
                                            <span class="email"><?php echo escape($user['email']); ?></span>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge-role badge-<?php echo $user['role']; ?>">
                                            <?php echo ucfirst($user['role']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="status-indicator status-<?php echo $user['status'] ?? 'active'; ?>">
                                            <?php echo ucfirst($user['status'] ?? 'Active'); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('d M Y', strtotime($user['created_at'])); ?></td>
                                    <td>
                                        <button class="action-btn"><i class="fa-solid fa-ellipsis-vertical"></i></button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>

            <!-- ==========================================
            JOB OVERVIEW
            ========================================== -->
            <div class="workspace-card">
                <div class="card-header">
                    <h3>Job Overview</h3>
                </div>

                <div class="job-overview-grid">
                    <div class="job-progress bar-open">
                        <div class="bar-info">
                            <div class="bar-label">
                                <div class="bar-icon box-open"><i class="fa-solid fa-folder-open"></i></div>
                                <div>
                                    <span class="bar-title">Open Jobs</span>
                                    <h4 class="bar-value"><?php echo $openJobs; ?></h4>
                                </div>
                            </div>
                            <span class="bar-percent"><?php echo getPercentage($openJobs, $totalJobs); ?>%</span>
                        </div>
                        <div class="progress-track">
                            <div class="progress-fill fill-open" style="width: <?php echo getPercentage($openJobs, $totalJobs); ?>%"></div>
                        </div>
                    </div>

                    <div class="job-progress bar-progress">
                        <div class="bar-info">
                            <div class="bar-label">
                                <div class="bar-icon box-progress"><i class="fa-solid fa-clock"></i></div>
                                <div>
                                    <span class="bar-title">In Progress</span>
                                    <h4 class="bar-value"><?php echo $inProgressJobs; ?></h4>
                                </div>
                            </div>
                            <span class="bar-percent"><?php echo getPercentage($inProgressJobs, $totalJobs); ?>%</span>
                        </div>
                        <div class="progress-track">
                            <div class="progress-fill fill-progress" style="width: <?php echo getPercentage($inProgressJobs, $totalJobs); ?>%"></div>
                        </div>
                    </div>

                    <div class="job-progress bar-completed">
                        <div class="bar-info">
                            <div class="bar-label">
                                <div class="bar-icon box-completed"><i class="fa-solid fa-circle-check"></i></div>
                                <div>
                                    <span class="bar-title">Completed</span>
                                    <h4 class="bar-value"><?php echo $completedJobs; ?></h4>
                                </div>
                            </div>
                            <span class="bar-percent"><?php echo getPercentage($completedJobs, $totalJobs); ?>%</span>
                        </div>
                        <div class="progress-track">
                            <div class="progress-fill fill-completed" style="width: <?php echo getPercentage($completedJobs, $totalJobs); ?>%"></div>
                        </div>
                    </div>
                </div>
            </div>

        </main>
    </div>

</body>
</html>