<?php
require_once '../config.php';

if (!isLoggedIn() || !isClient()) {
    redirect('../index.php');
}

$user_id = $_SESSION['user_id'];

// Get all jobs by this client
$jobs = $pdo->query("
    SELECT * FROM jobs 
    WHERE client_id = $user_id 
    ORDER BY created_at DESC
")->fetchAll();

// Get active jobs count
$activeJobs = $pdo->query("SELECT COUNT(*) FROM jobs WHERE client_id = $user_id AND status = 'active'")->fetchColumn();

// Delete job
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM jobs WHERE id = ? AND client_id = ?");
    $stmt->execute([$_GET['delete'], $user_id]);
    redirect('myjob_client.php?success=deleted');
}

// Update status
if (isset($_GET['status']) && isset($_GET['id'])) {
    $status = $_GET['status'];
    $id = $_GET['id'];
    $stmt = $pdo->prepare("UPDATE jobs SET status = ? WHERE id = ? AND client_id = ?");
    $stmt->execute([$status, $id, $user_id]);
    redirect('myjob_client.php?success=updated');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Jobs - Client Dashboard</title>
    <link rel="stylesheet" href="client-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        .myjobs-grid { display: grid; grid-template-columns: 1.35fr 0.65fr; gap: 24px; }
        .myjobs-panel { background-color: #fff; border-radius: 20px; padding: 24px; box-shadow: 0 2px 8px rgba(15, 23, 42, 0.06); }
        .project-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 22px; }
        .project-header h2 { font-size: 22px; margin: 0; }
        .btn-primary { background: #7c3aed; color: #fff; border: none; border-radius: 999px; padding: 12px 24px; cursor: pointer; font-weight: 700; text-decoration: none; display: inline-block; }
        .btn-primary:hover { background: #6d28d9; }
        .job-card { background: #f8f4ff; border-radius: 18px; padding: 18px 22px; margin-bottom: 18px; border: 1px solid #ede9fe; }
        .job-top { display: flex; gap: 16px; align-items: flex-start; }
        .job-icon { width: 44px; height: 44px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 18px; }
        .icon-yellow { background: #fef9c3; color: #a16207; }
        .job-details { flex: 1; }
        .job-title { font-size: 16px; font-weight: 700; margin-bottom: 6px; }
        .job-sub { font-size: 13px; color: #6b7280; margin-bottom: 10px; display: block; }
        .job-rate { font-size: 16px; font-weight: 800; color: #111827; margin-bottom: 10px; }
        .job-meta { display: flex; justify-content: space-between; gap: 12px; flex-wrap: wrap; }
        .pill { display: inline-flex; align-items: center; justify-content: center; padding: 6px 12px; border-radius: 999px; background: #eef2ff; color: #4338ca; font-size: 12px; font-weight: 700; }
        .job-actions { display: flex; gap: 10px; margin-top: 14px; flex-wrap: wrap; }
        .btn-secondary { background: #eef2ff; color: #4338ca; border: none; border-radius: 999px; padding: 10px 20px; cursor: pointer; font-weight: 700; text-decoration: none; display: inline-block; }
        .btn-secondary:hover { background: #dbeafe; }
        .btn-danger { background: #fee2e2; color: #dc2626; border: none; border-radius: 999px; padding: 10px 20px; cursor: pointer; font-weight: 700; }
        .btn-danger:hover { background: #fecaca; }
        .status-card { background: #fff; border-radius: 20px; padding: 24px; box-shadow: 0 2px 8px rgba(15, 23, 42, 0.06); min-height: 260px; }
        .status-card h3 { margin-top: 0; font-size: 18px; }
        .status-empty { min-height: 220px; display: grid; place-items: center; color: #6b7280; font-weight: 600; }
        .alert { padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; }
        .alert-success { background: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0; }
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
                <a href="myjob_client.php" class="menu-item active"><i class="fa-solid fa-file-lines"></i> My Jobs</a>
                <a href="message_client.php" class="menu-item"><i class="fa-solid fa-comment-dots"></i> Messages</a>
                <a href="payment_client.php" class="menu-item"><i class="fa-solid fa-credit-card"></i> Payment</a>
                <a href="savedLancer_client.php" class="menu-item"><i class="fa-solid fa-star"></i> Saved Freelancers</a>
                <a href="review_client.php" class="menu-item"><i class="fa-solid fa-star-half-stroke"></i> Review</a>
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

            <section class="myjobs-grid">
                <div class="myjobs-panel">
                    <div class="project-header">
                        <div>
                            <p class="eyebrow" style="font-size:13px;color:#6b7280;margin-bottom:4px;">My Projects</p>
                            <h2 style="margin:0;">My Posted Jobs</h2>
                        </div>
                        <a href="jobs_client.php" class="btn-primary">Post a new Job</a>
                    </div>

                    <?php if (isset($_GET['success'])): ?>
                        <div class="alert alert-success">
                            <?php if ($_GET['success'] == 'deleted'): ?>
                                ✅ Job deleted successfully!
                            <?php elseif ($_GET['success'] == 'updated'): ?>
                                ✅ Job status updated!
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <?php foreach ($jobs as $job): ?>
                    <div class="job-card">
                        <div class="job-top">
                            <div class="job-icon icon-yellow"><i class="fa-solid fa-briefcase"></i></div>
                            <div class="job-details">
                                <span class="job-title"><?php echo escape($job['title']); ?></span>
                                <span class="job-sub"><?php echo escape($job['category']); ?></span>
                                <span class="job-rate">RM <?php echo number_format($job['budget_min'], 2); ?> - RM <?php echo number_format($job['budget_max'], 2); ?></span>
                                <div class="job-meta">
                                    <span class="pill"><?php echo ucfirst($job['location_type']); ?></span>
                                    <span style="font-size:13px;color:#6b7280;">Status: <?php echo ucfirst($job['status']); ?></span>
                                </div>
                            </div>
                        </div>
                        <div class="job-actions">
                            <span class="btn-secondary">📌 <?php echo ucfirst($job['status']); ?></span>
                            <?php if ($job['status'] == 'active'): ?>
                                <a href="myjob_client.php?status=in_progress&id=<?php echo $job['id']; ?>" class="btn-secondary" onclick="return confirm('Mark as In Progress?')">Start</a>
                                <a href="myjob_client.php?status=completed&id=<?php echo $job['id']; ?>" class="btn-secondary" onclick="return confirm('Mark as Completed?')">Complete</a>
                            <?php endif; ?>
                            <a href="myjob_client.php?delete=<?php echo $job['id']; ?>" class="btn-danger" onclick="return confirm('Delete this job?')">Delete</a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <aside class="status-card">
                    <h3>Ongoing Projects</h3>
                    <div class="status-empty">
                        <?php if ($activeJobs > 0): ?>
                            You have <?php echo $activeJobs; ?> active job(s)
                        <?php else: ?>
                            No ongoing projects yet
                        <?php endif; ?>
                    </div>
                </aside>
            </section>
        </main>
    </div>
</body>
</html>