<?php
require_once '../config.php';

if (!isAdminLoggedIn()) {
    redirect('adminlogin.php');
}

// ============================================
// ACTIONS
// ============================================
$success = '';
$error = '';

// Change job status
if (isset($_POST['change_status'])) {
    $jobId = $_POST['job_id'];
    $newStatus = $_POST['status'];
    $allowed = ['active', 'in_progress', 'completed', 'closed'];
    if (in_array($newStatus, $allowed)) {
        $stmt = $pdo->prepare("UPDATE jobs SET status = ? WHERE id = ?");
        $stmt->execute([$newStatus, $jobId]);
        $success = '✅ Job status updated.';
    }
}

// Delete job
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $jobId = $_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM jobs WHERE id = ?");
    $stmt->execute([$jobId]);
    // Clean up related applications too
    $stmt2 = $pdo->prepare("DELETE FROM applications WHERE job_id = ?");
    $stmt2->execute([$jobId]);
    $success = '✅ Job deleted successfully.';
}

// ============================================
// FILTERS
// ============================================
$search = trim($_GET['search'] ?? '');
$statusFilter = $_GET['status'] ?? '';
$categoryFilter = $_GET['category'] ?? '';

$sql = "SELECT j.*, u.name as client_name,
        (SELECT COUNT(*) FROM applications a WHERE a.job_id = j.id) as applicant_count
        FROM jobs j
        JOIN users u ON j.client_id = u.id
        WHERE 1=1";
$params = [];

if ($search !== '') {
    $sql .= " AND (j.title LIKE ? OR u.name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($statusFilter !== '') {
    $sql .= " AND j.status = ?";
    $params[] = $statusFilter;
}
if ($categoryFilter !== '') {
    $sql .= " AND j.category = ?";
    $params[] = $categoryFilter;
}
$sql .= " ORDER BY j.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$jobs = $stmt->fetchAll();

$categories = $pdo->query("SELECT DISTINCT category FROM jobs ORDER BY category")->fetchAll();

// Quick counts
$totalJobs = $pdo->query("SELECT COUNT(*) FROM jobs")->fetchColumn();
$activeJobs = $pdo->query("SELECT COUNT(*) FROM jobs WHERE status = 'active'")->fetchColumn();
$inProgressJobs = $pdo->query("SELECT COUNT(*) FROM jobs WHERE status = 'in_progress'")->fetchColumn();
$completedJobs = $pdo->query("SELECT COUNT(*) FROM jobs WHERE status = 'completed'")->fetchColumn();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Jobs - Admin</title>
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
        .sidebar-menu .logout { margin-top: 20px; border-top: 1px solid #e5e7eb; padding-top: 16px; color: #ef4444; }
        .sidebar-menu .logout:hover { background: #fef2f2; color: #dc2626; }

        /* ===== MAIN CONTENT ===== */
        .main-content { flex: 1; padding: 32px 40px 60px; overflow-y: auto; }
        .page-header { margin-bottom: 28px; }
        .page-header h1 { font-size: 28px; font-weight: 800; color: #1f2937; }
        .page-header p { color: #6b7280; font-size: 14px; margin-top: 4px; }

        /* ===== MINI STATS ===== */
        .mini-stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 24px; }
        .mini-stat { background: #fff; border: 1px solid #e5e7eb; border-radius: 14px; padding: 16px 20px; }
        .mini-stat .num { font-size: 22px; font-weight: 800; color: #1f2937; }
        .mini-stat .lbl { font-size: 12px; color: #6b7280; margin-top: 2px; }

        /* ===== TOOLBAR ===== */
        .toolbar { display: flex; gap: 12px; margin-bottom: 20px; flex-wrap: wrap; }
        .toolbar input, .toolbar select { border: 1px solid #e5e7eb; border-radius: 10px; padding: 10px 14px; font-size: 13px; background: #fff; }
        .toolbar input { flex: 1; min-width: 200px; }
        .btn-filter { border: none; border-radius: 10px; padding: 10px 20px; font-size: 13px; font-weight: 600; background: #6366f1; color: #fff; cursor: pointer; }
        .btn-filter:hover { background: #4f46e5; }
        .btn-reset { border: none; border-radius: 10px; padding: 10px 20px; font-size: 13px; font-weight: 600; background: #6b7280; color: #fff; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; }

        /* ===== PANEL / TABLE ===== */
        .panel { background: #fff; border-radius: 16px; border: 1px solid #e5e7eb; padding: 24px; }
        .table-wrapper { overflow-x: auto; }
        .job-table { width: 100%; border-collapse: collapse; font-size: 13px; }
        .job-table thead th {
            text-align: left; padding: 10px 8px 12px; border-bottom: 2px solid #e5e7eb;
            color: #6b7280; font-weight: 600; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px;
        }
        .job-table tbody td { padding: 14px 8px; border-bottom: 1px solid #f3f4f6; vertical-align: middle; }
        .job-table tbody tr:hover td { background: #fafafa; }
        .job-table tbody tr:last-child td { border-bottom: none; }
        .job-meta .title { font-weight: 600; color: #1f2937; }
        .job-meta .cat { display: block; font-size: 12px; color: #9ca3af; margin-top: 2px; }
        .badge-status { padding: 3px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; display: inline-block; }
        .badge-active { background: #e0f2fe; color: #0369a1; }
        .badge-in_progress { background: #fef9c3; color: #a16207; }
        .badge-completed { background: #dcfce7; color: #15803d; }
        .badge-closed { background: #f3f4f6; color: #6b7280; }
        .status-select { border: 1px solid #e5e7eb; border-radius: 8px; padding: 6px 10px; font-size: 12px; background: #fff; cursor: pointer; }
        .row-actions { display: flex; gap: 8px; align-items: center; }
        .row-actions a { border: none; background: none; cursor: pointer; padding: 6px 8px; border-radius: 6px; color: #6b7280; text-decoration: none; }
        .row-actions .delete-btn:hover { background: #fef2f2; color: #dc2626; }
        .fm-empty { text-align: center; padding: 40px; color: #6b7280; }
        .alert { padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; font-size: 13px; }
        .alert-success { background: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0; }
        .alert-danger { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }
        .budget-cell { font-weight: 600; white-space: nowrap; }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 1024px) { .mini-stats { grid-template-columns: repeat(2, 1fr); } }
        @media (max-width: 768px) {
            .sidebar { width: 200px; padding: 16px 12px; }
            .main-content { padding: 20px; }
            .mini-stats { grid-template-columns: 1fr; }
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
                <a href="dashboard_admin.php"><i class="fa-solid fa-house"></i> Dashboard</a>
                <a href="manage_users.php"><i class="fa-solid fa-users"></i> Manage Users</a>
                <a href="manage_jobs.php" class="active"><i class="fa-solid fa-briefcase"></i> Manage Jobs</a>
                <a href="reports.php"><i class="fa-solid fa-chart-pie"></i> Reports</a>
                <a href="messages.php"><i class="fa-solid fa-comment-dots"></i> Messages</a>
                <a href="settings.php"><i class="fa-solid fa-gear"></i> Settings</a>
                <a href="profile.php"><i class="fa-solid fa-user"></i> Profile</a>
                <a href="adminlogin.php?logout=1" class="logout" onclick="return confirm('Are you sure you want to logout?')">
                    <i class="fa-solid fa-right-from-bracket"></i> Logout
                </a>
            </nav>
        </aside>

        <!-- MAIN CONTENT -->
        <main class="main-content">

            <div class="page-header">
                <h1>Manage Jobs</h1>
                <p>Monitor, update, or remove job postings on the platform.</p>
            </div>

            <?php if ($success): ?><div class="alert alert-success"><?php echo $success; ?></div><?php endif; ?>
            <?php if ($error): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php endif; ?>

            <div class="mini-stats">
                <div class="mini-stat"><div class="num"><?php echo $totalJobs; ?></div><div class="lbl">Total Jobs</div></div>
                <div class="mini-stat"><div class="num"><?php echo $activeJobs; ?></div><div class="lbl">Active</div></div>
                <div class="mini-stat"><div class="num"><?php echo $inProgressJobs; ?></div><div class="lbl">In Progress</div></div>
                <div class="mini-stat"><div class="num"><?php echo $completedJobs; ?></div><div class="lbl">Completed</div></div>
            </div>

            <form method="GET" class="toolbar">
                <input type="text" name="search" placeholder="Search by job title or client..." value="<?php echo escape($search); ?>">
                <select name="category">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo escape($cat['category']); ?>" <?php echo $categoryFilter == $cat['category'] ? 'selected' : ''; ?>>
                            <?php echo escape($cat['category']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <select name="status">
                    <option value="">All Status</option>
                    <option value="active" <?php echo $statusFilter == 'active' ? 'selected' : ''; ?>>Active</option>
                    <option value="in_progress" <?php echo $statusFilter == 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                    <option value="completed" <?php echo $statusFilter == 'completed' ? 'selected' : ''; ?>>Completed</option>
                    <option value="closed" <?php echo $statusFilter == 'closed' ? 'selected' : ''; ?>>Closed</option>
                </select>
                <button type="submit" class="btn-filter">Filter</button>
                <a href="manage_jobs.php" class="btn-reset">Reset</a>
            </form>

            <div class="panel">
                <?php if (count($jobs) > 0): ?>
                <div class="table-wrapper">
                    <table class="job-table">
                        <thead>
                            <tr>
                                <th>Job</th>
                                <th>Client</th>
                                <th>Budget</th>
                                <th>Applicants</th>
                                <th>Status</th>
                                <th>Posted</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($jobs as $job): ?>
                            <tr>
                                <td>
                                    <div class="job-meta">
                                        <span class="title"><?php echo escape($job['title']); ?></span>
                                        <span class="cat"><?php echo escape($job['category']); ?></span>
                                    </div>
                                </td>
                                <td><?php echo escape($job['client_name']); ?></td>
                                <td class="budget-cell">RM <?php echo number_format($job['budget_min'], 0); ?> - <?php echo number_format($job['budget_max'], 0); ?></td>
                                <td><?php echo $job['applicant_count']; ?></td>
                                <td>
                                    <form method="POST" style="display:inline-flex;align-items:center;gap:6px;">
                                        <input type="hidden" name="job_id" value="<?php echo $job['id']; ?>">
                                        <select name="status" class="status-select" onchange="this.form.submit()">
                                            <option value="active" <?php echo $job['status'] == 'active' ? 'selected' : ''; ?>>Active</option>
                                            <option value="in_progress" <?php echo $job['status'] == 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                                            <option value="completed" <?php echo $job['status'] == 'completed' ? 'selected' : ''; ?>>Completed</option>
                                            <option value="closed" <?php echo $job['status'] == 'closed' ? 'selected' : ''; ?>>Closed</option>
                                        </select>
                                        <input type="hidden" name="change_status" value="1">
                                    </form>
                                </td>
                                <td><?php echo date('d M Y', strtotime($job['created_at'])); ?></td>
                                <td>
                                    <div class="row-actions">
                                        <a href="manage_jobs.php?delete=<?php echo $job['id']; ?>" class="delete-btn" title="Delete"
                                           onclick="return confirm('Delete this job? All related applications will also be removed.')">
                                            <i class="fa-solid fa-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                    <div class="fm-empty">No jobs found.</div>
                <?php endif; ?>
            </div>

        </main>
    </div>

</body>
</html>
