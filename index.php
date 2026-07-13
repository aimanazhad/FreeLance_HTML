<?php
require_once 'config.php';

// ============================================
// LOGIN PROCESS
// ============================================
$login_error = '';

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
            header('Location: Admin/dashboard_admin.php');
        } elseif ($user['role'] === 'client') {
            header('Location: Client/dashboard_client.php');
        } elseif ($user['role'] === 'freelancer') {
            header('Location: dashboard_freelancer.php');
        }
        exit();
    } else {
        $login_error = "❌ Invalid email or password!";
    }
}

// ============================================
// IF LOGGED IN, REDIRECT
// ============================================
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    if ($_SESSION['role'] === 'admin') {
        header('Location: Admin/dashboard_admin.php');
    } elseif ($_SESSION['role'] === 'client') {
        header('Location: Client/dashboard_client.php');
    } elseif ($_SESSION['role'] === 'freelancer') {
        header('Location: dashboard_freelancer.php');
    }
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Freelance Market - Log In</title>
    <!-- Tailwind CSS CDN for styling -->
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            background-color: #0b0f19;
        }
        .error-message {
            background-color: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #f87171;
            padding: 10px 14px;
            border-radius: 8px;
            font-size: 13px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
    </style>
</head>
<body class="text-gray-300 min-h-screen flex items-center justify-center p-4 md:p-8">

    <div class="w-full max-w-6xl grid grid-cols-1 md:grid-cols-2 gap-8 md:gap-16 items-center">
        
        <!-- Left Column: Branding & Info -->
        <div class="space-y-6">
            <!-- App Icon and Name -->
            <div class="flex items-center space-x-3">
                <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-blue-500 to-purple-500 flex items-center justify-center text-white font-bold text-lg shadow-lg">
                    FM
                </div>
                <div>
                    <h2 class="text-white font-semibold text-lg leading-tight">Freelance Market</h2>
                    <p class="text-xs text-gray-400">Smart gigs &bull; Trusted clients</p>
                </div>
            </div>

            <!-- Main Heading -->
            <div class="space-y-2 pt-4">
                <span class="text-xs font-bold tracking-widest text-cyan-400 uppercase">Freelance Marketplace</span>
                <h1 class="text-4xl md:text-5xl font-extrabold text-white tracking-tight leading-tight">
                    Welcome back,<br>freelancer
                </h1>
            </div>

            <p class="text-gray-400 text-sm md:text-base leading-relaxed max-w-md">
                Log in to explore gigs, manage projects, and connect with clients in one place.
            </p>

            <!-- Bullet Points -->
            <ul class="space-y-2.5 text-sm md:text-base text-gray-300">
                <li class="flex items-center space-x-2">
                    <span class="text-cyan-400">&bull;</span>
                    <span>Discover trusted freelance opportunities</span>
                </li>
                <li class="flex items-center space-x-2">
                    <span class="text-cyan-400">&bull;</span>
                    <span>Track your orders and proposals</span>
                </li>
                <li class="flex items-center space-x-2">
                    <span class="text-cyan-400">&bull;</span>
                    <span>Work from any device, anywhere</span>
                </li>
            </ul>

            <!-- Badges -->
            <div class="flex flex-wrap gap-2 pt-2">
                <span class="px-3 py-1.5 text-xs font-medium rounded-full bg-gray-800 border border-gray-700 text-gray-300">Top Talent</span>
                <span class="px-3 py-1.5 text-xs font-medium rounded-full bg-gray-800 border border-gray-700 text-gray-300">Secure Login</span>
                <span class="px-3 py-1.5 text-xs font-medium rounded-full bg-gray-800 border border-gray-700 text-gray-300">Quick Access</span>
            </div>
        </div>

        <!-- Right Column: Login Form -->
        <div class="w-full max-w-md mx-auto space-y-6">
            <!-- Header section of form -->
            <div>
                <div class="flex items-center space-x-2 text-xs font-bold tracking-wider text-cyan-400 uppercase mb-1">
                    <span class="text-gray-400 text-xs">🌐 Freelance Marketplace logo</span>
                </div>
                <span class="text-xs font-bold tracking-widest text-cyan-500 uppercase block mb-1">LOGIN</span>
                <h2 class="text-2xl md:text-3xl font-bold text-white">Log in to your account</h2>
                <p class="text-xs text-gray-400 mt-1">
                    Use your email and password to enter the Freelance Marketplace.
                </p>
            </div>

            <!-- Error Message -->
            <?php if ($login_error): ?>
                <div class="error-message">
                    <i class="fas fa-circle-exclamation"></i>
                    <?php echo $login_error; ?>
                </div>
            <?php endif; ?>

            <!-- Form -->
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST" class="space-y-4">
                
                <!-- Email Input -->
                <div class="space-y-1.5">
                    <label for="email" class="block text-sm font-medium text-white">Email</label>
                    <input type="email" id="email" name="email" placeholder="you@example.com" required
                        class="w-full px-3 py-2.5 rounded-lg bg-gray-900 border border-gray-800 text-white placeholder-gray-600 focus:outline-none focus:border-cyan-500 text-sm transition-colors">
                </div>

                <!-- Password Input -->
                <div class="space-y-1.5">
                    <label for="password" class="block text-sm font-medium text-white">Password</label>
                    <input type="password" id="password" name="password" placeholder="Enter your password" required
                        class="w-full px-3 py-2.5 rounded-lg bg-gray-900 border border-gray-800 text-white placeholder-gray-600 focus:outline-none focus:border-cyan-500 text-sm transition-colors">
                </div>

                <!-- Remember Me & Forgot Password -->
                <div class="flex items-center justify-between text-xs pt-1">
                    <label class="flex items-center space-x-2 cursor-pointer select-none">
                        <input type="checkbox" name="remember" class="rounded bg-gray-900 border-gray-800 text-cyan-500 focus:ring-0 focus:ring-offset-0 w-3.5 h-3.5">
                        <span class="text-gray-300 font-medium">Remember me</span>
                    </label>
                    <a href="#" class="text-cyan-400 hover:underline">Forgot password?</a>
                </div>

                <!-- Submit Button -->
                <button type="submit" name="login" 
                    class="w-full py-3 px-4 rounded-xl font-semibold text-white bg-gradient-to-r from-indigo-500 via-purple-500 to-cyan-500 hover:opacity-90 transition-opacity shadow-lg shadow-indigo-500/20 text-sm mt-2">
                    Log In
                </button>
            </form>

            <!-- Divider -->
            <div class="relative flex py-2 items-center">
                <div class="flex-grow border-t border-gray-800/60"></div>
                <span class="flex-shrink mx-4 text-xs text-gray-500">or continue with</span>
                <div class="flex-grow border-t border-gray-800/60"></div>
            </div>

            <!-- OAuth Buttons -->
            <div class="grid grid-cols-2 gap-3">
                <button onclick="alert('Google login coming soon!')" class="flex items-center justify-center py-2.5 border border-gray-800 bg-gray-900/50 hover:bg-gray-900 rounded-lg text-sm font-medium transition-colors text-white">
                    Google
                </button>
                <button onclick="alert('GitHub login coming soon!')" class="flex items-center justify-center py-2.5 border border-gray-800 bg-gray-900/50 hover:bg-gray-900 rounded-lg text-sm font-medium transition-colors text-white">
                    GitHub
                </button>
            </div>

            <!-- Bottom Links - Navigate to signup.php & Admin -->
            <div class="text-center space-y-2 pt-2 text-xs">
                <p class="text-gray-400">
                    Don't have an account? <a href="signup.php" class="text-cyan-400 hover:underline font-medium">Create one</a>
                </p>
                <p class="text-gray-500">
                    Are you an administrator? <a href="Admin/adminlogin.php" class="text-cyan-400 hover:underline font-medium">Access Admin Portal</a>
                </p>
            </div>

        </div>
    </div>

    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

</body>
</html>