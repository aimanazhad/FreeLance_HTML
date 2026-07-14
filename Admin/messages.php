<?php
require_once '../config.php';

// Restrict page to logged in admins only
if (!isLoggedIn() || !isAdmin()) {
    redirect('../index.php');
}

// Filter by user name (sender or receiver)
$search = trim($_GET['search'] ?? '');

$sql = "
    SELECT m.*, u1.name as sender_name, u1.role as sender_role,
           u2.name as receiver_name, u2.role as receiver_role
    FROM messages m
    JOIN users u1 ON m.sender_id = u1.id
    JOIN users u2 ON m.receiver_id = u2.id
";
$params = [];

if ($search !== '') {
    $sql .= " WHERE u1.name LIKE ? OR u2.name LIKE ?";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
$sql .= " ORDER BY m.created_at DESC LIMIT 100";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$messages = $stmt->fetchAll();

$totalMessages = $pdo->query("SELECT COUNT(*) FROM messages")->fetchColumn();

// Delete a message (moderation)
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM messages WHERE id = ?");
    $stmt->execute([$_GET['delete']]);
    redirect('messages.php?success=deleted');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages - Admin</title>
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

        /* Toolbar */
        .toolbar { display: flex; gap: 12px; margin-bottom: 20px; }
        .toolbar input { flex: 1; border: 1px solid #e5e7eb; border-radius: 10px; padding: 10px 14px; font-size: 13px; background: #fff; }
        .btn-filter { border: none; border-radius: 10px; padding: 10px 20px; font-size: 13px; font-weight: 600; background: #6366f1; color: #fff; cursor: pointer; }
        .btn-filter:hover { background: #4f46e5; }

        /* Message list */
        .panel { background: #fff; border-radius: 16px; border: 1px solid #e5e7eb; padding: 24px; }
        .msg-count { font-size: 13px; color: #6b7280; margin-bottom: 16px; }
        .msg-row { display: flex; align-items: flex-start; gap: 14px; padding: 14px 0; border-bottom: 1px solid #f3f4f6; }
        .msg-row:last-child { border-bottom: none; }
        .msg-flow { font-size: 13px; font-weight: 600; color: #1f2937; }
        .msg-flow .role { font-size: 11px; font-weight: 500; color: #9ca3af; }
        .msg-text { font-size: 13px; color: #4b5563; margin-top: 4px; }
        .msg-time { font-size: 12px; color: #9ca3af; white-space: nowrap; margin-left: auto; }
        .msg-delete { color: #dc2626; background: none; border: none; cursor: pointer; padding: 4px 8px; border-radius: 6px; text-decoration: none; }
        .msg-delete:hover { background: #fef2f2; }
        .fm-empty { text-align: center; padding: 40px; color: #6b7280; }
        .alert { padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; font-size: 13px; }
        .alert-success { background: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0; }

        @media (max-width: 768px) { .sidebar { width: 200px; padding: 16px 12px; } .main-content { padding: 20px; } }
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
                <a href="reports.php"><i class="fa-solid fa-chart-pie"></i> Reports</a>
                <a href="messages.php" class="active"><i class="fa-solid fa-comment-dots"></i> Messages</a>
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
                <h1>Messages</h1>
                <p>Monitor conversations between clients and freelancers.</p>
            </div>

            <?php if (isset($_GET['success']) && $_GET['success'] == 'deleted'): ?>
                <div class="alert alert-success">Message removed successfully.</div>
            <?php endif; ?>

            <!-- Search by user name -->
            <form method="GET" class="toolbar">
                <input type="text" name="search" placeholder="Search by sender or receiver name..." value="<?php echo escape($search); ?>">
                <button type="submit" class="btn-filter">Search</button>
            </form>

            <div class="panel">
                <div class="msg-count">Showing <?php echo count($messages); ?> of <?php echo $totalMessages; ?> total messages</div>

                <?php if (count($messages) > 0): ?>
                    <?php foreach ($messages as $m): ?>
                    <div class="msg-row">
                        <div style="flex:1;">
                            <div class="msg-flow">
                                <?php echo escape($m['sender_name']); ?> <span class="role">(<?php echo ucfirst($m['sender_role']); ?>)</span>
                                &rarr;
                                <?php echo escape($m['receiver_name']); ?> <span class="role">(<?php echo ucfirst($m['receiver_role']); ?>)</span>
                            </div>
                            <div class="msg-text"><?php echo escape($m['message']); ?></div>
                        </div>
                        <span class="msg-time"><?php echo date('d M Y, h:i A', strtotime($m['created_at'])); ?></span>
                        <a href="messages.php?delete=<?php echo $m['id']; ?>" class="msg-delete" title="Remove message"
                           onclick="return confirm('Remove this message?')">
                            <i class="fa-solid fa-trash"></i>
                        </a>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="fm-empty">No messages found.</div>
                <?php endif; ?>
            </div>

        </main>
    </div>

</body>
</html>
