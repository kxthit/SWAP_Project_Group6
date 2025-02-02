<?php

// Include necessary files for session management and database connection
include 'csrf_protection.php';
include 'db_connection.php';


// Function to redirect with a message and a custom URL
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
    redirect('Unauthorized user. Redirecting To Login.', 'login.php');
}

// Check if the user has a valid role (Admin or Faculty) , if not authenticated redirect to login page.
if ($_SESSION['session_roleid'] != 1 && $_SESSION['session_roleid'] != 2) {
    redirect('You Do Not Have Permission To Access This.', 'login.php');
}

// Check for POST submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // CSRF check
    if (!isset($_POST['csrf_token']) || !validate_csrf_token($_POST['csrf_token'])) {
        regenerate_csrf_token();
        die("Invalid CSRF token. <a href='create_gradeform.php'>Try again</a>");
    }

    // Sanitize and validate inputs
    $student_id = filter_var($_POST['student_id'], FILTER_VALIDATE_INT);
    $course_id = filter_var($_POST['course_id'], FILTER_VALIDATE_INT);
    $method = filter_var($_POST['method'], FILTER_SANITIZE_STRING);

    // Check if student_id or course_id is invalid (false means validation failed)
    if ($student_id === false || $course_id === false) {
        // Invalid input, terminate script and show error message
        die('Invalid input. Please try again.');
    }

    // Check if the method is 'grade'
    if ($method == 'grade') {
        // Grade method selected, get the grade ID from the form
        $grade_id = (int)$_POST['grade_id'];
        $query = "INSERT INTO student_course_grade (student_id, course_id, grade_id) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('iii', $student_id, $course_id, $grade_id);

        // Check if the method is 'percentage'
    } elseif ($method == 'percentage') {
        // Percentage method selected, get the score from the form
        $score = (int)$_POST['score'];

        // Validate that a percentage score was provided
        if (empty($score)) {
            redirect('Percentage Is Required. Field Cannot Be Blank', 'create_gradeform.php?student_id=' . urlencode($student_id));
        }

        // Validate the percentage score range (0-100)
        if ($score < 0 || $score > 100) {
            redirect('Please enter a valid percentage between 0 and 100.', 'create_gradeform.php?student_id=' . urlencode($student_id));
        }

        // Fetch the grade based on the score range from the database
        $query = "SELECT grade_id FROM grade WHERE ? BETWEEN min_score AND max_score LIMIT 1";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('i', $score);
        $stmt->execute();
        $result = $stmt->get_result();

        // If a grade is found based on the score, insert it
        if ($result->num_rows > 0) {
            $grade = $result->fetch_assoc();
            $grade_id = $grade['grade_id'];

            // Insert the calculated grade into the student_course_grade table
            $query = "INSERT INTO student_course_grade (student_id, course_id, grade_id) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($query);
            $stmt->bind_param('iii', $student_id, $course_id, $grade_id);
        } else {
            // If no grade is found for the given score, redirect
            redirect('No Grade Found For Score.', 'display_grades.php?student_id=' . urlencode($student_id));
        }
    } else {
        // Invalid method selected (not 'grade' or 'percentage')
        redirect('Invalid Method.', 'display_grades.php?student_id=' . urlencode($student_id));
    }

    // Execute the prepared statement and insert the grade
    if ($stmt->execute()) {
        redirect('Grade Inserted Successfully.', 'display_grades.php?student_id=' . urlencode($student_id)); // Redirect to grades display page with success message
    } else {
        redirect('Error Inserting Grade. Please Try Again.', 'display_grades.php?student_id=' . urlencode($student_id)); // Handle query error and redirect
    }

    // Close the statement
    $stmt->close();
} else {
    // If the request is not a POST request, redirect to the grades page
    redirect('Invalid Request', 'grades.php');
}

// Close the connection
mysqli_close($conn);
