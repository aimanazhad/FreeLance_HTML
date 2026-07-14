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

    // Create required tables if they do not exist
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

    status ENUM('active','completed','cancelled') DEFAULT 'active',

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    INDEX (client_id)

    ) ENGINE=InnoDB
    DEFAULT CHARSET=utf8mb4
    COLLATE=utf8mb4_unicode_ci;
    ");

    $pdo->exec("CREATE TABLE IF NOT EXISTS applications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        job_id INT NOT NULL,
        freelancer_id INT NOT NULL,
        status VARCHAR(30) DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS messages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        sender_id INT NOT NULL,
        receiver_id INT NOT NULL,
        job_id INT DEFAULT NULL,
        message TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS payments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        job_id INT DEFAULT NULL,
        amount DECIMAL(10,2) DEFAULT 0,
        method VARCHAR(50) DEFAULT NULL,
        description TEXT DEFAULT NULL,
        status VARCHAR(20) DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS portfolio (
        id INT AUTO_INCREMENT PRIMARY KEY,
        freelancer_id INT NOT NULL,
        title VARCHAR(150) NOT NULL,
        description TEXT DEFAULT NULL,
        image_url VARCHAR(255) DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS reviews (
        id INT AUTO_INCREMENT PRIMARY KEY,
        reviewer_id INT NOT NULL,
        reviewee_id INT NOT NULL,
        job_id INT DEFAULT NULL,
        rating INT DEFAULT 0,
        comment TEXT DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

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
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
}

/**
 * Check if user is admin
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
 * Get recent users (last 5)
 */
function getRecentUsers($limit = 5) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM users ORDER BY created_at DESC LIMIT ?");
    $stmt->execute([$limit]);
    return $stmt->fetchAll();
}

/**
 * Create a new user
 */
function createUser($name, $email, $password, $role = 'client') {
    global $pdo;
    $hashedPassword = md5($password); // Demo sahaja. Guna password_hash() untuk production
    
    $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
    return $stmt->execute([$name, $email, $hashedPassword, $role]);
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
 * Update user status (active/suspended)
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
 * Login user
 */
function loginUser($email, $password) {
    global $pdo;
    $hashedPassword = md5($password); // Demo sahaja
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND password = ?");
    $stmt->execute([$email, $hashedPassword]);
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
// CSRF TOKEN GENERATION (for forms)
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

// ============================================
// DEBUGGING (Optional - Remove in production)
// ============================================

// Uncomment below to test database connection
// echo "✅ Database connected successfully!";
?>