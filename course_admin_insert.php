<?php
include('session_management.php');
include('db_connection.php');

// Check if the admin is logged in
if (!isset($_SESSION['session_userid']) || $_SESSION['session_roleid'] != 1) {
    echo "<h2>Unauthorized access. Please log in as an Admin.</h2>";
    header('Refresh: 3; URL=login.php');
    exit;
}

// If the return button was clicked, clear session data and redirect
if (isset($_POST['return_button'])) {
    unset($_SESSION['course_data']); // Clear stored form data
    unset($_SESSION['error_message']); // Clear errors
    header("Location: view_course.php");
    exit;
}

// If form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve form data
    $course_name = trim($_POST['course_name']);
    $course_code = trim($_POST['course_code']);
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $status_id = $_POST['status_id'];
    $department_id = $_POST['department_id'];
    $faculty_id = $_POST['faculty_id'];

    // Store submitted form data in session
    $_SESSION['course_data'] = $_POST;

    // === VALIDATION (Stopping at the first error) ===
    if (empty($course_name) || empty($course_code) || empty($start_date) || empty($end_date) || empty($status_id) || empty($department_id) || empty($faculty_id)) {
        $_SESSION['error_message'] = "All fields are required.";
        header('Location: course_admin_insert_form.php');
        exit;
    }

    if (strlen($course_name) > 100) {
        $_SESSION['error_message'] = "Course name must be less than 100 characters.";
        header('Location: course_admin_insert_form.php');
        exit;
    }

    if (!preg_match('/^[a-zA-Z0-9]{7}$/', $course_code)) {
        $_SESSION['error_message'] = "Course code must be exactly 7 alphanumeric characters.";
        header('Location: course_admin_insert_form.php');
        exit;
    }

    if ($start_date >= $end_date) {
        $_SESSION['error_message'] = "Start date must be earlier than end date.";
        header('Location: course_admin_insert_form.php');
        exit;
    }

    // === DATABASE VALIDATION ===
    $check_name_query = "SELECT * FROM course WHERE course_name = ?";
    $check_name_stmt = $conn->prepare($check_name_query);
    $check_name_stmt->bind_param('s', $course_name);
    $check_name_stmt->execute();
    if ($check_name_stmt->get_result()->num_rows > 0) {
        $_SESSION['error_message'] = "A course with the same name already exists.";
        header('Location: course_admin_insert_form.php');
        exit;
    }

    $check_code_query = "SELECT * FROM course WHERE course_code = ?";
    $check_code_stmt = $conn->prepare($check_code_query);
    $check_code_stmt->bind_param('s', $course_code);
    $check_code_stmt->execute();
    if ($check_code_stmt->get_result()->num_rows > 0) {
        $_SESSION['error_message'] = "A course with the same course code already exists.";
        header('Location: course_admin_insert_form.php');
        exit;
    }

    // === FACULTY-DEPARTMENT VALIDATION ===
    $faculty_department_query = "SELECT faculty_name, department_id FROM faculty WHERE faculty_id = ?";
    $faculty_department_stmt = $conn->prepare($faculty_department_query);
    $faculty_department_stmt->bind_param('i', $faculty_id);
    $faculty_department_stmt->execute();
    $faculty_department_result = $faculty_department_stmt->get_result();

    if ($faculty_department_result->num_rows > 0) {
        $faculty_data = $faculty_department_result->fetch_assoc();
        $faculty_name = $faculty_data['faculty_name'];
        $faculty_department_id = $faculty_data['department_id'];

        // Fetch the actual department name
        $department_query = "SELECT department_name FROM department WHERE department_id = ?";
        $department_stmt = $conn->prepare($department_query);
        $department_stmt->bind_param('i', $faculty_department_id);
        $department_stmt->execute();
        $department_result = $department_stmt->get_result();
        
        if ($department_result->num_rows > 0) {
            $department_data = $department_result->fetch_assoc();
            $faculty_department_name = $department_data['department_name'];
        } else {
            $faculty_department_name = "Unknown Department";
        }

        // Fetch the department name the admin is trying to assign
        $assigned_department_query = "SELECT department_name FROM department WHERE department_id = ?";
        $assigned_department_stmt = $conn->prepare($assigned_department_query);
        $assigned_department_stmt->bind_param('i', $department_id);
        $assigned_department_stmt->execute();
        $assigned_department_result = $assigned_department_stmt->get_result();

        if ($assigned_department_result->num_rows > 0) {
            $assigned_department_data = $assigned_department_result->fetch_assoc();
            $assigned_department_name = $assigned_department_data['department_name'];
        } else {
            $assigned_department_name = "Unknown Department";
        }

        if ($faculty_department_id != $department_id) {
            $_SESSION['error_message'] = "$faculty_name can only be assigned to $faculty_department_name, not $assigned_department_name.";
            header('Location: course_admin_insert_form.php');
            exit;
        }
    } else {
        $_SESSION['error_message'] = "Invalid Faculty ID.";
        header('Location: course_admin_insert_form.php');
        exit;
    }

    // === INSERT COURSE INTO DATABASE ===
    $insert_query = "INSERT INTO course (course_name, course_code, start_date, end_date, status_id, department_id)
                     VALUES (?, ?, ?, ?, ?, ?)";
    $insert_stmt = $conn->prepare($insert_query);
    $insert_stmt->bind_param('ssssii', $course_name, $course_code, $start_date, $end_date, $status_id, $department_id);
    $insert_stmt->execute();

    if ($insert_stmt->affected_rows > 0) {
        $course_id = $insert_stmt->insert_id;

        // Insert into faculty_course table to establish faculty-course relationship
        $faculty_course_query = "INSERT INTO faculty_course (faculty_id, course_id) VALUES (?, ?)";
        $faculty_course_stmt = $conn->prepare($faculty_course_query);
        $faculty_course_stmt->bind_param('ii', $faculty_id, $course_id);
        $faculty_course_stmt->execute();

        if ($faculty_course_stmt->affected_rows > 0) {
            // Clear session data after success
            unset($_SESSION['course_data']);
            unset($_SESSION['error_message']);

            // Redirect to the courses page
            header("Location: courses.php");
            exit;
        } else {
            $_SESSION['error_message'] = "Error: Could not assign faculty to course.";
            header('Location: course_admin_insert_form.php');
            exit;
        }
    } else {
        $_SESSION['error_message'] = "Error: Could not insert course. Please try again.";
        header('Location: course_admin_insert_form.php');
        exit;
    }
}
?>
