<?php
// ============================================
// DATABASE CONFIGURATION
// ============================================

$host = 'localhost';
$dbname = 'freelancer_db';
$username = 'root';
$password = '';

// ============================================
// PDO CONNECTION WITH ERROR HANDLING
// ============================================

try {
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->exec("SET NAMES utf8mb4");

    // Create database if it does not exist
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

    // Reconnect to the target database
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->exec("SET NAMES utf8mb4");

    // Create users table
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(150) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        role VARCHAR(20) DEFAULT 'client',
        phone VARCHAR(30) DEFAULT NULL,
        bio TEXT DEFAULT NULL,
        skills TEXT DEFAULT NULL,
        notifications VARCHAR(20) DEFAULT 'on',
        status VARCHAR(20) DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // Create admins table (separate from users)
    $pdo->exec("CREATE TABLE IF NOT EXISTS admins (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        email VARCHAR(100) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        full_name VARCHAR(100) NOT NULL,
        phone VARCHAR(20) DEFAULT NULL,
        role ENUM('super_admin','admin','moderator') DEFAULT 'admin',
        status ENUM('active','inactive') DEFAULT 'active',
        last_login TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");

    // Create jobs table
    $pdo->exec("
CREATE TABLE IF NOT EXISTS jobs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    title VARCHAR(150) NOT NULL,
    category VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    budget_min DECIMAL(10,2) DEFAULT 0.00,
    budget_max DECIMAL(10,2) DEFAULT 0.00,
    project_type VARCHAR(50) DEFAULT NULL,
    location_type VARCHAR(50) DEFAULT NULL,
    deadline DATE DEFAULT NULL,
    skills TEXT DEFAULT NULL,
    status ENUM('active','in_progress','completed','cancelled') DEFAULT 'active',
    hired_freelancer_id INT DEFAULT NULL,
    completed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX (client_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Create applications table
    $pdo->exec("CREATE TABLE IF NOT EXISTS applications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        job_id INT NOT NULL,
        freelancer_id INT NOT NULL,
        status VARCHAR(30) DEFAULT 'pending',
        cover_letter TEXT DEFAULT NULL,
        bid_amount DECIMAL(10,2) DEFAULT NULL,
        applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        message TEXT DEFAULT NULL
    )");

    // Create messages table
    $pdo->exec("CREATE TABLE IF NOT EXISTS messages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        sender_id INT NOT NULL,
        receiver_id INT NOT NULL,
        job_id INT DEFAULT NULL,
        message TEXT NOT NULL,
        is_read TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // Create payments table
    $pdo->exec("CREATE TABLE IF NOT EXISTS payments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        freelancer_id INT DEFAULT NULL,
        job_id INT DEFAULT NULL,
        amount DECIMAL(10,2) DEFAULT 0,
        method VARCHAR(50) DEFAULT NULL,
        description TEXT DEFAULT NULL,
        status VARCHAR(20) DEFAULT 'pending',
        reference VARCHAR(100) DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        payment_date TIMESTAMP NULL,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");

    // Create portfolio table
    $pdo->exec("CREATE TABLE IF NOT EXISTS portfolio (
        id INT AUTO_INCREMENT PRIMARY KEY,
        freelancer_id INT NOT NULL,
        title VARCHAR(150) NOT NULL,
        description TEXT DEFAULT NULL,
        image_url VARCHAR(255) DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // Create reviews table
    $pdo->exec("CREATE TABLE IF NOT EXISTS reviews (
        id INT AUTO_INCREMENT PRIMARY KEY,
        reviewer_id INT NOT NULL,
        reviewee_id INT NOT NULL,
        job_id INT DEFAULT NULL,
        rating INT DEFAULT 0,
        comment TEXT DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // Create saved_freelancers table
    $pdo->exec("CREATE TABLE IF NOT EXISTS saved_freelancers (
        id INT AUTO_INCREMENT PRIMARY KEY,
        client_id INT NOT NULL,
        freelancer_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

} catch(PDOException $e) {
    die("❌ Connection failed: " . $e->getMessage());
}

// ============================================
// SESSION START (if not already started)
// ============================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ============================================
// HELPER FUNCTIONS
// ============================================

/**
 * Check if user is logged in (from users table)
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
}

/**
 * Check if admin is logged in (from admins table)
 */
function isAdminLoggedIn() {
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

/**
 * Check if user is admin (from users table - legacy)
 */
function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

/**
 * Check if user is client
 */
function isClient() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'client';
}

/**
 * Check if user is freelancer
 */
function isFreelancer() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'freelancer';
}

/**
 * Redirect to a URL
 */
function redirect($url) {
    header("Location: $url");
    exit();
}

/**
 * Escape HTML characters for security
 */
function escape($data) {
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}

// ============================================
// USER FUNCTIONS (from users table)
// ============================================

/**
 * Get user data by ID
 */
function getUserById($id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

/**
 * Get user data by email
 */
function getUserByEmail($email) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    return $stmt->fetch();
}

/**
 * Get total number of users
 */
function getTotalUsers() {
    global $pdo;
    return $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
}

/**
 * Get total number of freelancers
 */
function getTotalFreelancers() {
    global $pdo;
    return $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'freelancer'")->fetchColumn();
}

/**
 * Get total number of clients
 */
function getTotalClients() {
    global $pdo;
    return $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'client'")->fetchColumn();
}

/**
 * Get all users
 */
function getAllUsers() {
    global $pdo;
    return $pdo->query("SELECT * FROM users ORDER BY created_at DESC")->fetchAll();
}

/**
 * Get recent users (last 5) - FIXED
 */
function getRecentUsers($limit = 5) {
    global $pdo;
    $stmt = $pdo->query("SELECT * FROM users ORDER BY created_at DESC LIMIT " . (int)$limit);
    return $stmt->fetchAll();
}

/**
* Create a new user (PLAIN PASSWORD - NO ENCRYPTION)
 */
function createUser($name, $email, $password, $role = 'client') {
    global $pdo;
    // Password disimpan terus - TANPA encryption
    $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
    return $stmt->execute([$name, $email, $password, $role]);
}


/**
 * Update user
 */
function updateUser($id, $name, $email, $role) {
    global $pdo;
    $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, role = ? WHERE id = ?");
    return $stmt->execute([$name, $email, $role, $id]);
}

/**
 * Delete user
 */
function deleteUser($id) {
    global $pdo;
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    return $stmt->execute([$id]);
}

/**
 * Update user status
 */
function updateUserStatus($id, $status) {
    global $pdo;
    $stmt = $pdo->prepare("UPDATE users SET status = ? WHERE id = ?");
    return $stmt->execute([$status, $id]);
}

/**
 * Check if email already exists
 */
function emailExists($email) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
    $stmt->execute([$email]);
    return $stmt->fetchColumn() > 0;
}

/**
* Login user (PLAIN PASSWORD - NO ENCRYPTION)
 */
function loginUser($email, $password) {
    global $pdo;
    // Bandingkan password terus - TANPA encryption
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND password = ?");
    $stmt->execute([$email, $password]);
    return $stmt->fetch();
}

/**
 * Logout user
 */
function logoutUser() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $_SESSION = array();

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }

    session_destroy();
}

// ============================================
// ADMIN FUNCTIONS (from admins table)
// ============================================

/**
 * Get admin data by ID
 */
function getAdminById($id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM admins WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

/**
 * Get admin data by email
 */
function getAdminByEmail($email) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM admins WHERE email = ?");
    $stmt->execute([$email]);
    return $stmt->fetch();
}

/**
 * Get admin data by username
 */
function getAdminByUsername($username) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ?");
    $stmt->execute([$username]);
    return $stmt->fetch();
}

/**
 * Get all admins
 */
function getAllAdmins() {
    global $pdo;
    return $pdo->query("SELECT * FROM admins ORDER BY created_at DESC")->fetchAll();
}

/**
 * Count total admins
 */
function countAdmins() {
    global $pdo;
    return $pdo->query("SELECT COUNT(*) FROM admins")->fetchColumn();
}

/**
 * Login admin (from admins table)
 */
function loginAdmin($email, $password) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM admins WHERE email = ? AND password = ? AND status = 'active'");
    $stmt->execute([$email, $password]);
    return $stmt->fetch();
}

/**
 * Update admin last login
 */
function updateAdminLastLogin($id) {
    global $pdo;
    $stmt = $pdo->prepare("UPDATE admins SET last_login = NOW() WHERE id = ?");
    return $stmt->execute([$id]);
}

/**
 * Update admin status
 */
function updateAdminStatus($id, $status) {
    global $pdo;
    $stmt = $pdo->prepare("UPDATE admins SET status = ? WHERE id = ?");
    return $stmt->execute([$status, $id]);
}

/**
 * Delete admin
 */
function deleteAdmin($id) {
    global $pdo;
    $stmt = $pdo->prepare("DELETE FROM admins WHERE id = ?");
    return $stmt->execute([$id]);
}

// ============================================
// CSRF TOKEN GENERATION
// ============================================

function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// ============================================
// GENERATE CSRF TOKEN
// ============================================

$csrf_token = generateCSRFToken();
?>