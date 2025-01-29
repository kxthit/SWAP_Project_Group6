<?php
include('db_connection.php');
session_start();

// Check if the user is authenticated
if (!isset($_SESSION['session_userid']) || !isset($_SESSION['session_roleid'])) {
    $_SESSION['delete_message'] = "Unauthorized access. Please log in.";
    header('Location: course_delete_notice.php');
    exit;
}

// Check if the user has admin privileges
if ($_SESSION['session_roleid'] == 1) { // Admin
    // Admin can delete any course, no status check needed
    $canDelete = true;
} elseif ($_SESSION['session_roleid'] == 2) { // Faculty
    // Faculty can delete course only if status is "Unassigned" (status_id == 4)
    $status_id = $_SESSION['session_statusid']; // Get the status_id from the session

    // Faculty can only delete if status_id is 4 (Unassigned)
    if ($status_id == 4) {
        // Check if the course belongs to the faculty's department using faculty_course table
        $faculty_id = $_SESSION['session_facultyid']; // Get faculty_id from session

        // Query to check if the course is assigned to the faculty
        $authQuery = $conn->prepare("
            SELECT COUNT(*) AS count
            FROM faculty_course 
            JOIN course ON faculty_course.course_id = course.course_id
            WHERE faculty_course.faculty_id = ? AND faculty_course.course_id = ?
        ");
        $authQuery->bind_param("ii", $faculty_id, $_SESSION['session_courseid']);
        $authQuery->execute();
        $authResult = $authQuery->get_result();
        $authRow = $authResult->fetch_assoc();

        // If the faculty is not authorized to delete the course, deny access
        if ($authRow['count'] == 0) {
            $_SESSION['delete_message'] = "You do not have permission to delete this course.";
            header("Location: course_delete_notice.php");
            exit;
        } else {
            $canDelete = true; // Faculty can delete this course
        }
    } else {
        $_SESSION['delete_message'] = "You cannot delete this course because it is not 'Unassigned' (status_id = 4).";
        header("Location: course_delete_notice.php");
        exit;
    }
} else {
    $_SESSION['delete_message'] = "You do not have permission to delete courses.";
    header("Location: course_delete_notice.php");
    exit;
}

// Prepare the statement 
$stmt = $conn->prepare("DELETE FROM course WHERE course_id=?");

// Retrieve course_id from session
$del_courseid = $_SESSION['session_courseid'];  // Get course_id from session

// Only proceed with deletion if the user has permission to delete
if ($canDelete) {
    // Sanitize the GET entry
    if (isset($_GET["course_id"])) {
        $del_courseid = htmlspecialchars($_GET["course_id"]);

        // Bind the parameters 
        $stmt->bind_param('i', $del_courseid);
        if ($stmt->execute()) {
            $_SESSION['delete_message'] = "Course deleted successfully.";
            header("Location: course_delete_notice.php");
            exit;
        } else {
            $_SESSION['delete_message'] = "Error executing DELETE query.";
            header("Location: course_delete_notice.php");
            exit;
        }
    } else {
        $_SESSION['delete_message'] = "No course ID provided.";
        header("Location: course_delete_notice.php");
        exit;
    }
} else {
    $_SESSION['delete_message'] = "You do not have permission to delete this course.";
    header("Location: course_delete_notice.php");
    exit;
}
?>
