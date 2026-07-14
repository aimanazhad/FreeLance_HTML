<?php
require_once 'config.php';

if (!isLoggedIn() || !isFreelancer()) {
    redirect('index.php');
}

$user_id = $_SESSION['user_id'];

// Get all active jobs (only active ones)
$jobs = $pdo->query("
    SELECT * FROM jobs 
    WHERE status = 'active' 
    ORDER BY created_at DESC
")->fetchAll();

// Get categories for filter
$categories = $pdo->query("SELECT DISTINCT category FROM jobs WHERE status = 'active'")->fetchAll();

// Apply to job
if (isset($_GET['apply']) && is_numeric($_GET['apply'])) {
    $job_id = $_GET['apply'];
    
    // Check if already applied
    $check = $pdo->prepare("SELECT * FROM applications WHERE job_id = ? AND freelancer_id = ?");
    $check->execute([$job_id, $user_id]);
    
    if ($check->rowCount() == 0) {
        $stmt = $pdo->prepare("INSERT INTO applications (job_id, freelancer_id, status, applied_at) VALUES (?, ?, 'pending', NOW())");
        $stmt->execute([$job_id, $user_id]);
        $success = '✅ Application submitted successfully!';
    } else {
        $error = '⚠️ You already applied for this job.';
    }
    
    // Refresh jobs
    $jobs = $pdo->query("
        SELECT * FROM jobs 
        WHERE status = 'active' 
        ORDER BY created_at DESC
    ")->fetchAll();
}

// Filter logic
$filteredJobs = $jobs;
if (isset($_GET['category']) && !empty($_GET['category'])) {
    $filteredJobs = array_filter($filteredJobs, function($j) {
        return $j['category'] == $_GET['category'];
    });
}
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search = strtolower($_GET['search']);
    $filteredJobs = array_filter($filteredJobs, function($j) use ($search) {
        return strpos(strtolower($j['title']), $search) !== false;
    });
}

// Get application statuses for all jobs
$appliedStatus = [];
$stmt = $pdo->prepare("SELECT job_id, status FROM applications WHERE freelancer_id = ?");
$stmt->execute([$user_id]);
while ($row = $stmt->fetch()) {
    $appliedStatus[$row['job_id']] = $row['status'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Browse Jobs</title>
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

        .toolbar {
            display: flex; gap: 12px; margin-bottom: 20px; flex-wrap: wrap;
            background: white; padding: 16px; border-radius: 12px; border: 1px solid #e5e7eb;
        }
        .toolbar input, .toolbar select {
            padding: 10px 14px; border: 1px solid #e5e7eb; border-radius: 8px; font-size: 14px;
            background: #f9fafb; outline: none; flex: 1; min-width: 160px; color: #1f2937;
        }
        .toolbar input:focus, .toolbar select:focus {
            border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99,102,241,0.1);
        }
        .toolbar .btn {
            padding: 10px 20px; border: none; border-radius: 8px; font-weight: 600; font-size: 13px;
            cursor: pointer; transition: all 0.2s ease; text-decoration: none; display: inline-flex; align-items: center; gap: 6px;
        }
        .toolbar .btn-primary { background: #6366f1; color: white; }
        .toolbar .btn-primary:hover { background: #4f46e5; }
        .toolbar .btn-secondary { background: #f1f5f9; color: #64748b; }
        .toolbar .btn-secondary:hover { background: #e2e8f0; }

        .jobs-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px; }
        .job-card {
            background: white; border-radius: 12px; padding: 20px; border: 1px solid #e5e7eb;
            display: flex; flex-direction: column; gap: 10px; transition: all 0.2s ease;
        }
        .job-card:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(0,0,0,0.06); }
        .job-card .top { display: flex; justify-content: space-between; align-items: flex-start; }
        .job-card .icon {
            width: 44px; height: 44px; border-radius: 10px; display: flex; align-items: center;
            justify-content: center; font-weight: 700; font-size: 16px; background: #f2eeff; color: #6c4ce0;
        }
        .job-card .title { font-weight: 700; font-size: 15px; color: #0f172a; }
        .job-card .meta { font-size: 12px; color: #94a3b8; }
        .job-card .budget { font-weight: 700; font-size: 14px; color: #0f172a; margin-top: 4px; }
        .job-card .desc { font-size: 13px; color: #64748b; line-height: 1.5; }
        .job-card .footer { display: flex; justify-content: space-between; align-items: center; margin-top: auto; }
        .job-card .pill {
            padding: 3px 10px; border-radius: 999px; font-size: 11px; font-weight: 600;
            background: #ecfdf5; color: #10b981;
        }

        .btn-apply {
            background: #6366f1; color: white; border: none; padding: 6px 14px; border-radius: 6px;
            font-weight: 600; font-size: 12px; cursor: pointer; text-decoration: none; transition: all 0.2s ease;
        }
        .btn-apply:hover { background: #4f46e5; }
        .btn-applied {
            background: #f1f5f9; color: #94a3b8; border: none; padding: 6px 14px; border-radius: 6px;
            font-weight: 600; font-size: 12px; cursor: default;
        }
        .btn-applied.pending { background: #fef3c7; color: #d97706; }
        .btn-applied.accepted { background: #d1fae5; color: #047857; }
        .btn-applied.rejected { background: #fee2e2; color: #dc2626; }
        .btn-applied.in_progress { background: #fef3c7; color: #d97706; }
        .btn-applied.completed { background: #dbeafe; color: #1d4ed8; }

        .alert { padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; font-weight: 600; font-size: 14px; }
        .alert-success { background: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0; }
        .alert-danger { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }

        .empty { text-align: center; padding: 60px; color: #94a3b8; grid-column: span 2; }
        .empty i { font-size: 48px; color: #d1d5db; margin-bottom: 16px; display: block; }
        .empty h3 { font-size: 18px; font-weight: 600; color: #1f2937; margin-bottom: 8px; }
        .empty p { font-size: 14px; color: #94a3b8; }

        @media (max-width: 1024px) { .jobs-grid { grid-template-columns: 1fr; } .empty { grid-column: span 1; } }
        @media (max-width: 768px) {
            .sidebar { width: 200px; padding: 16px 12px; }
            .main-content { padding: 20px; }
            .toolbar { flex-direction: column; }
            .hero-banner .emoji { display: none; }
            .hero-banner h1 { font-size: 24px; }
        }
        @media (max-width: 480px) {
            .sidebar { width: 100%; height: auto; position: relative; border-right: none; border-bottom: 1px solid #e5e7eb; }
            .dashboard-container { flex-direction: column; }
            .sidebar-menu { flex-direction: row; flex-wrap: wrap; }
            .sidebar-menu a { padding: 8px 12px; font-size: 13px; }
            .sidebar-menu .logout { margin-top: 0; border-top: none; padding-top: 0; }
            .jobs-grid { grid-template-columns: 1fr; }
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
            <a href="browse_jobs.php" class="active"><i class="fa-solid fa-briefcase"></i> Browse Jobs</a>
            <a href="my_applications.php"><i class="fa-solid fa-file-lines"></i> My Applications</a>
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
                <h1>🔍 Browse Jobs</h1>
                <p>Find projects that match your skills.</p>
            </div>
            <div class="emoji">🚀</div>
        </div>

        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="GET" class="toolbar">
            <input type="text" name="search" placeholder="Search jobs..." value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
            <select name="category">
                <option value="">All Categories</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo $cat['category']; ?>" <?php echo ($_GET['category'] ?? '') == $cat['category'] ? 'selected' : ''; ?>>
                        <?php echo $cat['category']; ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-primary"><i class="fa-solid fa-filter"></i> Filter</button>
            <a href="browse_jobs.php" class="btn btn-secondary"><i class="fa-solid fa-rotate-left"></i> Reset</a>
        </form>

        <div class="jobs-grid">
            <?php if (count($filteredJobs) > 0): ?>
                <?php foreach ($filteredJobs as $job): 
                    $status = $appliedStatus[$job['id']] ?? null;
                ?>
                <div class="job-card">
                    <div class="top">
                        <div style="display:flex;gap:12px;align-items:center;">
                            <div class="icon"><?php echo strtoupper(substr($job['title'], 0, 1)); ?></div>
                            <div>
                                <div class="title"><?php echo escape($job['title']); ?></div>
                                <div class="meta"><?php echo escape($job['category']); ?></div>
                            </div>
                        </div>
                    </div>
                    <div class="desc"><?php echo escape(substr($job['description'] ?? '', 0, 120)) . '...'; ?></div>
                    <div class="footer">
                        <div>
                            <div class="budget">RM <?php echo number_format($job['budget_min'], 2); ?> - RM <?php echo number_format($job['budget_max'], 2); ?></div>
                            <span class="pill"><?php echo ucfirst($job['location_type']); ?></span>
                        </div>
                        <?php if ($status): ?>
                            <span class="btn-applied <?php echo $status; ?>">
                                <?php if ($status == 'pending'): ?>
                                    <i class="fa-regular fa-clock"></i> Pending
                                <?php elseif ($status == 'accepted'): ?>
                                    <i class="fa-regular fa-check-circle"></i> Accepted 🎉
                                <?php elseif ($status == 'rejected'): ?>
                                    <i class="fa-regular fa-circle-xmark"></i> Rejected
                                <?php elseif ($status == 'in_progress'): ?>
                                    <i class="fa-regular fa-spinner"></i> In Progress
                                <?php elseif ($status == 'completed'): ?>
                                    <i class="fa-regular fa-check-circle"></i> Completed
                                <?php else: ?>
                                    <?php echo ucfirst($status); ?>
                                <?php endif; ?>
                            </span>
                        <?php else: ?>
                            <a href="browse_jobs.php?apply=<?php echo $job['id']; ?>" class="btn-apply" onclick="return confirm('Apply for this job?')">Apply Now</a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty">
                    <i class="fa-regular fa-face-frown"></i>
                    <h3>No jobs found</h3>
                    <p>Try adjusting your filters or search terms.</p>
                </div>
            <?php endif; ?>
        </div>

    </main>
</div>

</body>
</html>