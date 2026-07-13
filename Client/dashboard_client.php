<?php
require_once '../config.php';

if (!isLoggedIn() || !isClient()) {
    redirect('../index.php');
}

$user_id = $_SESSION['user_id'];

// Get stats from database
$totalJobs = $pdo->query("SELECT COUNT(*) FROM jobs WHERE client_id = $user_id")->fetchColumn();
$activeJobs = $pdo->query("SELECT COUNT(*) FROM jobs WHERE client_id = $user_id AND status = 'active'")->fetchColumn();
$completedJobs = $pdo->query("SELECT COUNT(*) FROM jobs WHERE client_id = $user_id AND status = 'completed'")->fetchColumn();
$totalSpent = $pdo->query("SELECT SUM(amount) FROM payments WHERE user_id = $user_id AND status = 'paid'")->fetchColumn() ?? 0;

// Get recent jobs
$recentJobs = $pdo->query("
    SELECT * FROM jobs 
    WHERE client_id = $user_id 
    ORDER BY created_at DESC LIMIT 5
")->fetchAll();

// Get applications stats
$totalApplications = $pdo->query("
    SELECT COUNT(*) FROM applications a 
    JOIN jobs j ON a.job_id = j.id 
    WHERE j.client_id = $user_id
")->fetchColumn();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Client Dashboard - Freelance Marketplace</title>
    <link rel="stylesheet" href="client-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
                <a href="dashboard_client.php" class="menu-item active"><i class="fa-solid fa-house"></i> Dashboard</a>
                <a href="jobs_client.php" class="menu-item"><i class="fa-solid fa-circle-plus"></i> Post a Job</a>
                <a href="myjob_client.php" class="menu-item"><i class="fa-solid fa-file-lines"></i> My Jobs</a>
                <a href="message_client.php" class="menu-item"><i class="fa-solid fa-comment-dots"></i> Messages</a>
                <a href="payment_client.php" class="menu-item"><i class="fa-solid fa-credit-card"></i> Payments</a>
                <a href="savedLancer_client.php" class="menu-item"><i class="fa-solid fa-star"></i> Saved Freelancers</a>
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

            <section class="metrics-summary-grid">
                <div class="metric-card-item">
                    <div class="metric-icon-box icon-purple"><i class="fa-solid fa-briefcase"></i></div>
                    <div class="metric-info-data">
                        <span class="metric-label">Total Jobs Posted</span>
                        <h2 class="metric-number"><?php echo $totalJobs; ?></h2>
                        <div class="metric-subtext-row">
                            <span class="metric-subtext">All time posted</span>
                            <i class="fa-solid fa-arrow-right arrow-link"></i>
                        </div>
                    </div>
                </div>

                <div class="metric-card-item">
                    <div class="metric-icon-box icon-blue"><i class="fa-solid fa-file-invoice"></i></div>
                    <div class="metric-info-data">
                        <span class="metric-label">Active Projects</span>
                        <h2 class="metric-number"><?php echo $activeJobs; ?></h2>
                        <div class="metric-subtext-row">
                            <span class="metric-subtext">In Progress</span>
                            <i class="fa-solid fa-arrow-right arrow-link"></i>
                        </div>
                    </div>
                </div>

                <div class="metric-card-item">
                    <div class="metric-icon-box icon-green"><i class="fa-solid fa-clock"></i></div>
                    <div class="metric-info-data">
                        <span class="metric-label">Total Spent</span>
                        <h2 class="metric-number">RM <?php echo number_format($totalSpent, 2); ?></h2>
                        <div class="metric-subtext-row">
                            <span class="metric-subtext">All time spending</span>
                            <i class="fa-solid fa-arrow-right arrow-link"></i>
                        </div>
                    </div>
                </div>

                <div class="metric-card-item">
                    <div class="metric-icon-box icon-orange"><i class="fa-solid fa-circle-check"></i></div>
                    <div class="metric-info-data">
                        <span class="metric-label">Completed Projects</span>
                        <h2 class="metric-number"><?php echo $completedJobs; ?></h2>
                        <div class="metric-subtext-row">
                            <span class="metric-subtext">All time completed</span>
                            <i class="fa-solid fa-arrow-right arrow-link"></i>
                        </div>
                    </div>
                </div>
            </section>

            <div class="workspace-split-layout">
                
                <section class="workspace-panel-card">
                    <div class="panel-header-row">
                        <h3 class="panel-title-text">Recent Job Posts</h3>
                    </div>
                    
                    <div class="job-items-vertical-list">
                        <?php foreach ($recentJobs as $job): ?>
                        <div class="job-list-card-row border-left-purple">
                            <div class="job-category-badge-icon icon-bg-yellow">
                                <i class="fa-solid fa-briefcase"></i>
                            </div>
                            <div class="job-core-details">
                                <h4 class="job-title-header"><?php echo escape($job['title']); ?></h4>
                                <span class="job-meta-sub"><?php echo escape($job['category']); ?></span>
                                <span class="job-applicants-count">RM <?php echo number_format($job['budget_min'], 2); ?></span>
                            </div>
                            <div class="job-status-tags-group">
                                <span class="status-text-pill color-open"><?php echo ucfirst($job['status']); ?></span>
                                <span class="job-date-text"><?php echo date('d M Y', strtotime($job['created_at'])); ?></span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="panel-footer-action-row">
                        <a href="myjob_client.php" class="view-all-central-link">View All</a>
                    </div>
                </section>

                <section class="workspace-panel-card">
                    <div class="panel-header-row split-header">
                        <h3 class="panel-title-text">Applications Overview</h3>
                    </div>

                    <div class="chart-wrapper-box">
                        <div class="chart-header-inside">
                            <span class="inside-title">Total Applications</span>
                            <select class="chart-dropdown-filter">
                                <option>This Month</option>
                            </select>
                        </div>
                        <div class="canvas-container">
                            <canvas id="applicationsChart"></canvas>
                        </div>
                    </div>

                    <div class="timeframe-summary-container">
                        <div class="timeframe-box">
                            <span class="timeframe-lbl">Total Applications</span>
                            <h4 class="timeframe-val color-brand"><?php echo $totalApplications; ?></h4>
                        </div>
                        <div class="timeframe-box">
                            <span class="timeframe-lbl">Active Jobs</span>
                            <h4 class="timeframe-val color-gold"><?php echo $activeJobs; ?></h4>
                        </div>
                    </div>
                </section>

            </div>
        </main>
    </div>

    <script>
        const ctx = document.getElementById('applicationsChart').getContext('2d');
        const gradient = ctx.createLinearGradient(0, 0, 0, 200);
        gradient.addColorStop(0, 'rgba(34, 197, 94, 0.4)');
        gradient.addColorStop(1, 'rgba(34, 197, 94, 0.0)');

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['1 Apr', '8 Apr', '15 Apr', '18 Apr', '20 Apr', '25 Apr', '27 Apr', '28 Apr'],
                datasets: [{
                    label: 'Applications',
                    data: [15, 20, 45, 55, 12, 35, 45, 62],
                    borderColor: '#22c55e',
                    borderWidth: 2,
                    pointBackgroundColor: '#22c55e',
                    pointRadius: 0,
                    pointHoverRadius: 4,
                    fill: true,
                    backgroundColor: gradient,
                    tension: 0.3
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: { grid: { display: false }, ticks: { color: '#9ca3af', font: { size: 10 } } },
                    y: { min: 0, max: 100, ticks: { stepSize: 20, color: '#9ca3af', font: { size: 10 } }, grid: { color: '#f3f4f6' } }
                }
            }
        });
    </script>
</body>
</html>