<?php
require_once '../config.php';

if (!isLoggedIn() || !isClient()) {
    redirect('../index.php');
}

$user_id = $_SESSION['user_id'];

// Get all payments
$payments = $pdo->query("
    SELECT * FROM payments 
    WHERE user_id = $user_id 
    ORDER BY created_at DESC
")->fetchAll();

// Get totals
$totalPaid = $pdo->query("SELECT SUM(amount) FROM payments WHERE user_id = $user_id AND status = 'paid'")->fetchColumn() ?? 0;
$totalPending = $pdo->query("SELECT SUM(amount) FROM payments WHERE user_id = $user_id AND status = 'pending'")->fetchColumn() ?? 0;
$totalPayments = count($payments);

// Process payment
if (isset($_POST['make_payment'])) {
    $amount = $_POST['amount'];
    $method = $_POST['method'];
    $description = trim($_POST['description'] ?? '');
    $job_id = $_POST['job_id'] ?? null;
    
    if ($amount > 0) {
        $stmt = $pdo->prepare("INSERT INTO payments (user_id, job_id, amount, method, description) VALUES (?, ?, ?, ?, ?)");
        if ($stmt->execute([$user_id, $job_id, $amount, $method, $description])) {
            $success = '✅ Payment request submitted!';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment - Client Dashboard</title>
    <link rel="stylesheet" href="client-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        .payment-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; }
        .payment-card { background: #fff; border-radius: 20px; padding: 24px; box-shadow: 0 2px 8px rgba(15, 23, 42, 0.06); }
        .payment-card h3 { margin-top: 0; margin-bottom: 16px; }
        .form-group { margin-bottom: 16px; }
        .form-group label { display: block; font-size: 13px; font-weight: 600; color: #1f2937; margin-bottom: 6px; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 10px 14px; border: 1px solid #e5e7eb; border-radius: 10px; font-size: 14px; background: white; }
        .form-group input:focus, .form-group select:focus { border-color: #6366f1; outline: none; }
        .btn-primary { background: #6366f1; color: white; border: none; padding: 12px 24px; border-radius: 10px; font-weight: 600; cursor: pointer; width: 100%; }
        .btn-primary:hover { background: #4f46e5; }
        .payment-history-table { width: 100%; border-collapse: collapse; }
        .payment-history-table th { padding: 12px; text-align: left; background: #f9fafb; border-bottom: 2px solid #e5e7eb; font-size: 12px; text-transform: uppercase; color: #6b7280; }
        .payment-history-table td { padding: 12px; border-bottom: 1px solid #f3f4f6; }
        .badge { padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; display: inline-block; }
        .badge-paid { background: #f0fdf4; color: #22c55e; }
        .badge-pending { background: #fef3c7; color: #d97706; }
        .badge-failed { background: #fef2f2; color: #dc2626; }
        .alert { padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; }
        .alert-success { background: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0; }
        .stats-mini { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-bottom: 24px; }
        .stat-card { background: white; padding: 16px 20px; border-radius: 12px; border: 1px solid #e5e7eb; }
        .stat-number { font-size: 24px; font-weight: 700; color: #1f2937; }
        .stat-label { font-size: 13px; color: #6b7280; }
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 28px; }
        .page-header h1 { font-size: 28px; font-weight: 800; color: #1f2937; }
        .payment-info-box { background: #f8f4ff; border-radius: 12px; padding: 16px; border: 1px solid #ede9fe; }
        .qr-placeholder { display: flex; align-items: center; gap: 16px; padding: 12px; background: white; border-radius: 10px; border: 1px solid #e5e7eb; }
        .qr-placeholder .qr-icon { width: 60px; height: 60px; background: #6366f1; border-radius: 10px; display: flex; align-items: center; justify-content: center; color: white; font-size: 24px; font-weight: 700; }
        @media (max-width: 768px) { .payment-grid { grid-template-columns: 1fr; } .stats-mini { grid-template-columns: 1fr; } }
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
                <a href="myjob_client.php" class="menu-item"><i class="fa-solid fa-file-lines"></i> My Jobs</a>
                <a href="message_client.php" class="menu-item"><i class="fa-solid fa-comment-dots"></i> Messages</a>
                <a href="payment_client.php" class="menu-item active"><i class="fa-solid fa-credit-card"></i> Payment</a>
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
                <h1 class="welcome-heading">Payment Center 👋</h1>
            </section>

            <?php if (isset($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>

            <div class="stats-mini">
                <div class="stat-card"><div class="stat-label">Total Paid</div><div class="stat-number">RM <?php echo number_format($totalPaid, 2); ?></div></div>
                <div class="stat-card"><div class="stat-label">Pending</div><div class="stat-number">RM <?php echo number_format($totalPending, 2); ?></div></div>
                <div class="stat-card"><div class="stat-label">Total Transactions</div><div class="stat-number"><?php echo $totalPayments; ?></div></div>
            </div>

            <div class="payment-grid">
                <div class="payment-card">
                    <h3><i class="fa-solid fa-credit-card" style="color:#6366f1;"></i> Make Payment</h3>
                    <form method="POST">
                        <div class="form-group">
                            <label>Amount (RM)</label>
                            <input type="number" name="amount" placeholder="0.00" step="0.01" required>
                        </div>
                        <div class="form-group">
                            <label>Payment Method</label>
                            <select name="method" required>
                                <option value="online">Online</option>
                                <option value="bank">Bank Transfer</option>
                                <option value="e-wallet">E-Wallet</option>
                                <option value="qrcode">QR Code</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Description</label>
                            <input type="text" name="description" placeholder="Payment for...">
                        </div>
                        <button type="submit" name="make_payment" class="btn-primary">
                            <i class="fa-regular fa-paper-plane"></i> Submit Payment
                        </button>
                    </form>

                    <div style="margin-top:16px;padding-top:16px;border-top:1px solid #e5e7eb;">
                        <h4>📱 QR Code Payment</h4>
                        <div class="qr-placeholder">
                            <div class="qr-icon">QR</div>
                            <div>
                                <p style="font-weight:600;margin:0;">Scan to Pay</p>
                                <p style="font-size:13px;color:#6b7280;margin:4px 0 0;">Use TNG eWallet, Maybank, or any bank app</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="payment-card">
                    <h3><i class="fa-solid fa-clock-rotate-left" style="color:#6366f1;"></i> Payment History</h3>
                    <?php if (count($payments) > 0): ?>
                    <table class="payment-history-table">
                        <thead><tr><th>Date</th><th>Description</th><th>Amount</th><th>Status</th></tr></thead>
                        <tbody>
                            <?php foreach ($payments as $p): ?>
                            <tr>
                                <td><?php echo date('d M Y', strtotime($p['created_at'])); ?></td>
                                <td><?php echo escape($p['description'] ?? '-'); ?></td>
                                <td>RM <?php echo number_format($p['amount'], 2); ?></td>
                                <td><span class="badge badge-<?php echo $p['status']; ?>"><?php echo ucfirst($p['status']); ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                    <p style="text-align:center;color:#6b7280;padding:20px;">No payment history yet.</p>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</body>
</html>