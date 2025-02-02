<?php
include 'csrf_protection.php';
include_once 'db_connection.php';

// Ensure the user is authenticated
if (!isset($_SESSION['session_userid']) || !isset($_SESSION['session_roleid'])) {
    echo "<h2>Unauthorized access. Please log in.</h2>";
    header('Refresh: 3; URL=login.php');
    exit;
}

// Check for POST submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // CSRF check
    if (!isset($_POST['csrf_token']) || !validate_csrf_token($_POST['csrf_token'])) {
        regenerate_csrf_token();
        die("Invalid CSRF token. <a href='form.php'>Try again</a>");
    }


$user_id = $_SESSION['session_userid'];
$role_id = $_SESSION['session_roleid'];

// Ensure only admins can delete classes
if ($role_id != 1) {
    echo "<script>alert('Unauthorized access. You do not have permission to delete classes.'); window.location.href = 'classes.php';</script>";
    exit;
}

// Validate CSRF token
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    echo "<script>alert('CSRF token validation failed.'); window.location.href = 'classes.php';</script>";
    exit;
}

// Validate and sanitize class_id (from POST)
$class_id = isset($_POST['class_id']) ? intval($_POST['class_id']) : 0;

if ($class_id <= 0) {
    echo "<script>alert('Invalid class ID.'); window.location.href = 'classes.php';</script>";
    exit;
}

// Add a confirmation dialog before proceeding
echo "<script>
    if (!confirm('Are you sure you want to delete this class? This action cannot be undone.')) {
        window.location.href = 'classes.php';
    }
</script>";

// Check if the class exists before deleting
$check_query = "SELECT class_id FROM class WHERE class_id = ?";
$check_stmt = $conn->prepare($check_query);
$check_stmt->bind_param('i', $class_id);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows == 0) {
    echo "<script>alert('Class not found.'); window.location.href = 'classes.php';</script>";
    exit;
}

// Proceed with class deletion
$delete_query = "DELETE FROM class WHERE class_id = ?";
$delete_stmt = $conn->prepare($delete_query);
$delete_stmt->bind_param('i', $class_id);

if ($delete_stmt->execute()) {
    echo "<script>alert('Class deleted successfully.'); window.location.href = 'classes.php';</script>";
} else {
    echo "<script>alert('Error deleting class. Please try again later.'); window.location.href = 'classes.php';</script>";
}
}
?>

<?php
// Close the database connection at the end of the script
mysqli_close($conn);
?>