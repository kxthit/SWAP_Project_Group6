<?php
session_start();

// Check if the user is authenticated
if (!isset($_SESSION['session_userid']) || !isset($_SESSION['session_role'])) {
    echo "<script>
            alert('Unauthorized user. Redirecting To Login.');
            window.location.href = 'login.php'; 
          </script>";
    exit;
}

// Include database connection
include 'db_connection.php';

// Check if the grade ID is passed in the URL
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $id = (int)$_GET['id'];  // Ensure it's an integer

    // Validate ID (ensure it is a positive number)
    if ($id <= 0) {
        echo "<script>
                alert('Invalid grade ID.');
                window.location.href = 'display_grades.php?student_id=" . $student_id . "';
            </script>";
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

        // Check if the user is an Admin (role_id 1) - only admins can delete
        if ($_SESSION['session_role'] == 1) {
            // Admins can delete grades for any course, no further checks
        } elseif ($_SESSION['session_role'] == 2) {
            // Faculty check: Fetch the status_id of the course to check if it is status '4'
            $course_query = "SELECT status_id FROM course WHERE course_id = ?";
            $course_stmt = $conn->prepare($course_query);
            $course_stmt->bind_param('i', $course_id);
            $course_stmt->execute();
            $course_stmt->store_result();
            $course_stmt->bind_result($status_id);
            $course_stmt->fetch();

            // Check if the course has the required status (status_id = 4)
            if ($status_id != 4) {
                echo "<script>
                        alert('You Cannot Delete Grade For This Course.');
                        window.location.href = 'display_grades.php?student_id=" . $student_id . "';
                    </script>";
                exit;
            }
        } else {
            // Invalid role, prevent deletion
            echo "<script>
                    alert('Invalid Permission. You Cannot Delete This.');
                    window.location.href = 'display_grades.php?student_id=" . $student_id . "'; // Redirect to grades
                </script>";
            exit;
        }

        // Handle delete confirmation via URL parameter
        if (isset($_GET['confirm']) && $_GET['confirm'] == 'yes') {
            // Proceed with deletion
            $delete_query = "DELETE FROM student_course_grade WHERE student_course_grade_id = ?";
            $delete_stmt = $conn->prepare($delete_query);
            $delete_stmt->bind_param('i', $id);

            if ($delete_stmt->execute()) {
                echo "<script>
                        alert('Grade Deleted Successfully.');
                        window.location.href = 'display_grades.php?student_id=" . $student_id . "';
                      </script>";
            } else {
                echo "<script>
                        alert('Error Deleting Grade. Please Try Again.');
                        window.location.href = 'display_grades.php?student_id=" . $student_id . "';
                      </script>";
            }

            $delete_stmt->close();
        } else {
            // Show JavaScript confirmation
            echo "<script>
                    if (confirm('Are You Sure You Want To Delete This Grade?')) {
                        window.location.href = 'delete_grade.php?id=$id&confirm=yes';
                    } else {
                        window.location.href = 'display_grades.php?student_id=$student_id';
                    }
                  </script>";
        }
    } else {
        echo "<script>
                alert('Grade not found.');
                window.location.href = 'display_grades.php?student_id=" . $student_id . "';
            </script>";
    }

    $stmt->close();
} else {
    echo "<script>
                alert('Grade not found.');
                window.location.href = 'display_grades.php?student_id=" . $student_id . "';
            </script>";
}

// Close the database connection
mysqli_close($conn);
