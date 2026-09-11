<?php
// Start session and enforce guard
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once __DIR__ . '/db_connect.php';

// Pull the logged-in farmer's profile from the session
$name     = $_SESSION['full_name']          ?? 'Farmer';
$location = $_SESSION['farm_location']      ?? 'Ashta, Sangli, Maharashtra';
$email    = $_SESSION['email']              ?? '';
$phone    = $_SESSION['phone']              ?? '';

// Fetch real details from database if present
$acres    = 10.0;
$soilType = 'Black Cotton Soil (Vertisol)';
try {
    $stmt = $pdo->prepare("SELECT farm_location, preferred_language FROM users WHERE user_id = ? LIMIT 1");
    $stmt->execute([$_SESSION['user_id']]);
    $row = $stmt->fetch();
    if ($row && !empty($row['farm_location'])) {
        $location = $row['farm_location'];
    }
} catch (PDOException $e) {
    // Non-fatal
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Opening Your FarmFlow Dashboard</title>
</head>
<body style="font-family: system-ui, -apple-system, sans-serif; background:#f8faf6; color:#14532d; display:flex; align-items:center; justify-content:center; min-height:100vh; margin:0;">
    <div style="text-align:center; padding: 24px;">
        <div style="font-size:48px; margin-bottom:12px;">🌱</div>
        <h2 style="margin:0 0 6px; font-size: 22px;">Opening your FarmFlow dashboard...</h2>
        <p style="margin:0; color:#166534; font-weight:500;">Welcome, <?php echo htmlspecialchars($name); ?>! Preparing your personalized smart farm workspace.</p>
    </div>
    <script>
        (function () {
            try {
                var uid = <?php echo json_encode($_SESSION['user_id'] ?? null); ?>;
                // Sign the browser session into the React dashboard for THIS farmer
                localStorage.setItem('farmflow_is_logged_in', 'true');
                if (uid) {
                    localStorage.setItem('farmflow_current_user_id', String(uid));
                    localStorage.setItem('farmflow_user_profile_v1_u' + uid, JSON.stringify({
                        name: <?php echo json_encode($name); ?>,
                        role: "Lead Farmer & Agri-Entrepreneur",
                        farmName: <?php echo json_encode(trim($name) . "'s Farm"); ?>,
                        location: <?php echo json_encode($location); ?>,
                        acres: 10.0,
                        soilType: "Black Cotton Soil (Vertisol)",
                        phone: <?php echo json_encode($phone); ?>,
                        email: <?php echo json_encode($email); ?>
                    }));
                } else {
                    localStorage.removeItem('farmflow_current_user_id');
                }
                localStorage.setItem('farmflow_current_location', <?php echo json_encode($location); ?>);
            } catch (e) { /* localStorage fallback */ }

            // Redirect to the feature dashboard page (boots the React app for this farmer)
            window.location.href = 'overview.php';
        })();
    </script>
</body>
</html>
