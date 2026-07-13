<?php
require_once '../config.php';

if (!isLoggedIn() || !isClient()) {
    redirect('../index.php');
}

$user_id = $_SESSION['user_id'];

// Get messages for this user
$messages = $pdo->query("
    SELECT m.*, 
           u.name as sender_name, u.role as sender_role
    FROM messages m
    JOIN users u ON m.sender_id = u.id
    WHERE m.receiver_id = $user_id OR m.sender_id = $user_id
    ORDER BY m.created_at DESC
")->fetchAll();

// Send message
if (isset($_POST['send_message'])) {
    $receiver_id = $_POST['receiver_id'];
    $message = trim($_POST['message']);
    $job_id = $_POST['job_id'] ?? null;
    
    if (!empty($message) && !empty($receiver_id)) {
        $stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, job_id, message) VALUES (?, ?, ?, ?)");
        $stmt->execute([$user_id, $receiver_id, $job_id, $message]);
        redirect('message_client.php?success=sent');
    }
}

// Get freelancers for dropdown
$freelancers = $pdo->query("SELECT id, name FROM users WHERE role = 'freelancer' ORDER BY name")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages - Client Dashboard</title>
    <link rel="stylesheet" href="client-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        .messages-layout { display: grid; grid-template-columns: 1fr; gap: 20px; }
        .conversation-list { display: flex; flex-direction: column; gap: 12px; }
        .conversation-card { display: flex; align-items: flex-start; gap: 14px; padding: 16px; border-radius: 16px; background: #fff; border: 1px solid #e5e7eb; cursor: pointer; }
        .conversation-card:hover { border-color: #6366f1; }
        .conversation-avatar { width: 48px; height: 48px; border-radius: 50%; object-fit: cover; }
        .conversation-content { flex: 1; }
        .conversation-name { font-weight: 700; font-size: 15px; }
        .conversation-time { font-size: 12px; color: #6b7280; float: right; }
        .conversation-snippet { font-size: 13px; color: #6b7280; margin-top: 4px; }
        .compose-form { background: #fff; border-radius: 16px; padding: 20px; border: 1px solid #e5e7eb; }
        .compose-form .form-group { margin-bottom: 12px; }
        .compose-form label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; }
        .compose-form input, .compose-form select, .compose-form textarea { width: 100%; padding: 10px 14px; border: 1px solid #e5e7eb; border-radius: 10px; font-size: 14px; background: white; }
        .compose-form textarea { min-height: 80px; resize: vertical; }
        .btn-send { background: #6366f1; color: white; border: none; padding: 10px 24px; border-radius: 10px; font-weight: 600; cursor: pointer; }
        .btn-send:hover { background: #4f46e5; }
        .alert { padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; }
        .alert-success { background: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0; }
        .badge-unread { background: #fef2f2; color: #dc2626; padding: 2px 10px; border-radius: 999px; font-size: 12px; font-weight: 600; }
        .badge-read { background: #f0fdf4; color: #22c55e; padding: 2px 10px; border-radius: 999px; font-size: 12px; font-weight: 600; }
        .message-preview { max-width: 200px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 28px; }
        .page-header h1 { font-size: 28px; font-weight: 800; color: #1f2937; }
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
                <a href="message_client.php" class="menu-item active"><i class="fa-solid fa-comment-dots"></i> Messages</a>
                <a href="payment_client.php" class="menu-item"><i class="fa-solid fa-credit-card"></i> Payment</a>
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
                <h1 class="welcome-heading"><?php echo escape($_SESSION['name']); ?>! 👋</h1>
            </section>

            <div class="page-header">
                <div>
                    <h1>Messages</h1>
                    <p>Communicate with freelancers</p>
                </div>
            </div>

            <?php if (isset($_GET['success']) && $_GET['success'] == 'sent'): ?>
                <div class="alert alert-success">✅ Message sent successfully!</div>
            <?php endif; ?>

            <div class="messages-layout">
                <!-- Compose -->
                <div class="compose-form">
                    <h3 style="margin-top:0;margin-bottom:16px;"><i class="fa-regular fa-pen-to-square" style="color:#6366f1;"></i> New Message</h3>
                    <form method="POST">
                        <div class="form-group">
                            <label>To</label>
                            <select name="receiver_id" required>
                                <option value="">Select freelancer...</option>
                                <?php foreach ($freelancers as $f): ?>
                                    <option value="<?php echo $f['id']; ?>"><?php echo escape($f['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Message</label>
                            <textarea name="message" placeholder="Type your message..." required></textarea>
                        </div>
                        <button type="submit" name="send_message" class="btn-send"><i class="fa-regular fa-paper-plane"></i> Send</button>
                    </form>
                </div>

                <!-- Messages List -->
                <div class="conversation-list">
                    <?php foreach ($messages as $msg): ?>
                    <div class="conversation-card">
                        <img src="https://api.dicebear.com/7.x/avataaars/svg?seed=<?php echo urlencode($msg['sender_name']); ?>" alt="" class="conversation-avatar">
                        <div class="conversation-content">
                            <div class="conversation-name">
                                <?php echo escape($msg['sender_name']); ?>
                                <span class="conversation-time"><?php echo date('d M Y, h:i A', strtotime($msg['created_at'])); ?></span>
                            </div>
                            <span style="font-size:12px;color:#6b7280;"><?php echo $msg['sender_role']; ?></span>
                            <div class="conversation-snippet">
                                <?php echo strlen($msg['message']) > 60 ? substr(escape($msg['message']), 0, 60) . '...' : escape($msg['message']); ?>
                            </div>
                        </div>
                        <?php if (!$msg['is_read'] && $msg['receiver_id'] == $user_id): ?>
                            <span class="badge-unread">New</span>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </main>
    </div>
</body>
</html>