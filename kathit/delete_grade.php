<?php
session_start();

// Check if the user is authenticated
if (!isset($_SESSION['session_userid']) || !isset($_SESSION['session_role'])) {
    echo "<h2>Unauthorized access. Please log in.</h2>";
    header('Refresh: 3; URL=login.php');
    exit;
}

// Check if the user is an Admin (role_id 1) - only admins can delete
if ($_SESSION['session_role'] != 1) {
    echo "<h2>You do not have permission to delete records. Only admins can perform this action.</h2>";
    exit;
}

// Include database connection
include 'db_connection.php';

// Check if the grade ID is passed in the URL
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $id = (int)$_GET['id'];  // Ensure it's an integer

    // Validate ID (ensure it is a positive number)
    if ($id <= 0) {
        echo "<p>Invalid grade ID. Please try again.</p>";
        exit;
    }

    // Check if the grade exists before attempting to delete
    $check_query = "SELECT * FROM student_course_grade WHERE student_course_grade_id = ?";
    $stmt = $conn->prepare($check_query);
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $student_id = $row['student_id'];  // Get the student ID
        $course_id = $row['course_id'];    // Get the course ID

        // Fetch the status_id of the course to check if it has ended
        $course_query = "SELECT status_id FROM course WHERE course_id = ?";
        $course_stmt = $conn->prepare($course_query);
        $course_stmt->bind_param('i', $course_id);
        $course_stmt->execute();
        $course_stmt->store_result();
        $course_stmt->bind_result($status_id);
        $course_stmt->fetch();

        // Check if the course has ended (status_id = 3)
        if ($status_id != 3) {
            echo "<p>Error: You cannot delete grades for courses that are still ongoing.</p>";
            exit;
        }

        // Confirm deletion
        if (isset($_POST['confirm_delete']) && $_POST['confirm_delete'] == 'yes') {
            // Proceed with deletion
            $delete_query = "DELETE FROM student_course_grade WHERE student_course_grade_id = ?";
            $delete_stmt = $conn->prepare($delete_query);
            $delete_stmt->bind_param('i', $id);

            if ($delete_stmt->execute()) {
                echo "<p>Grade deleted successfully!</p>";
                // Redirect to display grades page with student_id
                header("Refresh: 3; URL=display_grades.php?student_id=" . $student_id);
            } else {
                echo "<p>Error deleting grade. Please try again.</p>";
            }

            $delete_stmt->close();
        } else {
            // Display confirmation form
            echo "<p>Are you sure you want to delete this grade?</p>";
            echo "<form method='POST'>
                    <button type='submit' name='confirm_delete' value='yes'>Yes</button>
                    <a href='display_grades.php?student_id=" . $student_id . "'>No</a>
                  </form>";
        }
    } else {
        echo "<p>Grade not found.</p>";
    }

    $stmt->close();
} else {
    echo "<p>No grade ID specified. Unable to delete grade.</p>";
}

// Close the database connection
mysqli_close($conn);
