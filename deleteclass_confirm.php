<?php
include 'db_connection.php';
session_start();

// Check if the user is authenticated
if (!isset($_SESSION['session_userid']) || !isset($_SESSION['session_roleid'])) {
    echo "<h2>Unauthorized access. Please log in.</h2>";
    header('Refresh: 3; URL=login.php');
    exit;
}

$user_id = $_SESSION['session_userid'];
$role_id = $_SESSION['session_roleid'];

if ($role_id != 1) {  // Only Admins (role_id = 1) can delete classes
    echo "<script>alert('Unauthorized access. You do not have permission to delete classes.'); window.location.href = 'classes.php';</script>";
    exit;
}

// Get the class ID from the request
$class_id = isset($_GET['class_id']) ? intval($_GET['class_id']) : 0;

// Ensure class ID is valid
if ($class_id <= 0) {
    echo "<script>alert('Invalid class ID.'); window.location.href = 'classes.php';</script>";
    exit;
}

// Delete the class
$delete_query = "DELETE FROM class WHERE class_id = ?";
$delete_stmt = $conn->prepare($delete_query);
$delete_stmt->bind_param('i', $class_id);

if ($delete_stmt->execute()) {
    echo "<script>alert('Class deleted successfully!'); window.location.href = 'classes.php';</script>";
} else {
    echo "<script>alert('Error deleting class.'); window.location.href = 'classes.php';</script>";
}
?>
