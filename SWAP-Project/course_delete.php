<?php
include 'csrf_protection.php';
include_once 'db_connection.php';

// Ensure the user is authenticated
if (!isset($_SESSION['session_userid']) || !isset($_SESSION['session_roleid'])) {
    mysqli_close($conn); // Close the DB connection before redirecting
    header("Location: logout.php"); // Redirect if not authenticated
    exit;
}
// Check for POST submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // CSRF check
    if (!isset($_POST['csrf_token']) || !validate_csrf_token($_POST['csrf_token'])) {
        regenerate_csrf_token();
        die("Invalid CSRF token. <a href='view_course?course_id=?.php'>Try again</a>");
    }
// Retrieve session variables
$role_id = $_SESSION['session_roleid'];
$user_id = $_SESSION['session_userid'];
$faculty_id = $_SESSION['session_facultyid'];
$course_id = $_SESSION['session_courseid'];
$course_status_id = $_SESSION['session_coursestatusid'];
$faculty_department_id = $_SESSION['session_facultydepartmentid'];

// Check if the form sent a course_id via POST
if (isset($_POST['course_id'])) {
    $del_courseid = htmlspecialchars($_POST['course_id']);  // Use POST, not GET

    // Check if user is an Admin (role_id == 1)
    if ($role_id == 1) {
        // Admin can delete any course without restriction
        $canDelete = true;
    } elseif ($role_id == 2) {
        // Faculty can delete a course if:
        // - The course belongs to the faculty
        // - The status_id is 4 (Unassigned)
        
        // Check if the course exists for the faculty in the `faculty_course` table
        $stmt = $conn->prepare("
            SELECT fc.faculty_id, c.status_id
            FROM faculty_course fc
            JOIN course c ON c.course_id = fc.course_id
            WHERE fc.faculty_id = ? AND fc.course_id = ? AND c.status_id = 4
        ");
        $stmt->bind_param('ii', $faculty_id, $del_courseid);
        $stmt->execute();
        $result = $stmt->get_result();

        // If a matching record is found, allow deletion
        if ($result && $result->num_rows > 0) {
            $canDelete = true;
        } else {
            // No match found or status is not 4, faculty can't delete this course
            $canDelete = false;
        }
    } else {
        // Invalid role, can't delete course
        $canDelete = false;
    }

    // Proceed with deletion if user has permission
    if ($canDelete) {
        // Prepare the delete statement
        $stmt = $conn->prepare("DELETE FROM course WHERE course_id = ?");
        $stmt->bind_param('i', $del_courseid);

        // Execute the statement
        if ($stmt->execute()) {
            // Set session message for successful deletion
            $_SESSION['delete_message'] = "Course deleted successfully.";
            mysqli_close($conn); // Close DB connection before redirect
            header("Location: course_delete_notice.php"); // Redirect to confirmation page
            exit;
        } else {
            // If execution fails, display an error
            $_SESSION['delete_message'] = "Failed to delete course. Please try again.";
            mysqli_close($conn); // Close DB connection before redirect
            header("Location: course_delete_notice.php");
            exit;
        }
    } else {
        // If the user doesn't have permission to delete, set a specific error message
        $_SESSION['delete_message'] = "You cannot delete a course that is not Unassigned.";
        mysqli_close($conn); // Close DB connection before redirect
        header("Location: course_delete_notice.php");
        exit;
    }
} else {
    // If no course_id is provided, handle the error
    $_SESSION['delete_message'] = "No course ID provided.";
    mysqli_close($conn); // Close DB connection before redirect
    header("Location: course_delete_notice.php");
    exit;
}
}
?>
