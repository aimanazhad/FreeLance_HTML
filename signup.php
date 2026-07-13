<?php
require_once 'config.php';

// ============================================
// SIGNUP PROCESS
// ============================================
$error = '';
$success = '';

if (isset($_POST['signup'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);
    $role = $_POST['role'] ?? 'client';
    
    // Validation
    if (empty($name) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = "⚠️ Please fill in all fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "⚠️ Please enter a valid email address.";
    } elseif ($password !== $confirm_password) {
        $error = "⚠️ Passwords do not match!";
    } elseif (strlen($password) < 6) {
        $error = "⚠️ Password must be at least 6 characters.";
    } else {
        // Check if email already exists
        $check = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $check->execute([$email]);
        
        if ($check->rowCount() > 0) {
            $error = "⚠️ Email already registered! Please login.";
        } else {
            // Hash password
            $hashed_password = md5($password);
            
            // Insert into database
            $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
            if ($stmt->execute([$name, $email, $hashed_password, $role])) {
                // ✅ REDIRECT KE LOGIN PAGE
                header('Location: index.php?signup=success');
                exit();
            } else {
                $error = "❌ Failed to create account. Please try again.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Freelance Marketplace - Create Profile</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        .message {
            padding: 10px 14px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .message.error {
            background: #fef2f2;
            color: #dc2626;
            border: 1px solid #fecaca;
        }
        .message.success {
            background: #f0fdf4;
            color: #16a34a;
            border: 1px solid #bbf7d0;
        }
        .message i {
            font-size: 16px;
        }
    </style>
</head>
<body>

    <main class="signup-container">
        <section class="banner-panel">
            <div class="illustration-placeholder">
                <svg viewBox="0 0 400 400" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <circle cx="200" cy="200" r="120" fill="#6366f1" fill-opacity="0.15"/>
                    <rect x="80" y="180" width="100" height="130" rx="12" fill="#4338ca" opacity="0.8"/>
                    <rect x="220" y="180" width="100" height="130" rx="12" fill="#6d28d9" opacity="0.8"/>
                    <circle cx="130" cy="130" r="25" fill="#fbcfe8"/>
                    <circle cx="270" cy="130" r="25" fill="#fed7aa"/>
                    <circle cx="200" cy="90" r="15" fill="#fde047"/>
                    <path d="M110 230 H150 M250 230 H290" stroke="#fff" stroke-width="4" stroke-linecap="round"/>
                </svg>
            </div>
        </section>

        <section class="form-panel">
            <div class="brand-header">
                <div class="brand-logo">
                    <span class="logo-f">F</span>
                    <span class="logo-arrow">↗</span>
                </div>
                <div class="brand-text">
                    <span class="title-main">Freelance</span>
                    <span class="title-sub">Marketplace</span>
                </div>
            </div>

            <header class="form-title">
                <h1>CREATE YOUR PROFILE</h1>
                <p>Connect with a world of opportunity for UiTM student</p>
            </header>

            <?php if ($error): ?>
                <div class="message error">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <form id="profileForm" method="POST" action="">
                <input type="hidden" name="signup" value="1">
                
                <div class="input-group">
                    <label for="fullName">Full Name</label>
                    <input type="text" id="fullName" name="name" placeholder="Your full name" required>
                </div>

                <div class="input-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" placeholder="your.email@example.com" required>
                </div>

                <div class="input-group">
                    <label for="password">Password</label>
                    <div class="password-wrapper">
                        <i class="fa-solid fa-lock input-icon"></i>
                        <input type="password" id="password" name="password" placeholder="••••••••••••••••" required>
                        <button type="button" class="toggle-password">
                            <i class="fa-regular fa-eye-slash"></i> Show
                        </button>
                    </div>
                </div>

                <div class="input-group">
                    <label for="confirmPassword">Confirm Password</label>
                    <div class="password-wrapper">
                        <i class="fa-solid fa-lock input-icon"></i>
                        <input type="password" id="confirmPassword" name="confirm_password" placeholder="••••••••••••••••" required>
                        <button type="button" class="toggle-password">
                            <i class="fa-regular fa-eye-slash"></i> Show
                        </button>
                    </div>
                </div>

                <input type="hidden" id="signupRole" name="role" value="client">

                <button type="submit" class="btn-submit">Sign Up</button>
            </form>

            <div class="divider">
                <span>Or</span>
            </div>

            <button class="btn-google">
                <svg viewBox="0 0 24 24" width="16" height="16">
                    <path fill="#4285F4" d="M23.745 12.27c0-.7-.06-1.4-.19-2.07H12v3.92h6.61c-.29 1.53-1.14 2.82-2.4 3.68v3.05h3.88c2.27-2.09 3.66-5.17 3.66-8.58z"/>
                    <path fill="#34A853" d="M12 24c3.24 0 5.95-1.08 7.93-2.91l-3.88-3.05c-1.08.72-2.45 1.16-4.05 1.16-3.11 0-5.74-2.11-6.68-4.96H1.21v3.15C3.18 21.88 7.31 24 12 24z"/>
                    <path fill="#FBBC05" d="M5.32 14.24A7.16 7.16 0 0 1 5 12c0-.79.13-1.57.32-2.34V6.51H1.21A11.94 11.94 0 0 0 0 12c0 1.92.45 3.79 1.21 5.49l4.11-3.25z"/>
                    <path fill="#EA4335" d="M12 4.75c1.77 0 3.35.61 4.6 1.8l3.42-3.42C17.95 1.19 15.24 0 12 0 7.31 0 3.18 2.12 1.21 5.51l4.11 3.25c.94-2.85 3.57-4.96 6.68-4.96z"/>
                </svg>
                Sign Up with Google
            </button>

            <p class="login-redirect">
                Already have an account? <a href="index.php">Login</a>
            </p>
        </section>
    </main>

    <!-- ==========================================
    ROLE MODAL
    ========================================== -->
    <div id="roleModal" class="modal-overlay">
        <div class="modal-box">
            <button class="modal-close" onclick="closeRoleModal()">✕</button>
            
            <h2>SELECT YOUR ROLE</h2>
            
            <div class="role-options">
                <label class="role-option-card">
                    <input type="radio" name="userRole" value="freelancer">
                    <span class="custom-radio"></span>
                    <span class="role-label">Freelancer</span>
                </label>

                <label class="role-option-card">
                    <input type="radio" name="userRole" value="client">
                    <span class="custom-radio"></span>
                    <span class="role-label">Client</span>
                </label>
            </div>

            <div class="modal-footer">
                <button class="btn-done" onclick="submitRoleSelection()">DONE</button>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('profileForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirmPassword').value;
            
            if (password !== confirmPassword) {
                alert('Passwords do not match!');
                return;
            }
            
            if (password.length < 6) {
                alert('Password must be at least 6 characters long!');
                return;
            }
            
            openRoleModal();
        });

        function openRoleModal() {
            document.getElementById('roleModal').classList.add('active');
        }

        function closeRoleModal() {
            document.getElementById('roleModal').classList.remove('active');
        }

        function submitRoleSelection() {
            const selected = document.querySelector('input[name="userRole"]:checked');
            if (selected) {
                document.getElementById('signupRole').value = selected.value;
                closeRoleModal();
                document.getElementById('profileForm').submit();
            } else {
                alert('Please select a role.');
            }
        }

        document.querySelectorAll('.toggle-password').forEach(btn => {
            btn.addEventListener('click', function() {
                const input = this.closest('.password-wrapper').querySelector('input');
                const icon = this.querySelector('i');
                
                if (input.type === 'password') {
                    input.type = 'text';
                    icon.className = 'fa-regular fa-eye';
                    this.innerHTML = '<i class="fa-regular fa-eye"></i> Hide';
                } else {
                    input.type = 'password';
                    icon.className = 'fa-regular fa-eye-slash';
                    this.innerHTML = '<i class="fa-regular fa-eye-slash"></i> Show';
                }
            });
        });
    </script>

</body>
</html>