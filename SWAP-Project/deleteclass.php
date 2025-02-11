<?php
// Include necessary files
include 'db_connection.php';
include 'csrf_protection.php';
session_start();

// Validate CSRF token only on POST
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!isset($_POST['csrf_token']) || !validate_csrf_token($_POST['csrf_token'])) {
        regenerate_csrf_token(); // Generate a new CSRF token
        die("Invalid CSRF token. <a href='logout.php'>Try again</a>");
    }
}

// Check if user is authenticated
if (!isset($_SESSION['session_userid']) || !isset($_SESSION['session_roleid'])) {
    header('Location: logout.php'); // Redirect unauthorized users
    exit;
}

$user_id = $_SESSION['session_userid'];
$role_id = $_SESSION['session_roleid'];

// Ensure only admins can delete classes
if ($role_id != 1) {
    $_SESSION['error_message'] = "Unauthorized access. You do not have permission to delete classes.";
    header("Location: classes.php");
    exit;
}

// Validate and sanitize class_id (from POST)
$class_id = isset($_POST['class_id']) ? intval($_POST['class_id']) : 0;

if ($class_id <= 0) {
    $_SESSION['error_message'] = "Invalid class ID.";
    header("Location: classes.php");
    exit;
}

// Check if the class exists before deleting
$class_exists_query = "SELECT EXISTS(SELECT 1 FROM class WHERE class_id = ?)";
$class_exists_stmt = $conn->prepare($class_exists_query);
$class_exists_stmt->bind_param('i', $class_id);
$class_exists_stmt->execute();
$class_exists_stmt->bind_result($class_exists);
$class_exists_stmt->fetch();
$class_exists_stmt->close();

if (!$class_exists) {
    $_SESSION['error_message'] = "Class not found.";
    header("Location: classes.php");
    exit;
}

// Check for dependencies (if students are enrolled in the class)
$dependency_query = "SELECT EXISTS(SELECT 1 FROM student_class WHERE class_id = ?)";
$dependency_stmt = $conn->prepare($dependency_query);
$dependency_stmt->bind_param('i', $class_id);
$dependency_stmt->execute();
$dependency_stmt->bind_result($has_dependency);
$dependency_stmt->fetch();
$dependency_stmt->close();

if ($has_dependency) {
    $_SESSION['error_message'] = "Cannot delete this class because students are currently enrolled.";
    header("Location: classes.php");
    exit;
}

// Proceed with class deletion
$delete_query = "DELETE FROM class WHERE class_id = ?";
$delete_stmt = $conn->prepare($delete_query);
$delete_stmt->bind_param('i', $class_id);

if ($delete_stmt->execute()) {
    $_SESSION['success_message'] = "Class deleted successfully.";
} else {
    $_SESSION['error_message'] = "Error deleting class. Please try again.";
}

// Close the database connection
mysqli_close($conn);

// Redirect back to class list
header("Location: classes.php");
exit;
