<?php

// Include necessary files for session management and database connection
include 'csrf_protection.php';
include 'db_connection.php';

// Function to redirect with alert and a custom URL
function redirect($alert, $redirect)
{
    echo "<script>
            alert('$alert'); // Alert message
            window.location.href = '$redirect'; // Redirect to the given URL
        </script>";
    exit;
}


// Check if the user is authenticated , if not authenticated redirect to login page.
if (!isset($_SESSION['session_userid']) || !isset($_SESSION['session_roleid'])) {
    redirect('Unauthorized user. Redirecting To Login.', 'logout.php');
}

// Check if the user has a valid role (Admin or Faculty) , if not authenticated redirect to login page.
if ($_SESSION['session_roleid'] != 1 && $_SESSION['session_roleid'] != 2) {
    redirect('You Do Not Have Permission To Access This.', 'logout.php');
}

// Check for POST submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // CSRF check
    if (!isset($_POST['csrf_token']) || !validate_csrf_token($_POST['csrf_token'])) {
        regenerate_csrf_token();
        die("Invalid CSRF token. <a href='update_gradeform.php'>Try again</a>");
    }

    // Retrieve and validate POST values
    $student_course_grade_id = (int)$_POST['student_course_grade_id']; // SCG ID to update
    $student_id = (int)$_POST['student_id'];
    $course_id = (int)$_POST['course_id'];
    $grade_id = (int)$_POST['grade_id'];

    // Ensure all required fields are provided and valid
    if (empty($student_course_grade_id) || empty($student_id) || empty($course_id) || empty($grade_id)) {
        redirect('Cannot Update To Same Grade. Please Select A Different Grade.', 'display_grades.php?student_id=' . urlencode($student_id));
    }

    // Fetch the GPA points associated with the selected grade
    $gpa_query = "SELECT gpa_point FROM grade WHERE grade_id = ?";
    $stmt = $conn->prepare($gpa_query);
    $stmt->bind_param('i', $grade_id);  // Bind the grade ID parameter
    $stmt->execute();
    $gpa_result = $stmt->get_result();

    if ($gpa_result->num_rows > 0) {
        $gpa_data = $gpa_result->fetch_assoc(); // Fetch GPA point data
        $gpa_point = $gpa_data['gpa_point'];  // Extract the GPA point
    } else {
        // If no GPA point found, redirect
        redirect('Error Catching Grade Point. Please Try Again.', 'display_grades.php?student_id=' . urlencode($student_id));
    }

    // Update the student's grade in the database
    $update_query = "
        UPDATE student_course_grade 
        SET grade_id = ? 
        WHERE student_course_grade_id = ?
    ";

    $stmt = $conn->prepare($update_query);
    $stmt->bind_param('ii', $grade_id, $student_course_grade_id);

    // Execute the update and handle success or failure
    if ($stmt->execute()) {
        redirect('Grade Updated Successfully.', 'display_grades.php?student_id=' . urlencode($student_id)); // Successful update
        exit;
    } else {
        redirect('Error Updating Grade. Please Try Again.', 'display_grades.php?student_id=' . urlencode($student_id)); // Handle errors
    }

    // Close statement
    $stmt->close();
} else {
    // If the request is not a POST request, redirect to the grades page
    redirect('Invalid Request. Redirecting', 'grades.php');
}

// Close the connection
mysqli_close($conn);
