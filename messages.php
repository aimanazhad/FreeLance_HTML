<?php
require_once 'config.php';

if (!isLoggedIn() || !isFreelancer()) {
    redirect('index.php');
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
        redirect('messages.php?success=sent');
    }
}

$clients = $pdo->query("SELECT id, name FROM users WHERE role = 'client' ORDER BY name")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages</title>
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

        .messages-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; }
        .panel {
            background: white; border-radius: 12px; border: 1px solid #e5e7eb; padding: 24px;
        }
        .panel h2 { font-size: 16px; font-weight: 700; color: #0f172a; margin-bottom: 16px; }
        .panel h2 i { color: #6366f1; }

        .compose-form .form-group { margin-bottom: 12px; }
        .compose-form label {
            display: block; font-size: 13px; font-weight: 600; color: #1f2937; margin-bottom: 4px;
        }
        .compose-form input, .compose-form select, .compose-form textarea {
            width: 100%; padding: 10px 14px; border: 1px solid #e5e7eb; border-radius: 8px;
            font-size: 14px; background: #f9fafb; outline: none; font-family: inherit;
        }
        .compose-form input:focus, .compose-form select:focus, .compose-form textarea:focus {
            border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99,102,241,0.1);
        }
        .compose-form textarea { min-height: 80px; resize: vertical; }
        .compose-form .btn-send {
            background: #6366f1; color: white; border: none; padding: 10px 24px; border-radius: 8px;
            font-weight: 600; cursor: pointer; width: 100%; transition: all 0.2s ease; font-size: 14px;
        }
        .compose-form .btn-send:hover { background: #4f46e5; }

        .conversation-list { display: flex; flex-direction: column; gap: 12px; }
        .conversation-card {
            display: flex; align-items: flex-start; gap: 14px; padding: 14px; border-radius: 10px;
            border: 1px solid #f1f5f9; cursor: pointer; transition: all 0.2s ease;
        }
        .conversation-card:hover { border-color: #c7d2fe; background: #f8faff; }
        .conversation-avatar { width: 44px; height: 44px; border-radius: 50%; object-fit: cover; flex-shrink: 0; }
        .conversation-content { flex: 1; }
        .conversation-name { font-weight: 700; font-size: 14px; color: #0f172a; }
        .conversation-time { font-size: 11px; color: #94a3b8; float: right; }
        .conversation-role { font-size: 12px; color: #94a3b8; }
        .conversation-snippet {
            font-size: 13px; color: #64748b; margin-top: 4px;
            overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 200px;
        }
        .badge-unread { background: #fef2f2; color: #dc2626; padding: 2px 10px; border-radius: 999px; font-size: 11px; font-weight: 600; }
        .badge-read { background: #f0fdf4; color: #22c55e; padding: 2px 10px; border-radius: 999px; font-size: 11px; font-weight: 600; }
        .alert { padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; font-weight: 600; font-size: 14px; }
        .alert-success { background: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0; }
        .empty { text-align: center; padding: 30px; color: #94a3b8; font-size: 14px; }
        .empty i { font-size: 32px; color: #d1d5db; display: block; margin-bottom: 8px; }

        @media (max-width: 1024px) { .messages-grid { grid-template-columns: 1fr; } }
        @media (max-width: 768px) {
            .sidebar { width: 200px; padding: 16px 12px; }
            .main-content { padding: 20px; }
            .hero-banner .emoji { display: none; }
            .hero-banner h1 { font-size: 24px; }
            .hero-banner p { font-size: 14px; }
        }
        @media (max-width: 480px) {
            .sidebar { width: 100%; height: auto; position: relative; border-right: none; border-bottom: 1px solid #e5e7eb; }
            .dashboard-container { flex-direction: column; }
            .sidebar-menu { flex-direction: row; flex-wrap: wrap; }
            .sidebar-menu a { padding: 8px 12px; font-size: 13px; }
            .sidebar-menu .logout { margin-top: 0; border-top: none; padding-top: 0; }
            .messages-grid { grid-template-columns: 1fr; }
            .hero-banner { padding: 24px; }
            .hero-banner h1 { font-size: 22px; }
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
            <a href="messages.php" class="active"><i class="fa-solid fa-comment-dots"></i> Messages</a>
            <a href="portfolio.php"><i class="fa-solid fa-folder-open"></i> Portfolio</a>
            <a href="earnings.php"><i class="fa-solid fa-wallet"></i> Earnings</a>
            <a href="profile.php"><i class="fa-solid fa-user"></i> Profile</a>
            <a href="settings_freelancer.php"><i class="fa-solid fa-gear"></i> Settings</a>
            <a href="index.php?logout=1" class="logout" onclick="return confirm('Are you sure you want to logout?')">
                <i class="fa-solid fa-right-from-bracket"></i> Log out
            </a>
        </nav>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="main-content">

        <div class="hero-banner">
            <div>
                <h1>💬 Messages</h1>
                <p>Chat with your clients.</p>
            </div>
            <div class="emoji">✉️</div>
        </div>

        <?php if (isset($_GET['success']) && $_GET['success'] == 'sent'): ?>
            <div class="alert alert-success">✅ Message sent successfully!</div>
        <?php endif; ?>

        <div class="messages-grid">

            <!-- COMPOSE -->
            <div class="panel">
                <h2><i class="fa-regular fa-pen-to-square"></i> New Message</h2>
                <form method="POST" class="compose-form">
                    <div class="form-group">
                        <label>To</label>
                        <select name="receiver_id" required>
                            <option value="">Select client...</option>
                            <?php foreach ($clients as $c): ?>
                                <option value="<?php echo $c['id']; ?>"><?php echo escape($c['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Message</label>
                        <textarea name="message" placeholder="Type your message..." required></textarea>
                    </div>
                    <button type="submit" name="send_message" class="btn-send">
                        <i class="fa-regular fa-paper-plane"></i> Send
                    </button>
                </form>
            </div>

            <!-- CONVERSATIONS -->
            <div class="panel">
                <h2><i class="fa-regular fa-comment-dots"></i> Conversations</h2>
                <?php if (count($messages) > 0): ?>
                    <div class="conversation-list">
                        <?php foreach ($messages as $msg): ?>
                        <div class="conversation-card">
                            <img src="https://api.dicebear.com/7.x/avataaars/svg?seed=<?php echo urlencode($msg['sender_name']); ?>" alt="" class="conversation-avatar">
                            <div class="conversation-content">
                                <div class="conversation-name">
                                    <?php echo escape($msg['sender_name']); ?>
                                    <span class="conversation-time"><?php echo date('d M Y, h:i A', strtotime($msg['created_at'])); ?></span>
                                </div>
                                <span class="conversation-role"><?php echo $msg['sender_role']; ?></span>
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
                <?php else: ?>
                    <div class="empty">
                        <i class="fa-regular fa-comment"></i>
                        <p>No messages yet.</p>
                    </div>
                <?php endif; ?>
            </div>

        </div>

    </main>
</div>

</body>
</html>