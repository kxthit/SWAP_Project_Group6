<?php
include'csrf_protection.php';
include('db_connection.php');

// If the return button was clicked, clear session data and redirect
if (isset($_POST['return_button'])) {
    unset($_SESSION['course_data']); // Clear session data
    header("Location: view_course.php");
    exit;
}

// Check if the user is authenticated
if (!isset($_SESSION['session_userid']) || !isset($_SESSION['session_roleid'])) {
    echo "<h2>Unauthorized access. Please log in.</h2>";
    header('Refresh: 3; URL=login.php');
    exit;
}

// Check if the user is either Admin or Faculty (role_id 1 or 2)
if ($_SESSION['session_roleid'] != 1 && $_SESSION['session_roleid'] != 2) {
    echo "<h2>You do not have permission to access this page.</h2>";
    header('Refresh: 3; URL=login.php');
    exit;
}

// Check if the user is either Admin or Faculty (role_id 1 or 2)
if ($_SESSION['session_roleid'] == 1) {
    echo "<h2>Please use the Admin Update Form.</h2>";
    header('Refresh: 3; URL=courses.php');
    exit;
}

// Validate course_id from session
if (!isset($_SESSION['session_courseid']) || empty($_SESSION['session_courseid'])) {
    echo "<h2>Invalid Request. Course ID is missing.</h2>";
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!isset($_POST['csrf_token']) || !validate_csrf_token($_POST['csrf_token'])) {
        unset($_SESSION['csrf_token']);  // Remove the old token
        regenerate_csrf_token();  // Generate a fresh token
        $_SESSION['error_message'] = ["Invalid CSRF token. Please try again."];
        header("Location: course_update_form.php"); // Redirect instead of just dying
        exit;
    }

$upd_courseid = $_SESSION['session_courseid']; // Use the course_id from the session

// Fetch existing course data
$stmt = $conn->prepare("SELECT * FROM course WHERE course_id=?");
$stmt->bind_param('i', $upd_courseid);
$stmt->execute();
$result = $stmt->get_result();
$existing_course = $result->fetch_assoc();

// Sanitize and retrieve form input
$upd_coursename = htmlspecialchars($_POST["upd_coursename"]);
$upd_coursecode = htmlspecialchars($_POST["upd_coursecode"]);
$upd_startdate = htmlspecialchars($_POST["upd_startdate"]);
$upd_enddate = htmlspecialchars($_POST["upd_enddate"]);
$upd_statusid = htmlspecialchars($_POST["upd_statusid"]);
$upd_departmentid = htmlspecialchars($_POST["upd_departmentid"]);

// Input validation
$errors = [];
if (strlen($upd_coursename) < 1 || strlen($upd_coursename) > 200) {
    $errors[] = "Course name must be between 1 and 200 characters.";
}

if (strlen($upd_coursecode) != 7) {
    $errors[] = "Course code must be exactly 7 characters.";
}

if (strtotime($upd_startdate) >= strtotime($upd_enddate)) {
    $errors[] = "Start date must be earlier than end date.";
}

if (empty($upd_coursename) || empty($upd_coursecode) || empty($upd_startdate) || empty($upd_enddate) || empty($upd_statusid) || empty($upd_departmentid)) {
    $errors[] = "All fields are required.";
}

// If there are errors, store them in the session and redirect back to the form
if (!empty($errors)) {
    $_SESSION['error_message'] = $errors;
    $_SESSION['course_data'] = $_POST;  // Store user-submitted data
    header('Location: course_update_form.php');
    exit;
}

// Check if the course name already exists (excluding the current course)
$name_check_query = "SELECT * FROM course WHERE course_name = ? AND course_id != ?";
$name_check_stmt = $conn->prepare($name_check_query);
$name_check_stmt->bind_param('si', $upd_coursename, $upd_courseid);
$name_check_stmt->execute();
$name_check_result = $name_check_stmt->get_result();

// If there is any existing course with the same name, return an error
if ($name_check_result->num_rows > 0) {
    $errors[] = "A course with the same name already exists.";
}

// Check if the course code already exists (excluding the current course)
$code_check_query = "SELECT * FROM course WHERE course_code = ? AND course_id != ?";
$code_check_stmt = $conn->prepare($code_check_query);
$code_check_stmt->bind_param('si', $upd_coursecode, $upd_courseid);
$code_check_stmt->execute();
$code_check_result = $code_check_stmt->get_result();

// If there is any existing course with the same code, return an error
if ($code_check_result->num_rows > 0) {
    $errors[] = "A course with the same course code already exists.";
}

// Check if the course data has actually been updated
$updated = false;

if ($upd_coursename !== $existing_course['course_name']) {
    $updated = true;
}

if ($upd_coursecode !== $existing_course['course_code']) {
    $updated = true;
}

if ($upd_startdate !== $existing_course['start_date']) {
    $updated = true;
}

if ($upd_enddate !== $existing_course['end_date']) {
    $updated = true;
}

if ($upd_statusid !== $existing_course['status_id']) {
    $updated = true;
}

if ($upd_departmentid !== $existing_course['department_id']) {
    $updated = true;
}

// If no field was updated, set a session flag
if (!$updated) {
    $_SESSION['no_update'] = true;
    header("Location: course_update_form.php"); // Redirect back to the form with no update message
    exit;
}

// Prepare and bind the update query
$query = $conn->prepare("UPDATE course SET course_name=?, course_code=?, start_date=?, end_date=?, status_id=?, department_id=? WHERE course_id=?");
$query->bind_param('ssssiii', $upd_coursename, $upd_coursecode, $upd_startdate, $upd_enddate, $upd_statusid, $upd_departmentid, $upd_courseid);

// Execute the query
if ($query->execute()) {
    unset($_SESSION['course_data']); // Clear the form data
    header("location:courses.php"); // Redirect to course list
    exit;
} else {
    echo "Error executing UPDATE query.";
}
mysqli_close($conn);
} ?>