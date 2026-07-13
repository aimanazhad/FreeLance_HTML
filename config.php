<?php
// ============================================
// DATABASE CONFIGURATION
// ============================================

$host = 'localhost';
$dbname = 'freelancer_db';  // Nama database yang awak buat
$username = 'root';
$password = '';  // Default Laragon = kosong

// ============================================
// PDO CONNECTION WITH ERROR HANDLING
// ============================================

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    
    // Set PDO error mode to exception
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Set default fetch mode to associative array
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // Set charset to UTF-8
    $pdo->exec("SET NAMES utf8mb4");
    
} catch(PDOException $e) {
    // If connection fails, show error and stop execution
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
    $_SESSION = array();
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