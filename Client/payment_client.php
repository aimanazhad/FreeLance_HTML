<?php
require_once '../config.php';

if (!isLoggedIn() || !isClient()) {
    redirect('../index.php');
}

$user_id = $_SESSION['user_id'];

// ============================================
// GET UNPAID COMPLETED JOBS
// ============================================
$unpaidJobs = $pdo->query("
    SELECT j.*, 
           u.id as freelancer_id,
           u.name as freelancer_name,
           u.email as freelancer_email,
           a.bid_amount,
           a.id as application_id
    FROM jobs j
    JOIN applications a ON a.job_id = j.id
    JOIN users u ON a.freelancer_id = u.id
    WHERE j.client_id = $user_id 
      AND j.status = 'completed'
      AND a.status = 'accepted'
      AND NOT EXISTS (
          SELECT 1 FROM payments p 
          WHERE p.job_id = j.id AND p.user_id = $user_id
      )
    ORDER BY j.completed_at DESC
")->fetchAll();

// ============================================
// GET ALL PAYMENTS WITH DETAILS
// ============================================
$payments = $pdo->query("
    SELECT p.*, 
           j.title as job_title,
           u.name as freelancer_name,
           u.email as freelancer_email
    FROM payments p
    LEFT JOIN jobs j ON p.job_id = j.id
    LEFT JOIN users u ON p.freelancer_id = u.id
    WHERE p.user_id = $user_id
    ORDER BY p.created_at DESC
")->fetchAll();

// ============================================
// PROCESS PAYMENT
// ============================================
if (isset($_POST['make_payment'])) {
    $job_id = $_POST['job_id'];
    $freelancer_id = $_POST['freelancer_id'];
    $amount = $_POST['amount'];
    $method = $_POST['method'];
    $description = trim($_POST['description'] ?? 'Payment for job completion');
    
    if ($amount > 0 && $job_id && $freelancer_id) {
        // Check if payment already exists
        $check = $pdo->prepare("SELECT * FROM payments WHERE job_id = ? AND user_id = ?");
        $check->execute([$job_id, $user_id]);
        
        if ($check->rowCount() == 0) {
            $stmt = $pdo->prepare("
                INSERT INTO payments (user_id, freelancer_id, job_id, amount, method, description, status, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, 'pending', NOW())
            ");
            if ($stmt->execute([$user_id, $freelancer_id, $job_id, $amount, $method, $description])) {
                $success = '✅ Payment submitted! Waiting for admin approval.';
                
                // Refresh data
                $unpaidJobs = $pdo->query("
                    SELECT j.*, 
                           u.id as freelancer_id,
                           u.name as freelancer_name,
                           u.email as freelancer_email,
                           a.bid_amount,
                           a.id as application_id
                    FROM jobs j
                    JOIN applications a ON a.job_id = j.id
                    JOIN users u ON a.freelancer_id = u.id
                    WHERE j.client_id = $user_id 
                      AND j.status = 'completed'
                      AND a.status = 'accepted'
                      AND NOT EXISTS (
                          SELECT 1 FROM payments p 
                          WHERE p.job_id = j.id AND p.user_id = $user_id
                      )
                    ORDER BY j.completed_at DESC
                ")->fetchAll();
                
                $payments = $pdo->query("
                    SELECT p.*, 
                           j.title as job_title,
                           u.name as freelancer_name,
                           u.email as freelancer_email
                    FROM payments p
                    LEFT JOIN jobs j ON p.job_id = j.id
                    LEFT JOIN users u ON p.freelancer_id = u.id
                    WHERE p.user_id = $user_id
                    ORDER BY p.created_at DESC
                ")->fetchAll();
            } else {
                $error = '❌ Failed to process payment.';
            }
        } else {
            $error = '⚠️ Payment already submitted for this job.';
        }
    } else {
        $error = '⚠️ Please enter a valid amount.';
    }
}

// ============================================
// GET TOTALS
// ============================================
$totalPaid = $pdo->query("SELECT SUM(amount) FROM payments WHERE user_id = $user_id AND status = 'paid'")->fetchColumn() ?? 0;
$totalPending = $pdo->query("SELECT SUM(amount) FROM payments WHERE user_id = $user_id AND status = 'pending'")->fetchColumn() ?? 0;
$totalPayments = count($payments);
$unpaidCount = count($unpaidJobs);
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
        .payment-grid { display: grid; grid-template-columns: 1fr 1.5fr; gap: 24px; }
        .payment-card { background: #fff; border-radius: 20px; padding: 24px; box-shadow: 0 2px 8px rgba(15, 23, 42, 0.06); }
        .payment-card h3 { margin-top: 0; margin-bottom: 16px; }
        .form-group { margin-bottom: 16px; }
        .form-group label { display: block; font-size: 13px; font-weight: 600; color: #1f2937; margin-bottom: 6px; }
        .form-group input, .form-group select, .form-group textarea { 
            width: 100%; padding: 10px 14px; border: 1px solid #e5e7eb; border-radius: 10px; font-size: 14px; background: white; 
            transition: all 0.2s ease;
        }
        .form-group input:focus, .form-group select:focus { border-color: #6366f1; outline: none; box-shadow: 0 0 0 3px rgba(99,102,241,0.1); }
        .form-group input[readonly] { background: #f9fafb; cursor: not-allowed; }
        .btn-primary { 
            background: #6366f1; color: white; border: none; padding: 12px 24px; border-radius: 10px; 
            font-weight: 600; cursor: pointer; width: 100%; transition: all 0.2s ease;
        }
        .btn-primary:hover { background: #4f46e5; box-shadow: 0 4px 12px rgba(99,102,241,0.3); }
        .btn-primary:disabled { background: #9ca3af; cursor: not-allowed; box-shadow: none; }
        
        .payment-history-table { width: 100%; border-collapse: collapse; }
        .payment-history-table th { 
            padding: 12px; text-align: left; background: #f9fafb; border-bottom: 2px solid #e5e7eb; 
            font-size: 12px; text-transform: uppercase; color: #6b7280; 
        }
        .payment-history-table td { padding: 12px; border-bottom: 1px solid #f3f4f6; font-size: 14px; }
        .payment-history-table tr:hover { background: #f8faff; }
        
        .badge { padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; display: inline-block; }
        .badge-paid { background: #d1fae5; color: #047857; }
        .badge-pending { background: #fef3c7; color: #d97706; }
        .badge-failed { background: #fee2e2; color: #dc2626; }
        
        .alert { padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; font-weight: 600; }
        .alert-success { background: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0; }
        .alert-danger { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }
        
        .stats-mini { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-bottom: 24px; }
        .stat-card { background: white; padding: 16px 20px; border-radius: 12px; border: 1px solid #e5e7eb; }
        .stat-number { font-size: 24px; font-weight: 700; color: #1f2937; }
        .stat-label { font-size: 13px; color: #6b7280; }
        .stat-number.green { color: #10b981; }
        .stat-number.yellow { color: #d97706; }
        .stat-number.purple { color: #6366f1; }
        
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 28px; flex-wrap: wrap; gap: 12px; }
        .page-header h1 { font-size: 28px; font-weight: 800; color: #1f2937; }
        .page-header .badge-count { 
            background: #eef2ff; color: #6366f1; padding: 6px 16px; border-radius: 999px; 
            font-weight: 600; font-size: 14px; 
        }
        
        .qr-placeholder { display: flex; align-items: center; gap: 16px; padding: 12px; background: #f8f4ff; border-radius: 10px; border: 1px solid #ede9fe; }
        .qr-placeholder .qr-icon { 
            width: 60px; height: 60px; background: #6366f1; border-radius: 10px; 
            display: flex; align-items: center; justify-content: center; color: white; 
            font-size: 24px; font-weight: 700; flex-shrink: 0; 
        }
        
        .job-select-option { 
            display: flex; justify-content: space-between; padding: 8px 0; 
            border-bottom: 1px solid #f3f4f6; 
        }
        .job-select-option .amount { font-weight: 600; color: #10b981; }
        
        .empty { text-align: center; padding: 30px; color: #6b7280; }
        .empty i { font-size: 40px; color: #d1d5db; display: block; margin-bottom: 12px; }
        
        .unpaid-notice {
            background: #fef3c7; border: 1px solid #fcd34d; border-radius: 10px; padding: 12px 16px; 
            margin-bottom: 16px; display: flex; align-items: center; gap: 12px;
        }
        .unpaid-notice i { color: #d97706; font-size: 20px; }
        .unpaid-notice span { font-size: 14px; color: #78350f; }
        .unpaid-notice strong { color: #92400e; }
        
        .freelancer-info {
            background: #f8f4ff; padding: 12px 16px; border-radius: 10px; border: 1px solid #ede9fe;
            display: flex; align-items: center; gap: 12px; margin-bottom: 16px;
        }
        .freelancer-info img { width: 40px; height: 40px; border-radius: 50%; object-fit: cover; }
        .freelancer-info .name { font-weight: 700; font-size: 14px; }
        .freelancer-info .email { font-size: 12px; color: #6b7280; }
        
        .amount-display {
            font-size: 24px; font-weight: 700; color: #1f2937; padding: 8px 0;
        }
        .amount-display .currency { font-size: 16px; color: #6b7280; }
        
        @media (max-width: 1024px) { .payment-grid { grid-template-columns: 1fr; } }
        @media (max-width: 768px) { 
            .stats-mini { grid-template-columns: 1fr; }
            .page-header { flex-direction: column; align-items: flex-start; }
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
                <a href="myjob_client.php" class="menu-item"><i class="fa-solid fa-file-lines"></i> My Jobs</a>
                <a href="message_client.php" class="menu-item"><i class="fa-solid fa-comment-dots"></i> Messages</a>
                <a href="payment_client.php" class="menu-item active"><i class="fa-solid fa-credit-card"></i> Payment</a>
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
                <h1 class="welcome-heading">Payment Center 👋</h1>
            </section>

            <?php if (isset($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <div class="stats-mini">
                <div class="stat-card">
                    <div class="stat-label">Total Paid</div>
                    <div class="stat-number green">RM <?php echo number_format($totalPaid, 2); ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Pending</div>
                    <div class="stat-number yellow">RM <?php echo number_format($totalPending, 2); ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Pending Jobs</div>
                    <div class="stat-number purple"><?php echo $unpaidCount; ?></div>
                </div>
            </div>

            <div class="payment-grid">
                <!-- Make Payment Form -->
                <div class="payment-card">
                    <h3><i class="fa-solid fa-credit-card" style="color:#6366f1;"></i> Make Payment</h3>
                    
                    <?php if ($unpaidCount > 0): ?>
                        <div class="unpaid-notice">
                            <i class="fa-solid fa-circle-exclamation"></i>
                            <span>You have <strong><?php echo $unpaidCount; ?> job(s)</strong> waiting for payment.</span>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" id="paymentForm">
                        <div class="form-group">
                            <label>Select Completed Job *</label>
                            <select name="job_id" id="jobSelect" required onchange="updatePaymentDetails(this)">
                                <option value="">-- Select a job --</option>
                                <?php foreach ($unpaidJobs as $job): ?>
                                    <option value="<?php echo $job['id']; ?>" 
                                            data-freelancer-id="<?php echo $job['freelancer_id']; ?>"
                                            data-freelancer-name="<?php echo escape($job['freelancer_name']); ?>"
                                            data-freelancer-email="<?php echo escape($job['freelancer_email']); ?>"
                                            data-amount="<?php echo $job['bid_amount'] ?? $job['budget_max']; ?>">
                                        <?php echo escape($job['title']); ?> 
                                        (RM <?php echo number_format($job['bid_amount'] ?? $job['budget_max'], 2); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <!-- Freelancer Info (auto-filled) -->
                        <div id="freelancerInfo" style="display:none;" class="freelancer-info">
                            <img id="freelancerAvatar" src="" alt="">
                            <div>
                                <div class="name" id="freelancerName"></div>
                                <div class="email" id="freelancerEmail"></div>
                            </div>
                        </div>
                        
                        <input type="hidden" name="freelancer_id" id="freelancerId">
                        
                        <div class="form-group">
                            <label>Amount (RM)</label>
                            <div class="amount-display">
                                <span class="currency">RM</span> 
                                <span id="amountDisplay">0.00</span>
                            </div>
                            <input type="hidden" name="amount" id="amountInput" value="0">
                            <input type="range" id="amountRange" min="0" max="0" step="50" 
                                   oninput="updateAmountSlider(this.value)" style="width:100%;">
                            <div style="display:flex;justify-content:space-between;font-size:11px;color:#94a3b8;margin-top:4px;">
                                <span>RM 0</span>
                                <span id="rangeMax">RM 0</span>
                            </div>
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
                            <input type="text" name="description" placeholder="Payment for job completion" 
                                   value="Payment for completed freelance job">
                        </div>
                        
                        <button type="submit" name="make_payment" class="btn-primary" id="payBtn" disabled>
                            <i class="fa-regular fa-paper-plane"></i> Pay Now
                        </button>
                    </form>

                    <div style="margin-top:16px;padding-top:16px;border-top:1px solid #e5e7eb;">
                        <h4>📱 Quick Payment</h4>
                        <div class="qr-placeholder">
                            <div class="qr-icon">QR</div>
                            <div>
                                <p style="font-weight:600;margin:0;">Scan to Pay</p>
                                <p style="font-size:13px;color:#6b7280;margin:4px 0 0;">Use TNG eWallet, Maybank, or any bank app</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Payment History -->
                <div class="payment-card">
                    <h3><i class="fa-solid fa-clock-rotate-left" style="color:#6366f1;"></i> Payment History</h3>
                    <?php if (count($payments) > 0): ?>
                    <div style="overflow-x:auto;">
                        <table class="payment-history-table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Job</th>
                                    <th>Freelancer</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($payments as $p): ?>
                                <tr>
                                    <td><?php echo date('d M Y', strtotime($p['created_at'])); ?></td>
                                    <td><?php echo escape($p['job_title'] ?? '-'); ?></td>
                                    <td><?php echo escape($p['freelancer_name'] ?? '-'); ?></td>
                                    <td>RM <?php echo number_format($p['amount'], 2); ?></td>
                                    <td><span class="badge badge-<?php echo $p['status']; ?>"><?php echo ucfirst($p['status']); ?></span></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="empty">
                        <i class="fa-regular fa-receipt"></i>
                        <p>No payment history yet.</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <script>
        function updatePaymentDetails(select) {
            const selected = select.options[select.selectedIndex];
            const freelancerInfo = document.getElementById('freelancerInfo');
            const freelancerId = document.getElementById('freelancerId');
            const freelancerName = document.getElementById('freelancerName');
            const freelancerEmail = document.getElementById('freelancerEmail');
            const freelancerAvatar = document.getElementById('freelancerAvatar');
            const amountDisplay = document.getElementById('amountDisplay');
            const amountInput = document.getElementById('amountInput');
            const amountRange = document.getElementById('amountRange');
            const rangeMax = document.getElementById('rangeMax');
            const payBtn = document.getElementById('payBtn');
            
            if (selected.value) {
                const fId = selected.dataset.freelancerId;
                const fName = selected.dataset.freelancerName;
                const fEmail = selected.dataset.freelancerEmail;
                const amount = parseFloat(selected.dataset.amount) || 0;
                
                // Show freelancer info
                freelancerInfo.style.display = 'flex';
                freelancerId.value = fId;
                freelancerName.textContent = fName;
                freelancerEmail.textContent = fEmail;
                freelancerAvatar.src = 'https://api.dicebear.com/7.x/avataaars/svg?seed=' + encodeURIComponent(fName);
                
                // Update amount
                amountDisplay.textContent = amount.toFixed(2);
                amountInput.value = amount;
                amountRange.max = amount;
                amountRange.value = amount;
                rangeMax.textContent = 'RM ' + amount.toFixed(2);
                
                // Enable pay button
                payBtn.disabled = false;
            } else {
                freelancerInfo.style.display = 'none';
                freelancerId.value = '';
                amountDisplay.textContent = '0.00';
                amountInput.value = 0;
                amountRange.max = 0;
                amountRange.value = 0;
                rangeMax.textContent = 'RM 0';
                payBtn.disabled = true;
            }
        }
        
        function updateAmountSlider(value) {
            const amountDisplay = document.getElementById('amountDisplay');
            const amountInput = document.getElementById('amountInput');
            amountDisplay.textContent = parseFloat(value).toFixed(2);
            amountInput.value = value;
        }
    </script>
</body>
</html>