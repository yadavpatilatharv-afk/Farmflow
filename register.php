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

// Form processing
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and capture inputs
    $full_name          = trim($_POST['full_name'] ?? '');
    $email              = trim($_POST['email'] ?? '');
    $phone              = trim($_POST['phone'] ?? '');
    $farm_location      = trim($_POST['farm_location'] ?? '');
    $preferred_language = trim($_POST['preferred_language'] ?? 'en');
    $password           = $_POST['password'] ?? '';
    $confirm_password   = $_POST['confirm_password'] ?? '';
    $terms              = isset($_POST['terms']);

    // Server-Side Validations
    if (empty($full_name)) {
        $errors[] = "Full Name is required.";
    }

    if (empty($email)) {
        $errors[] = "Email Address is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email address.";
    }

    if (empty($phone)) {
        $errors[] = "Phone Number is required.";
    }

    if (empty($farm_location)) {
        $errors[] = "Farm Location is required.";
    }

    if (empty($password)) {
        $errors[] = "Password is required.";
    } elseif (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters long.";
    }

    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match.";
    }

    if (!$terms) {
        $errors[] = "You must agree to the Terms & Conditions.";
    }

    // If no validation errors, proceed to check duplicate email and insert
    if (empty($errors)) {
        try {
            // Check if email already exists
            $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = :email LIMIT 1");
            $stmt->execute([':email' => $email]);
            if ($stmt->fetch()) {
                $errors[] = "An account with this email address already exists. Please log in.";
            } else {
                // Hash password with bcrypt
                $password_hash = password_hash($password, PASSWORD_BCRYPT);

                // Insert into DB
                $insertStmt = $pdo->prepare("
                    INSERT INTO users (full_name, email, phone, password_hash, preferred_language, farm_location)
                    VALUES (:full_name, :email, :phone, :password_hash, :preferred_language, :farm_location)
                ");

                $inserted = $insertStmt->execute([
                    ':full_name'          => htmlspecialchars($full_name),
                    ':email'              => $email,
                    ':phone'              => htmlspecialchars($phone),
                    ':password_hash'       => $password_hash,
                    ':preferred_language' => $preferred_language,
                    ':farm_location'      => htmlspecialchars($farm_location)
                ]);

                if ($inserted) {
                    header("Location: login.php?registered=1");
                    exit();
                } else {
                    $errors[] = "Registration failed. Please try again.";
                }
            }
        } catch (PDOException $e) {
            $errors[] = "System Error: Unable to complete registration. " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account - FarmFlow Smart Farming Platform</title>
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
                <a href="login.php" class="bg-emerald-700 hover:bg-emerald-800 text-white text-xs sm:text-sm font-bold py-2 px-4 rounded-xl transition-colors shadow-sm flex items-center gap-1.5">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14"></path></svg>
                    <span>Log In</span>
                </a>

                <div class="flex items-center gap-2 bg-slate-100 hover:bg-slate-200 border border-slate-200 rounded-lg px-3 py-1.5 transition-colors">
                    <svg class="w-4 h-4 text-emerald-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129"></path>
                    </svg>
                    <select id="langSelect" onchange="changeLanguage(this.value)" class="bg-transparent text-sm font-medium text-slate-700 focus:outline-none cursor-pointer">
                        <option value="en" <?php echo (($_POST['preferred_language'] ?? '') === 'en') ? 'selected' : ''; ?>>English</option>
                        <option value="hi" <?php echo (($_POST['preferred_language'] ?? '') === 'hi') ? 'selected' : ''; ?>>हिंदी (Hindi)</option>
                        <option value="mr" <?php echo (($_POST['preferred_language'] ?? '') === 'mr') ? 'selected' : ''; ?>>मराठी (Marathi)</option>
                    </select>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Registration Section -->
    <main class="flex-grow flex items-center justify-center py-10 px-4 sm:px-6 lg:px-8">
        <div class="max-w-xl w-full">

            <!-- Card Header -->
            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-emerald-100 text-emerald-700 mb-3 shadow-inner">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
                    </svg>
                </div>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight" id="txtTitle">Farmer Registration</h1>
                <p class="text-slate-500 text-sm mt-1" id="txtSubtitle">Join thousands of farmers optimizing crop yield & market returns</p>
            </div>

            <!-- Form Card -->
            <div class="bg-white rounded-2xl shadow-xl shadow-slate-200/60 border border-slate-100 p-6 sm:p-10">

                <!-- Error Messages Banner -->
                <?php if (!empty($errors)): ?>
                    <div class="mb-6 bg-red-50 border-l-4 border-red-500 p-4 rounded-r-xl">
                        <div class="flex items-start">
                            <svg class="w-5 h-5 text-red-500 mr-2 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <div>
                                <h3 class="text-sm font-semibold text-red-800">Please fix the following errors:</h3>
                                <ul class="mt-1 text-xs text-red-700 list-disc list-inside space-y-1">
                                    <?php foreach ($errors as $error): ?>
                                        <li><?php echo htmlspecialchars($error); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <form action="register.php" method="POST" class="space-y-5" id="registerForm">

                    <!-- Full Name -->
                    <div>
                        <label for="full_name" class="block text-sm font-semibold text-slate-700 mb-1.5" id="lblFullName">Full Name <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                </svg>
                            </div>
                            <input type="text" id="full_name" name="full_name" required placeholder="e.g. Ramesh Patil"
                                value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>"
                                class="w-full pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 focus:bg-white text-slate-900 placeholder-slate-400 text-sm transition-all">
                        </div>
                    </div>

                    <!-- Email & Phone Grid -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <!-- Email -->
                        <div>
                            <label for="email" class="block text-sm font-semibold text-slate-700 mb-1.5" id="lblEmail">Email Address <span class="text-red-500">*</span></label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                    </svg>
                                </div>
                                <input type="email" id="email" name="email" required placeholder="name@farm.com"
                                    value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                                    class="w-full pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 focus:bg-white text-slate-900 placeholder-slate-400 text-sm transition-all">
                            </div>
                        </div>

                        <!-- Phone -->
                        <div>
                            <label for="phone" class="block text-sm font-semibold text-slate-700 mb-1.5" id="lblPhone">Phone Number <span class="text-red-500">*</span></label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                                    </svg>
                                </div>
                                <input type="tel" id="phone" name="phone" required placeholder="9876543210"
                                    value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>"
                                    class="w-full pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 focus:bg-white text-slate-900 placeholder-slate-400 text-sm transition-all">
                            </div>
                        </div>
                    </div>

                    <!-- Farm Location & Preferred Language Grid -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <!-- Farm Location -->
                        <div>
                            <label for="farm_location" class="block text-sm font-semibold text-slate-700 mb-1.5" id="lblLocation">Farm Location <span class="text-red-500">*</span></label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    </svg>
                                </div>
                                <input type="text" id="farm_location" name="farm_location" required placeholder="e.g. Kolhapur, Maharashtra"
                                    value="<?php echo htmlspecialchars($_POST['farm_location'] ?? ''); ?>"
                                    class="w-full pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 focus:bg-white text-slate-900 placeholder-slate-400 text-sm transition-all">
                            </div>
                        </div>

                        <!-- Preferred Language -->
                        <div>
                            <label for="preferred_language" class="block text-sm font-semibold text-slate-700 mb-1.5" id="lblPrefLang">Preferred Language</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129"></path>
                                    </svg>
                                </div>
                                <select id="preferred_language" name="preferred_language"
                                    class="w-full pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 focus:bg-white text-slate-900 text-sm transition-all cursor-pointer">
                                    <option value="en" <?php echo (($_POST['preferred_language'] ?? '') === 'en') ? 'selected' : ''; ?>>English</option>
                                    <option value="hi" <?php echo (($_POST['preferred_language'] ?? '') === 'hi') ? 'selected' : ''; ?>>हिंदी (Hindi)</option>
                                    <option value="mr" <?php echo (($_POST['preferred_language'] ?? '') === 'mr') ? 'selected' : ''; ?>>मराठी (Marathi)</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Password & Confirm Password Grid -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <!-- Password -->
                        <div>
                            <label for="password" class="block text-sm font-semibold text-slate-700 mb-1.5" id="lblPassword">Password <span class="text-red-500">*</span></label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                    </svg>
                                </div>
                                <input type="password" id="password" name="password" required placeholder="••••••••"
                                    class="w-full pl-10 pr-10 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 focus:bg-white text-slate-900 placeholder-slate-400 text-sm transition-all">
                                <button type="button" onclick="togglePassword('password', 'eyeIcon1')" class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600 focus:outline-none">
                                    <svg id="eyeIcon1" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <!-- Confirm Password -->
                        <div>
                            <label for="confirm_password" class="block text-sm font-semibold text-slate-700 mb-1.5" id="lblConfirmPassword">Confirm Password <span class="text-red-500">*</span></label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                                    </svg>
                                </div>
                                <input type="password" id="confirm_password" name="confirm_password" required placeholder="••••••••"
                                    class="w-full pl-10 pr-10 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 focus:bg-white text-slate-900 placeholder-slate-400 text-sm transition-all">
                                <button type="button" onclick="togglePassword('confirm_password', 'eyeIcon2')" class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600 focus:outline-none">
                                    <svg id="eyeIcon2" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Terms & Conditions Checkbox -->
                    <div class="flex items-start pt-1">
                        <div class="flex items-center h-5">
                            <input id="terms" name="terms" type="checkbox" required class="w-4 h-4 text-emerald-700 bg-slate-50 border-slate-300 rounded focus:ring-emerald-500 cursor-pointer">
                        </div>
                        <div class="ml-3 text-xs sm:text-sm text-slate-600">
                            <label for="terms" class="cursor-pointer">I agree to the <a href="#" class="font-semibold text-emerald-700 hover:underline">Terms of Service</a> and <a href="#" class="font-semibold text-emerald-700 hover:underline">Privacy Policy</a>.</label>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" class="w-full py-3 px-4 bg-emerald-700 hover:bg-emerald-800 text-white font-bold text-base rounded-xl shadow-lg shadow-emerald-700/25 transition-all transform active:scale-[0.99] flex items-center justify-center gap-2 cursor-pointer">
                        <span>Create Account</span>
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                        </svg>
                    </button>
                </form>

                <!-- Footer Switch Link -->
                <div class="mt-6 pt-5 border-t border-slate-100 text-center">
                    <p class="text-slate-600 text-sm">
                        Already have a FarmFlow account? 
                        <a href="login.php" class="font-bold text-emerald-700 hover:text-emerald-800 hover:underline inline-flex items-center gap-1">
                            <span>Log In</span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14"></path>
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

        // Multilingual Text Map
        const translations = {
            en: {
                title: "Farmer Registration",
                subtitle: "Join thousands of farmers optimizing crop yield & market returns",
                fullName: "Full Name *",
                email: "Email Address *",
                phone: "Phone Number *",
                location: "Farm Location *",
                prefLang: "Preferred Language"
            },
            hi: {
                title: "किसान पंजीकरण",
                subtitle: "फसल उपज और बाजार लाभ को अनुकूलित करने वाले हजारों किसानों से जुड़ें",
                fullName: "पूरा नाम *",
                email: "ईमेल पता *",
                phone: "फोन नंबर *",
                location: "खेत का स्थान (जिला/गांव) *",
                prefLang: "पसंदीदा भाषा"
            },
            mr: {
                title: "शेतकरी नोंदणी",
                subtitle: "पिकांचे उत्पन्न आणि बाजारभाव वाढवणार्‍या हजारो शेतकऱ्यांशी जोडा",
                fullName: "पूर्ण नाव *",
                email: "ईमेल पत्ता *",
                phone: "फोन नंबर *",
                location: "शेताचे ठिकाण (जिल्हा/गाव) *",
                prefLang: "पसंतीची भाषा"
            }
        };

        function changeLanguage(lang) {
            document.getElementById('preferred_language').value = lang;
            if (translations[lang]) {
                document.getElementById('txtTitle').textContent = translations[lang].title;
                document.getElementById('txtSubtitle').textContent = translations[lang].subtitle;
                document.getElementById('lblFullName').childNodes[0].nodeValue = translations[lang].fullName.replace(' *', '') + ' ';
                document.getElementById('lblEmail').childNodes[0].nodeValue = translations[lang].email.replace(' *', '') + ' ';
                document.getElementById('lblPhone').childNodes[0].nodeValue = translations[lang].phone.replace(' *', '') + ' ';
                document.getElementById('lblLocation').childNodes[0].nodeValue = translations[lang].location.replace(' *', '') + ' ';
                document.getElementById('lblPrefLang').textContent = translations[lang].prefLang;
            }
        }
    </script>
</body>
</html>
