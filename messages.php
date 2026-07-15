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
    ORDER BY m.created_at ASC
")->fetchAll();

// Build conversation list
$conversations = [];
$contactIds = [];
foreach ($messages as $msg) {
    $otherId = $msg['sender_id'] == $user_id ? $msg['receiver_id'] : $msg['sender_id'];
    $contactIds[$otherId] = true;
}

$contactMap = [];
if (!empty($contactIds)) {
    $contactIdsList = implode(',', array_keys($contactIds));
    $contacts = $pdo->query("SELECT id, name, role FROM users WHERE id IN ($contactIdsList)")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($contacts as $contact) {
        $contactMap[$contact['id']] = $contact;
    }
}

foreach ($messages as $msg) {
    $otherId = $msg['sender_id'] == $user_id ? $msg['receiver_id'] : $msg['sender_id'];
    if (!isset($conversations[$otherId])) {
        $conversations[$otherId] = [
            'id' => $otherId,
            'name' => $contactMap[$otherId]['name'] ?? 'Unknown',
            'role' => $contactMap[$otherId]['role'] ?? 'Client',
            'last_message' => '',
            'last_time' => '',
            'unread' => 0
        ];
    }
    $conversations[$otherId]['last_message'] = $msg['message'];
    $conversations[$otherId]['last_time'] = $msg['created_at'];
    if ($msg['receiver_id'] == $user_id && !$msg['is_read']) {
        $conversations[$otherId]['unread']++;
    }
}

$conversations = array_values($conversations);
usort($conversations, function($a, $b) {
    return strtotime($b['last_time']) - strtotime($a['last_time']);
});

// Determine selected contact
$selectedContactId = isset($_GET['contact']) && is_numeric($_GET['contact']) ? intval($_GET['contact']) : null;
if ($selectedContactId && !isset($contactMap[$selectedContactId])) {
    $selectedContactId = null;
}
if (!$selectedContactId && !empty($conversations)) {
    $selectedContactId = $conversations[0]['id'];
}

$selectedConversation = [];
if ($selectedContactId) {
    foreach ($messages as $msg) {
        $otherId = $msg['sender_id'] == $user_id ? $msg['receiver_id'] : $msg['sender_id'];
        if ($otherId == $selectedContactId) {
            $selectedConversation[] = $msg;
        }
    }
}

$selectedContact = null;
if ($selectedContactId) {
    if (isset($contactMap[$selectedContactId])) {
        $selectedContact = $contactMap[$selectedContactId];
    } else {
        foreach ($conversations as $conversation) {
            if ($conversation['id'] == $selectedContactId) {
                $selectedContact = [
                    'id' => $conversation['id'],
                    'name' => $conversation['name'],
                    'role' => $conversation['role'],
                ];
                break;
            }
        }
    }
}

// Send message
if (isset($_POST['send_message'])) {
    $receiver_id = $_POST['receiver_id'];
    $message = trim($_POST['message']);
    $job_id = $_POST['job_id'] ?? null;
    
    if (!empty($message) && !empty($receiver_id)) {
        $stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, job_id, message) VALUES (?, ?, ?, ?)");
        $stmt->execute([$user_id, $receiver_id, $job_id, $message]);
        redirect('messages.php?success=sent&contact=' . intval($receiver_id));
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

        .messages-layout { display: grid; grid-template-columns: 360px 1fr; gap: 24px; }
        .conversations-panel, .chat-panel { background: #fff; border-radius: 24px; border: 1px solid #e5e7eb; box-shadow: 0 16px 40px rgba(15, 23, 42, 0.06); }
        .conversations-panel { display: flex; flex-direction: column; overflow: hidden; }
        .conversations-header { padding: 22px 24px; border-bottom: 1px solid #eef2ff; display: flex; justify-content: space-between; align-items: center; gap: 12px; }
        .conversations-header h2 { margin: 0; font-size: 18px; font-weight: 700; }
        .conversations-header a { text-decoration: none; background: #6366f1; color: white; padding: 10px 16px; border-radius: 999px; font-size: 13px; }
        .conversation-list { display: flex; flex-direction: column; overflow-y: auto; max-height: calc(100vh - 240px); }
        .conversation-card { display: flex; align-items: center; gap: 14px; padding: 16px 20px; border-bottom: 1px solid #f3f4f6; cursor: pointer; text-decoration: none; color: inherit; }
        .conversation-card:last-child { border-bottom: none; }
        .conversation-card.active, .conversation-card:hover { background: #f8f4ff; }
        .conversation-avatar { width: 48px; height: 48px; border-radius: 50%; object-fit: cover; flex-shrink: 0; }
        .conversation-content { flex: 1; min-width: 0; }
        .conversation-name { display: flex; justify-content: space-between; align-items: center; gap: 12px; font-weight: 700; font-size: 14px; }
        .conversation-time { font-size: 11px; color: #9ca3af; white-space: nowrap; }
        .conversation-snippet { font-size: 13px; color: #6b7280; margin-top: 6px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .conversation-meta { display: flex; align-items: center; gap: 8px; margin-top: 8px; }
        .conversation-role { font-size: 11px; color: #6b7280; }
        .badge-unread { background: #6366f1; color: white; padding: 4px 10px; border-radius: 999px; font-size: 11px; font-weight: 700; }
        .chat-panel { display: flex; flex-direction: column; min-height: 600px; }
        .chat-header { padding: 22px 24px; border-bottom: 1px solid #eef2ff; display: flex; align-items: center; gap: 16px; }
        .chat-header .avatar { width: 52px; height: 52px; border-radius: 50%; object-fit: cover; }
        .chat-header .chat-title { display: flex; flex-direction: column; }
        .chat-header .chat-title h2 { margin: 0; font-size: 18px; font-weight: 700; }
        .chat-header .chat-title span { font-size: 13px; color: #6b7280; }
        .chat-messages { flex: 1; padding: 20px 24px; overflow-y: auto; display: flex; flex-direction: column; gap: 14px; background: #f8f7ff; }
        .message-row { display: flex; gap: 10px; align-items: flex-end; }
        .message-row.from-me { justify-content: flex-end; }
        .message-bubble { max-width: 70%; padding: 14px 18px; border-radius: 22px; background: white; box-shadow: 0 8px 20px rgba(15, 23, 42, 0.06); position: relative; }
        .message-row.from-me .message-bubble { background: #6366f1; color: white; }
        .message-row .message-meta { font-size: 11px; color: #9ca3af; margin-top: 6px; text-align: right; }
        .chat-empty { padding: 40px; text-align: center; color: #6b7280; }
        .chat-empty h3 { margin-bottom: 12px; font-size: 18px; }
        .chat-input { padding: 16px 24px; border-top: 1px solid #eef2ff; background: white; }
        .chat-input form { display: flex; gap: 12px; align-items: center; }
        .chat-input textarea { flex: 1; min-height: 48px; border-radius: 999px; border: 1px solid #e5e7eb; padding: 14px 16px; resize: none; font-size: 14px; }
        .chat-input button { background: #6366f1; color: white; border: none; border-radius: 999px; padding: 14px 20px; cursor: pointer; font-weight: 700; }
        .chat-input button:hover { background: #4f46e5; }
        .chat-create { padding: 20px; border-top: 1px solid #eef2ff; }
        .chat-create label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; }
        .chat-create select, .chat-create textarea { width: 100%; padding: 12px 14px; border: 1px solid #e5e7eb; border-radius: 12px; font-size: 14px; background: white; }
        .chat-create textarea { min-height: 120px; resize: vertical; }
        .chat-create button { margin-top: 12px; display: inline-flex; align-items: center; gap: 8px; }
        .panel { padding: 0; border: none; box-shadow: none; }
        .panel > h2 { padding: 22px 24px; border-bottom: 1px solid #eef2ff; margin: 0; }
        @media (max-width: 1024px) { .messages-layout { grid-template-columns: 1fr; } .conversations-panel { max-height: 520px; } }
        @media (max-width: 600px) { .chat-input form { flex-direction: column; } .chat-input button { width: 100%; } }

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

        <div class="messages-layout">
            <div class="conversations-panel">
                <div class="conversations-header">
                    <div>
                        <h2>Chats</h2>
                        <span style="font-size:13px;color:#6b7280;">Recent conversations</span>
                    </div>
                    <a href="#new-chat">New</a>
                </div>
                <div class="conversation-list">
                    <?php if (count($conversations) > 0): ?>
                        <?php foreach ($conversations as $conversation): ?>
                        <a href="messages.php?contact=<?php echo $conversation['id']; ?>" class="conversation-card<?php echo $selectedContactId == $conversation['id'] ? ' active' : ''; ?>">
                            <img src="https://api.dicebear.com/7.x/avataaars/svg?seed=<?php echo urlencode($conversation['name']); ?>" alt="" class="conversation-avatar">
                            <div class="conversation-content">
                                <div class="conversation-name">
                                    <span><?php echo escape($conversation['name']); ?></span>
                                    <span class="conversation-time"><?php echo date('h:i A', strtotime($conversation['last_time'])); ?></span>
                                </div>
                                <div class="conversation-snippet"><?php echo strlen($conversation['last_message']) > 60 ? substr(escape($conversation['last_message']), 0, 60) . '...' : escape($conversation['last_message']); ?></div>
                                <div class="conversation-meta">
                                    <span class="conversation-role"><?php echo escape($conversation['role']); ?></span>
                                    <?php if ($conversation['unread'] > 0): ?>
                                        <span class="badge-unread"><?php echo $conversation['unread']; ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </a>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="chat-empty">
                            <h3>No conversations yet</h3>
                            <p>Start a chat by sending a message below.</p>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="chat-create" id="new-chat">
                    <h3 style="margin-top:0;margin-bottom:14px;font-size:16px;color:#111827;">Start new message</h3>
                    <form method="POST">
                        <label>To</label>
                        <select name="receiver_id" required>
                            <option value="">Select client...</option>
                            <?php foreach ($clients as $c): ?>
                                <option value="<?php echo $c['id']; ?>"><?php echo escape($c['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <label style="margin-top:12px;">Message</label>
                        <textarea name="message" placeholder="Type your message..." required></textarea>
                        <button type="submit" name="send_message" class="btn-send"><i class="fa-regular fa-paper-plane"></i> Send</button>
                    </form>
                </div>
            </div>

            <div class="chat-panel">
                <?php if ($selectedContact): ?>
                    <div class="chat-header">
                        <img src="https://api.dicebear.com/7.x/avataaars/svg?seed=<?php echo urlencode($selectedContact['name']); ?>" alt="" class="avatar">
                        <div class="chat-title">
                            <h2><?php echo escape($selectedContact['name']); ?></h2>
                            <span><?php echo escape($selectedContact['role']); ?></span>
                        </div>
                    </div>
                    <div class="chat-messages">
                        <?php if (count($selectedConversation) > 0): ?>
                            <?php foreach ($selectedConversation as $msg): ?>
                                <div class="message-row<?php echo $msg['sender_id'] == $user_id ? ' from-me' : ''; ?>">
                                    <?php if ($msg['sender_id'] != $user_id): ?>
                                        <img src="https://api.dicebear.com/7.x/avataaars/svg?seed=<?php echo urlencode($selectedContact['name']); ?>" alt="" class="conversation-avatar">
                                    <?php endif; ?>
                                    <div class="message-bubble">
                                        <?php echo escape($msg['message']); ?>
                                        <div class="message-meta"><?php echo date('d M, h:i A', strtotime($msg['created_at'])); ?></div>
                                    </div>
                                    <?php if ($msg['sender_id'] == $user_id): ?>
                                        <img src="https://api.dicebear.com/7.x/avataaars/svg?seed=<?php echo urlencode($_SESSION['name']); ?>" alt="" class="conversation-avatar">
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="chat-empty">
                                <h3>Start the conversation</h3>
                                <p>Send a message to <?php echo escape($selectedContact['name']); ?> to begin chatting.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="chat-input">
                        <form method="POST">
                            <input type="hidden" name="receiver_id" value="<?php echo $selectedContact['id']; ?>">
                            <textarea name="message" placeholder="Type a message..." required></textarea>
                            <button type="submit" name="send_message"><i class="fa-regular fa-paper-plane"></i> Send</button>
                        </form>
                    </div>
                <?php else: ?>
                    <div class="chat-empty">
                        <h3>Select a chat</h3>
                        <p>Pick a conversation from the list or start a new message.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </main>
</div>

</body>
</html>