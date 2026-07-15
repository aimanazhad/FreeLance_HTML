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
            'role' => $contactMap[$otherId]['role'] ?? 'Freelancer',
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

// Mark messages as read when a conversation is opened
if ($selectedContactId) {
    $markRead = $pdo->prepare("UPDATE messages SET is_read = 1 WHERE sender_id = ? AND receiver_id = ? AND is_read = 0");
    $markRead->execute([$selectedContactId, $user_id]);
    foreach ($conversations as &$conversation) {
        if ($conversation['id'] == $selectedContactId) {
            $conversation['unread'] = 0;
            break;
        }
    }
    unset($conversation);
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
        redirect('message_client.php?success=sent&contact=' . intval($receiver_id));
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
        .messages-layout { display: grid; grid-template-columns: 320px 1fr; gap: 24px; }
        .conversations-panel, .chat-panel { background: #fff; border-radius: 24px; border: 1px solid #e5e7eb; box-shadow: 0 16px 40px rgba(15, 23, 42, 0.06); }
        .conversations-panel { display: flex; flex-direction: column; overflow: hidden; }
        .conversations-header { padding: 22px 24px; border-bottom: 1px solid #eef2ff; display: flex; justify-content: space-between; align-items: center; gap: 12px; }
        .conversations-header h2 { margin: 0; font-size: 18px; font-weight: 700; }
        .conversation-list { display: flex; flex-direction: column; overflow-y: auto; max-height: calc(100vh - 220px); }
        .conversation-card { display: flex; align-items: center; gap: 14px; padding: 16px 20px; border-bottom: 1px solid #f3f4f6; cursor: pointer; text-decoration: none; color: inherit; transition: background 0.2s ease, border-color 0.2s ease; }
        .conversation-card:last-child { border-bottom: none; }
        .conversation-card:hover { background: #f8f4ff; }
        .conversation-card.active { background: #f8f9ff; border-left: 2px solid #6366f1; padding-left: 16px; }
        .conversation-card.active .conversation-name span { color: #0f172a; }
        .conversation-card.active .conversation-snippet { color: #6b7280; }
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
        .chat-actions { display: flex; justify-content: flex-end; margin-top: 10px; }
        .chat-create { padding: 20px; border-bottom: 1px solid #eef2ff; }
        .chat-create label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; }
        .chat-create select, .chat-create textarea { width: 100%; padding: 12px 14px; border: 1px solid #e5e7eb; border-radius: 12px; font-size: 14px; background: white; }
        .chat-create textarea { min-height: 120px; resize: vertical; }
        .chat-create button { margin-top: 12px; display: inline-flex; align-items: center; gap: 8px; }
        @media (max-width: 1024px) { .messages-layout { grid-template-columns: 1fr; } .conversations-panel { max-height: 520px; } }
        @media (max-width: 600px) { .chat-input form { flex-direction: column; } .chat-input button { width: 100%; } }
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
                <div class="conversations-panel">
                    <div class="conversations-header">
                        <div>
                            <h2>Chats</h2>
                            <span style="font-size:13px;color:#6b7280;">Recent conversations</span>
                        </div>
                        <a href="#new-chat" class="btn-send" style="padding:10px 16px;font-size:13px;">New</a>
                    </div>
                    <div class="conversation-list">
                        <?php if (count($conversations) > 0): ?>
                            <?php foreach ($conversations as $conversation): ?>
                                <a href="message_client.php?contact=<?php echo $conversation['id']; ?>" class="conversation-card<?php echo $selectedContactId == $conversation['id'] ? ' active' : ''; ?>">
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
                                <p>Start a chat by selecting a freelancer below.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="chat-create" id="new-chat">
                        <h3 style="margin-top:0;margin-bottom:14px;font-size:16px;color:#111827;">Start new message</h3>
                        <form method="POST">
                            <label>To</label>
                            <select name="receiver_id" required>
                                <option value="">Select freelancer...</option>
                                <?php foreach ($freelancers as $f): ?>
                                    <option value="<?php echo $f['id']; ?>"><?php echo escape($f['name']); ?></option>
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