<?php
require_once 'config.php';

if (!isLoggedIn() || !isFreelancer()) {
    redirect('index.php');
}

$user_id = $_SESSION['user_id'];

// Get all applications
$applications = $pdo->query("
    SELECT a.*, j.title as job_title, j.category, u.name as client_name 
    FROM applications a
    JOIN jobs j ON a.job_id = j.id
    JOIN users u ON j.client_id = u.id
    WHERE a.freelancer_id = $user_id
    ORDER BY a.applied_at DESC
")->fetchAll();

// Withdraw application
if (isset($_GET['withdraw']) && is_numeric($_GET['withdraw'])) {
    $stmt = $pdo->prepare("DELETE FROM applications WHERE id = ? AND freelancer_id = ?");
    $stmt->execute([$_GET['withdraw'], $user_id]);
    redirect('my_applications.php?success=withdrawn');
}

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
    <title>My Applications</title>
    <link rel="stylesheet" href="freelancer.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
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

        .dashboard-container {
            display: flex;
            min-height: 100vh;
        }

        /* ============================================
           SIDEBAR - SAME AS DASHBOARD
        ============================================ */
        .sidebar {
            width: 260px;
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

        .sidebar-brand .logo-icon {
            font-size: 28px;
            color: #6366f1;
        }

        .sidebar-brand .brand-title {
            font-size: 18px;
            font-weight: 700;
            color: #1f2937;
        }

        .sidebar-brand .brand-sub {
            font-size: 12px;
            color: #6b7280;
            display: block;
            margin-top: -2px;
        }

        .sidebar-menu {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

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

        .sidebar-menu a:hover {
            background: #f5f3ff;
            color: #6366f1;
        }

        .sidebar-menu a.active {
            background: #eef2ff;
            color: #6366f1;
            font-weight: 600;
        }

        .sidebar-menu a i {
            width: 20px;
            font-size: 16px;
        }

        .sidebar-menu .logout {
            margin-top: 20px;
            border-top: 1px solid #e5e7eb;
            padding-top: 16px;
            color: #ef4444;
        }

        .sidebar-menu .logout:hover {
            background: #fef2f2;
            color: #dc2626;
        }

        /* ============================================
           MAIN CONTENT
        ============================================ */
        .main-content {
            flex: 1;
            padding: 32px 40px 60px;
            overflow-y: auto;
        }

        /* ============================================
           HERO BANNER - SAME AS DASHBOARD
        ============================================ */
        .hero-banner {
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            border-radius: 16px;
            padding: 32px 40px;
            color: white;
            margin-bottom: 28px;
            position: relative;
            overflow: hidden;
        }

        .hero-banner::after {
            content: '';
            position: absolute;
            right: -60px;
            top: -60px;
            width: 200px;
            height: 200px;
            border-radius: 50%;
            background: rgba(255,255,255,0.05);
        }

        .hero-banner h1 {
            font-size: 28px;
            font-weight: 800;
        }

        .hero-banner p {
            font-size: 15px;
            opacity: 0.85;
            margin-top: 4px;
        }

        .hero-banner .emoji {
            position: absolute;
            right: 40px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 48px;
            z-index: 1;
        }

        /* ============================================
           STATS MINI
        ============================================ */
        .stats-mini {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
            margin-bottom: 20px;
        }

        .stat-card {
            background: white;
            padding: 16px;
            border-radius: 12px;
            border: 1px solid #e5e7eb;
            text-align: center;
        }

        .stat-card .number {
            font-size: 24px;
            font-weight: 700;
            color: #0f172a;
        }

        .stat-card .label {
            font-size: 13px;
            color: #94a3b8;
        }

        .stat-card .pending { color: #6b7280; }
        .stat-card .under-review { color: #3b82f6; }
        .stat-card .accepted { color: #10b981; }
        .stat-card .rejected { color: #ef4444; }

        /* ============================================
           TABLE PANEL
        ============================================ */
        .panel {
            background: white;
            border-radius: 12px;
            border: 1px solid #e5e7eb;
            padding: 24px;
        }

        .panel h2 {
            font-size: 16px;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 16px;
        }

        .panel h2 i {
            color: #6366f1;
        }

        .table-wrapper {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            text-align: left;
            padding: 10px 8px 12px;
            border-bottom: 2px solid #e5e7eb;
            color: #6b7280;
            font-weight: 600;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        td {
            padding: 12px 8px;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: middle;
            font-size: 14px;
        }

        tr:last-child td {
            border-bottom: none;
        }

        .badge {
            padding: 4px 12px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }

        .badge-pending { background: #f3f4f6; color: #6b7280; }
        .badge-under_review { background: #e9f1fe; color: #3b82f6; }
        .badge-accepted { background: #eaf6ef; color: #10b981; }
        .badge-rejected { background: #fceaea; color: #ef4444; }
        .badge-in_progress { background: #fdf3e0; color: #eab308; }

        .btn-withdraw {
            background: #fee2e2;
            color: #dc2626;
            border: none;
            padding: 6px 12px;
            border-radius: 6px;
            font-weight: 600;
            font-size: 12px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }

        .btn-withdraw:hover {
            background: #fecaca;
        }

        .empty {
            text-align: center;
            padding: 40px;
            color: #94a3b8;
        }

        .empty i {
            font-size: 40px;
            color: #d1d5db;
            display: block;
            margin-bottom: 12px;
        }

        .empty p {
            font-size: 14px;
        }

        /* ============================================
           ALERT
        ============================================ */
        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 16px;
            font-weight: 600;
            font-size: 14px;
        }

        .alert-success {
            background: #f0fdf4;
            color: #16a34a;
            border: 1px solid #bbf7d0;
        }

        /* ============================================
           RESPONSIVE
        ============================================ */
        @media (max-width: 1024px) {
            .stats-mini {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 200px;
                padding: 16px 12px;
            }

            .main-content {
                padding: 20px;
            }

            .stats-mini {
                grid-template-columns: 1fr 1fr;
                gap: 12px;
            }

            .hero-banner .emoji {
                display: none;
            }

            .hero-banner h1 {
                font-size: 24px;
            }

            .hero-banner p {
                font-size: 14px;
            }
        }

        @media (max-width: 480px) {
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
                border-right: none;
                border-bottom: 1px solid #e5e7eb;
            }

            .dashboard-container {
                flex-direction: column;
            }

            .sidebar-menu {
                flex-direction: row;
                flex-wrap: wrap;
            }

            .sidebar-menu a {
                padding: 8px 12px;
                font-size: 13px;
            }

            .sidebar-menu .logout {
                margin-top: 0;
                border-top: none;
                padding-top: 0;
            }

            .stats-mini {
                grid-template-columns: 1fr;
            }

            .hero-banner {
                padding: 24px;
            }

            .hero-banner h1 {
                font-size: 22px;
            }
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

    <!-- MAIN CONTENT -->
    <main class="main-content">

        <!-- HERO BANNER -->
        <div class="hero-banner">
            <div>
                <h1>📋 My Applications</h1>
                <p>Status of all your job applications.</p>
            </div>
            <div class="emoji">📊</div>
        </div>

        <!-- ALERTS -->
        <?php if (isset($_GET['success']) && $_GET['success'] == 'withdrawn'): ?>
            <div class="alert alert-success">✅ Application withdrawn successfully!</div>
        <?php endif; ?>

        <!-- STATS -->
        <div class="stats-mini">
            <div class="stat-card">
                <div class="number pending"><?php echo $pending; ?></div>
                <div class="label">Pending</div>
            </div>
            <div class="stat-card">
                <div class="number under-review"><?php echo $underReview; ?></div>
                <div class="label">Under Review</div>
            </div>
            <div class="stat-card">
                <div class="number accepted"><?php echo $accepted; ?></div>
                <div class="label">Accepted</div>
            </div>
            <div class="stat-card">
                <div class="number rejected"><?php echo $rejected; ?></div>
                <div class="label">Rejected</div>
            </div>
        </div>

        <!-- TABLE -->
        <div class="panel">
            <h2><i class="fa-regular fa-file-lines"></i> All Applications</h2>
            <?php if (count($applications) > 0): ?>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Job</th>
                            <th>Client</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($applications as $app): ?>
                        <tr>
                            <td><strong><?php echo escape($app['job_title']); ?></strong></td>
                            <td><?php echo escape($app['client_name']); ?></td>
                            <td><?php echo date('d M Y', strtotime($app['applied_at'])); ?></td>
                            <td>
                                <span class="badge badge-<?php echo $app['status']; ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $app['status'])); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($app['status'] == 'pending' || $app['status'] == 'under_review'): ?>
                                    <a href="my_applications.php?withdraw=<?php echo $app['id']; ?>" class="btn-withdraw" onclick="return confirm('Withdraw this application?')">Withdraw</a>
                                <?php else: ?>
                                    <span style="color:#94a3b8;font-size:12px;">-</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
                <div class="empty">
                    <i class="fa-regular fa-file"></i>
                    <p>No applications yet. Start browsing jobs!</p>
                </div>
            <?php endif; ?>
        </div>

    </main>
</div>

</body>
</html>