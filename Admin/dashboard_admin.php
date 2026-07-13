<?php
require_once '../config.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('../index.php');
}

$totalUsers = getTotalUsers();
$totalFreelancers = getTotalFreelancers();
$totalClients = getTotalClients();
$recentUsers = getRecentUsers(5);
$jobsCount = $pdo->query("SELECT COUNT(*) FROM jobs")->fetchColumn();
$paymentsCount = $pdo->query("SELECT COUNT(*) FROM payments")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="admin-style.css">
</head>
<body>
    <div style="max-width: 1100px; margin: 40px auto; padding: 20px;">
        <h1>Admin Dashboard</h1>
        <p>Data dipaparkan dari database.</p>
        <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; margin-bottom: 24px;">
            <div style="background:#fff; border-radius:16px; padding:20px; box-shadow:0 2px 8px rgba(0,0,0,0.06);">Users: <strong><?php echo $totalUsers; ?></strong></div>
            <div style="background:#fff; border-radius:16px; padding:20px; box-shadow:0 2px 8px rgba(0,0,0,0.06);">Freelancers: <strong><?php echo $totalFreelancers; ?></strong></div>
            <div style="background:#fff; border-radius:16px; padding:20px; box-shadow:0 2px 8px rgba(0,0,0,0.06);">Clients: <strong><?php echo $totalClients; ?></strong></div>
            <div style="background:#fff; border-radius:16px; padding:20px; box-shadow:0 2px 8px rgba(0,0,0,0.06);">Jobs: <strong><?php echo $jobsCount; ?></strong></div>
            <div style="background:#fff; border-radius:16px; padding:20px; box-shadow:0 2px 8px rgba(0,0,0,0.06);">Payments: <strong><?php echo $paymentsCount; ?></strong></div>
        </div>
        <div style="background:#fff; border-radius:16px; padding:20px; box-shadow:0 2px 8px rgba(0,0,0,0.06);">
            <h3>Recent Users</h3>
            <?php foreach ($recentUsers as $user): ?>
                <div style="padding:10px 0; border-bottom:1px solid #e5e7eb;"><?php echo escape($user['name']); ?> · <?php echo escape($user['email']); ?> · <?php echo escape($user['role']); ?></div>
            <?php endforeach; ?>
        </div>
    </div>
</body>
</html>
