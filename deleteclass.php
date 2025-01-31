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

// Check if the class exists before deleting
$check_query = "SELECT * FROM class WHERE class_id = ?";
$check_stmt = $conn->prepare($check_query);
$check_stmt->bind_param('i', $class_id);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows == 0) {
    echo "<script>alert('Class not found.'); window.location.href = 'classes.php';</script>";
    exit;
}

// Add JavaScript confirmation prompt before proceeding with deletion
echo "<script>
    if (confirm('Are you sure you want to delete this class? This action cannot be undone.')) {
        window.location.href = 'deleteclass_confirm.php?class_id=$class_id';
    } else {
        window.location.href = 'classes.php';
    }
</script>";
exit;
?>
