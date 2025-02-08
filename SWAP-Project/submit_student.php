<?php

// Include required files
include 'db_connection.php';
include 'csrf_protecttion.php';

$error_message="";
$error_message_logout="";

// Check if the user is authenticated
if (!isset($_SESSION['session_userid']) || !isset($_SESSION['session_roleid'])) {
    $error_message_logout="Session expired or unauthorized access. Please log in.";
}

// Authorization: Check user role (e.g., only admins or faculty can submit students)
if ($_SESSION['session_roleid'] != 1 && $_SESSION['session_roleid'] != 2) {
    $error_message_logout= "Unauthorized access. You do not have permission to perform this action.";
}

// Validate CSRF token
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token'])) {
    $error_message="Invalid CSRF token. Please reload the page and try again.";
}

// Handle final submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['student_data'], $_SESSION['selected_courses'], $_SESSION['selected_classes'])) {
        $error_message="Session data missing. Please restart the registration process.";
    }

    $student_data = $_SESSION['student_data'];
    $selected_courses = $_SESSION['selected_courses'];
    $selected_classes = $_SESSION['selected_classes'];

    // Validate student data
    if (!preg_match('/^\d{7}[A-Z]$/', $student_data['admission_number'])) {
        die('Invalid admission number.');
    }
    if (!filter_var($student_data['student_email'], FILTER_VALIDATE_EMAIL)) {
        die('Invalid email address.');
    }
    if (!ctype_digit($student_data['student_phone']) || strlen($student_data['student_phone']) !== 8) {
        die('Invalid phone number.');
    }

    try {
        // Begin transaction
        $conn->begin_transaction();

        // Hash the password
        $hashed_password = password_hash($student_data['hashed_password'], PASSWORD_DEFAULT);

        // Insert into user table
        $role_id = 3;
        $stmt = $conn->prepare("INSERT INTO user (admission_number, hashed_password, role_id) VALUES (?, ?, ?)");
        $stmt->bind_param("ssi", $student_data['admission_number'], $hashed_password, $role_id);
        $stmt->execute();
        $user_id = $conn->insert_id;

        // Insert into student table
        $stmt = $conn->prepare("INSERT INTO student (student_name, student_email, student_phone, user_id, department_id) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssi", $student_data['student_name'], $student_data['student_email'], $student_data['student_phone'], $user_id, $student_data['department_id']);
        $stmt->execute();
        $student_id = $conn->insert_id;

        // Map student to courses
        $stmt = $conn->prepare("INSERT INTO student_course (student_id, course_id) VALUES (?, ?)");
        foreach ($selected_courses as $course_id) {
            $stmt->bind_param("ii", $student_id, $course_id);
            $stmt->execute();
        }

        // Map student to classes
        $stmt = $conn->prepare("INSERT INTO student_class (student_id, class_id) VALUES (?, ?)");
        foreach ($selected_classes as $course_id => $class_id) {
            $stmt->bind_param("ii", $student_id, $class_id);
            $stmt->execute();
        }

        // Commit the transaction
        $conn->commit();

        header('Location: student.php');
        exit;
    } catch (Exception $e) {
        $conn->rollback();
        echo '<h2>An error occurred during submission</h2>';
        echo '<p>Error details: ' . htmlspecialchars($e->getMessage()) . '</p>';
        exit;
    }
}

?>
