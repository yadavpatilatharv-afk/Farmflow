<?php
/**
 * Internal shared renderer for FarmFlow pages.
 *
 * Two modes:
 *  - Dashboard feature page: $feature is set (e.g. 'crops'), requires a logged-in session.
 *  - Public page: $publicPage is set (e.g. 'features'), no login required.
 *
 * Location: _spa_page.php at the app root. Each feature/public page boots the
 * React app by including this file and injecting the boot config.
 */

$VALID_FEATURES     = ['overview', 'crops', 'scheduler', 'financials', 'harvest', 'schemes', 'news', 'settings'];
$VALID_PUBLIC_PAGES = ['home', 'features', 'about'];

$isFeaturePage = isset($feature) && in_array($feature, $VALID_FEATURES, true);
$isPublicPage  = isset($publicPage) && in_array($publicPage, $VALID_PUBLIC_PAGES, true);

if ($isFeaturePage) {
    // Session guard for dashboard feature pages
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit();
    }
} elseif (!$isPublicPage) {
    header('Location: index.php');
    exit();
}

$boot   = [];
$titles = [];

if ($isFeaturePage) {
    // Pull the logged-in farmer's profile from session + database
    $name     = $_SESSION['full_name']     ?? 'Farmer';
    $location = $_SESSION['farm_location'] ?? 'Ashta, Sangli, Maharashtra';
    $email    = $_SESSION['email']         ?? '';
    $phone    = $_SESSION['phone']         ?? '';

    try {
        $stmt = $pdo->prepare("SELECT full_name, email, phone, farm_location, preferred_language FROM users WHERE user_id = ? LIMIT 1");
        $stmt->execute([$_SESSION['user_id']]);
        $row = $stmt->fetch();
        if ($row) {
            $name     = $row['full_name']     ?: $name;
            $location = $row['farm_location'] ?: $location;
            $email    = $row['email']         ?: $email;
            $phone    = $row['phone']         ?: $phone;
        }
    } catch (PDOException $e) {
        // Non-fatal: fall back to session values
    }

    $boot = [
        'feature' => $feature,
        'userId'  => $_SESSION['user_id'],
        'profile' => [
            'name'     => $name,
            'role'     => 'Lead Farmer & Agri-Entrepreneur',
            'farmName' => trim($name) . "'s Farm",
            'location' => $location,
            'acres'    => 10.0,
            'soilType' => 'Black Cotton Soil (Vertisol)',
            'phone'    => $phone,
            'email'    => $email
        ]
    ];

    $titles = [
        'overview'   => 'Overview - FarmFlow Smart Farming',
        'crops'      => 'Crop Management - FarmFlow Smart Farming',
        'scheduler'  => 'Irrigation Scheduler - FarmFlow Smart Farming',
        'financials' => 'Financial Ledger - FarmFlow Smart Farming',
        'harvest'    => 'Harvest Records - FarmFlow Smart Farming',
        'schemes'    => 'Govt Schemes - FarmFlow Smart Farming',
        'news'       => 'News & Weather - FarmFlow Smart Farming',
        'settings'   => 'Settings - FarmFlow Smart Farming'
    ];
} else {
    $boot = ['publicPage' => $publicPage];

    $titles = [
        'home'     => 'FarmFlow - Smart Farming Management System',
        'features' => 'Features - FarmFlow Smart Farming',
        'about'    => 'About - FarmFlow Smart Farming'
    ];
}

$config = require __DIR__ . '/config.php';
$distHtml = $config['app']['dist_html'];
$html = file_exists($distHtml) ? file_get_contents($distHtml) : file_get_contents($config['app']['fallback_html']);

$html = str_replace(
    '<title>FarmFlow - Smart Farming Management System</title>',
    '<title>' . ($titles[$isFeaturePage ? $feature : $publicPage] ?? 'FarmFlow Smart Farming') . '</title>',
    $html
);

$bootScript = '<script>window.farmflow_boot=' . json_encode($boot) . ';</script>';
$html = str_replace('<div id="root"></div>', '<div id="root"></div>' . $bootScript, $html);

echo $html;
