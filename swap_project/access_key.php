<?php
// Include database connection
include_once 'db_connection.php';
session_start();

// Check if the user is logged in
if (!isset($_SESSION['session_userid']) || !isset($_SESSION['session_role'])) {
    echo "<h2>Unauthorized access. Please log in.</h2>";
    header('Refresh: 3; URL=login.php');
    exit;
}

$user_id = $_SESSION['session_userid'];
$role_id = $_SESSION['session_role']; // role_id is used here
$access_key = htmlspecialchars($_POST['access_key'] ?? ''); // Access key input from user

// Validate access key
if ($_SERVER['REQUEST_METHOD'] == 'POST' && empty($access_key)) {
    echo "<h2>Access key cannot be empty. Please try again.</h2>";
    header('Refresh: 3; URL=access_key.php');
    exit;
}

// Handle role-specific logic using a switch statement
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
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
            echo "<h2>Invalid role. Please contact the administrator.</h2>";
            header('Refresh: 3; URL=login.php');
            exit;
    }

    // Prepare and execute the SQL query based on the role
    $stmt = $conn->prepare("SELECT {$role_table}_access_key, {$role_column}, profile_picture FROM {$role_table} WHERE user_id = ?");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();



    // Check access key and role data
    if ($row && $row["{$role_table}_access_key"] === $access_key) {
        $_SESSION['session_name'] = $row[$role_column];
        $_SESSION['profile_picture'] = $row['profile_picture'] ?? 'default_profile.png'; // Fallback to default picture
        echo "<script> alert('Welcome, {$_SESSION['session_name']}!'); window.location.href = '{$redirect}'; </script>";
        exit;
    } else {
        echo "<script> alert('Invalid access key. Please try again.'); window.location.href = 'access_key_form.php'; </script>";
        exit;
    }
}
?>

