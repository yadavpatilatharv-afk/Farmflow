<?php
// Start session
session_start();

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: overview.php");
    exit();
}

require_once __DIR__ . '/db_connect.php';

$errors = [];
$success_msg = "";

// Check query strings for status alerts
if (isset($_GET['registered']) && $_GET['registered'] == '1') {
    $success_msg = "Account created successfully! Please enter your credentials to log in.";
}
if (isset($_GET['logout']) && $_GET['logout'] == '1') {
    $success_msg = "You have been logged out safely.";
}

// Form processing
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login_input = trim($_POST['login_input'] ?? '');
    $password    = $_POST['password'] ?? '';
    $remember    = isset($_POST['remember']);

    if (empty($login_input)) {
        $errors[] = "Please enter your Email Address or Phone Number.";
    }

    if (empty($password)) {
        $errors[] = "Please enter your Password.";
    }

    if (empty($errors)) {
        try {
            // Query user by email OR phone number
            $stmt = $pdo->prepare("
                SELECT user_id, full_name, email, phone, password_hash, preferred_language, farm_location
                FROM users
                WHERE email = :email OR phone = :phone
                LIMIT 1
            ");
            $stmt->execute([
                ':email' => $login_input,
                ':phone' => $login_input
            ]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password_hash'])) {
                // Password is correct! Set session variables
                $_SESSION['user_id']            = $user['user_id'];
                $_SESSION['full_name']          = $user['full_name'];
                $_SESSION['email']              = $user['email'];
                $_SESSION['phone']              = $user['phone'];
                $_SESSION['preferred_language'] = $user['preferred_language'];
                $_SESSION['farm_location']      = $user['farm_location'];

                // Handle optional remember me cookie (7 days)
                if ($remember) {
                    setcookie("farmflow_user", $user['email'], time() + (86400 * 7), "/");
                }

                // Redirect to the farmer's personal dashboard
                header("Location: overview.php");
                exit();
            } else {
                $errors[] = "Invalid Email/Phone Number or Password. Please check your credentials.";
            }
        } catch (PDOException $e) {
            $errors[] = "Database connection error during login: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log In - FarmFlow Smart Farming Platform</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        emerald: {
                            700: '#15803d',
                            800: '#166534',
                            900: '#14532d',
                        }
                    }
                }
            }
        }
    </script>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
</head>
<body class="bg-slate-50 min-h-screen flex flex-col justify-between text-slate-800 antialiased selection:bg-emerald-500 selection:text-white">

    <!-- Top Navigation Header -->
    <header class="bg-white/80 backdrop-blur-md border-b border-slate-200 sticky top-0 z-50 py-3 px-4 sm:px-8">
        <div class="max-w-7xl mx-auto flex items-center justify-between">
            <a href="index.php" class="flex items-center gap-3 group">
                <div class="w-10 h-10 rounded-xl bg-emerald-700 flex items-center justify-center text-white shadow-md shadow-emerald-700/20 group-hover:bg-emerald-800 transition-colors">
                    <!-- Sprout / Farm Icon -->
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                    </svg>
                </div>
                <div>
                    <span class="font-bold text-xl tracking-tight text-slate-900">Farm<span class="text-emerald-700">Flow</span></span>
                    <span class="block text-[10px] text-slate-500 font-semibold tracking-wider uppercase">Smart Farm Management</span>
                </div>
            </a>

            <!-- Top Action Buttons & Language Selector -->
            <div class="flex items-center gap-3">
                <a href="register.php" class="bg-emerald-700 hover:bg-emerald-800 text-white text-xs sm:text-sm font-bold py-2 px-4 rounded-xl transition-colors shadow-sm flex items-center gap-1.5">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path></svg>
                    <span>Register Account</span>
                </a>

                <div class="flex items-center gap-2 bg-slate-100 hover:bg-slate-200 border border-slate-200 rounded-lg px-3 py-1.5 transition-colors">
                    <svg class="w-4 h-4 text-emerald-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129"></path>
                    </svg>
                    <select id="langSelect" onchange="changeLanguage(this.value)" class="bg-transparent text-sm font-medium text-slate-700 focus:outline-none cursor-pointer">
                        <option value="en">English</option>
                        <option value="hi">हिंदी (Hindi)</option>
                        <option value="mr">मराठी (Marathi)</option>
                    </select>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Login Section -->
    <main class="flex-grow flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full">

            <!-- Card Header -->
            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-emerald-100 text-emerald-700 mb-3 shadow-inner">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path>
                    </svg>
                </div>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight" id="txtTitle">Welcome Back</h1>
                <p class="text-slate-500 text-sm mt-1" id="txtSubtitle">Log in to manage your crops, schedule & finances</p>
            </div>

            <!-- Form Card -->
            <div class="bg-white rounded-2xl shadow-xl shadow-slate-200/60 border border-slate-100 p-6 sm:p-8">

                <!-- Success Alert Banner -->
                <?php if (!empty($success_msg)): ?>
                    <div class="mb-6 bg-emerald-50 border-l-4 border-emerald-600 p-4 rounded-r-xl">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 text-emerald-600 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            <p class="text-sm font-semibold text-emerald-800"><?php echo htmlspecialchars($success_msg); ?></p>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Error Messages Banner -->
                <?php if (!empty($errors)): ?>
                    <div class="mb-6 bg-red-50 border-l-4 border-red-500 p-4 rounded-r-xl">
                        <div class="flex items-start">
                            <svg class="w-5 h-5 text-red-500 mr-2 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <div>
                                <h3 class="text-sm font-semibold text-red-800">Login Error</h3>
                                <ul class="mt-1 text-xs text-red-700 list-disc list-inside space-y-1">
                                    <?php foreach ($errors as $error): ?>
                                        <li><?php echo htmlspecialchars($error); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <form action="login.php" method="POST" class="space-y-5" id="loginForm">

                    <!-- Email Address or Phone Number -->
                    <div>
                        <label for="login_input" class="block text-sm font-semibold text-slate-700 mb-1.5" id="lblIdentifier">Email Address or Phone Number <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"></path>
                                </svg>
                            </div>
                            <input type="text" id="login_input" name="login_input" required placeholder="email@farm.com or 9876543210"
                                value="<?php echo htmlspecialchars($_POST['login_input'] ?? $_COOKIE['farmflow_user'] ?? ''); ?>"
                                class="w-full pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 focus:bg-white text-slate-900 placeholder-slate-400 text-sm transition-all">
                        </div>
                    </div>

                    <!-- Password -->
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <label for="password" class="block text-sm font-semibold text-slate-700" id="lblPassword">Password <span class="text-red-500">*</span></label>
                            <a href="#" onclick="alert('Password reset feature: Please contact your system administrator or farm manager.')" class="text-xs font-semibold text-emerald-700 hover:underline" id="lnkForgot">Forgot Password?</a>
                        </div>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                </svg>
                            </div>
                            <input type="password" id="password" name="password" required placeholder="••••••••"
                                class="w-full pl-10 pr-10 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 focus:bg-white text-slate-900 placeholder-slate-400 text-sm transition-all">
                            <button type="button" onclick="togglePassword('password', 'eyeIcon')" class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600 focus:outline-none">
                                <svg id="eyeIcon" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Remember Me Checkbox -->
                    <div class="flex items-center justify-between pt-1">
                        <label class="flex items-center cursor-pointer">
                            <input type="checkbox" name="remember" class="w-4 h-4 text-emerald-700 bg-slate-50 border-slate-300 rounded focus:ring-emerald-500 cursor-pointer">
                            <span class="ml-2 text-xs sm:text-sm text-slate-600 font-medium" id="lblRemember">Remember Me</span>
                        </label>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" class="w-full py-3 px-4 bg-emerald-700 hover:bg-emerald-800 text-white font-bold text-base rounded-xl shadow-lg shadow-emerald-700/25 transition-all transform active:scale-[0.99] flex items-center justify-center gap-2 cursor-pointer">
                        <span>Log In</span>
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                        </svg>
                    </button>
                </form>

                <!-- Switch Link -->
                <div class="mt-6 pt-5 border-t border-slate-100 text-center">
                    <p class="text-slate-600 text-sm">
                        Don't have an account? 
                        <a href="register.php" class="font-bold text-emerald-700 hover:text-emerald-800 hover:underline inline-flex items-center gap-1">
                            <span>Create One</span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
                            </svg>
                        </a>
                    </p>
                </div>

            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="py-4 text-center text-xs text-slate-500">
        &copy; <?php echo date('Y'); ?> FarmFlow Smart Farming Platform. All rights reserved.
    </footer>

    <!-- Interactive Scripts -->
    <script>
        function togglePassword(inputId, iconId) {
            const input = document.getElementById(inputId);
            const icon = document.getElementById(iconId);
            if (input.type === 'password') {
                input.type = 'text';
                icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858-5.908a10.04 10.04 0 013.98-.863c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m-4.692-4.692a3 3 0 00-4.243-4.243"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3l18 18"></path>';
            } else {
                input.type = 'password';
                icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>';
            }
        }

        const translations = {
            en: {
                title: "Welcome Back",
                subtitle: "Log in to manage your crops, schedule & finances",
                identifier: "Email Address or Phone Number *",
                password: "Password *",
                remember: "Remember Me"
            },
            hi: {
                title: "वापसी पर स्वागत है",
                subtitle: "अपनी फसलों, सिंचाई और वित्त का प्रबंधन करने के लिए लॉग इन करें",
                identifier: "ईमेल पता या फोन नंबर *",
                password: "पासवर्ड *",
                remember: "मुझे याद रखें"
            },
            mr: {
                title: "पुन्हा स्वागत आहे",
                subtitle: "तुमच्या पिकांचे, सिंचनाचे आणि आर्थिक व्यवस्थापन करण्यासाठी लॉग इन करा",
                identifier: "ईमेल पत्ता किंवा फोन नंबर *",
                password: "पासवर्ड *",
                remember: "माझी नोंद ठेवा"
            }
        };

        function changeLanguage(lang) {
            if (translations[lang]) {
                document.getElementById('txtTitle').textContent = translations[lang].title;
                document.getElementById('txtSubtitle').textContent = translations[lang].subtitle;
                document.getElementById('lblIdentifier').childNodes[0].nodeValue = translations[lang].identifier.replace(' *', '') + ' ';
                document.getElementById('lblPassword').childNodes[0].nodeValue = translations[lang].password.replace(' *', '') + ' ';
                document.getElementById('lblRemember').textContent = translations[lang].remember;
            }
        }
    </script>
</body>
</html>
