<?php
include('csrf_protection.php');
include('db_connection.php');

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!isset($_POST['csrf_token']) || !validate_csrf_token($_POST['csrf_token'])) {
        unset($_SESSION['csrf_token']);  // Remove the old token
        regenerate_csrf_token();  // Generate a fresh token
        $_SESSION['error_message'] = ["Invalid CSRF token. Please try again."];
        header("Location: course_update_form.php"); // Redirect instead of just dying
        exit;
    }

// Check authentication
if (!isset($_SESSION['session_userid']) || !isset($_SESSION['session_roleid'])) {
    echo "<h2>Unauthorized access. Please log in.</h2>";
    header('Refresh: 3; URL=login.php');
    exit;
}

// Check Admin or Faculty role
if ($_SESSION['session_roleid'] != 1 && $_SESSION['session_roleid'] != 2) {
    echo "<h2>You do not have permission to access this page.</h2>";
    // Check Admin or Faculty role
    
    if ($_SESSION['session_roleid'] == 2) {
    echo "<h2>You do not have permission to update courses with Admin privileges. In the 
    first place, how do you expect to update a course without any details to submit?</h2>";
    header('Refresh: 5; URL=courses.php');
    exit;
    }
}

// Start by getting the user_id from session
$user_id = $_SESSION['session_userid'];

// Initialize error array
$errors = [];

// If the return button was clicked, clear session data and redirect
if (isset($_POST['return_button'])) {
    unset($_SESSION['course_data']);
    unset($_SESSION['error_message']);
    header("Location: view_course.php");
    exit;
}

// Check if the form is submitted via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve submitted form data
    $upd_courseid = $_POST['course_id'];
    $upd_coursename = trim($_POST['upd_coursename']);
    $upd_coursecode = trim($_POST['upd_coursecode']);
    $upd_startdate = $_POST['upd_startdate'];
    $upd_enddate = $_POST['upd_enddate'];
    $upd_statusid = $_POST['upd_statusid'];
    $upd_departmentid = $_POST['upd_departmentid'];
    $upd_facultyid = $_POST['upd_facultyid'];

    // Store submitted form data in session to persist it on validation failure
    $_SESSION['course_data'] = $_POST;

    // === VALIDATION ===
    // Validate that all fields are filled
    if (empty($upd_coursename) || empty($upd_coursecode) || empty($upd_startdate) || empty($upd_enddate) || empty($upd_statusid) || empty($upd_departmentid) || empty($upd_facultyid)) {
        $_SESSION['error_message'] = "All fields are required.";
        header('Location: course_admin_update_form.php');
        exit;
    }

    // Validate course name length
    if (strlen($upd_coursename) >= 200) {
        $_SESSION['error_message'] = "Course name must be 200 characters max.";
        header('Location: course_admin_update_form.php');
        exit;
    }

    // Validate course code format (must be exactly 7 alphanumeric characters)
    if (!preg_match('/^[a-zA-Z0-9]{7}$/', $upd_coursecode)) {
        $_SESSION['error_message'] = "Course code must be exactly 7 alphanumeric characters.";
        header('Location: course_admin_update_form.php');
        exit;
    }

    // Validate start and end dates
    if ($upd_startdate >= $upd_enddate) {
        $_SESSION['error_message'] = "Start date must be earlier than end date.";
        header('Location: course_admin_update_form.php');
        exit;
    }

    // === DATABASE VALIDATION ===
    // Check if course name already exists (excluding the current course)
    $name_check_query = "SELECT * FROM course WHERE course_name = ? AND course_id != ?";
    $name_check_stmt = $conn->prepare($name_check_query);
    $name_check_stmt->bind_param('si', $upd_coursename, $upd_courseid);
    $name_check_stmt->execute();
    if ($name_check_stmt->get_result()->num_rows > 0) {
        $_SESSION['error_message'] = "A course with the same name already exists.";
        header('Location: course_admin_update_form.php');
        exit;
    }

    // Check if course code already exists (excluding the current course)
    $code_check_query = "SELECT * FROM course WHERE course_code = ? AND course_id != ?";
    $code_check_stmt = $conn->prepare($code_check_query);
    $code_check_stmt->bind_param('si', $upd_coursecode, $upd_courseid);
    $code_check_stmt->execute();
    if ($code_check_stmt->get_result()->num_rows > 0) {
        $_SESSION['error_message'] = "A course with the same course code already exists.";
        header('Location: course_admin_update_form.php');
        exit;
    }

    // === FACULTY-DEPARTMENT VALIDATION ===
    $faculty_department_query = "SELECT faculty_name, department_id FROM faculty WHERE faculty_id = ?";
    $faculty_department_stmt = $conn->prepare($faculty_department_query);
    $faculty_department_stmt->bind_param('i', $upd_facultyid);
    $faculty_department_stmt->execute();
    $faculty_department_result = $faculty_department_stmt->get_result();

    if ($faculty_department_result->num_rows > 0) {
        $faculty_data = $faculty_department_result->fetch_assoc();
        $faculty_name = $faculty_data['faculty_name'];
        $faculty_departmentid = $faculty_data['department_id'];

        // Fetch the department name for the faculty
        $faculty_department_name_query = "SELECT department_name FROM department WHERE department_id = ?";
        $faculty_department_name_stmt = $conn->prepare($faculty_department_name_query);
        $faculty_department_name_stmt->bind_param('i', $faculty_departmentid);
        $faculty_department_name_stmt->execute();
        $faculty_department_name_result = $faculty_department_name_stmt->get_result();
        
        if ($faculty_department_name_result->num_rows > 0) {
            $faculty_department_name_data = $faculty_department_name_result->fetch_assoc();
            $faculty_department_name = $faculty_department_name_data['department_name'];
        } else {
            $faculty_department_name = "Unknown Department";
        }

        // Fetch the department name the admin is trying to assign
        $assigned_department_query = "SELECT department_name FROM department WHERE department_id = ?";
        $assigned_department_stmt = $conn->prepare($assigned_department_query);
        $assigned_department_stmt->bind_param('i', $upd_departmentid);
        $assigned_department_stmt->execute();
        $assigned_department_result = $assigned_department_stmt->get_result();

        if ($assigned_department_result->num_rows > 0) {
            $assigned_department_data = $assigned_department_result->fetch_assoc();
            $assigned_department_name = $assigned_department_data['department_name'];
        } else {
            $assigned_department_name = "Unknown Department";
        }

        // Check if the faculty's department matches the department being assigned to the course
        if ($faculty_departmentid != $upd_departmentid) {
            $_SESSION['error_message'] = "$faculty_name can only be assigned to $faculty_department_name, not $assigned_department_name.";
            header('Location: course_admin_update_form.php');
            exit;
        }
    } else {
        $_SESSION['error_message'] = "Invalid Faculty ID.";
        header('Location: course_admin_update_form.php');
        exit;
    }

    // === UPDATE COURSE INTO DATABASE ===
    $update_course_query = "UPDATE course SET course_name = ?, course_code = ?, start_date = ?, end_date = ?, status_id = ?, department_id = ? WHERE course_id = ?";
    $update_course_stmt = $conn->prepare($update_course_query);
    $update_course_stmt->bind_param('ssssiii', $upd_coursename, $upd_coursecode, $upd_startdate, $upd_enddate, $upd_statusid, $upd_departmentid, $upd_courseid);
    $update_course_stmt->execute();

    // === FACULTY-COURSE RELATIONSHIP UPDATE ===
    $check_faculty_course_query = "SELECT * FROM faculty_course WHERE course_id = ? AND faculty_id = ?";
    $check_faculty_course_stmt = $conn->prepare($check_faculty_course_query);
    $check_faculty_course_stmt->bind_param('ii', $upd_courseid, $upd_facultyid);
    $check_faculty_course_stmt->execute();
    $check_faculty_course_result = $check_faculty_course_stmt->get_result();

    if ($check_faculty_course_result->num_rows > 0) {
        // Relationship exists, so update it
        $update_faculty_course_query = "UPDATE faculty_course SET faculty_id = ? WHERE course_id = ?";
        $update_faculty_course_stmt = $conn->prepare($update_faculty_course_query);
        $update_faculty_course_stmt->bind_param('ii', $upd_facultyid, $upd_courseid);
        $update_faculty_course_stmt->execute();
    } else {
        // Relationship doesn't exist, so insert a new record
        $insert_faculty_course_query = "INSERT INTO faculty_course (faculty_id, course_id) VALUES (?, ?)";
        $insert_faculty_course_stmt = $conn->prepare($insert_faculty_course_query);
        $insert_faculty_course_stmt->bind_param('ii', $upd_facultyid, $upd_courseid);
        $insert_faculty_course_stmt->execute();
    }

    // If any of the course or faculty updates were successful
    if ($update_course_stmt->affected_rows > 0 || $insert_faculty_course_stmt->affected_rows > 0 || $update_faculty_course_stmt->affected_rows > 0) {
        // Clear session data after success
        unset($_SESSION['course_data']);
        unset($_SESSION['error_message']);

        // Redirect to the courses page
        header("Location: courses.php");
        exit;
    } elseif($update_course_stmt->affected_rows == 0 && $insert_faculty_course_stmt->affected_rows == 0 && $update_faculty_course_stmt->affected_rows == 0) {
        // Clear session data after success
        unset($_SESSION['course_data']);
        unset($_SESSION['error_message']);

        // Redirect to the courses page
        header("Location: courses.php");{
        }
    }
}
}
mysqli_close($conn); ?>