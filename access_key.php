<?php
// Include database connection
include_once 'db_connection.php';
session_start();

// Check if the user is logged in
if (!isset($_SESSION['session_userid']) || !isset($_SESSION['session_role'])) {
    $_SESSION['error_message'] = "Unauthorized access. Please log in.";
    header('Location: login.php');
    exit;
}

// Validate CSRF token
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error_message'] = "Invalid CSRF token.";
        header('Location: access_keyform.php');
        exit;
    }
    // Regenerate CSRF token to prevent reuse
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Retrieve and sanitize inputs
$user_id = $_SESSION['session_userid'];
$role_id = $_SESSION['session_role'];
$access_key = filter_var($_POST['access_key'] ?? '', FILTER_SANITIZE_STRING);

// Check for empty fields
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($access_key)) {
    $_SESSION['error_message'] = "Access key cannot be empty.";
    header('Location: access_keyform.php');
    exit;
}

// Handle role-specific logic using a switch statement
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $role_table = '';
    $role_column = '';
    $redirect = '';

    switch ($role_id) {
        case 1: // Admin
            $role_table = 'admin';
            $role_column = 'admin_name';
            $redirect = 'admin_dashboard.php';
            break;
        case 2: // Faculty
            $role_table = 'faculty';
            $role_column = 'faculty_name';
            $redirect = 'admin_dashboard.php';
            break;
        default:
            $_SESSION['error_message'] = "Invalid role. Please contact the administrator.";
            header('Location: login.php');
            exit;
    }

    // Validate table and column names
    $allowed_tables = ['admin', 'faculty'];
    $allowed_columns = ['admin_name', 'faculty_name'];

    if (!in_array($role_table, $allowed_tables) || !in_array($role_column, $allowed_columns)) {
        $_SESSION['error_message'] = "Invalid role configuration.";
        header('Location: access_keyform.php');
        exit;
    }

    // Prepare and execute the SQL query
    $stmt = $conn->prepare("SELECT {$role_table}_access_key, {$role_column}, profile_picture FROM {$role_table} WHERE user_id = ?");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    // Check access key and role data
    if ($row && $row["{$role_table}_access_key"] === $access_key) {
        // Regenerate session ID for security
        session_regenerate_id(true);

        // Store user data in session
        $_SESSION['session_name'] = htmlspecialchars($row[$role_column], ENT_QUOTES, 'UTF-8');
        $_SESSION['profile_picture'] = $row['profile_picture'] ?? 'default_profile.png';

        // Redirect user
        header("Location: {$redirect}");
        exit;
    } else {
        $_SESSION['error_message'] = "Invalid access key. Please try again.";
        header('Location: access_key_form.php');
        exit;
    }
}
?>
