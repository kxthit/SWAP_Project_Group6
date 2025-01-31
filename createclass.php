<?php
include 'db_connection.php';
session_start();

// Check if the user is authenticated
if (!isset($_SESSION['session_userid']) || !isset($_SESSION['session_roleid'])) {
    echo "<h2>Unauthorized access. Please log in.</h2>";
    header('Refresh: 3; URL=login.php');
    exit;
}

$user_id = $_SESSION['session_userid'];
$role_id = $_SESSION['session_roleid'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $class_id = $_POST['class_id']; // Pre-generated from form
    $class_name = $_POST['class_name'];
    $class_type = $_POST['class_type'];
    $course_id = $_POST['course_id'];

    // Admin sets faculty_id, faculty cannot
    if ($role_id == 1) {
        $faculty_id = $_POST['faculty_id'];
    } else {
        // Get faculty ID for logged-in faculty user
        $faculty_query = "SELECT faculty_id FROM faculty WHERE user_id = ?";
        $faculty_stmt = $conn->prepare($faculty_query);
        $faculty_stmt->bind_param('i', $user_id);
        $faculty_stmt->execute();
        $faculty_result = $faculty_stmt->get_result();
        $faculty_id = $faculty_result->fetch_assoc()['faculty_id'];
    }

    // Validation: Check for duplicate class name
    $duplicate_check_query = "SELECT * FROM class WHERE class_name = ?";
    $duplicate_check_stmt = $conn->prepare($duplicate_check_query);
    $duplicate_check_stmt->bind_param('s', $class_name);
    $duplicate_check_stmt->execute();
    $duplicate_check_result = $duplicate_check_stmt->get_result();

    if ($duplicate_check_result->num_rows > 0) {
        echo "<script>alert('Class Name already exists.'); window.history.back();</script>";
        exit;
    }

    // Validation: Ensure faculty and course belong to the same department
    $validation_query = "
        SELECT 
            c.department_id AS course_dept, 
            f.department_id AS faculty_dept 
        FROM course c 
        INNER JOIN faculty f ON f.faculty_id = ? 
        WHERE c.course_id = ?
    ";
    $validation_stmt = $conn->prepare($validation_query);
    $validation_stmt->bind_param('ii', $faculty_id, $course_id);
    $validation_stmt->execute();
    $validation_result = $validation_stmt->get_result();
    $validation_data = $validation_result->fetch_assoc();

    if ($validation_data['course_dept'] != $validation_data['faculty_dept']) {
        echo "<script>alert('Course and Faculty must belong to the same department.'); window.history.back();</script>";
        exit;
    }

    // Insert the new class
    $insert_query = "
        INSERT INTO class (class_id, class_name, class_type, course_id, faculty_id) 
        VALUES (?, ?, ?, ?, ?)
    ";
    $insert_stmt = $conn->prepare($insert_query);
    $insert_stmt->bind_param('issii', $class_id, $class_name, $class_type, $course_id, $faculty_id);

    if ($insert_stmt->execute()) {
        echo "<script>alert('Class created successfully!'); window.location.href = 'classes.php';</script>";
    } else {
        echo "<script>alert('Error creating class.'); window.history.back();</script>";
    }
}
?>
