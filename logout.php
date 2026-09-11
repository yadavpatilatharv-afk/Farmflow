<?php
/**
 * FarmFlow Session Termination (Logout) Script
 */
session_start();

// Unset all session variables
$_SESSION = array();

// If session cookie exists, destroy it
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// Clear optional remember-me cookie if present
if (isset($_COOKIE['farmflow_user'])) {
    setcookie('farmflow_user', '', time() - 3600, '/');
}

// Destroy session
session_destroy();

// Redirect to login page with logout alert flag
header("Location: login.php?logout=1");
exit();
?>
