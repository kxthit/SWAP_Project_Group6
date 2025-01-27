<?php
session_start();

// Check if the user is authenticated
if (!isset($_SESSION['session_userid']) || !isset($_SESSION['session_role'])) {
    echo "<script>
            alert('Unauthorized user. Redirecting To Login.');
            window.location.href = 'login.php'; // Redirect to homepage or any page you want
          </script>";
    exit;
}

// Check if the user is either Admin or Faculty (role_id 1 or 2)
if ($_SESSION['session_role'] != 1 && $_SESSION['session_role'] != 2) {
    echo "<script>
            alert('You Do Not Have Permission To Access This.');
            window.location.href = 'login.php'; // Redirect to login
        </script>";
    exit;
}

// Include database connection
include 'db_connection.php';

// Check if form data is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get POST values
    $student_course_grade_id = (int)$_POST['student_course_grade_id']; // Grade ID to update
    $student_id = (int)$_POST['student_id'];
    $course_id = (int)$_POST['course_id'];
    $grade_id = (int)$_POST['grade_id'];

    // Validate data (check if values are valid integers)
    if (empty($student_course_grade_id) || empty($student_id) || empty($course_id) || empty($grade_id)) {
        echo "<script>
                alert('Cannot Update To Same Grade. Please Select A Different Grade.');
                window.location.href = 'display_grades.php?student_id=" . $student_id . "'; // Redirect to grades
            </script>";
        exit;
    }

    // Fetch the GPA points associated with the selected grade
    $gpa_query = "SELECT gpa_point FROM grade WHERE grade_id = ?";
    $stmt = $conn->prepare($gpa_query);
    $stmt->bind_param('i', $grade_id);
    $stmt->execute();
    $gpa_result = $stmt->get_result();

    if ($gpa_result->num_rows > 0) {
        $gpa_data = $gpa_result->fetch_assoc();
        $gpa_point = $gpa_data['gpa_point'];
    } else {
        echo "<script>
                alert('Error Catching Grade Point. Please Try Again.');
                window.location.href = 'display_grades.php?student_id=" . $student_id . "'; // Redirect to grades
            </script>";
        exit;
    }

    // Update the student's grade in the database
    $update_query = "
        UPDATE student_course_grade 
        SET grade_id = ? 
        WHERE student_course_grade_id = ?
    ";

    $stmt = $conn->prepare($update_query);
    $stmt->bind_param('ii', $grade_id, $student_course_grade_id);

    if ($stmt->execute()) {
        echo "<script>
                alert('Grade Updated Successfully.');
                window.location.href = 'display_grades.php?student_id=" . $student_id . "'; // Redirect to grades
            </script>";
        exit;
    } else {
        echo "<script>
                alert('Error Updating Grade. Please Try Again.');
                window.location.href = 'display_grades.php?student_id=" . $student_id . "'; // Redirect to grades
            </script>";
    }

    // Close statement
    $stmt->close();
} else {
    echo "<script>
            alert('Invalid Request.');
            window.location.href = 'grades.php'; // Redirect to login
        </script>";
}

// Close database connection
mysqli_close($conn);
