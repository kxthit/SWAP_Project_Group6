<?php

// Include necessary files for session management and database connection
include 'csrf_protection.php';
include 'db_connection.php';

// Function to redirect with an alert and a custom URL
function redirect($alert, $redirect)
{
    echo "<script>
            alert('$alert'); // Alert message
            window.location.href = '$redirect'; // Redirect to the given URL
        </script>";
    exit;
}

// Only allow Admins to delete grades
if ($_SESSION['session_roleid'] != 1) {
    redirect('Unauthorized Action. You cannot delete grades.', 'grades.php');
    exit;
}


// Check if the user is authenticated
if (!isset($_SESSION['session_userid']) || !isset($_SESSION['session_roleid'])) {
    redirect('Unauthorized user. Redirecting to login.', 'login.php');
}

// Check for POST submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // CSRF check
    if (!isset($_POST['csrf_token']) || !validate_csrf_token($_POST['csrf_token'])) {
        regenerate_csrf_token();
        die("Invalid CSRF token. <a href='grades.php'>Try again</a>");
    }

    // Check if the Student Course Grade ID is passed via POST request
    if (isset($_POST['student_course_grade_id']) && !empty($_POST['student_course_grade_id'])) {

        // Validate that the passed ID is a valid integer
        $id = filter_var($_POST['student_course_grade_id'], FILTER_VALIDATE_INT);

        if ($id === false || $id <= 0) {
            redirect('Invalid SCG ID.', 'grades.php');
        }

        // Check if the user is a faculty member (role ID 2) and restrict them from deleting grades
        if ($_SESSION['session_roleid'] == 2) {
            redirect('Unauthorized Action. Faculty cannot delete grades.', 'grades.php');
        }

        // Validate grade record existence by checking if the grade ID exists in the database
        $check_query = "SELECT student_id, course_id FROM student_course_grade WHERE student_course_grade_id = ?";
        $stmt = $conn->prepare($check_query);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();

        // If a record exists, fetch the details
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $student_id = $row['student_id'];
            $course_id = $row['course_id'];

            // Check the course status to ensure it has ended (status_id = 3 indicates course is finished)
            $course_query = "SELECT status_id FROM course WHERE course_id = ?";
            $course_stmt = $conn->prepare($course_query);
            $course_stmt->bind_param('i', $course_id);
            $course_stmt->execute();
            $course_stmt->store_result();
            $course_stmt->bind_result($status_id);
            $course_stmt->fetch();

            // If the course hasn't ended, prevent grade deletion and show an error message
            if ($status_id != 3) {
                redirect('Unable to delete grade. Course has not ended.', 'display_grades.php?student_id=' . urlencode($student_id));
            }

            // Proceed with grade deletion if all checks pass
            $delete_query = "DELETE FROM student_course_grade WHERE student_course_grade_id = ?";
            $delete_stmt = $conn->prepare($delete_query);
            $delete_stmt->bind_param('i', $id);

            // Execute deletion and redirect based on the outcome
            if ($delete_stmt->execute()) {
                redirect('Grade deleted successfully.', 'display_grades.php?student_id=' . urlencode($student_id));
            } else {
                redirect('Error deleting grade. Please try again.', 'display_grades.php?student_id=' . urlencode($student_id));
            }

            $delete_stmt->close();
        } else {
            redirect('Grade record not found.', 'grades.php');
        }

        $stmt->close();
    } else {
        redirect('Invalid or missing SCG ID.', 'grades.php');
    }
}

// Close the connection
mysqli_close($conn);
