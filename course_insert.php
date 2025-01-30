<?php
session_start();

include('db_connection.php');

// Check authentication
if (!isset($_SESSION['session_userid']) || !isset($_SESSION['session_roleid'])) {
    echo "<h2>Unauthorized access. Please log in.</h2>";
    header('Refresh: 3; URL=login.php');
    exit;
}

// Check Admin or Faculty role
if ($_SESSION['session_roleid'] != 1 && $_SESSION['session_roleid'] != 2) {
    echo "<h2>You do not have permission to access this page.</h2>";
    exit;
}
// Get the user_id from the session
$user_id = $_SESSION['session_userid'];

// Check if the user is Faculty or Admin
if ($_SESSION['session_roleid'] == 2) {
    // If Faculty, query the database to get the faculty_id associated with the logged-in user
    $get_faculty_id_query = "SELECT faculty_id FROM faculty WHERE user_id = ?";
    $get_faculty_id_stmt = $conn->prepare($get_faculty_id_query);
    $get_faculty_id_stmt->bind_param('i', $user_id);
    $get_faculty_id_stmt->execute();
    $get_faculty_id_result = $get_faculty_id_stmt->get_result();

    // Check if the faculty_id exists
    if ($get_faculty_id_result->num_rows > 0) {
        // Fetch the faculty_id for the logged-in user
        $faculty_data = $get_faculty_id_result->fetch_assoc();
        $faculty_id = $faculty_data['faculty_id'];
    } else {
        // If no faculty_id found, redirect or show an error message for Faculty
        $_SESSION['error_message'] = 'Faculty ID not found for the logged-in user.';
        header('Location: unauthorized.php');
        exit;
    }
} elseif ($_SESSION['session_roleid'] == 1) {
    // If Admin, no need to fetch faculty_id, just proceed with Admin logic
    // Admin can access all courses, so no check for faculty ID required
    $faculty_id = null;  // You can set this to null or ignore it as Admin doesn't need faculty_id
} else {
    // For any other roles (if you have any), handle accordingly
    $_SESSION['error_message'] = 'Unauthorized access.';
    header('Location: unauthorized.php');
    exit;
}

// Initialize errors array
$errors = [];

// Check if the form is submitted via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Retrieve the submitted form data
    $course_name = $_POST['course_name'];
    $course_code = $_POST['course_code'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $status_id = $_POST['status_id'];
    $department_id = $_POST['department_id'];

    // Store the form data in session for repopulation
    $_SESSION['course_data'] = $_POST;

    // Validate form fields
    if (empty($course_name)) {
        $errors[] = "Course name is required.";
    } elseif (strlen($course_name) > 100) {
        $errors[] = "Course name must be between 1 and 100 characters.";
    }

    if (strlen($course_code) !== 7) {
        $errors[] = "Course code must be exactly 7 characters.";
    }

    if ($start_date >= $end_date) {
        $errors[] = "Start date must be earlier than end date.";
    }

    if (empty($course_name) || empty($course_code) || empty($start_date) || empty($end_date) || empty($status_id) || empty($department_id)) {
        $errors[] = "All fields are required.";
    }

    // If there are validation errors, store them in the session and redirect back to the form
    if (!empty($errors)) {
        $_SESSION['error_message'] = $errors;  // Store errors in session
        header('Location: course_insertform.php');  // Redirect back to the form
        exit;
    }

    // Check if the course name already exists in the database
    $check_name_query = "SELECT * FROM course WHERE course_name = ?";
    $check_name_stmt = $conn->prepare($check_name_query);
    $check_name_stmt->bind_param('s', $course_name);
    $check_name_stmt->execute();
    $check_name_result = $check_name_stmt->get_result();

    if ($check_name_result->num_rows > 0) {
        $errors[] = "A course with the same name already exists.";
    }

    // Check if the course code already exists in the database
    $check_code_query = "SELECT * FROM course WHERE course_code = ?";
    $check_code_stmt = $conn->prepare($check_code_query);
    $check_code_stmt->bind_param('s', $course_code);
    $check_code_stmt->execute();
    $check_code_result = $check_code_stmt->get_result();

    if ($check_code_result->num_rows > 0) {
        $errors[] = "A course with the same course code already exists.";
    }

    // If there are any errors, store them in the session and redirect back to the form
    if (!empty($errors)) {
        $_SESSION['error_message'] = $errors;  // Store errors in session
        $_SESSION['course_data'] = $_POST;  // Store form data in session for repopulation
        header('Location: course_insertform.php');  // Redirect back to the form
        exit;
    }

    // If no errors, proceed with course insertion into the database
    $insert_query = "INSERT INTO course (course_name, course_code, start_date, end_date, status_id, department_id)
                     VALUES (?, ?, ?, ?, ?, ?)";
    $insert_stmt = $conn->prepare($insert_query);
    $insert_stmt->bind_param('ssssii', $course_name, $course_code, $start_date, $end_date, $status_id, $department_id);
    $insert_stmt->execute();

    // Get the last inserted course ID
    $course_id = $insert_stmt->insert_id;

    // Check if the insertion was successful
    if ($insert_stmt->affected_rows > 0) {
        // Insert into faculty_course table to establish the relationship
        $faculty_course_query = "INSERT INTO faculty_course (faculty_id, course_id) VALUES (?, ?)";
        $faculty_course_stmt = $conn->prepare($faculty_course_query);
        $faculty_course_stmt->bind_param('ii', $faculty_id, $course_id);
        $faculty_course_stmt->execute();

        if ($faculty_course_stmt->affected_rows > 0) {
            // Clear session data after successful insert
            unset($_SESSION['course_data']);
            unset($_SESSION['error_message']);

            // Redirect to the courses page or a success page
            header("Location: courses.php");
            exit;
        } else {
            echo "Error: Could not insert into faculty_course table.";
        }
    } else {
        // If something goes wrong, show an error message
        echo "Error: Could not insert course. Please try again.";
    }
}
?>