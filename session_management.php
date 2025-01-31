<?php
session_start();

// Generate CSRF token if not already set
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Define session timeout in seconds (e.g., 15 minutes)
define('SESSION_TIMEOUT', 900); // 15 minutes

// Check for session timeout
if (isset($_SESSION['last_activity'])) {
    $inactive_time = time() - $_SESSION['last_activity'];
    if ($inactive_time > SESSION_TIMEOUT) {
        session_unset();
        session_destroy();
        header('Location: session_timeout.php'); // Redirect to timeout page
        exit;
    }
}

// Update the last activity timestamp
$_SESSION['last_activity'] = time();

?>
