<?php
// Only start the session if it's not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Regenerate session ID for security
session_regenerate_id(true); // Prevent session fixation attacks
// Session timeout logic
if (!defined('SESSION_TIMEOUT')) {
    define('SESSION_TIMEOUT', 100000000000000); // 15 minutes ( 900)
}

if (isset($_SESSION['last_activity'])) {
    $inactive_time = time() - $_SESSION['last_activity'];
    if ($inactive_time > SESSION_TIMEOUT) {
        session_unset();  // Clear session variables
        session_destroy(); // Destroy the session
        header('Location: session_timeout.php');  // Redirect to timeout page
        exit;
    }
}

// Update the last activity timestamp
$_SESSION['last_activity'] = time();
