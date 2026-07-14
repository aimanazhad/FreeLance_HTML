<?php
require_once 'config.php';

if (!isLoggedIn() || !isFreelancer()) {
    redirect('../index.php');
}

$user_id = $_SESSION['user_id'];

// Get portfolio items
$portfolio = $pdo->query("
    SELECT * FROM portfolio 
    WHERE freelancer_id = $user_id 
    ORDER BY created_at DESC
")->fetchAll();

// Add portfolio item
if (isset($_POST['add_portfolio'])) {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $image_url = trim($_POST['image_url']);
    
    if (!empty($title)) {
        $stmt = $pdo->prepare("INSERT INTO portfolio (freelancer_id, title, description, image_url) VALUES (?, ?, ?, ?)");
        $stmt->execute([$user_id, $title, $description, $image_url]);
        redirect('portfolio.php?success=added');
    }
}

// Delete portfolio
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM portfolio WHERE id = ? AND freelancer_id = ?");
    $stmt->execute([$_GET['delete'], $user_id]);
    redirect('portfolio.php?success=deleted');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portfolio - Freelancer</title>
    <link rel="stylesheet" href="freelancer.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        .fm-portfolio-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 18px; }
        .fm-portfolio-card { background: #fff; border-radius: 16px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.06); }
        .fm-portfolio-card img { width: 100%; height: 180px; object-fit: cover; display: block; }
        .fm-portfolio-card-body { padding: 14px 16px; }
        .fm-portfolio-card-body h3 { font-size: 16px; margin: 0 0 6px; }
        .fm-portfolio-card-body p { font-size: 13px; color: #6b7280; margin: 0 0 12px; line-height: 1.5; }
        .fm-add-card { border: 2px dashed #e5e7eb; border-radius: 16px; display: flex; align-items: center; justify-content: center; flex-direction: column; gap: 8px; color: #6b7280; min-height: 220px; background: transparent; cursor: pointer; }
        .fm-add-card:hover { border-color: #6366f1; color: #6366f1; }
        .fm-btn { background: #6366f1; color: #fff; border: none; padding: 8px 16px; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 13px; }
        .fm-btn:hover { background: #4f46e5; }
        .fm-btn.danger { background: #fee2e2; color: #dc2626; }
        .fm-btn.danger:hover { background: #fecaca; }
        .alert { padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; }
        .alert-success { background: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0; }
        .modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.5); display: none; align-items: center; justify-content: center; z-index: 100; }
        .modal-overlay.show { display: flex; }
        .modal-box { background: #fff; border-radius: 16px; padding: 24px; width: 480px; max-width: 90%; }
        .modal-box h3 { margin-top: 0; }
        .modal-box .form-group { margin-bottom: 12px; }
        .modal-box label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 4px; }
        .modal-box input, .modal-box textarea { width: 100%; padding: 10px 14px; border: 1px solid #e5e7eb; border-radius: 10px; font-size: 14px; background: white; }
        .modal-box textarea { min-height: 80px; resize: vertical; }
        .modal-actions { display: flex; gap: 10px; justify-content: flex-end; margin-top: 12px; }
        .btn-secondary { background: #f3f4f6; color: #1f2937; border: none; padding: 8px 16px; border-radius: 8px; cursor: pointer; font-weight: 600; }
        .btn-secondary:hover { background: #e5e7eb; }
        @media (max-width: 768px) { .fm-portfolio-grid { grid-template-columns: 1fr; } }
        .fm-empty { text-align: center; padding: 40px; color: #6b7280; }
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
                <a href="portfolio.php" class="menu-item active"><i class="fa-solid fa-folder-open"></i> Portfolio</a>
                <a href="earnings.php" class="menu-item"><i class="fa-solid fa-wallet"></i> Earnings</a>
                <a href="profile.php" class="menu-item"><i class="fa-solid fa-user"></i> Profile</a>
                <a href="settings_freelancer.php" class="menu-item"><i class="fa-solid fa-gear"></i> Settings</a>
                <a href="index.php?logout=1" class="menu-item" style="margin-top: 20px; border-top: 1px solid var(--color-border-line); padding-top: 16px;"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
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

            <div class="fm-hero">
                <div class="fm-hero-eyebrow">Showcase</div>
                <h1>Portfolio</h1>
                <p>Showcase your best work to clients.</p>
            </div>

            <?php if (isset($_GET['success']) && $_GET['success'] == 'added'): ?>
                <div class="alert alert-success">✅ Portfolio item added successfully!</div>
            <?php endif; ?>
            <?php if (isset($_GET['success']) && $_GET['success'] == 'deleted'): ?>
                <div class="alert alert-success">✅ Portfolio item deleted!</div>
            <?php endif; ?>

            <div class="fm-portfolio-grid">
                <?php if (count($portfolio) > 0): ?>
                    <?php foreach ($portfolio as $item): ?>
                    <div class="fm-portfolio-card">
                        <img src="<?php echo escape($item['image_url'] ?? 'https://picsum.photos/seed/' . urlencode($item['title']) . '/400/300'); ?>" alt="<?php echo escape($item['title']); ?>">
                        <div class="fm-portfolio-card-body">
                            <h3><?php echo escape($item['title']); ?></h3>
                            <p><?php echo escape($item['description'] ?? ''); ?></p>
                            <a href="portfolio.php?delete=<?php echo $item['id']; ?>" class="fm-btn danger" onclick="return confirm('Delete this project?')">Delete</a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="fm-empty" style="grid-column: span 3;">No portfolio items yet. Add your first project!</div>
                <?php endif; ?>
                <div class="fm-add-card" onclick="document.getElementById('addModal').classList.add('show')">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14m-7-7h14"/></svg>
                    Add New Project
                </div>
            </div>
        </main>
    </div>

    <!-- Add Portfolio Modal -->
    <div class="modal-overlay" id="addModal" onclick="if(event.target===this)this.classList.remove('show')">
        <div class="modal-box">
            <h3>Add New Project</h3>
            <form method="POST">
                <div class="form-group">
                    <label>Project Title *</label>
                    <input type="text" name="title" placeholder="Enter project title" required>
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" placeholder="Describe your project..."></textarea>
                </div>
                <div class="form-group">
                    <label>Image URL</label>
                    <input type="text" name="image_url" placeholder="https://example.com/image.jpg (optional)">
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn-secondary" onclick="document.getElementById('addModal').classList.remove('show')">Cancel</button>
                    <button type="submit" name="add_portfolio" class="fm-btn">Save Project</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>