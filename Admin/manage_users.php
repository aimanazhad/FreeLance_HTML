<?php
require_once '../config.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('../index.php');
}

// ============================================
// ACTIONS
// ============================================
$success = '';
$error = '';

// Suspend / Activate user
if (isset($_GET['toggle_status']) && is_numeric($_GET['toggle_status'])) {
    $targetId = $_GET['toggle_status'];
    $target = getUserById($targetId);
    if ($target && $target['role'] !== 'admin') {
        $newStatus = ($target['status'] ?? 'active') === 'active' ? 'suspended' : 'active';
        updateUserStatus($targetId, $newStatus);
        $success = $newStatus === 'suspended' ? '✅ User suspended.' : '✅ User activated.';
    }
}

// Delete user
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $targetId = $_GET['delete'];
    $target = getUserById($targetId);
    if ($target && $target['role'] !== 'admin' && $targetId != $_SESSION['user_id']) {
        deleteUser($targetId);
        $success = '✅ User deleted successfully.';
    } else {
        $error = '⚠️ This user cannot be deleted.';
    }
}

// Edit user (name, email, role)
if (isset($_POST['edit_user'])) {
    $id = $_POST['user_id'];
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $role = $_POST['role'];

    if (empty($name) || empty($email)) {
        $error = '⚠️ Name and email are required.';
    } else {
        updateUser($id, $name, $email, $role);
        $success = '✅ User updated successfully.';
    }
}

// ============================================
// FILTERS
// ============================================
$search = trim($_GET['search'] ?? '');
$roleFilter = $_GET['role'] ?? '';
$statusFilter = $_GET['status'] ?? '';

$sql = "SELECT * FROM users WHERE 1=1";
$params = [];

if ($search !== '') {
    $sql .= " AND (name LIKE ? OR email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($roleFilter !== '') {
    $sql .= " AND role = ?";
    $params[] = $roleFilter;
}
if ($statusFilter !== '') {
    $sql .= " AND status = ?";
    $params[] = $statusFilter;
}
$sql .= " ORDER BY created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();

// Quick counts
$totalUsers = getTotalUsers();
$totalFreelancers = getTotalFreelancers();
$totalClients = getTotalClients();
$totalSuspended = $pdo->query("SELECT COUNT(*) FROM users WHERE status = 'suspended'")->fetchColumn();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - Admin</title>
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
        .page-header { margin-bottom: 28px; display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 12px; }
        .page-header h1 { font-size: 28px; font-weight: 800; color: #1f2937; }
        .page-header p { color: #6b7280; font-size: 14px; margin-top: 4px; }

        /* ===== MINI STATS ===== */
        .mini-stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 24px; }
        .mini-stat { background: #fff; border: 1px solid #e5e7eb; border-radius: 14px; padding: 16px 20px; }
        .mini-stat .num { font-size: 22px; font-weight: 800; color: #1f2937; }
        .mini-stat .lbl { font-size: 12px; color: #6b7280; margin-top: 2px; }

        /* ===== TOOLBAR ===== */
        .toolbar { display: flex; gap: 12px; margin-bottom: 20px; flex-wrap: wrap; }
        .toolbar input, .toolbar select {
            border: 1px solid #e5e7eb; border-radius: 10px; padding: 10px 14px;
            font-size: 13px; background: #fff;
        }
        .toolbar input { flex: 1; min-width: 200px; }
        .btn-filter { border: none; border-radius: 10px; padding: 10px 20px; font-size: 13px; font-weight: 600; background: #6366f1; color: #fff; cursor: pointer; }
        .btn-filter:hover { background: #4f46e5; }
        .btn-reset { border: none; border-radius: 10px; padding: 10px 20px; font-size: 13px; font-weight: 600; background: #6b7280; color: #fff; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; }

        /* ===== PANEL / TABLE ===== */
        .panel { background: #fff; border-radius: 16px; border: 1px solid #e5e7eb; padding: 24px; }
        .table-wrapper { overflow-x: auto; }
        .user-table { width: 100%; border-collapse: collapse; font-size: 13px; }
        .user-table thead th {
            text-align: left; padding: 10px 8px 12px; border-bottom: 2px solid #e5e7eb;
            color: #6b7280; font-weight: 600; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px;
        }
        .user-table tbody td { padding: 14px 8px; border-bottom: 1px solid #f3f4f6; vertical-align: middle; }
        .user-table tbody tr:hover td { background: #fafafa; }
        .user-table tbody tr:last-child td { border-bottom: none; }
        .user-meta .name { font-weight: 600; color: #1f2937; }
        .user-meta .email { display: block; font-size: 12px; color: #9ca3af; margin-top: 2px; }
        .badge-role { padding: 3px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; display: inline-block; }
        .badge-freelancer { background: #f5f3ff; color: #8b5cf6; }
        .badge-client { background: #fffbeb; color: #f59e0b; }
        .badge-admin { background: #fef2f2; color: #ef4444; }
        .status-indicator { padding: 3px 12px; border-radius: 6px; font-size: 12px; font-weight: 600; display: inline-block; }
        .status-active { background: #f0fdf4; color: #22c55e; }
        .status-suspended { background: #fef2f2; color: #ef4444; }
        .row-actions { display: flex; gap: 8px; }
        .row-actions button, .row-actions a {
            border: none; background: none; cursor: pointer; padding: 6px 8px; border-radius: 6px;
            color: #6b7280; font-size: 13px; text-decoration: none;
        }
        .row-actions .edit-btn:hover { background: #eef2ff; color: #6366f1; }
        .row-actions .suspend-btn:hover { background: #fffbeb; color: #d97706; }
        .row-actions .delete-btn:hover { background: #fef2f2; color: #dc2626; }
        .fm-empty { text-align: center; padding: 40px; color: #6b7280; }
        .alert { padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; font-size: 13px; }
        .alert-success { background: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0; }
        .alert-danger { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }

        /* ===== MODAL ===== */
        .modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.5); display: none; align-items: center; justify-content: center; z-index: 100; }
        .modal-overlay.show { display: flex; }
        .modal-box { background: #fff; border-radius: 16px; padding: 24px; width: 420px; max-width: 90%; }
        .modal-box h3 { margin-bottom: 16px; }
        .modal-box .form-group { margin-bottom: 14px; }
        .modal-box label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; color: #374151; }
        .modal-box input, .modal-box select {
            width: 100%; padding: 10px 14px; border: 1px solid #e5e7eb; border-radius: 10px; font-size: 14px; background: #fff;
        }
        .modal-actions { display: flex; gap: 10px; justify-content: flex-end; margin-top: 16px; }
        .btn-secondary { background: #f3f4f6; color: #1f2937; border: none; padding: 8px 18px; border-radius: 8px; cursor: pointer; font-weight: 600; }
        .btn-secondary:hover { background: #e5e7eb; }
        .btn-primary { background: #6366f1; color: #fff; border: none; padding: 8px 18px; border-radius: 8px; cursor: pointer; font-weight: 600; }
        .btn-primary:hover { background: #4f46e5; }

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
                <a href="manage_users.php" class="active"><i class="fa-solid fa-users"></i> Manage Users</a>
                <a href="manage_jobs.php"><i class="fa-solid fa-briefcase"></i> Manage Jobs</a>
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
                <div>
                    <h1>Manage Users</h1>
                    <p>View, edit, suspend, or remove platform users.</p>
                </div>
            </div>

            <?php if ($success): ?><div class="alert alert-success"><?php echo $success; ?></div><?php endif; ?>
            <?php if ($error): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php endif; ?>

            <div class="mini-stats">
                <div class="mini-stat"><div class="num"><?php echo $totalUsers; ?></div><div class="lbl">Total Users</div></div>
                <div class="mini-stat"><div class="num"><?php echo $totalFreelancers; ?></div><div class="lbl">Freelancers</div></div>
                <div class="mini-stat"><div class="num"><?php echo $totalClients; ?></div><div class="lbl">Clients</div></div>
                <div class="mini-stat"><div class="num"><?php echo $totalSuspended; ?></div><div class="lbl">Suspended</div></div>
            </div>

            <form method="GET" class="toolbar">
                <input type="text" name="search" placeholder="Search by name or email..." value="<?php echo escape($search); ?>">
                <select name="role">
                    <option value="">All Roles</option>
                    <option value="freelancer" <?php echo $roleFilter == 'freelancer' ? 'selected' : ''; ?>>Freelancer</option>
                    <option value="client" <?php echo $roleFilter == 'client' ? 'selected' : ''; ?>>Client</option>
                    <option value="admin" <?php echo $roleFilter == 'admin' ? 'selected' : ''; ?>>Admin</option>
                </select>
                <select name="status">
                    <option value="">All Status</option>
                    <option value="active" <?php echo $statusFilter == 'active' ? 'selected' : ''; ?>>Active</option>
                    <option value="suspended" <?php echo $statusFilter == 'suspended' ? 'selected' : ''; ?>>Suspended</option>
                </select>
                <button type="submit" class="btn-filter">Filter</button>
                <a href="manage_users.php" class="btn-reset">Reset</a>
            </form>

            <div class="panel">
                <?php if (count($users) > 0): ?>
                <div class="table-wrapper">
                    <table class="user-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Joined Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $u): ?>
                            <tr>
                                <td>
                                    <div class="user-meta">
                                        <span class="name"><?php echo escape($u['name']); ?></span>
                                        <span class="email"><?php echo escape($u['email']); ?></span>
                                    </div>
                                </td>
                                <td><span class="badge-role badge-<?php echo $u['role']; ?>"><?php echo ucfirst($u['role']); ?></span></td>
                                <td><span class="status-indicator status-<?php echo $u['status'] ?? 'active'; ?>"><?php echo ucfirst($u['status'] ?? 'Active'); ?></span></td>
                                <td><?php echo date('d M Y', strtotime($u['created_at'])); ?></td>
                                <td>
                                    <div class="row-actions">
                                        <a href="#" class="edit-btn" title="Edit"
                                           onclick="openEdit(<?php echo htmlspecialchars(json_encode($u), ENT_QUOTES); ?>); return false;">
                                            <i class="fa-solid fa-pen"></i>
                                        </a>
                                        <?php if ($u['role'] !== 'admin'): ?>
                                            <a href="manage_users.php?toggle_status=<?php echo $u['id']; ?>" class="suspend-btn" title="<?php echo ($u['status'] ?? 'active') === 'active' ? 'Suspend' : 'Activate'; ?>"
                                               onclick="return confirm('<?php echo ($u['status'] ?? 'active') === 'active' ? 'Suspend this user?' : 'Activate this user?'; ?>')">
                                                <i class="fa-solid <?php echo ($u['status'] ?? 'active') === 'active' ? 'fa-user-slash' : 'fa-user-check'; ?>"></i>
                                            </a>
                                            <a href="manage_users.php?delete=<?php echo $u['id']; ?>" class="delete-btn" title="Delete"
                                               onclick="return confirm('Delete this user permanently? This cannot be undone.')">
                                                <i class="fa-solid fa-trash"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                    <div class="fm-empty">No users found.</div>
                <?php endif; ?>
            </div>

        </main>
    </div>

    <!-- EDIT USER MODAL -->
    <div class="modal-overlay" id="editModal" onclick="if(event.target===this)closeEdit()">
        <div class="modal-box">
            <h3>Edit User</h3>
            <form method="POST">
                <input type="hidden" name="user_id" id="edit_id">
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="name" id="edit_name" required>
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" id="edit_email" required>
                </div>
                <div class="form-group">
                    <label>Role</label>
                    <select name="role" id="edit_role">
                        <option value="freelancer">Freelancer</option>
                        <option value="client">Client</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn-secondary" onclick="closeEdit()">Cancel</button>
                    <button type="submit" name="edit_user" class="btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openEdit(user) {
            document.getElementById('edit_id').value = user.id;
            document.getElementById('edit_name').value = user.name;
            document.getElementById('edit_email').value = user.email;
            document.getElementById('edit_role').value = user.role;
            document.getElementById('editModal').classList.add('show');
        }
        function closeEdit() {
            document.getElementById('editModal').classList.remove('show');
        }
    </script>

</body>
</html>
