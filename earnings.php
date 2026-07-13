<?php
require_once 'config.php';

if (!isLoggedIn() || !isFreelancer()) {
    redirect('../index.php');
}

$user_id = $_SESSION['user_id'];

// Get earnings
$payments = $pdo->query("
    SELECT * FROM payments 
    WHERE user_id = $user_id 
    ORDER BY created_at DESC
")->fetchAll();

$balance = $pdo->query("SELECT SUM(amount) FROM payments WHERE user_id = $user_id AND status = 'paid'")->fetchColumn() ?? 0;
$pendingBalance = $pdo->query("SELECT SUM(amount) FROM payments WHERE user_id = $user_id AND status = 'pending'")->fetchColumn() ?? 0;

// Process withdrawal
if (isset($_POST['withdraw'])) {
    $amount = $_POST['amount'];
    if ($amount > 0 && $amount <= $balance) {
        $stmt = $pdo->prepare("INSERT INTO payments (user_id, amount, method, description, status) VALUES (?, ?, 'bank', 'Withdrawal', 'pending')");
        $stmt->execute([$user_id, -$amount]);
        redirect('earnings.php?success=withdrawn');
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Earnings - Freelancer</title>
    <link rel="stylesheet" href="freelancer.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        .fm-earn-hero { background: linear-gradient(120deg, #6366f1, #8a6cf0); color: #fff; border-radius: 16px; padding: 28px 32px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px; }
        .fm-earn-hero-label { font-size: 13px; opacity: .85; }
        .fm-earn-hero-value { font-size: 32px; font-weight: 700; margin-top: 4px; }
        .fm-btn { background: #fff; color: #6366f1; border: none; padding: 10px 24px; border-radius: 10px; font-weight: 600; cursor: pointer; }
        .fm-btn:hover { background: #f0f0ff; }
        .fm-table { width: 100%; border-collapse: collapse; }
        .fm-table th { text-align: left; font-size: 12px; color: #6b7280; padding: 10px 8px; border-bottom: 1px solid #e5e7eb; }
        .fm-table td { padding: 14px 8px; border-bottom: 1px solid #e5e7eb; font-size: 13px; vertical-align: middle; }
        .fm-panel { background: #fff; border-radius: 16px; padding: 24px; }
        .alert { padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; }
        .alert-success { background: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0; }
        .modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.5); display: none; align-items: center; justify-content: center; z-index: 100; }
        .modal-overlay.show { display: flex; }
        .modal-box { background: #fff; border-radius: 16px; padding: 24px; width: 400px; max-width: 90%; }
        .modal-box h3 { margin-top: 0; }
        .modal-box input { width: 100%; padding: 10px 14px; border: 1px solid #e5e7eb; border-radius: 10px; font-size: 14px; margin: 10px 0; }
        .modal-actions { display: flex; gap: 10px; justify-content: flex-end; margin-top: 10px; }
        .btn-outline { background: transparent; border: 1px solid #e5e7eb; padding: 8px 16px; border-radius: 8px; cursor: pointer; }
        .btn-primary { background: #6366f1; color: #fff; border: none; padding: 8px 16px; border-radius: 8px; cursor: pointer; }
        .btn-primary:hover { background: #4f46e5; }
        .fm-empty { text-align: center; padding: 40px; color: #6b7280; }
        .earnings-stats { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 20px; }
        .earnings-stat-card { background: white; padding: 16px; border-radius: 12px; border: 1px solid #e5e7eb; text-align: center; }
        .earnings-stat-card .number { font-size: 28px; font-weight: 700; }
        .earnings-stat-card .label { font-size: 12px; color: #6b7280; }
        .earnings-stat-card .number.green { color: #10b981; }
        .earnings-stat-card .number.yellow { color: #eab308; }
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
                <a href="browse_jobs.php" class="menu-item"><i class="fa-solid fa-briefcase"></i> Browse Jobs</a>
                <a href="my_applications.php" class="menu-item"><i class="fa-solid fa-file-lines"></i> My Applications</a>
                <a href="messages.php" class="menu-item"><i class="fa-solid fa-comment-dots"></i> Messages</a>
                <a href="portfolio.php" class="menu-item"><i class="fa-solid fa-folder-open"></i> Portfolio</a>
                <a href="earnings.php" class="menu-item active"><i class="fa-solid fa-wallet"></i> Earnings</a>
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

            <?php if (isset($_GET['success']) && $_GET['success'] == 'withdrawn'): ?>
                <div class="alert alert-success">✅ Withdrawal request submitted!</div>
            <?php endif; ?>

            <div class="fm-earn-hero">
                <div>
                    <div class="fm-earn-hero-label">Current Balance</div>
                    <div class="fm-earn-hero-value">RM <?php echo number_format($balance, 2); ?></div>
                </div>
                <button class="fm-btn" onclick="document.getElementById('withdrawModal').classList.add('show')">Withdraw</button>
            </div>

            <div class="earnings-stats">
                <div class="earnings-stat-card">
                    <div class="number green">RM <?php echo number_format($balance, 2); ?></div>
                    <div class="label">Available Balance</div>
                </div>
                <div class="earnings-stat-card">
                    <div class="number yellow">RM <?php echo number_format($pendingBalance, 2); ?></div>
                    <div class="label">Pending Clearance</div>
                </div>
            </div>

            <div class="fm-panel">
                <h2>Transaction History</h2>
                <?php if (count($payments) > 0): ?>
                <table class="fm-table">
                    <thead><tr><th>Date</th><th>Description</th><th>Amount</th></tr></thead>
                    <tbody>
                        <?php foreach ($payments as $p): ?>
                        <tr>
                            <td><?php echo date('d M Y', strtotime($p['created_at'])); ?></td>
                            <td><?php echo escape($p['description'] ?? 'Payment'); ?></td>
                            <td style="font-weight:600;color:<?php echo $p['amount'] < 0 ? '#dc2626' : '#10b981'; ?>">
                                <?php echo $p['amount'] < 0 ? '-' : '+'; ?> RM <?php echo number_format(abs($p['amount']), 2); ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                    <div class="fm-empty">No transactions yet.</div>
                <?php endif; ?>
            </div>

            <!-- Withdraw Modal -->
            <div class="modal-overlay" id="withdrawModal" onclick="if(event.target===this)this.classList.remove('show')">
                <div class="modal-box">
                    <h3>Withdraw Earnings</h3>
                    <p style="color:#6b7280;font-size:13px;">Current Balance: RM <?php echo number_format($balance, 2); ?></p>
                    <form method="POST">
                        <input type="number" name="amount" placeholder="Amount (RM)" min="1" max="<?php echo $balance; ?>" step="0.01" required>
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