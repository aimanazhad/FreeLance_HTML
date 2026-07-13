<?php
require_once '../config.php';

if (!isLoggedIn() || !isClient()) {
    redirect('../index.php');
}

$error = '';
$success = '';

if (isset($_POST['post_job'])) {
    $client_id = $_SESSION['user_id'];
    $title = trim($_POST['title']);
    $category = trim($_POST['category']);
    $description = trim($_POST['description']);
    $budget_min = $_POST['budget_min'] ?? 0;
    $budget_max = $_POST['budget_max'] ?? 0;
    $project_type = $_POST['project_type'] ?? 'one-time';
    $location_type = $_POST['location_type'] ?? 'remote';
    $deadline = $_POST['deadline'] ?? null;
    $skills = trim($_POST['skills'] ?? '');
    
    if (empty($title) || empty($category)) {
        $error = '⚠️ Please fill in title and category.';
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO jobs (client_id, title, category, description, budget_min, budget_max, project_type, location_type, deadline, skills) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        if ($stmt->execute([$client_id, $title, $category, $description, $budget_min, $budget_max, $project_type, $location_type, $deadline, $skills])) {
            $success = '✅ Job posted successfully!';
        } else {
            $error = '❌ Failed to post job.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Post a Job - Client Dashboard</title>
    <link rel="stylesheet" href="client-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        .form-grid { display: grid; gap: 24px; }
        .panel-form-card { background: #fff; border-radius: 20px; padding: 24px; box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04); }
        .field-row { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 16px; align-items: end; }
        .field-row.two { grid-template-columns: 1.5fr 1fr; }
        .field-row.single { grid-template-columns: 1fr; }
        label.input-label { display: block; margin-bottom: 10px; font-weight: 700; font-size: 13px; color: #111827; }
        input[type="text"], input[type="date"], input[type="number"], textarea, select { width: 100%; border: 1px solid #d1d5db; border-radius: 14px; padding: 14px 16px; font-size: 14px; color: #111827; background: #f8fafc; }
        textarea { min-height: 130px; resize: vertical; }
        select { appearance: none; background-image: linear-gradient(45deg, transparent 50%, #6d28d9 50%), linear-gradient(135deg, #6d28d9 50%, transparent 50%); background-position: calc(100% - 18px) calc(1em + 2px), calc(100% - 12px) calc(1em + 2px); background-size: 6px 6px, 6px 6px; background-repeat: no-repeat; }
        .tag-list { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 10px; }
        .tag-pill { display: inline-flex; align-items: center; gap: 8px; background: #eef2ff; color: #4338ca; padding: 8px 12px; border-radius: 999px; font-size: 13px; }
        .tag-pill button { border: none; background: transparent; color: #4338ca; cursor: pointer; font-size: 14px; }
        .file-drop { border: 2px dashed #c4b5fd; border-radius: 18px; padding: 28px; text-align: center; color: #6b7280; background: #faf5ff; }
        .file-drop i { font-size: 30px; margin-bottom: 12px; color: #7c3aed; }
        .button-group { display: flex; gap: 12px; justify-content: flex-end; margin-top: 20px; }
        .btn-secondary { background: transparent; border: 1px solid #c7d2fe; color: #4338ca; padding: 14px 26px; border-radius: 999px; cursor: pointer; font-weight: 700; }
        .btn-primary { background: #7c3aed; color: #fff; border: none; padding: 14px 26px; border-radius: 999px; cursor: pointer; font-weight: 700; }
        .section-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px; }
        .section-title { font-size: 18px; font-weight: 700; }
        .checkbox-group { display: flex; gap: 28px; margin-top: 12px; }
        .radio-label { display: inline-flex; align-items: center; gap: 10px; cursor: pointer; font-weight: 600; color: #111827; }
        .radio-label input { accent-color: #7c3aed; }
        .alert { padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; }
        .alert-success { background: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0; }
        .alert-danger { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }
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
                <a href="jobs_client.php" class="menu-item active"><i class="fa-solid fa-circle-plus"></i> Post a Job</a>
                <a href="myjob_client.php" class="menu-item"><i class="fa-solid fa-file-lines"></i> My Jobs</a>
                <a href="message_client.php" class="menu-item"><i class="fa-solid fa-comment-dots"></i> Messages</a>
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

            <div class="form-grid">
                <div class="panel-form-card">
                    <div class="section-header">
                        <div>
                            <p class="eyebrow" style="font-size:13px;color:#6b7280;margin-bottom:4px;">Job Title &amp; Category</p>
                            <h2 style="font-size:22px; margin:0;">Create a new job post</h2>
                        </div>
                    </div>

                    <?php if ($error): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php endif; ?>
                    <?php if ($success): ?><div class="alert alert-success"><?php echo $success; ?></div><?php endif; ?>

                    <form method="POST">
                        <div class="field-row single">
                            <div>
                                <label class="input-label" for="jobTitle">Job Title *</label>
                                <input id="jobTitle" name="title" type="text" placeholder="Your job title" required>
                            </div>
                        </div>
                        <div class="field-row">
                            <div>
                                <label class="input-label" for="jobCategory">Job Category *</label>
                                <select id="jobCategory" name="category" required>
                                    <option value="">Select...</option>
                                    <option value="Design & Creative">Design & Creative</option>
                                    <option value="Development & IT">Development & IT</option>
                                    <option value="Writing & Translation">Writing & Translation</option>
                                    <option value="Marketing">Marketing</option>
                                    <option value="Photography & Video">Photography & Video</option>
                                    <option value="Tutoring & Education">Tutoring & Education</option>
                                </select>
                            </div>
                            <div>
                                <label class="input-label" for="projectType">Project Type</label>
                                <select id="projectType" name="project_type">
                                    <option value="one-time">One-time</option>
                                    <option value="ongoing">Ongoing</option>
                                    <option value="hourly">Hourly</option>
                                </select>
                            </div>
                        </div>
                        <div class="field-row single">
                            <div>
                                <label class="input-label" for="jobDescription">Job Description</label>
                                <textarea id="jobDescription" name="description" placeholder="Describe your project..."></textarea>
                            </div>
                        </div>
                        <div class="field-row three" style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px;">
                            <div>
                                <label class="input-label" for="minBudget">Min Budget (RM)</label>
                                <input id="minBudget" name="budget_min" type="number" placeholder="100">
                            </div>
                            <div>
                                <label class="input-label" for="maxBudget">Max Budget (RM)</label>
                                <input id="maxBudget" name="budget_max" type="number" placeholder="500">
                            </div>
                            <div>
                                <label class="input-label" for="deadline">Deadline</label>
                                <input id="deadline" name="deadline" type="date">
                            </div>
                        </div>
                        <div class="field-row single">
                            <div>
                                <label class="input-label" for="skills">Skills Required</label>
                                <input id="skills" name="skills" type="text" placeholder="e.g. Figma, Photoshop">
                            </div>
                        </div>
                        <div class="field-row">
                            <div>
                                <label class="input-label" for="locationType">Location</label>
                                <select id="locationType" name="location_type">
                                    <option value="remote">Remote</option>
                                    <option value="onsite">On-site</option>
                                    <option value="hybrid">Hybrid</option>
                                </select>
                            </div>
                        </div>
                        <div class="button-group">
                            <button type="reset" class="btn-secondary">Reset</button>
                            <button type="submit" name="post_job" class="btn-primary">Post Job</button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
</body>
</html>