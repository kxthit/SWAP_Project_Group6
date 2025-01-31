<?php
// Include the database connection and session management
include 'db_connection.php';
include 'session_management.php';
session_start();

// Check if the user is authenticated
if (!isset($_SESSION['session_userid']) || !isset($_SESSION['session_roleid'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['session_userid'];
$role_id = $_SESSION['session_roleid'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $class_id = intval($_POST['class_id']);
    $class_name = $_POST['class_name'];
    $class_type = $_POST['class_type'];

    // Determine the course and faculty IDs based on the user's role
    if ($role_id == 1) { // Admin can edit everything
        $course_id = $_POST['course_id'];
        $faculty_id = $_POST['faculty_id'];
    } else { // Faculty cannot edit course or faculty
        // Fetch the current course and faculty IDs from the database
        $query = "SELECT course_id, faculty_id FROM class WHERE class_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('i', $class_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $current_data = $result->fetch_assoc();

        $course_id = $current_data['course_id'];
        $faculty_id = $current_data['faculty_id'];
    }

    // Update query
    $update_query = "
        UPDATE class 
        SET class_name = ?, 
            class_type = ?, 
            course_id = ?, 
            faculty_id = ? 
        WHERE class_id = ?
    ";
    $update_stmt = $conn->prepare($update_query);
    $update_stmt->bind_param('ssiii', $class_name, $class_type, $course_id, $faculty_id, $class_id);

    if ($update_stmt->execute()) {
        echo "<script>alert('Class updated successfully!'); window.location.href = 'classes.php';</script>";
    } else {
        echo "<script>alert('Error updating class.'); window.history.back();</script>";
    }
} else {
    header('Location: classes.php');
    exit;
}
?>
