<?php
// Start session and enforce guard
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once __DIR__ . '/db_connect.php';

$user_id   = $_SESSION['user_id'];
$full_name = $_SESSION['full_name'] ?? 'Farmer';
$location  = $_SESSION['farm_location'] ?? 'Unknown Location';
$email     = $_SESSION['email'] ?? '';
$phone     = $_SESSION['phone'] ?? '';
$language  = strtoupper($_SESSION['preferred_language'] ?? 'EN');

// Fetch user dynamic stats from database if available
$crops_count = 0;
$pending_tasks = 0;
$financial_balance = 0.00;

try {
    // Count active crops
    $cropStmt = $pdo->prepare("SELECT COUNT(*) FROM crops WHERE user_id = :uid AND status = 'Growing'");
    $cropStmt->execute([':uid' => $user_id]);
    $crops_count = $cropStmt->fetchColumn();

    // Count pending irrigation / care tasks
    $schedStmt = $pdo->prepare("
        SELECT COUNT(*) FROM schedules s 
        JOIN crops c ON s.crop_id = c.crop_id 
        WHERE c.user_id = :uid AND s.status = 'Pending'
    ");
    $schedStmt->execute([':uid' => $user_id]);
    $pending_tasks = $schedStmt->fetchColumn();

    // Calculate net financial balance
    $finStmt = $pdo->prepare("
        SELECT 
            SUM(CASE WHEN type = 'Income' THEN amount ELSE -amount END) as net_balance 
        FROM financials 
        WHERE user_id = :uid
    ");
    $finStmt->execute([':uid' => $user_id]);
    $balance = $finStmt->fetchColumn();
    if ($balance !== null) {
        $financial_balance = (float)$balance;
    }
} catch (PDOException $e) {
    // Graceful fallback if tables aren't populated yet
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - FarmFlow Smart Farming Platform</title>
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
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
</head>
<body class="bg-slate-50 min-h-screen text-slate-800 antialiased selection:bg-emerald-500 selection:text-white flex flex-col justify-between">

    <!-- Top Navigation Bar -->
    <header class="bg-white border-b border-slate-200 sticky top-0 z-50 py-3 px-4 sm:px-8 shadow-sm">
        <div class="max-w-7xl mx-auto flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-emerald-700 flex items-center justify-center text-white shadow-md shadow-emerald-700/20">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                    </svg>
                </div>
                <div>
                    <span class="font-bold text-xl tracking-tight text-slate-900">Farm<span class="text-emerald-700">Flow</span></span>
                    <span class="block text-[10px] text-slate-500 font-semibold tracking-wider uppercase">Smart Farm Dashboard</span>
                </div>
            </div>

            <!-- Farmer Profile Info & Logout -->
            <div class="flex items-center gap-3 sm:gap-5">
                <div class="hidden sm:flex items-center gap-2.5 bg-slate-100 py-1.5 px-3 rounded-xl border border-slate-200">
                    <div class="w-8 h-8 rounded-lg bg-emerald-700 text-white font-bold flex items-center justify-center text-sm">
                        <?php echo strtoupper(substr($full_name, 0, 1)); ?>
                    </div>
                    <div class="text-left">
                        <p class="text-xs font-bold text-slate-900 leading-none"><?php echo htmlspecialchars($full_name); ?></p>
                        <p class="text-[10px] text-slate-500 leading-tight mt-0.5"><?php echo htmlspecialchars($location); ?></p>
                    </div>
                    <span class="ml-1 text-[10px] bg-emerald-100 text-emerald-800 font-bold px-2 py-0.5 rounded-full uppercase"><?php echo $language; ?></span>
                </div>

                <a href="logout.php" class="inline-flex items-center gap-1.5 py-2 px-3.5 bg-red-50 hover:bg-red-100 text-red-600 font-bold text-xs sm:text-sm rounded-xl border border-red-200 transition-colors cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path>
                    </svg>
                    <span>Log Out</span>
                </a>
            </div>
        </div>
    </header>

    <!-- Main Dashboard Container -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 flex-grow w-full">

        <!-- Welcome Banner -->
        <div class="bg-gradient-to-r from-emerald-800 to-emerald-900 rounded-3xl p-6 sm:p-10 text-white shadow-xl shadow-emerald-900/10 mb-8 relative overflow-hidden">
            <!-- Background Decorative Leaf Graphic -->
            <div class="absolute -right-10 -bottom-10 opacity-10 pointer-events-none">
                <svg class="w-72 h-72 text-white" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/>
                </svg>
            </div>

            <div class="relative z-10">
                <div class="inline-flex items-center gap-2 bg-white/10 backdrop-blur-sm border border-white/20 rounded-full px-3 py-1 text-xs font-semibold text-emerald-200 mb-3">
                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                    <span>Session Authenticated</span>
                </div>
                <h1 class="text-2xl sm:text-4xl font-extrabold tracking-tight">Namaste, <?php echo htmlspecialchars($full_name); ?>! 👋</h1>
                <p class="text-emerald-100 text-sm sm:text-base max-w-2xl mt-2">
                    Welcome to your Smart Farming Command Center. Your account is active for 
                    <span class="font-bold text-white"><?php echo htmlspecialchars($location); ?></span>.
                </p>

                <!-- Quick Farmer Profile Tags -->
                <div class="flex flex-wrap items-center gap-4 mt-6 text-xs text-emerald-200">
                    <span class="flex items-center gap-1.5 bg-black/20 px-3 py-1.5 rounded-lg border border-white/10">
                        <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                        <?php echo htmlspecialchars($email); ?>
                    </span>
                    <?php if (!empty($phone)): ?>
                    <span class="flex items-center gap-1.5 bg-black/20 px-3 py-1.5 rounded-lg border border-white/10">
                        <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path></svg>
                        <?php echo htmlspecialchars($phone); ?>
                    </span>
                    <?php endif; ?>
                    <span class="flex items-center gap-1.5 bg-black/20 px-3 py-1.5 rounded-lg border border-white/10">
                        <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129"></path></svg>
                        Language: <?php echo $language; ?>
                    </span>
                </div>
            </div>
        </div>

        <!-- Dashboard Key Metrics Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
            <!-- Active Crops Card -->
            <div class="bg-white rounded-2xl p-6 border border-slate-200/80 shadow-sm hover:shadow-md transition-shadow">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Active Crops</p>
                        <p class="text-3xl font-extrabold text-slate-900 mt-1"><?php echo $crops_count; ?> Crops</p>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"></path>
                        </svg>
                    </div>
                </div>
                <p class="text-xs text-emerald-700 font-semibold mt-4 flex items-center gap-1">
                    <span>🌱 Sugarcane, Wheat, Cotton tracking enabled</span>
                </p>
            </div>

            <!-- Pending Tasks Card -->
            <div class="bg-white rounded-2xl p-6 border border-slate-200/80 shadow-sm hover:shadow-md transition-shadow">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Irrigation Tasks</p>
                        <p class="text-3xl font-extrabold text-slate-900 mt-1"><?php echo $pending_tasks; ?> Scheduled</p>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-blue-100 text-blue-700 flex items-center justify-center">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                        </svg>
                    </div>
                </div>
                <p class="text-xs text-blue-700 font-semibold mt-4 flex items-center gap-1">
                    <span>💧 Drip irrigation & fertigation reminders</span>
                </p>
            </div>

            <!-- Financial Ledger Card -->
            <div class="bg-white rounded-2xl p-6 border border-slate-200/80 shadow-sm hover:shadow-md transition-shadow sm:col-span-2 lg:col-span-1">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Net Farm Ledger</p>
                        <p class="text-3xl font-extrabold text-slate-900 mt-1">₹<?php echo number_format($financial_balance, 2); ?></p>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
                <p class="text-xs text-amber-700 font-semibold mt-4 flex items-center gap-1">
                    <span>💰 Income & Expense balance log</span>
                </p>
            </div>
        </div>

        <!-- Standalone React App Quick Launch Card -->
        <div class="bg-white rounded-2xl border border-slate-200 p-6 sm:p-8 shadow-sm">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
                <div>
                    <h3 class="text-lg font-bold text-slate-900">Interactive FarmFlow React Suite</h3>
                    <p class="text-slate-500 text-sm mt-1">
                        Explore full interactive Mandi Prices, 5-Day Weather Forecasts, Govt Schemes (PM-KISAN), and Harvest Records.
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <a href="overview.php" class="py-2.5 px-5 bg-emerald-700 hover:bg-emerald-800 text-white font-bold text-sm rounded-xl shadow-md transition-colors inline-flex items-center gap-2">
                        <span>Open Interactive SPA</span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                    </a>
                </div>
            </div>
        </div>

    </main>

    <!-- Footer -->
    <footer class="bg-white border-t border-slate-200 py-4 text-center text-xs text-slate-500">
        &copy; <?php echo date('Y'); ?> FarmFlow Smart Farming Management System. Authenticated Session ID: #<?php echo $user_id; ?>
    </footer>

</body>
</html>
