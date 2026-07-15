<?php
require_once 'config.php';

if (!isLoggedIn() || !isFreelancer()) {
    redirect('index.php');
}

$user_id = $_SESSION['user_id'];

// Get earnings (payments received as freelancer)
$payments = $pdo->query("
    SELECT p.*, 
           j.title as job_title,
           u.name as client_name
    FROM payments p
    LEFT JOIN jobs j ON p.job_id = j.id
    LEFT JOIN users u ON p.user_id = u.id
    WHERE p.freelancer_id = $user_id
    ORDER BY p.created_at DESC
")->fetchAll();

$paidBalance = $pdo->query("SELECT SUM(amount) FROM payments WHERE freelancer_id = $user_id AND status = 'paid'")->fetchColumn() ?? 0;
$pendingBalance = $pdo->query("SELECT SUM(amount) FROM payments WHERE freelancer_id = $user_id AND status = 'pending'")->fetchColumn() ?? 0;
$availableBalance = $paidBalance + $pendingBalance;
$pendingDisplay = $pendingBalance < 0 ? abs($pendingBalance) : $pendingBalance;

// Process withdrawal
if (isset($_POST['withdraw'])) {
    $amount = $_POST['amount'];
    if ($amount > 0 && $amount <= $availableBalance) {
        $stmt = $pdo->prepare("INSERT INTO payments (user_id, freelancer_id, amount, method, description, status) VALUES (?, ?, ?, 'bank', 'Withdrawal', 'pending')");
        $stmt->execute([$_SESSION['user_id'], $user_id, -$amount]);
        redirect('earnings.php?success=withdrawn');
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Earnings</title>
    <link rel="stylesheet" href="freelancer.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body { background: #f3f0ff; min-height: 100vh; }

        .dashboard-container { display: flex; min-height: 100vh; }

        /* SIDEBAR */
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

        /* EARNINGS HERO */
        .earnings-hero {
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            border-radius: 16px;
            padding: 32px 40px;
            color: white;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 16px;
            position: relative;
            overflow: hidden;
        }
        .earnings-hero::after {
            content: '';
            position: absolute;
            right: -60px;
            top: -60px;
            width: 200px;
            height: 200px;
            border-radius: 50%;
            background: rgba(255,255,255,0.05);
        }
        .earnings-hero .label { font-size: 14px; opacity: 0.85; }
        .earnings-hero .value { font-size: 32px; font-weight: 700; margin-top: 4px; }
        .earnings-hero .btn-withdraw {
            background: white; color: #6366f1; border: none; padding: 10px 24px; border-radius: 10px;
            font-weight: 600; font-size: 14px; cursor: pointer; transition: all 0.2s ease; position: relative; z-index: 1;
        }
        .earnings-hero .btn-withdraw:hover { background: #f0f0ff; }

        /* STATS */
        .earnings-stats {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            margin-bottom: 20px;
        }
        .earnings-stat-card {
            background: white; padding: 16px; border-radius: 12px; border: 1px solid #e5e7eb; text-align: center;
        }
        .earnings-stat-card .number { font-size: 28px; font-weight: 700; }
        .earnings-stat-card .label { font-size: 13px; color: #6b7280; }
        .earnings-stat-card .green { color: #10b981; }
        .earnings-stat-card .yellow { color: #eab308; }

        /* TABLE */
        .panel {
            background: white; border-radius: 12px; border: 1px solid #e5e7eb; padding: 24px;
        }
        .panel h2 { font-size: 16px; font-weight: 700; color: #0f172a; margin-bottom: 16px; }
        .panel h2 i { color: #6366f1; }
        .table-wrapper { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th {
            text-align: left; padding: 10px 8px 12px; border-bottom: 2px solid #e5e7eb;
            color: #6b7280; font-weight: 600; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px;
        }
        td { padding: 12px 8px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; font-size: 14px; }
        .empty { text-align: center; padding: 40px; color: #94a3b8; }
        .empty i { font-size: 40px; color: #d1d5db; display: block; margin-bottom: 12px; }
        .empty p { font-size: 14px; }

        .badge { padding: 3px 10px; border-radius: 999px; font-size: 11px; font-weight: 600; display: inline-block; }
        .badge-paid { background: #d1fae5; color: #047857; }
        .badge-pending { background: #fef3c7; color: #d97706; }

        .alert { padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; font-weight: 600; font-size: 14px; }
        .alert-success { background: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0; }

        /* MODAL */
        .modal-overlay {
            position: fixed; inset: 0; background: rgba(0,0,0,0.5);
            display: none; align-items: center; justify-content: center; z-index: 100;
        }
        .modal-overlay.show { display: flex; }
        .modal-box {
            background: #fff; border-radius: 16px; padding: 24px; width: 400px; max-width: 90%;
        }
        .modal-box h3 { margin-top: 0; margin-bottom: 8px; font-size: 18px; }
        .modal-box p { color: #6b7280; font-size: 14px; margin-bottom: 12px; }
        .modal-box input {
            width: 100%; padding: 10px 14px; border: 1px solid #e5e7eb; border-radius: 10px; font-size: 14px; margin-bottom: 12px;
        }
        .modal-actions { display: flex; gap: 10px; justify-content: flex-end; }
        .btn-outline {
            background: transparent; border: 1px solid #e5e7eb; padding: 8px 16px; border-radius: 8px;
            cursor: pointer; font-weight: 600; font-size: 14px;
        }
        .btn-outline:hover { background: #f1f5f9; }
        .btn-primary {
            background: #6366f1; color: white; border: none; padding: 8px 16px; border-radius: 8px;
            cursor: pointer; font-weight: 600; font-size: 14px;
        }
        .btn-primary:hover { background: #4f46e5; }

        @media (max-width: 768px) {
            .sidebar { width: 200px; padding: 16px 12px; }
            .main-content { padding: 20px; }
            .earnings-hero { padding: 24px; flex-direction: column; text-align: center; }
            .earnings-stats { grid-template-columns: 1fr; }
        }
        @media (max-width: 480px) {
            .sidebar { width: 100%; height: auto; position: relative; border-right: none; border-bottom: 1px solid #e5e7eb; }
            .dashboard-container { flex-direction: column; }
            .sidebar-menu { flex-direction: row; flex-wrap: wrap; }
            .sidebar-menu a { padding: 8px 12px; font-size: 13px; }
            .sidebar-menu .logout { margin-top: 0; border-top: none; padding-top: 0; }
            .earnings-hero .value { font-size: 24px; }
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
            <a href="my_applications.php"><i class="fa-solid fa-file-lines"></i> My Applications</a>
            <a href="messages.php"><i class="fa-solid fa-comment-dots"></i> Messages</a>
            <a href="portfolio.php"><i class="fa-solid fa-folder-open"></i> Portfolio</a>
            <a href="earnings.php" class="active"><i class="fa-solid fa-wallet"></i> Earnings</a>
            <a href="profile.php"><i class="fa-solid fa-user"></i> Profile</a>
            <a href="settings_freelancer.php"><i class="fa-solid fa-gear"></i> Settings</a>
            <a href="index.php?logout=1" class="logout" onclick="return confirm('Are you sure you want to logout?')">
                <i class="fa-solid fa-right-from-bracket"></i> Log out
            </a>
        </nav>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="main-content">

        <?php if (isset($_GET['success']) && $_GET['success'] == 'withdrawn'): ?>
            <div class="alert alert-success">✅ Withdrawal request submitted!</div>
        <?php endif; ?>

        <!-- EARNINGS HERO -->
        <div class="earnings-hero">
            <div>
                <div class="label">Current Balance</div>
                <div class="value">RM <?php echo number_format($availableBalance, 2); ?></div>
            </div>
            <button class="btn-withdraw" onclick="document.getElementById('withdrawModal').classList.add('show')">
                <i class="fa-solid fa-arrow-up-right-from-square"></i> Withdraw
            </button>
        </div>

        <!-- STATS -->
        <div class="earnings-stats">
            <div class="earnings-stat-card">
                <div class="number green">RM <?php echo number_format($availableBalance, 2); ?></div>
                <div class="label">Available Balance</div>
            </div>
            <div class="earnings-stat-card">
                <div class="number yellow">RM <?php echo number_format($pendingDisplay, 2); ?></div>
                <div class="label">Pending Clearance</div>
            </div>
        </div>

        <!-- TABLE -->
        <div class="panel">
            <h2><i class="fa-regular fa-clock"></i> Transaction History</h2>
            <?php if (count($payments) > 0): ?>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Job</th>
                            <th>Client</th>
                            <th>Amount</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($payments as $p): ?>
                        <tr>
                            <td><?php echo date('d M Y', strtotime($p['created_at'])); ?></td>
                            <td><?php echo escape($p['job_title'] ?? 'Payment'); ?></td>
                            <td><?php echo escape($p['client_name'] ?? '-'); ?></td>
                            <td style="font-weight:600;color:<?php echo $p['amount'] < 0 ? '#dc2626' : '#10b981'; ?>">
                                <?php echo $p['amount'] < 0 ? '-' : '+'; ?> RM <?php echo number_format(abs($p['amount']), 2); ?>
                            </td>
                            <td><span class="badge badge-<?php echo $p['status']; ?>"><?php echo ucfirst($p['status']); ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
                <div class="empty">
                    <i class="fa-regular fa-receipt"></i>
                    <p>No transactions yet.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- WITHDRAW MODAL -->
        <div class="modal-overlay" id="withdrawModal" onclick="if(event.target===this)this.classList.remove('show')">
            <div class="modal-box">
                <h3>Withdraw Earnings</h3>
                <p>Current Balance: RM <?php echo number_format($availableBalance, 2); ?></p>
                <form method="POST">
                    <input type="number" name="amount" placeholder="Amount (RM)" min="1" max="<?php echo $availableBalance; ?>" step="0.01" required>
                    <div class="modal-actions">
                        <button type="button" class="btn-outline" onclick="document.getElementById('withdrawModal').classList.remove('show')">Cancel</button>
                        <button type="submit" name="withdraw" class="btn-primary">Withdraw</button>
                    </div>
                </form>
            </div>
        </div>

    </main>
</div>

</body>
</html>