<?php
// Include database connection and session management
include_once 'db_connection.php';
include 'session_management.php';


// Check if the user is logged in
if (!isset($_SESSION['session_userid']) || !isset($_SESSION['session_roleid'])) {
    $_SESSION['error_message'] = "Unauthorized access. Please log in.";
    header('Location: login.php');
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Debug: Check CSRF token
    if (!isset($_POST['csrf_token'])) {
        die('CSRF token missing in the form.');
    }
    if ($_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die('CSRF token mismatch. Debug: ' . $_POST['csrf_token'] . ' != ' . $_SESSION['csrf_token']);
    }

    // Regenerate CSRF token to prevent reuse
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

    // Retrieve and sanitize the access key input
    $user_id = $_SESSION['session_userid'];
    $role_id = $_SESSION['session_roleid'];
    $access_key = filter_var($_POST['access_key'] ?? '', FILTER_SANITIZE_STRING);

    // Debug: Check access key in POST
    echo 'Access Key in POST: ' . ($_POST['access_key'] ?? 'Not set') . '<br>';

    // Check for empty access key
    if (empty($access_key)) {
        $_SESSION['error_message'] = "Access key cannot be empty.";
        header('Location: access_key_form.php');
        exit;
    }

    // Handle role-specific logic using a switch statement
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
            $redirect = 'faculty_dashboard.php';
            break;
        default:
            $_SESSION['error_message'] = "Invalid role. Please contact the administrator.";
            header('Location: login.php');
            exit;
    }

    // Validate table and column names (whitelist validation)
    $allowed_tables = ['admin', 'faculty'];
    $allowed_columns = ['admin_name', 'faculty_name'];

    if (!in_array($role_table, $allowed_tables) || !in_array($role_column, $allowed_columns)) {
        $_SESSION['error_message'] = "Invalid role configuration.";
        header('Location: access_key_form.php');
        exit;
    }

    // Prepare and execute the SQL query to validate the access key
    $stmt = $conn->prepare("SELECT {$role_table}_access_key, {$role_column}, profile_picture FROM {$role_table} WHERE user_id = ?");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    // Debug: Check access key in database
    echo 'Access Key from DB: ' . ($row["{$role_table}_access_key"] ?? 'Not set') . '<br>';
    echo 'Access Key Entered: ' . $access_key . '<br>';

    // Validate access key
    if ($row && $row["{$role_table}_access_key"] === $access_key) {
        // Regenerate session ID for security
        session_regenerate_id(true);

        // Store user details in session
        $_SESSION['session_name'] = htmlspecialchars($row[$role_column], ENT_QUOTES, 'UTF-8');
        $_SESSION['profile_picture'] = $row['profile_picture'] ?? 'default_profile.png';

        // Redirect to the dashboard
        header("Location: {$redirect}");
        exit;
    } else {
        $_SESSION['error_message'] = "Invalid access key. Please try again.";
        header('Location: access_key_form.php');
        exit;
    }
}
?>
