<?php
require_once 'config.php';

if (!isLoggedIn() || !isFreelancer()) {
    redirect('../index.php');
}

$user_id = $_SESSION['user_id'];

// Get all active jobs
$jobs = $pdo->query("SELECT * FROM jobs WHERE status = 'active' ORDER BY created_at DESC")->fetchAll();

// Get categories for filter
$categories = $pdo->query("SELECT DISTINCT category FROM jobs WHERE status = 'active'")->fetchAll();

// Apply to job
if (isset($_GET['apply']) && is_numeric($_GET['apply'])) {
    $job_id = $_GET['apply'];
    
    $check = $pdo->prepare("SELECT * FROM applications WHERE job_id = ? AND freelancer_id = ?");
    $check->execute([$job_id, $user_id]);
    
    if ($check->rowCount() == 0) {
        $stmt = $pdo->prepare("INSERT INTO applications (job_id, freelancer_id, status) VALUES (?, ?, 'pending')");
        $stmt->execute([$job_id, $user_id]);
        $success = '✅ Application submitted successfully!';
    } else {
        $error = '⚠️ You already applied for this job.';
    }
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Browse Jobs - Freelancer</title>
    <link rel="stylesheet" href="freelancer.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        .fm-toolbar { display: flex; gap: 12px; margin-bottom: 20px; flex-wrap: wrap; }
        .fm-input { border: 1px solid #e5e7eb; border-radius: 10px; padding: 10px 14px; font-size: 13px; background: #fff; flex: 1; min-width: 180px; }
        .fm-select { border: 1px solid #e5e7eb; border-radius: 10px; padding: 10px 14px; font-size: 13px; background: #fff; }
        .fm-btn { border: none; border-radius: 10px; padding: 10px 18px; font-size: 13px; font-weight: 600; background: #6366f1; color: #fff; cursor: pointer; }
        .fm-btn:hover { background: #4f46e5; }
        .fm-jobs-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px; }
        .fm-job-card { background: #fff; border-radius: 16px; padding: 18px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); display: flex; flex-direction: column; gap: 10px; }
        .fm-job-card-top { display: flex; justify-content: space-between; align-items: flex-start; }
        .fm-job-icon { width: 44px; height: 44px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 16px; background: #f2eeff; color: #6c4ce0; }
        .fm-job-title { font-weight: 700; font-size: 15px; }
        .fm-job-meta { font-size: 12px; color: #6b7280; }
        .fm-job-budget { font-weight: 700; font-size: 14px; color: #1f2937; }
        .fm-pill { padding: 3px 10px; border-radius: 999px; font-size: 11px; font-weight: 600; }
        .fm-pill-remote { background: #eaf6ef; color: #1faa6d; }
        .fm-job-card-desc { font-size: 13px; color: #6b7280; }
        .fm-job-card-foot { display: flex; justify-content: space-between; align-items: center; margin-top: auto; }
        .btn-apply { background: #6366f1; color: white; border: none; padding: 8px 16px; border-radius: 8px; font-weight: 600; cursor: pointer; text-decoration: none; display: inline-block; }
        .btn-apply:hover { background: #4f46e5; }
        .btn-applied { background: #d1d5db; color: #6b7280; border: none; padding: 8px 16px; border-radius: 8px; font-weight: 600; cursor: default; }
        .alert { padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; }
        .alert-success { background: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0; }
        .alert-danger { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }
        .fm-bookmark { background: none; border: none; color: #6b7280; cursor: pointer; font-size: 18px; }
        .fm-bookmark.active { color: #6366f1; }
        .fm-empty { text-align: center; padding: 40px; color: #6b7280; }
        @media (max-width: 768px) { .fm-jobs-grid { grid-template-columns: 1fr; } }
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
                <a href="browse_jobs.php" class="menu-item active"><i class="fa-solid fa-briefcase"></i> Browse Jobs</a>
                <a href="my_applications.php" class="menu-item"><i class="fa-solid fa-file-lines"></i> My Applications</a>
                <a href="messages.php" class="menu-item"><i class="fa-solid fa-comment-dots"></i> Messages</a>
                <a href="portfolio.php" class="menu-item"><i class="fa-solid fa-folder-open"></i> Portfolio</a>
                <a href="earnings.php" class="menu-item"><i class="fa-solid fa-wallet"></i> Earnings</a>
                <a href="profile.php" class="menu-item"><i class="fa-solid fa-user"></i> Profile</a>
                <a href="settings.php" class="menu-item"><i class="fa-solid fa-gear"></i> Settings</a>
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
                <div class="fm-hero-eyebrow">Explore</div>
                <h1>Browse Jobs</h1>
                <p>Find projects that match your skills.</p>
            </div>

            <?php if (isset($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <form method="GET" class="fm-toolbar">
                <input class="fm-input" name="search" placeholder="Search jobs..." value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
                <select class="fm-select" name="category">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat['category']; ?>" <?php echo ($_GET['category'] ?? '') == $cat['category'] ? 'selected' : ''; ?>>
                            <?php echo $cat['category']; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="fm-btn">Filter</button>
                <a href="browse_jobs.php" class="fm-btn" style="background:#6b7280;">Reset</a>
            </form>

            <div class="fm-jobs-grid">
                <?php if (count($filteredJobs) > 0): ?>
                    <?php foreach ($filteredJobs as $job): 
                        $applied = $pdo->prepare("SELECT * FROM applications WHERE job_id = ? AND freelancer_id = ?");
                        $applied->execute([$job['id'], $user_id]);
                        $isApplied = $applied->rowCount() > 0;
                    ?>
                    <div class="fm-job-card">
                        <div class="fm-job-card-top">
                            <div style="display:flex;gap:12px;align-items:center;">
                                <div class="fm-job-icon"><?php echo strtoupper(substr($job['title'], 0, 1)); ?></div>
                                <div>
                                    <div class="fm-job-title"><?php echo escape($job['title']); ?></div>
                                    <div class="fm-job-meta"><?php echo escape($job['category']); ?></div>
                                </div>
                            </div>
                            <button class="fm-bookmark <?php echo $job['bookmarked'] ?? false ? 'active' : ''; ?>">
                                <i class="fa-<?php echo ($job['bookmarked'] ?? false) ? 'solid' : 'regular'; ?> fa-bookmark"></i>
                            </button>
                        </div>
                        <div class="fm-job-card-desc"><?php echo escape(substr($job['description'] ?? '', 0, 100)) . '...'; ?></div>
                        <div class="fm-job-card-foot">
                            <div>
                                <div class="fm-job-budget">RM <?php echo number_format($job['budget_min'], 2); ?> - RM <?php echo number_format($job['budget_max'], 2); ?></div>
                                <span class="fm-pill fm-pill-remote"><?php echo ucfirst($job['location_type']); ?></span>
                            </div>
                            <?php if ($isApplied): ?>
                                <span class="btn-applied">Applied ✓</span>
                            <?php else: ?>
                                <a href="browse_jobs.php?apply=<?php echo $job['id']; ?>" class="btn-apply" onclick="return confirm('Apply for this job?')">Apply Now</a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="fm-empty">No jobs found.</div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>