<?php
require_once '../config.php';

if (!isLoggedIn() || !isFreelancer()) {
    redirect('../index.php');
}

$user_id = $_SESSION['user_id'];

// Generate CSRF Token if not already set (ensure this is configured in config.php)
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

try {
    // 1. Get Stats using secure Prepared Statements
    $stmtJobs = $pdo->prepare("SELECT COUNT(*) FROM jobs WHERE status = 'active'");
    $stmtJobs->execute();
    $totalJobs = $stmtJobs->fetchColumn();

    $stmtApps = $pdo->prepare("SELECT COUNT(*) FROM applications WHERE freelancer_id = :user_id");
    $stmtApps->execute(['user_id' => $user_id]);
    $applicationsSent = $stmtApps->fetchColumn();

    $stmtProgress = $pdo->prepare("SELECT COUNT(*) FROM applications WHERE freelancer_id = :user_id AND status = 'in_progress'");
    $stmtProgress->execute(['user_id' => $user_id]);
    $inProgress = $stmtProgress->fetchColumn();

    $stmtEarnings = $pdo->prepare("SELECT SUM(amount) FROM payments WHERE user_id = :user_id AND status = 'paid'");
    $stmtEarnings->execute(['user_id' => $user_id]);
    $totalEarnings = $stmtEarnings->fetchColumn() ?? 0;

    // 2. Get Recommended Jobs
    $stmtRec = $pdo->prepare("SELECT * FROM jobs WHERE status = 'active' ORDER BY created_at DESC LIMIT 4");
    $stmtRec->execute();
    $recommendedJobs = $stmtRec->fetchAll();

    // 3. Get Applications Status
    $stmtAppStatus = $pdo->prepare("
        SELECT a.*, j.title as job_title, u.name as client_name 
        FROM applications a
        JOIN jobs j ON a.job_id = j.id
        JOIN users u ON j.client_id = u.id
        WHERE a.freelancer_id = :user_id
        ORDER BY a.id DESC LIMIT 4
    ");
    $stmtAppStatus->execute(['user_id' => $user_id]);
    $applications = $stmtAppStatus->fetchAll();

} catch (PDOException $e) {
    // Log error securely and show a user-friendly message
    error_log("Dashboard query error: " . $e->getMessage());
    die("An error occurred while loading your dashboard. Please try again later.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Freelancer Dashboard</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* ============================================
           RESET & BASE
        ============================================ */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', sans-serif;
        }

        body {
            background: #f3f0ff;
            min-height: 100vh;
        }

        /* ============================================
           APP SHELL + SIDEBAR
        ============================================ */
        .app-shell {
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            width: 260px;
            background: #ffffff;
            border-right: 1px solid #e5e7eb;
            padding: 28px 18px;
            flex-shrink: 0;
            position: sticky;
            top: 0;
            height: 100vh;
            overflow-y: auto;
        }

        .sidebar .brand {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 28px;
            padding: 0 4px;
        }

        .sidebar .brand .logo {
            font-weight: 800;
            color: #1f2937;
            font-size: 18px;
        }

        .sidebar .brand .logo i {
            color: #6366f1;
        }

        .sidebar .brand .sub {
            font-size: 12px;
            color: #6b7280;
            display: block;
            margin-top: -2px;
        }

        .sidebar .menu {
            display: flex;
            flex-direction: column;
            gap: 4px;
            margin-top: 8px;
        }

        .sidebar .menu a {
            text-decoration: none;
            color: #6b7280;
            font-weight: 500;
            padding: 10px 14px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 14px;
            transition: all 0.2s ease;
        }

        .sidebar .menu a i {
            width: 20px;
            text-align: center;
            color: #6b7280;
            font-size: 16px;
        }

        .sidebar .menu a:hover, .sidebar .menu a.active {
            background: #eef2ff;
            color: #6366f1;
            font-weight: 600;
        }

        .sidebar .menu a:hover i, .sidebar .menu a.active i {
            color: #6366f1;
        }

        .sidebar .logout-btn {
            background: none;
            border: none;
            width: 100%;
            text-align: left;
            cursor: pointer;
            margin-top: 20px;
            padding-top: 16px;
            border-top: 1px solid #e5e7eb;
            display: flex;
            align-items: center;
            gap: 12px;
            color: #ef4444;
            font-weight: 600;
            padding: 10px 14px;
            border-radius: 10px;
            transition: all 0.2s ease;
            font-size: 14px;
        }

        .sidebar .logout-btn i {
            width: 20px;
            text-align: center;
        }

        .sidebar .logout-btn:hover {
            background: #fef2f2;
            color: #dc2626;
        }

        /* ============================================
           CONTENT & PANELS
        ============================================ */
        .content {
            flex: 1;
            min-width: 0;
        }

        .main-content {
            max-width: 1280px;
            margin: 0 auto;
            padding: 32px 32px 60px;
        }

        .welcome-banner {
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            border-radius: 16px;
            padding: 32px 40px;
            color: white;
            margin-bottom: 28px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: relative;
            overflow: hidden;
        }

        .welcome-banner h1 {
            font-size: 28px;
            font-weight: 800;
        }

        .welcome-banner p {
            font-size: 15px;
            opacity: 0.85;
            margin-top: 4px;
        }

        .welcome-banner .emoji {
            font-size: 48px;
        }

        /* ============================================
           STATS CARDS
        ============================================ */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
            margin-bottom: 28px;
        }

        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 20px 24px;
            border: 1px solid #e5e7eb;
            transition: all 0.2s ease;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(0,0,0,0.06);
        }

        .stat-card .icon {
            width: 44px;
            height: 44px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            margin-bottom: 10px;
        }

        .icon-purple { background: #f5f3ff; color: #8b5cf6; }
        .icon-blue { background: #eff6ff; color: #3b82f6; }
        .icon-green { background: #ecfdf5; color: #10b981; }
        .icon-orange { background: #fffbeb; color: #f59e0b; }

        .stat-card .number {
            font-size: 24px;
            font-weight: 800;
            color: #0f172a;
        }

        .stat-card .label {
            font-size: 13px;
            color: #64748b;
            font-weight: 500;
        }

        .stat-card .link {
            display: inline-block;
            margin-top: 6px;
            font-size: 13px;
            color: #6366f1;
            font-weight: 600;
            text-decoration: none;
        }

        .stat-card .link:hover {
            text-decoration: underline;
        }

        /* ============================================
           TWO COLUMN LAYOUT
        ============================================ */
        .two-col {
            display: grid;
            grid-template-columns: 1.3fr 1fr;
            gap: 24px;
        }

        .panel {
            background: white;
            border-radius: 12px;
            border: 1px solid #e5e7eb;
            padding: 24px;
        }

        .panel-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
        }

        .panel-header h3 {
            font-size: 16px;
            font-weight: 700;
            color: #0f172a;
        }

        .panel-header a {
            font-size: 13px;
            color: #6366f1;
            font-weight: 600;
            text-decoration: none;
        }

        /* ============================================
           JOB ITEMS (Form-based action for security)
        ============================================ */
        .job-item {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 14px 0;
            border-bottom: 1px solid #f1f5f9;
        }

        .job-item:last-child {
            border-bottom: none;
        }

        .job-icon {
            width: 42px;
            height: 42px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 16px;
            flex-shrink: 0;
        }

        .icon-yellow { background: #fef9c3; color: #a16207; }

        .job-info {
            flex: 1;
        }

        .job-info .title {
            font-size: 14px;
            font-weight: 600;
            color: #0f172a;
        }

        .job-info .meta {
            font-size: 12px;
            color: #94a3b8;
            margin-top: 2px;
        }

        .job-info .budget {
            font-size: 13px;
            font-weight: 700;
            color: #0f172a;
            margin-top: 4px;
        }

        .job-right {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 6px;
        }

        .pill {
            font-size: 11px;
            font-weight: 600;
            padding: 3px 10px;
            border-radius: 999px;
        }

        .pill-remote {
            background: #ecfdf5;
            color: #10b981;
        }

        .btn-apply-submit {
            background: #6366f1;
            color: white;
            border: none;
            padding: 6px 14px;
            border-radius: 6px;
            font-weight: 600;
            font-size: 12px;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .btn-apply-submit:hover {
            background: #4f46e5;
        }

        /* ============================================
           STATUS ITEMS
        ============================================ */
        .status-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 14px 0;
            border-bottom: 1px solid #f1f5f9;
            border-left: 4px solid transparent;
            padding-left: 12px;
        }

        .status-item:last-child {
            border-bottom: none;
        }

        .status-item .title {
            font-size: 14px;
            font-weight: 600;
            color: #0f172a;
        }

        .status-item .client {
            font-size: 12px;
            color: #94a3b8;
        }

        .status-item .status-label {
            font-size: 12px;
            font-weight: 600;
        }

        .status-item .date {
            font-size: 11px;
            color: #94a3b8;
        }

        .border-yellow { border-left-color: #eab308; }
        .border-blue { border-left-color: #3b82f6; }
        .border-red { border-left-color: #ef4444; }
        .border-green { border-left-color: #10b981; }

        .text-yellow { color: #eab308; }
        .text-blue { color: #3b82f6; }
        .text-red { color: #ef4444; }
        .text-green { color: #10b981; }

        /* Responsive Breakpoints */
        @media (max-width: 1024px) {
            .sidebar { display: none; }
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
            .two-col { grid-template-columns: 1fr; }
        }
        @media (max-width: 768px) {
            .main-content { padding: 16px; }
            .welcome-banner { padding: 24px; flex-direction: column; text-align: center; }
            .stats-grid { grid-template-columns: 1fr 1fr; gap: 12px; }
        }
        @media (max-width: 480px) {
            .stats-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<div class="app-shell">

    <!-- SIDEBAR -->
    <aside class="sidebar">
        <div class="brand">
            <div>
                <div class="logo"><i class="fa-solid fa-chart-line"></i> Freelance</div>
                <div class="sub">Marketplace</div>
            </div>
        </div>
        <nav class="menu">
            <a href="dashboard_freelancer.php" class="active"><i class="fa-solid fa-house"></i> Dashboard</a>
            <a href="browse_jobs.php"><i class="fa-solid fa-briefcase"></i> Browse Jobs</a>
            <a href="my_applications.php"><i class="fa-solid fa-file-lines"></i> My Applications</a>
            <a href="messages.php"><i class="fa-solid fa-comment-dots"></i> Messages</a>
            <a href="portfolio.php"><i class="fa-solid fa-folder-open"></i> Portfolio</a>
            <a href="earnings.php"><i class="fa-solid fa-wallet"></i> Earnings</a>
            <a href="profile.php"><i class="fa-solid fa-user"></i> Profile</a>
            <a href="settings_freelancer.php"><i class="fa-solid fa-gear"></i> Settings</a>
        </nav>
        
        <!-- SECURE LOGOUT FORM (POST method avoids CSRF) -->
        <form action="logout.php" method="POST" onsubmit="return confirm('Are you sure you want to logout?')">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            <button type="submit" class="sidebar.logout-btn" style="width: 100%; border: none; background: none;">
                <i class="fa-solid fa-right-from-bracket"></i> Log out
            </button>
        </form>
    </aside>

    <!-- CONTENT -->
    <div class="content">
        <main class="main-content">

            <!-- WELCOME BANNER -->
            <div class="welcome-banner">
                <div>
                    <h1>Welcome back, <?php echo escape($_SESSION['name']); ?>! 👋</h1>
                    <p>Find amazing freelance opportunities and grow your skills.</p>
                </div>
                <div class="emoji">🚀</div>
            </div>

            <!-- STATS -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="icon icon-purple"><i class="fa-solid fa-briefcase"></i></div>
                    <div class="number"><?php echo (int)$totalJobs; ?></div>
                    <div class="label">Available Jobs</div>
                    <a href="browse_jobs.php" class="link">View all jobs →</a>
                </div>
                <div class="stat-card">
                    <div class="icon icon-blue"><i class="fa-solid fa-file-invoice"></i></div>
                    <div class="number"><?php echo (int)$applicationsSent; ?></div>
                    <div class="label">Applications Sent</div>
                    <a href="my_applications.php" class="link">View my applications →</a>
                </div>
                <div class="stat-card">
                    <div class="icon icon-green"><i class="fa-solid fa-clock"></i></div>
                    <div class="number"><?php echo (int)$inProgress; ?></div>
                    <div class="label">In Progress</div>
                    <a href="my_applications.php" class="link">View projects →</a>
                </div>
                <div class="stat-card">
                    <div class="icon icon-orange"><i class="fa-solid fa-dollar-sign"></i></div>
                    <div class="number">RM <?php echo number_format($totalEarnings, 2); ?></div>
                    <div class="label">Total Earnings</div>
                    <a href="earnings.php" class="link">View earnings →</a>
                </div>
            </div>

            <!-- TWO COLUMN -->
            <div class="two-col">

                <!-- RECOMMENDED JOBS -->
                <div class="panel">
                    <div class="panel-header">
                        <h3>📌 Recommended Jobs</h3>
                        <a href="browse_jobs.php">View all →</a>
                    </div>
                    <?php foreach ($recommendedJobs as $job): ?>
                    <div class="job-item">
                        <div class="job-icon icon-yellow"><?php echo escape(strtoupper(substr($job['title'], 0, 1))); ?></div>
                        <div class="job-info">
                            <div class="title"><?php echo escape($job['title']); ?></div>
                            <div class="meta"><?php echo escape($job['category']); ?></div>
                            <div class="budget">RM <?php echo number_format($job['budget_min'], 2); ?> - RM <?php echo number_format($job['budget_max'], 2); ?></div>
                        </div>
                        <div class="job-right">
                            <span class="pill pill-remote"><?php echo escape(ucfirst($job['location_type'])); ?></span>
                            <!-- Mock days left element placeholder -->
                            <span class="job-days">New</span>
                            
                            <!-- SECURE POST FORM FOR APPLYING -->
                            <form action="apply_action.php" method="POST" onsubmit="return confirm('Apply for this job?')">
                                <input type="hidden" name="job_id" value="<?php echo (int)$job['id']; ?>">
                                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                <button type="submit" class="btn-apply-submit">Apply</button>
                            </form>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- APPLICATION STATUS -->
                <div class="panel">
                    <div class="panel-header">
                        <h3>📋 My Application Status</h3>
                        <a href="my_applications.php">View all →</a>
                    </div>
                    <?php foreach ($applications as $app): 
                        $statusClass = $app['status'] == 'in_progress' ? 'yellow' : ($app['status'] == 'accepted' ? 'green' : ($app['status'] == 'rejected' ? 'red' : 'blue'));
                    ?>
                    <div class="status-item border-<?php echo $statusClass; ?>">
                        <div>
                            <div class="title"><?php echo escape($app['job_title']); ?></div>
                            <div class="client">Client: <?php echo escape($app['client_name']); ?></div>
                        </div>
                        <div style="text-align:right;">
                            <div class="status-label text-<?php echo $statusClass; ?>">
                                <?php echo escape(ucfirst(str_replace('_', ' ', $app['status']))); ?>
                            </div>
                            <div class="date"><?php echo htmlspecialchars(date('d M Y', strtotime($app['applied_at']))); ?></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

            </div>
        </main>
    </div>
</div>

<script>
    // Highlight active sidebar link
    (function() {
        const path = window.location.pathname.split('/').pop();
        document.querySelectorAll('.sidebar .menu a').forEach(a => {
            const href = a.getAttribute('href').split('/').pop();
            if (href === path) a.classList.add('active');
        });
    })();
</script>
</body>
</html>