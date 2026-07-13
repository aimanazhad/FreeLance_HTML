<?php
require_once '../config.php';

if (!isLoggedIn() || !isClient()) {
    redirect('../index.php');
}

$user_id = $_SESSION['user_id'];

// Get completed jobs that need review
$reviews = $pdo->query("
    SELECT j.id as job_id, j.title, u.name as freelancer_name, u.id as freelancer_id
    FROM jobs j
    JOIN applications a ON a.job_id = j.id
    JOIN users u ON a.freelancer_id = u.id
    WHERE j.client_id = $user_id AND j.status = 'completed'
    ORDER BY j.updated_at DESC LIMIT 5
")->fetchAll();

// Submit review
if (isset($_POST['submit_review'])) {
    $freelancer_id = $_POST['freelancer_id'];
    $job_id = $_POST['job_id'];
    $rating = $_POST['rating'];
    $comment = trim($_POST['comment']);
    
    $stmt = $pdo->prepare("INSERT INTO reviews (reviewer_id, reviewee_id, job_id, rating, comment) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$user_id, $freelancer_id, $job_id, $rating, $comment]);
    redirect('review_client.php?success=submitted');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Review - Client Dashboard</title>
    <link rel="stylesheet" href="client-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        .review-card-list { display: flex; flex-direction: column; gap: 14px; }
        .review-card { display: flex; align-items: center; gap: 16px; padding: 14px 18px; border-radius: 14px; background: #fff; border: 1px solid #e5e7eb; }
        .review-avatar { width: 56px; height: 56px; border-radius: 50%; object-fit: cover; flex-shrink: 0; background: #eef2ff; }
        .review-card-content { flex: 1; }
        .review-card-content h4 { margin: 0; font-size: 16px; font-weight: 800; }
        .review-card-content p { margin: 6px 0 4px 0; color: #6b7280; font-size: 13px; }
        .review-card-content span { font-size: 12px; color: #6b7280; }
        .btn-primary { background: #6366f1; color: white; border: none; padding: 8px 16px; border-radius: 8px; font-weight: 600; cursor: pointer; }
        .btn-primary:hover { background: #4f46e5; }
        .alert { padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; }
        .alert-success { background: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0; }
        .modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.5); display: none; align-items: center; justify-content: center; z-index: 100; }
        .modal-overlay.show { display: flex; }
        .modal-box { background: white; border-radius: 16px; padding: 24px; max-width: 480px; width: 100%; }
        .modal-box h3 { margin-top: 0; }
        .modal-box .form-group { margin-bottom: 12px; }
        .modal-box label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 4px; }
        .modal-box input, .modal-box select, .modal-box textarea { width: 100%; padding: 10px 14px; border: 1px solid #e5e7eb; border-radius: 10px; font-size: 14px; background: white; }
        .modal-box textarea { min-height: 80px; resize: vertical; }
        .modal-actions { display: flex; gap: 10px; justify-content: flex-end; margin-top: 12px; }
        .btn-secondary { background: #f3f4f6; color: #1f2937; border: none; padding: 8px 16px; border-radius: 8px; font-weight: 600; cursor: pointer; }
        .btn-secondary:hover { background: #e5e7eb; }
        .rating-stars { display: flex; gap: 4px; font-size: 24px; cursor: pointer; }
        .rating-stars .star { color: #d1d5db; transition: color 0.2s; }
        .rating-stars .star.active { color: #f59e0b; }
        .rating-stars .star:hover { color: #f59e0b; }
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
                <a href="payment_client.php" class="menu-item"><i class="fa-solid fa-credit-card"></i> Payment</a>
                <a href="savedLancer_client.php" class="menu-item"><i class="fa-solid fa-star"></i> Saved Freelancers</a>
                <a href="review_client.php" class="menu-item active"><i class="fa-solid fa-star-half-stroke"></i> Review</a>
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

            <?php if (isset($_GET['success']) && $_GET['success'] == 'submitted'): ?>
                <div class="alert alert-success">✅ Review submitted successfully!</div>
            <?php endif; ?>

            <section class="workspace-panel-card">
                <div class="panel-header-row">
                    <h3 class="panel-title-text">Freelancer Review & Rating</h3>
                    <p class="panel-subtitle-text">Rate your experience with freelancers who have completed jobs for you.</p>
                </div>
                <div class="review-card-list">
                    <?php foreach ($reviews as $review): ?>
                    <article class="review-card">
                        <img src="https://api.dicebear.com/7.x/avataaars/svg?seed=<?php echo urlencode($review['freelancer_name']); ?>" alt="<?php echo escape($review['freelancer_name']); ?>" class="review-avatar">
                        <div class="review-card-content">
                            <h4><?php echo escape($review['freelancer_name']); ?></h4>
                            <p><?php echo escape($review['title']); ?></p>
                            <span>Completed: <?php echo date('d/m/Y'); ?></span>
                        </div>
                        <button class="btn-primary" onclick="openReviewModal(<?php echo $review['freelancer_id']; ?>, <?php echo $review['job_id']; ?>, '<?php echo escape($review['freelancer_name']); ?>')">Submit Review</button>
                    </article>
                    <?php endforeach; ?>
                </div>
            </section>
        </main>
    </div>

    <!-- Review Modal -->
    <div class="modal-overlay" id="reviewModal" onclick="if(event.target===this)this.classList.remove('show')">
        <div class="modal-box">
            <h3><i class="fa-solid fa-star" style="color:#f59e0b;"></i> Submit Review</h3>
            <form method="POST">
                <input type="hidden" name="freelancer_id" id="review_freelancer_id">
                <input type="hidden" name="job_id" id="review_job_id">
                <p style="color:#6b7280;margin-bottom:12px;">Reviewing: <strong id="review_name"></strong></p>
                <div class="form-group">
                    <label>Rating</label>
                    <div class="rating-stars" id="ratingStars">
                        <span class="star" data-value="1" onclick="setRating(1)">★</span>
                        <span class="star" data-value="2" onclick="setRating(2)">★</span>
                        <span class="star" data-value="3" onclick="setRating(3)">★</span>
                        <span class="star" data-value="4" onclick="setRating(4)">★</span>
                        <span class="star" data-value="5" onclick="setRating(5)">★</span>
                    </div>
                    <input type="hidden" name="rating" id="ratingValue" value="5">
                </div>
                <div class="form-group">
                    <label>Comment</label>
                    <textarea name="comment" placeholder="Share your experience..."></textarea>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn-secondary" onclick="document.getElementById('reviewModal').classList.remove('show')">Cancel</button>
                    <button type="submit" name="submit_review" class="btn-primary">Submit Review</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        let selectedRating = 5;

        function openReviewModal(freelancerId, jobId, name) {
            document.getElementById('review_freelancer_id').value = freelancerId;
            document.getElementById('review_job_id').value = jobId;
            document.getElementById('review_name').textContent = name;
            document.getElementById('reviewModal').classList.add('show');
            setRating(5);
        }

        function setRating(value) {
            selectedRating = value;
            document.getElementById('ratingValue').value = value;
            document.querySelectorAll('#ratingStars .star').forEach(star => {
                star.classList.toggle('active', parseInt(star.dataset.value) <= value);
            });
        }
    </script>
</body>
</html>