<?php
require_once 'config.php';

// ============================================
// LOGIN PROCESS
// ============================================
if (isset($_POST['login'])) {
    $email = trim($_POST['email']);
    $password = md5(trim($_POST['password']));
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND password = ?");
    $stmt->execute([$email, $password]);
    $user = $stmt->fetch();
    
    if ($user) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['name'] = $user['name'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['logged_in'] = true;
        
        if ($user['role'] === 'admin') {
            header('Location: Admin/dashboard_admin.html');
        } elseif ($user['role'] === 'client') {
            header('Location: Client/dashboard_client.html');
        } elseif ($user['role'] === 'freelancer') {
            header('Location: Freelancer_HTML/dashboard_freelancer.html');
        }
        exit();
    } else {
        // Redirect back to login with error
        header('Location: index.html?error=1');
        exit();
    }
}

// ============================================
// SIGNUP PROCESS
// ============================================
if (isset($_POST['signup'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = md5(trim($_POST['password']));
    $role = $_POST['role'];
    
    // Check if email exists
    $check = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $check->execute([$email]);
    
    if ($check->rowCount() > 0) {
        header('Location: signup.html?error=email_exists');
        exit();
    } else {
        $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
        $stmt->execute([$name, $email, $password, $role]);
        header('Location: index.html?success=1');
        exit();
    }
}

// ============================================
// LOGOUT
// ============================================
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: index.html');
    exit();
}

// ============================================
// IF LOGGED IN, REDIRECT TO DASHBOARD
// ============================================
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    if ($_SESSION['role'] === 'admin') {
        header('Location: Admin/dashboard_admin.html');
    } elseif ($_SESSION['role'] === 'client') {
        header('Location: Client/dashboard_client.html');
    } elseif ($_SESSION['role'] === 'freelancer') {
        header('Location: Freelancer_HTML/dashboard_freelancer.html');
    }
    exit();
}

// If no action, redirect to login
header('Location: index.html');
exit();
?>