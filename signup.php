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
            // Save password as plain text
            $plain_password = $password;
            
            // Insert into database
            $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
            if ($stmt->execute([$name, $email, $plain_password, $role])) {
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
        :root {
            font-family: 'Inter', sans-serif;
        }

        * {
            box-sizing: border-box;
        }

        html, body {
            margin: 0;
            padding: 0;
            min-height: 100%;
            background: #050816;
            color: #f8fafc;
        }

        body {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }

        .signup-container {
            display: grid;
            grid-template-columns: 1fr 420px;
            gap: 28px;
            width: min(1180px, 100%);
            max-width: 1180px;
        }

        .banner-panel,
        .form-panel {
            border-radius: 32px;
            overflow: hidden;
            background: #081025;
            border: 1px solid rgba(148, 163, 184, 0.16);
            box-shadow: 0 28px 90px rgba(15, 23, 42, 0.35);
        }

        .banner-panel {
            padding: 40px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            background: linear-gradient(180deg, #0b132a 0%, #101a38 100%);
        }

        .banner-panel .illustration-placeholder {
            width: 100%;
            max-width: 420px;
            aspect-ratio: 1 / 1;
            border-radius: 28px;
            background: linear-gradient(180deg, rgba(79, 70, 229, 0.1), rgba(15, 23, 42, 0.4));
            display: grid;
            place-items: center;
            box-shadow: inset 0 0 0 1px rgba(148, 163, 184, 0.08);
        }

        .banner-panel .illustration-placeholder svg {
            width: 90%;
            height: auto;
        }

        .banner-panel .brand-text {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .banner-panel .brand-title {
            color: #38bdf8;
            font-size: 13px;
            font-weight: 700;
            letter-spacing: 0.2em;
            text-transform: uppercase;
        }

        .banner-panel h2 {
            margin: 0;
            font-size: clamp(2rem, 2.5vw, 3.4rem);
            line-height: 1.05;
            letter-spacing: -0.05em;
            color: #ffffff;
        }

        .banner-panel p {
            margin: 24px 0 0;
            color: #cbd5e1;
            font-size: 15px;
            line-height: 1.8;
            max-width: 420px;
        }

        .banner-features {
            display: grid;
            gap: 12px;
            margin-top: 28px;
        }

        .banner-feature {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            color: #cbd5e1;
            font-size: 14px;
        }

        .feature-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: #38bdf8;
            margin-top: 6px;
            flex-shrink: 0;
        }

        .banner-actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            margin-top: 28px;
        }

        .banner-chip {
            padding: 10px 14px;
            border-radius: 999px;
            border: 1px solid rgba(148, 163, 184, 0.18);
            background: rgba(15, 23, 42, 0.72);
            color: #cbd5e1;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        .form-panel {
            padding: 38px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            background: #071028;
        }

        .brand-header {
            display: flex;
            align-items: center;
            gap: 14px;
            margin-bottom: 26px;
        }

        .brand-logo {
            width: 48px;
            height: 48px;
            display: grid;
            place-items: center;
            border-radius: 16px;
            background: linear-gradient(135deg, #6366f1, #22d3ee);
            color: white;
            font-size: 20px;
            font-weight: 700;
            box-shadow: 0 18px 30px rgba(99, 102, 241, 0.24);
        }

        .brand-text .title-main {
            font-size: 18px;
            font-weight: 700;
            color: #38bdf8;
            letter-spacing: 0.14em;
            text-transform: uppercase;
        }

        .brand-text .title-sub {
            font-size: 13px;
            color: #94a3b8;
        }

        .form-title h1 {
            margin: 0;
            font-size: 2.3rem;
            line-height: 1.05;
            color: #f8fafc;
        }

        .form-title p {
            margin: 14px 0 0;
            color: #94a3b8;
            font-size: 14px;
            line-height: 1.8;
            max-width: 380px;
        }

        .message {
            padding: 14px 18px;
            border-radius: 18px;
            font-size: 14px;
            margin-bottom: 22px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .message.error {
            background: #38181b;
            color: #fecaca;
            border: 1px solid #fca5a5;
        }

        .message.success {
            background: #0f172a;
            color: #7dd3fc;
            border: 1px solid #38bdf8;
        }

        .message i {
            font-size: 16px;
        }

        form {
            display: grid;
            gap: 18px;
        }

        .input-group {
            display: grid;
            gap: 10px;
        }

        .input-group label {
            font-size: 14px;
            font-weight: 700;
            color: #cbd5e1;
        }

        .input-group input {
            width: 100%;
            border: 1px solid #1f2937;
            border-radius: 16px;
            padding: 15px 16px;
            font-size: 15px;
            background: #0c1427;
            color: #e2e8f0;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        .input-group input::placeholder {
            color: #64748b;
        }

        .input-group input:focus {
            outline: none;
            border-color: #6366f1;
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.12);
        }

        .password-wrapper {
            position: relative;
            display: grid;
        }

        .password-wrapper .input-icon {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #64748b;
            pointer-events: none;
        }

        .password-wrapper input {
            padding-left: 48px;
        }

        .toggle-password {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            border: none;
            background: rgba(255, 255, 255, 0.06);
            color: #cbd5e1;
            font-weight: 700;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 8px 10px;
            border-radius: 999px;
            transition: background 0.2s ease, color 0.2s ease;
        }

        .toggle-password:hover {
            background: rgba(99, 102, 241, 0.24);
            color: #f8fafc;
        }

        .btn-submit,
        .btn-google,
        .btn-done {
            width: 100%;
            border: none;
            border-radius: 16px;
            padding: 16px 18px;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            transition: transform 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
        }

        .btn-submit {
            background: linear-gradient(90deg, #6366f1, #22d3ee);
            color: #ffffff;
            box-shadow: 0 20px 40px rgba(34, 211, 238, 0.28);
        }

        .btn-submit:hover {
            transform: translateY(-1px);
            background: linear-gradient(90deg, #4f46e5, #0ea5e9);
        }

        .divider {
            display: flex;
            align-items: center;
            gap: 12px;
            margin: 12px 0 16px;
            color: #94a3b8;
            font-size: 14px;
        }

        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: rgba(148, 163, 184, 0.2);
        }

        .btn-google {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            background: rgba(255, 255, 255, 0.04);
            color: #e2e8f0;
            border: 1px solid rgba(148, 163, 184, 0.18);
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.08);
        }

        .btn-google svg {
            width: 18px;
            height: 18px;
        }

        .btn-google:hover {
            background: rgba(255, 255, 255, 0.08);
        }

        .login-redirect {
            margin-top: 18px;
            color: #94a3b8;
            font-size: 14px;
            text-align: center;
        }

        .login-redirect a {
            color: #38bdf8;
            text-decoration: none;
            font-weight: 700;
        }

        .login-redirect a:hover {
            text-decoration: underline;
        }

        .modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.72);
            display: none;
            align-items: center;
            justify-content: center;
            padding: 24px;
            z-index: 20;
        }

        .modal-overlay.active {
            display: flex;
        }

        .modal-box {
            width: min(520px, 100%);
            background: #0c1729;
            border-radius: 28px;
            padding: 32px;
            box-shadow: 0 38px 80px rgba(15, 23, 42, 0.5);
            position: relative;
            border: 1px solid rgba(148, 163, 184, 0.16);
        }

        .modal-close {
            position: absolute;
            right: 20px;
            top: 20px;
            border: none;
            background: transparent;
            font-size: 22px;
            cursor: pointer;
            color: #94a3b8;
        }

        .modal-box h2 {
            margin: 0 0 18px;
            font-size: 22px;
            font-weight: 800;
            color: #f8fafc;
        }

        .role-options {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 16px;
            margin: 24px 0;
        }

        .role-option-card {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 18px 16px;
            border: 1px solid rgba(148, 163, 184, 0.16);
            border-radius: 18px;
            cursor: pointer;
            transition: border-color 0.2s ease, background 0.2s ease;
            background: rgba(255, 255, 255, 0.04);
        }

        .role-option-card:hover {
            border-color: rgba(56, 189, 248, 0.6);
            background: rgba(56, 189, 248, 0.08);
        }

        .role-option-card input {
            appearance: none;
            width: 20px;
            height: 20px;
            border: 1px solid rgba(148, 163, 184, 0.4);
            border-radius: 50%;
            cursor: pointer;
            display: grid;
            place-items: center;
            background: transparent;
        }

        .role-option-card input:checked {
            border-color: #38bdf8;
            background: #38bdf8;
        }

        .role-option-card .custom-radio {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            display: block;
        }

        .role-label {
            font-size: 15px;
            font-weight: 700;
            color: #f8fafc;
        }

        .modal-footer {
            display: flex;
            justify-content: flex-end;
        }

        .btn-done {
            width: auto;
            padding: 14px 24px;
            background: linear-gradient(90deg, #6366f1, #22d3ee);
            color: white;
        }

        @media (max-width: 960px) {
            .signup-container {
                grid-template-columns: 1fr;
            }

            .banner-panel {
                min-height: 360px;
                padding: 32px;
            }

            .form-panel {
                padding: 32px;
            }
        }

        @media (max-width: 620px) {
            body {
                padding: 16px;
            }

            .signup-container {
                gap: 20px;
            }

            .banner-panel,
            .form-panel {
                border-radius: 24px;
            }

            .btn-submit,
            .btn-google,
            .btn-done {
                padding: 14px 16px;
            }

            .role-options {
                grid-template-columns: 1fr;
            }
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