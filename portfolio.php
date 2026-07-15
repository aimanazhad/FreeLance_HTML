<?php
require_once 'config.php';

if (!isLoggedIn() || !isFreelancer()) {
    redirect('index.php');
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
    <title>Portfolio</title>
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

        .portfolio-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 18px;
        }
        .portfolio-card {
            background: white; border-radius: 12px; border: 1px solid #e5e7eb; overflow: hidden; transition: all 0.2s ease;
        }
        .portfolio-card:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(0,0,0,0.06); }
        .portfolio-card img { width: 100%; height: 180px; object-fit: cover; display: block; }
        .portfolio-card-body { padding: 16px; }
        .portfolio-card-body h3 { font-size: 16px; font-weight: 700; color: #0f172a; margin-bottom: 4px; }
        .portfolio-card-body p { font-size: 13px; color: #64748b; margin-bottom: 12px; line-height: 1.5; }
        .portfolio-card-body .btn-delete {
            background: #fee2e2; color: #dc2626; border: none; padding: 6px 14px; border-radius: 6px;
            font-weight: 600; font-size: 13px; cursor: pointer; transition: all 0.2s ease;
        }
        .portfolio-card-body .btn-delete:hover { background: #fecaca; }

        .add-card {
            border: 2px dashed #e5e7eb; border-radius: 12px; display: flex; align-items: center;
            justify-content: center; flex-direction: column; gap: 8px; color: #94a3b8;
            min-height: 280px; background: transparent; cursor: pointer; transition: all 0.2s ease;
        }
        .add-card:hover { border-color: #6366f1; color: #6366f1; background: #f8faff; }
        .add-card i { font-size: 32px; }
        .add-card span { font-size: 14px; font-weight: 500; }

        .modal-overlay {
            position: fixed; inset: 0; background: rgba(0,0,0,0.5);
            display: none; align-items: center; justify-content: center; z-index: 100;
        }
        .modal-overlay.show { display: flex; }
        .modal-box {
            background: #fff; border-radius: 16px; padding: 24px; width: 480px; max-width: 90%;
        }
        .modal-box h3 { margin-top: 0; margin-bottom: 16px; font-size: 18px; }
        .modal-box .form-group { margin-bottom: 12px; }
        .modal-box label {
            display: block; font-size: 13px; font-weight: 600; color: #1f2937; margin-bottom: 4px;
        }
        .modal-box input, .modal-box textarea {
            width: 100%; padding: 10px 14px; border: 1px solid #e5e7eb; border-radius: 8px;
            font-size: 14px; background: #f9fafb; outline: none; font-family: inherit;
        }
        .modal-box input:focus, .modal-box textarea:focus {
            border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99,102,241,0.1);
        }
        .modal-box textarea { min-height: 80px; resize: vertical; }
        .modal-actions { display: flex; gap: 10px; justify-content: flex-end; margin-top: 12px; }
        .btn-cancel {
            background: #f1f5f9; color: #1f2937; border: none; padding: 8px 16px; border-radius: 8px;
            font-weight: 600; cursor: pointer; font-size: 14px;
        }
        .btn-cancel:hover { background: #e5e7eb; }
        .btn-save {
            background: #6366f1; color: white; border: none; padding: 8px 16px; border-radius: 8px;
            font-weight: 600; cursor: pointer; font-size: 14px;
        }
        .btn-save:hover { background: #4f46e5; }

        .alert { padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; font-weight: 600; font-size: 14px; }
        .alert-success { background: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0; }
        .empty { grid-column: span 3; text-align: center; padding: 60px; color: #94a3b8; }
        .empty i { font-size: 48px; color: #d1d5db; display: block; margin-bottom: 12px; }
        .empty p { font-size: 14px; }

        @media (max-width: 1024px) {
            .portfolio-grid { grid-template-columns: repeat(2, 1fr); }
            .empty { grid-column: span 2; }
        }
        @media (max-width: 768px) {
            .sidebar { width: 200px; padding: 16px 12px; }
            .main-content { padding: 20px; }
            .portfolio-grid { grid-template-columns: 1fr; }
            .empty { grid-column: span 1; }
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
            <a href="messages.php"><i class="fa-solid fa-comment-dots"></i> Messages</a>
            <a href="portfolio.php" class="active"><i class="fa-solid fa-folder-open"></i> Portfolio</a>
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
                <h1>📂 Portfolio</h1>
                <p>Showcase your best work to clients.</p>
            </div>
            <div class="emoji">🎨</div>
        </div>

        <?php if (isset($_GET['success']) && $_GET['success'] == 'added'): ?>
            <div class="alert alert-success">✅ Portfolio item added successfully!</div>
        <?php endif; ?>
        <?php if (isset($_GET['success']) && $_GET['success'] == 'deleted'): ?>
            <div class="alert alert-success">✅ Portfolio item deleted!</div>
        <?php endif; ?>

        <div class="portfolio-grid">
            <?php if (count($portfolio) > 0): ?>
                <?php foreach ($portfolio as $item): ?>
                <div class="portfolio-card">
                    <img src="<?php echo escape($item['image_url'] ?? 'https://picsum.photos/seed/' . urlencode($item['title']) . '/400/260'); ?>" alt="<?php echo escape($item['title']); ?>">
                    <div class="portfolio-card-body">
                        <h3><?php echo escape($item['title']); ?></h3>
                        <p><?php echo escape($item['description'] ?? ''); ?></p>
                        <a href="portfolio.php?delete=<?php echo $item['id']; ?>" class="btn-delete" onclick="return confirm('Delete this project?')">
                            <i class="fa-regular fa-trash-can"></i> Delete
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty">
                    <i class="fa-regular fa-folder-open"></i>
                    <p>No portfolio items yet. Add your first project!</p>
                </div>
            <?php endif; ?>
            <div class="add-card" onclick="document.getElementById('addModal').classList.add('show')">
                <i class="fa-regular fa-plus"></i>
                <span>Add New Project</span>
            </div>
        </div>

        <!-- ADD MODAL -->
        <div class="modal-overlay" id="addModal" onclick="if(event.target===this)this.classList.remove('show')">
            <div class="modal-box">
                <h3><i class="fa-regular fa-plus" style="color:#6366f1;"></i> Add New Project</h3>
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
                        <button type="button" class="btn-cancel" onclick="document.getElementById('addModal').classList.remove('show')">Cancel</button>
                        <button type="submit" name="add_portfolio" class="btn-save">
                            <i class="fa-regular fa-floppy-disk"></i> Save Project
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </main>
</div>

</body>
</html>