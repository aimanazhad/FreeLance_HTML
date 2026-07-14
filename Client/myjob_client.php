<?php
require_once '../config.php';

if (!isLoggedIn() || !isClient()) {
    redirect('../index.php');
}

$user_id = $_SESSION['user_id'];

// ============================================
// PROCESS PAYMENT
// ============================================
if (isset($_POST['make_payment'])) {
    $job_id = $_POST['job_id'];
    $freelancer_id = $_POST['freelancer_id'];
    $amount = $_POST['amount'];
    $method = $_POST['method'];
    $description = trim($_POST['description'] ?? 'Payment for job');
    
    if ($amount > 0 && $job_id && $freelancer_id) {
        $stmt = $pdo->prepare("
            INSERT INTO payments (user_id, freelancer_id, job_id, amount, method, description, status, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, 'pending', NOW())
        ");
        if ($stmt->execute([$user_id, $freelancer_id, $job_id, $amount, $method, $description])) {
            $payment_success = '✅ Payment request submitted! Waiting for admin approval.';
        } else {
            $payment_error = '❌ Failed to process payment.';
        }
    }
}

// ============================================
// GET ALL DATA
// ============================================

// Get all jobs by this client with application stats
$jobs = $pdo->query("
    SELECT j.*, 
           (SELECT COUNT(*) FROM applications WHERE job_id = j.id) as total_apps,
           (SELECT COUNT(*) FROM applications WHERE job_id = j.id AND status = 'pending') as pending_apps
    FROM jobs j
    WHERE j.client_id = $user_id
    ORDER BY j.created_at DESC
")->fetchAll();

// Get all applications for client's jobs with freelancer details
$applications = $pdo->query("
    SELECT a.*, 
           j.title as job_title, 
           j.status as job_status,
           j.budget_min,
           j.budget_max,
           u.id as freelancer_id,
           u.name as freelancer_name, 
           u.email as freelancer_email,
           u.phone as freelancer_phone
    FROM applications a
    JOIN jobs j ON a.job_id = j.id
    JOIN users u ON a.freelancer_id = u.id
    WHERE j.client_id = $user_id
    ORDER BY a.applied_at DESC
")->fetchAll();

// Get payments for client
$payments = $pdo->query("
    SELECT p.*, 
           j.title as job_title,
           u.name as freelancer_name
    FROM payments p
    LEFT JOIN jobs j ON p.job_id = j.id
    LEFT JOIN users u ON p.freelancer_id = u.id
    WHERE p.user_id = $user_id
    ORDER BY p.created_at DESC
")->fetchAll();

// ============================================
// PROCESS ACTIONS
// ============================================

// Process application action (accept/reject)
if (isset($_GET['action']) && isset($_GET['app_id']) && is_numeric($_GET['app_id'])) {
    $app_id = $_GET['app_id'];
    $action = $_GET['action'];
    
    $stmt = $pdo->prepare("SELECT a.*, j.client_id, j.id as job_id FROM applications a JOIN jobs j ON a.job_id = j.id WHERE a.id = ?");
    $stmt->execute([$app_id]);
    $app = $stmt->fetch();
    
    if ($app && $app['client_id'] == $user_id) {
        if ($action == 'accept') {
            $stmt = $pdo->prepare("UPDATE applications SET status = 'accepted', updated_at = NOW() WHERE id = ?");
            $stmt->execute([$app_id]);
            
            $stmt = $pdo->prepare("UPDATE applications SET status = 'rejected', updated_at = NOW() WHERE job_id = ? AND id != ? AND status = 'pending'");
            $stmt->execute([$app['job_id'], $app_id]);
            
            $stmt = $pdo->prepare("UPDATE jobs SET status = 'in_progress', hired_freelancer_id = ? WHERE id = ?");
            $stmt->execute([$app['freelancer_id'], $app['job_id']]);
            
            $success = '✅ Freelancer accepted! Job is now in progress.';
        } elseif ($action == 'reject') {
            $stmt = $pdo->prepare("UPDATE applications SET status = 'rejected', updated_at = NOW() WHERE id = ?");
            $stmt->execute([$app_id]);
            $success = '✅ Application rejected.';
        }
        
        // Refresh data
        $jobs = $pdo->query("
            SELECT j.*, 
                   (SELECT COUNT(*) FROM applications WHERE job_id = j.id) as total_apps,
                   (SELECT COUNT(*) FROM applications WHERE job_id = j.id AND status = 'pending') as pending_apps
            FROM jobs j
            WHERE j.client_id = $user_id
            ORDER BY j.created_at DESC
        ")->fetchAll();
        
        $applications = $pdo->query("
            SELECT a.*, 
                   j.title as job_title, 
                   j.status as job_status,
                   j.budget_min,
                   j.budget_max,
                   u.id as freelancer_id,
                   u.name as freelancer_name, 
                   u.email as freelancer_email,
                   u.phone as freelancer_phone
            FROM applications a
            JOIN jobs j ON a.job_id = j.id
            JOIN users u ON a.freelancer_id = u.id
            WHERE j.client_id = $user_id
            ORDER BY a.applied_at DESC
        ")->fetchAll();
    }
}

// ============================================
// COMPLETE JOB
// ============================================
if (isset($_GET['complete']) && is_numeric($_GET['complete'])) {
    $job_id = $_GET['complete'];
    
    // Check if job belongs to this client
    $check = $pdo->prepare("SELECT * FROM jobs WHERE id = ? AND client_id = ?");
    $check->execute([$job_id, $user_id]);
    $job = $check->fetch();
    
    if ($job) {
        // Update job status to completed
        $stmt = $pdo->prepare("UPDATE jobs SET status = 'completed', completed_at = NOW() WHERE id = ?");
        $stmt->execute([$job_id]);
        
        // Update the accepted application status
        $stmt = $pdo->prepare("UPDATE applications SET status = 'completed', updated_at = NOW() WHERE job_id = ? AND status = 'accepted'");
        $stmt->execute([$job_id]);
        
        $success = '✅ Job marked as completed! Please make payment to freelancer.';
        
        // Refresh data
        $jobs = $pdo->query("
            SELECT j.*, 
                   (SELECT COUNT(*) FROM applications WHERE job_id = j.id) as total_apps,
                   (SELECT COUNT(*) FROM applications WHERE job_id = j.id AND status = 'pending') as pending_apps
            FROM jobs j
            WHERE j.client_id = $user_id
            ORDER BY j.created_at DESC
        ")->fetchAll();
        
        $applications = $pdo->query("
            SELECT a.*, 
                   j.title as job_title, 
                   j.status as job_status,
                   j.budget_min,
                   j.budget_max,
                   u.id as freelancer_id,
                   u.name as freelancer_name, 
                   u.email as freelancer_email,
                   u.phone as freelancer_phone
            FROM applications a
            JOIN jobs j ON a.job_id = j.id
            JOIN users u ON a.freelancer_id = u.id
            WHERE j.client_id = $user_id
            ORDER BY a.applied_at DESC
        ")->fetchAll();
    }
}

// Delete job
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM jobs WHERE id = ? AND client_id = ?");
    $stmt->execute([$_GET['delete'], $user_id]);
    redirect('myjob_client.php?success=deleted');
}

// ============================================
// STATS
// ============================================

$activeJobs = $pdo->query("SELECT COUNT(*) FROM jobs WHERE client_id = $user_id AND status = 'active'")->fetchColumn();
$inProgressJobs = $pdo->query("SELECT COUNT(*) FROM jobs WHERE client_id = $user_id AND status = 'in_progress'")->fetchColumn();
$completedJobs = $pdo->query("SELECT COUNT(*) FROM jobs WHERE client_id = $user_id AND status = 'completed'")->fetchColumn();
$pendingApps = $pdo->query("
    SELECT COUNT(*) FROM applications a 
    JOIN jobs j ON a.job_id = j.id 
    WHERE j.client_id = $user_id AND a.status = 'pending'
")->fetchColumn();
$totalPaid = $pdo->query("SELECT SUM(amount) FROM payments WHERE user_id = $user_id AND status = 'paid'")->fetchColumn() ?? 0;
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
        .myjobs-grid { display: grid; grid-template-columns: 1fr; gap: 24px; }
        .myjobs-panel { background-color: #fff; border-radius: 20px; padding: 24px; box-shadow: 0 2px 8px rgba(15, 23, 42, 0.06); }
        .project-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 22px; flex-wrap: wrap; gap: 12px; }
        .project-header h2 { font-size: 22px; margin: 0; }
        .btn-primary { background: #7c3aed; color: #fff; border: none; border-radius: 999px; padding: 12px 24px; cursor: pointer; font-weight: 700; text-decoration: none; display: inline-block; }
        .btn-primary:hover { background: #6d28d9; }
        
        .job-card { background: #f8f4ff; border-radius: 18px; padding: 18px 22px; margin-bottom: 18px; border: 1px solid #ede9fe; }
        .job-top { display: flex; gap: 16px; align-items: flex-start; flex-wrap: wrap; }
        .job-icon { width: 44px; height: 44px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 18px; background: #fef9c3; color: #a16207; flex-shrink: 0; }
        .job-details { flex: 1; min-width: 200px; }
        .job-title { font-size: 16px; font-weight: 700; margin-bottom: 4px; }
        .job-sub { font-size: 13px; color: #6b7280; display: block; }
        .job-rate { font-size: 16px; font-weight: 800; color: #111827; margin-top: 6px; }
        .job-meta { display: flex; gap: 12px; flex-wrap: wrap; margin-top: 6px; }
        .pill { display: inline-flex; align-items: center; padding: 4px 12px; border-radius: 999px; font-size: 12px; font-weight: 700; }
        .pill-active { background: #d1fae5; color: #047857; }
        .pill-in_progress { background: #fef3c7; color: #d97706; }
        .pill-completed { background: #dbeafe; color: #1d4ed8; }
        .pill-cancelled { background: #fee2e2; color: #dc2626; }
        
        .job-actions { display: flex; gap: 10px; margin-top: 12px; flex-wrap: wrap; }
        .btn-secondary { background: #eef2ff; color: #4338ca; border: none; border-radius: 999px; padding: 8px 16px; cursor: pointer; font-weight: 700; text-decoration: none; display: inline-block; font-size: 13px; }
        .btn-secondary:hover { background: #dbeafe; }
        .btn-danger { background: #fee2e2; color: #dc2626; border: none; border-radius: 999px; padding: 8px 16px; cursor: pointer; font-weight: 700; font-size: 13px; }
        .btn-danger:hover { background: #fecaca; }
        .btn-success { background: #d1fae5; color: #047857; border: none; border-radius: 999px; padding: 8px 16px; cursor: pointer; font-weight: 700; font-size: 13px; }
        .btn-success:hover { background: #a7f3d0; }
        .btn-pay { background: #6366f1; color: white; border: none; border-radius: 999px; padding: 8px 16px; cursor: pointer; font-weight: 700; font-size: 13px; }
        .btn-pay:hover { background: #4f46e5; }
        
        .apps-section { margin-top: 20px; border-top: 2px solid #f3f4f6; padding-top: 16px; }
        .apps-section h4 { font-size: 15px; font-weight: 700; margin-bottom: 12px; }
        .app-card { background: #fff; border-radius: 12px; padding: 14px 18px; border: 1px solid #e5e7eb; margin-bottom: 10px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; }
        .app-card:hover { border-color: #c7d2fe; }
        .app-info { display: flex; align-items: center; gap: 12px; }
        .app-avatar { width: 40px; height: 40px; border-radius: 50%; object-fit: cover; }
        .app-details .name { font-weight: 700; font-size: 14px; }
        .app-details .email { font-size: 12px; color: #6b7280; }
        .app-status { padding: 3px 10px; border-radius: 999px; font-size: 11px; font-weight: 600; }
        .app-status-pending { background: #fef3c7; color: #d97706; }
        .app-status-accepted { background: #d1fae5; color: #047857; }
        .app-status-rejected { background: #fee2e2; color: #dc2626; }
        .app-status-in_progress { background: #fef3c7; color: #d97706; }
        .app-status-completed { background: #dbeafe; color: #1d4ed8; }
        
        .app-actions { display: flex; gap: 6px; }
        .btn-accept { background: #10b981; color: white; border: none; padding: 5px 12px; border-radius: 6px; font-weight: 600; cursor: pointer; font-size: 12px; }
        .btn-accept:hover { background: #059669; }
        .btn-reject { background: #ef4444; color: white; border: none; padding: 5px 12px; border-radius: 6px; font-weight: 600; cursor: pointer; font-size: 12px; }
        .btn-reject:hover { background: #dc2626; }
        
        .stats-mini { display: grid; grid-template-columns: repeat(5, 1fr); gap: 16px; margin-bottom: 20px; }
        .stat-card { background: white; padding: 16px; border-radius: 12px; border: 1px solid #e5e7eb; text-align: center; }
        .stat-number { font-size: 28px; font-weight: 700; color: #1f2937; }
        .stat-label { font-size: 13px; color: #6b7280; }
        .stat-number.pending { color: #d97706; }
        .stat-number.active { color: #047857; }
        .stat-number.inprogress { color: #3b82f6; }
        .stat-number.completed { color: #8b5cf6; }
        
        .alert { padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; }
        .alert-success { background: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0; }
        .alert-danger { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }
        .empty { text-align: center; padding: 40px; color: #6b7280; }
        .empty i { font-size: 40px; color: #d1d5db; display: block; margin-bottom: 12px; }
        
        .no-apps { color: #6b7280; font-size: 13px; padding: 8px 0; }
        
        .payment-status { padding: 2px 8px; border-radius: 999px; font-size: 11px; font-weight: 600; display: inline-block; }
        .payment-status-paid { background: #d1fae5; color: #047857; }
        .payment-status-pending { background: #fef3c7; color: #d97706; }
        .payment-status-failed { background: #fee2e2; color: #dc2626; }
        
        .modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.5); display: none; align-items: center; justify-content: center; z-index: 100; }
        .modal-overlay.show { display: flex; }
        .modal-box { background: #fff; border-radius: 16px; padding: 24px; width: 420px; max-width: 90%; }
        .modal-box h3 { margin-top: 0; }
        .modal-box .form-group { margin-bottom: 12px; }
        .modal-box label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 4px; }
        .modal-box input, .modal-box select, .modal-box textarea { width: 100%; padding: 10px 14px; border: 1px solid #e5e7eb; border-radius: 10px; font-size: 14px; background: white; }
        .modal-box textarea { min-height: 60px; resize: vertical; }
        .modal-actions { display: flex; gap: 10px; justify-content: flex-end; margin-top: 12px; }
        .btn-cancel { background: #f1f5f9; color: #1f2937; border: none; padding: 8px 16px; border-radius: 8px; font-weight: 600; cursor: pointer; }
        .btn-cancel:hover { background: #e5e7eb; }
        
        .freelancer-hired {
            background: #d1fae5; padding: 4px 12px; border-radius: 999px; font-size: 12px; font-weight: 600; color: #047857;
            display: inline-block;
        }
        
        @media (max-width: 768px) { 
            .stats-mini { grid-template-columns: 1fr 1fr; }
            .app-card { flex-direction: column; align-items: stretch; }
            .app-actions { justify-content: flex-end; }
            .project-header { flex-direction: column; align-items: stretch; }
        }
        @media (max-width: 480px) {
            .stats-mini { grid-template-columns: 1fr; }
        }
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

            <!-- Stats -->
            <div class="stats-mini">
                <div class="stat-card">
                    <div class="stat-number active"><?php echo $activeJobs; ?></div>
                    <div class="stat-label">Active Jobs</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number inprogress"><?php echo $inProgressJobs; ?></div>
                    <div class="stat-label">In Progress</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number completed"><?php echo $completedJobs; ?></div>
                    <div class="stat-label">Completed</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number pending"><?php echo $pendingApps; ?></div>
                    <div class="stat-label">Pending Apps</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">RM <?php echo number_format($totalPaid, 0); ?></div>
                    <div class="stat-label">Total Paid</div>
                </div>
            </div>

            <?php if (isset($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            <?php if (isset($payment_success)): ?>
                <div class="alert alert-success"><?php echo $payment_success; ?></div>
            <?php endif; ?>
            <?php if (isset($payment_error)): ?>
                <div class="alert alert-danger"><?php echo $payment_error; ?></div>
            <?php endif; ?>
            <?php if (isset($_GET['success']) && $_GET['success'] == 'deleted'): ?>
                <div class="alert alert-success">✅ Job deleted successfully!</div>
            <?php endif; ?>

            <section class="myjobs-grid">
                <div class="myjobs-panel">
                    <div class="project-header">
                        <div>
                            <p class="eyebrow" style="font-size:13px;color:#6b7280;margin-bottom:4px;">My Projects</p>
                            <h2 style="margin:0;">My Posted Jobs</h2>
                        </div>
                        <a href="jobs_client.php" class="btn-primary">Post a new Job</a>
                    </div>

                    <?php if (count($jobs) > 0): ?>
                        <?php foreach ($jobs as $job): 
                            // Check if payment already made for this job
                            $paymentCheck = $pdo->prepare("SELECT * FROM payments WHERE job_id = ? AND user_id = ?");
                            $paymentCheck->execute([$job['id'], $user_id]);
                            $hasPayment = $paymentCheck->rowCount() > 0;
                            $paymentData = $paymentCheck->fetch();
                            
                            // Get hired freelancer for this job
                            $hired = null;
                            if ($job['hired_freelancer_id']) {
                                $hiredStmt = $pdo->prepare("SELECT id, name FROM users WHERE id = ?");
                                $hiredStmt->execute([$job['hired_freelancer_id']]);
                                $hired = $hiredStmt->fetch();
                            }
                        ?>
                        <div class="job-card">
                            <div class="job-top">
                                <div class="job-icon"><i class="fa-solid fa-briefcase"></i></div>
                                <div class="job-details">
                                    <div class="job-title"><?php echo escape($job['title']); ?></div>
                                    <span class="job-sub"><?php echo escape($job['category']); ?></span>
                                    <div class="job-rate">RM <?php echo number_format($job['budget_min'], 2); ?> - RM <?php echo number_format($job['budget_max'], 2); ?></div>
                                    <div class="job-meta">
                                        <span class="pill pill-<?php echo $job['status']; ?>"><?php echo ucfirst(str_replace('_', ' ', $job['status'])); ?></span>
                                        <span class="pill pill-active"><?php echo ucfirst($job['location_type']); ?></span>
                                        <span style="font-size:13px;color:#6b7280;">
                                            <?php echo $job['total_apps']; ?> application(s)
                                            <?php if ($job['pending_apps'] > 0): ?>
                                                (<strong style="color:#d97706;"><?php echo $job['pending_apps']; ?> pending</strong>)
                                            <?php endif; ?>
                                        </span>
                                        <?php if ($hired): ?>
                                            <span class="freelancer-hired">
                                                <i class="fa-regular fa-user-check"></i> Hired: <?php echo escape($hired['name']); ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="job-actions">
                                <?php if ($job['status'] == 'active'): ?>
                                    <span style="font-size:13px;color:#6b7280;">Waiting for freelancer...</span>
                                
                                <?php elseif ($job['status'] == 'in_progress'): ?>
                                    <a href="myjob_client.php?complete=<?php echo $job['id']; ?>" class="btn-success" onclick="return confirm('Mark this job as completed?')">
                                        <i class="fa-regular fa-check-circle"></i> Complete Job
                                    </a>
                                
                                <?php elseif ($job['status'] == 'completed'): ?>
                                    <?php if (!$hasPayment && $hired): ?>
                                        <button class="btn-pay" onclick="openPaymentModal(<?php echo $job['id']; ?>, <?php echo $hired['id']; ?>, '<?php echo escape($hired['name']); ?>', <?php echo $job['budget_max']; ?>)">
                                            <i class="fa-regular fa-credit-card"></i> Pay Now
                                        </button>
                                    <?php elseif ($hasPayment): ?>
                                        <span style="font-size:13px;font-weight:600;">
                                            <span class="payment-status payment-status-<?php echo $paymentData['status']; ?>">
                                                <i class="fa-regular fa-check-circle"></i> Payment: <?php echo ucfirst($paymentData['status']); ?>
                                            </span>
                                        </span>
                                    <?php else: ?>
                                        <span style="font-size:13px;color:#6b7280;">No freelancer hired</span>
                                    <?php endif; ?>
                                <?php endif; ?>
                                
                                <a href="myjob_client.php?delete=<?php echo $job['id']; ?>" class="btn-danger" onclick="return confirm('Delete this job?')">
                                    <i class="fa-regular fa-trash-can"></i> Delete
                                </a>
                            </div>
                            
                            <!-- Applications for this job -->
                            <div class="apps-section">
                                <h4>📩 Applications (<?php echo $job['total_apps']; ?>)</h4>
                                <?php 
                                $jobApps = array_filter($applications, function($a) use ($job) {
                                    return $a['job_id'] == $job['id'];
                                });
                                if (count($jobApps) > 0): 
                                ?>
                                    <?php foreach ($jobApps as $app): ?>
                                    <div class="app-card">
                                        <div class="app-info">
                                            <img src="https://api.dicebear.com/7.x/avataaars/svg?seed=<?php echo urlencode($app['freelancer_name']); ?>" alt="" class="app-avatar">
                                            <div class="app-details">
                                                <div class="name"><?php echo escape($app['freelancer_name']); ?></div>
                                                <div class="email"><?php echo escape($app['freelancer_email']); ?></div>
                                                <div style="font-size:11px;color:#6b7280;">
                                                    Applied: <?php echo date('d M Y, h:i A', strtotime($app['applied_at'])); ?>
                                                    <?php if (!empty($app['bid_amount'])): ?>
                                                        • Bid: RM <?php echo number_format($app['bid_amount'], 2); ?>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                        <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
                                            <span class="app-status app-status-<?php echo $app['status']; ?>">
                                                <?php echo ucfirst(str_replace('_', ' ', $app['status'])); ?>
                                            </span>
                                            <?php if ($app['status'] == 'pending' && $job['status'] == 'active'): ?>
                                                <div class="app-actions">
                                                    <a href="myjob_client.php?action=accept&app_id=<?php echo $app['id']; ?>" class="btn-accept" onclick="return confirm('Accept this freelancer for the job?')">Accept</a>
                                                    <a href="myjob_client.php?action=reject&app_id=<?php echo $app['id']; ?>" class="btn-reject" onclick="return confirm('Reject this application?')">Reject</a>
                                                </div>
                                            <?php elseif ($app['status'] == 'accepted' && $job['status'] == 'in_progress'): ?>
                                                <span style="font-size:12px;color:#047857;font-weight:600;">
                                                    <i class="fa-regular fa-check-circle"></i> Working on job
                                                </span>
                                            <?php elseif ($app['status'] == 'completed'): ?>
                                                <span style="font-size:12px;color:#1d4ed8;font-weight:600;">
                                                    <i class="fa-regular fa-check-circle"></i> Completed
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="no-apps">No applications yet for this job.</div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty">
                            <i class="fa-regular fa-file"></i>
                            <p>No jobs posted yet. <a href="jobs_client.php" style="color:#6366f1;font-weight:600;">Post your first job</a></p>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        </main>
    </div>

    <!-- Payment Modal -->
    <div class="modal-overlay" id="paymentModal" onclick="if(event.target===this)this.classList.remove('show')">
        <div class="modal-box">
            <h3><i class="fa-regular fa-credit-card" style="color:#6366f1;"></i> Make Payment</h3>
            <form method="POST">
                <input type="hidden" name="job_id" id="pay_job_id">
                <input type="hidden" name="freelancer_id" id="pay_freelancer_id">
                
                <p style="color:#6b7280;margin-bottom:12px;">
                    Paying: <strong id="pay_freelancer_name"></strong>
                </p>
                
                <div class="form-group">
                    <label>Amount (RM)</label>
                    <input type="number" name="amount" id="pay_amount" placeholder="0.00" step="0.01" required>
                </div>
                
                <div class="form-group">
                    <label>Payment Method</label>
                    <select name="method" required>
                        <option value="online">Online Banking</option>
                        <option value="bank">Bank Transfer</option>
                        <option value="e-wallet">E-Wallet</option>
                        <option value="qrcode">QR Code</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" placeholder="Payment for job completion">Payment for completed freelance job</textarea>
                </div>
                
                <div class="modal-actions">
                    <button type="button" class="btn-cancel" onclick="document.getElementById('paymentModal').classList.remove('show')">Cancel</button>
                    <button type="submit" name="make_payment" class="btn-pay" style="padding:10px 24px;border-radius:8px;">
                        <i class="fa-regular fa-paper-plane"></i> Pay Now
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openPaymentModal(jobId, freelancerId, freelancerName, maxBudget) {
            document.getElementById('pay_job_id').value = jobId;
            document.getElementById('pay_freelancer_id').value = freelancerId;
            document.getElementById('pay_freelancer_name').textContent = freelancerName;
            document.getElementById('pay_amount').value = maxBudget || 0;
            document.getElementById('paymentModal').classList.add('show');
        }
    </script>
</body>
</html>