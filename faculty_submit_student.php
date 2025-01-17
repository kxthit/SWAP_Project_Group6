<?php
session_start();

// Check if the user is authenticated
if (!isset($_SESSION['session_userid']) || !isset($_SESSION['session_role'])) {
    echo "<h2>Unauthorized access. Please log in.</h2>";
    header('Refresh: 3; URL=login.php');
    exit;
}

// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include database connection
include 'db_connection.php';
if (!$conn) {
    die("Failed to connect to database: " . mysqli_connect_error());
}

// Handle final submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ensure session contains the required data
    if (!isset($_SESSION['student_data'], $_SESSION['selected_courses'], $_SESSION['selected_classes'])) {
        echo '<h2>Session data missing. Please restart the registration process.</h2>';
        var_dump($_SESSION);
        exit;
    }

    $student_data = $_SESSION['student_data'];
    $selected_courses = $_SESSION['selected_courses'];
    $selected_classes = $_SESSION['selected_classes'];

    try {
        // Begin transaction
        $conn->begin_transaction();

        // Debugging: Verify the content of student data
        echo '<h3>Debug: Student Data</h3>';
        var_dump($student_data);

        // Insert into user table
        $hashed_password = password_hash($student_data['hashed_password'], PASSWORD_DEFAULT);
        $role_id = 3; // Ensure this role exists in the database
        $stmt = $conn->prepare("INSERT INTO user (admission_number, hashed_password, role_id) VALUES (?, ?, ?)");
        $stmt->bind_param("ssi", $student_data['admission_number'], $hashed_password, $role_id);
        if (!$stmt->execute()) {
            throw new Exception("User table error: " . $stmt->error);
        }
        $user_id = $conn->insert_id;

        // Debugging: Confirm user ID
        echo "<h3>Debug: User ID</h3><p>{$user_id}</p>";

        // Insert into student table
        $stmt = $conn->prepare("INSERT INTO student (student_name, student_email, student_phone, user_id, department_id) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssi", $student_data['student_name'], $student_data['student_email'], $student_data['student_phone'], $user_id, $student_data['department_id']);
        if (!$stmt->execute()) {
            throw new Exception("Student table error: " . $stmt->error);
        }
        $student_id = $conn->insert_id;

        // Debugging: Confirm student ID
        echo "<h3>Debug: Student ID</h3><p>{$student_id}</p>";

        // Map student to courses
        $stmt = $conn->prepare("INSERT INTO student_course (student_id, course_id) VALUES (?, ?)");
        foreach ($selected_courses as $course_id) {
            $stmt->bind_param("ii", $student_id, $course_id);
            if (!$stmt->execute()) {
                throw new Exception("Student-course mapping error: " . $stmt->error);
            }
        }

        // Map student to classes
        $stmt = $conn->prepare("INSERT INTO student_class (student_id, class_id) VALUES (?, ?)");
        foreach ($selected_classes as $course_id => $class_id) {
            $stmt->bind_param("ii", $student_id, $class_id);
            if (!$stmt->execute()) {
                throw new Exception("Student-class mapping error: " . $stmt->error);
            }
        }

        // Commit the transaction
        $conn->commit();


        header('Location: faculty_student.php');
        exit;
    } catch (Exception $e) {
        // Rollback on error
        $conn->rollback();
        echo '<h2>An error occurred during submission</h2>';
        echo '<p>Error details: ' . htmlspecialchars($e->getMessage()) . '</p>';
        exit;
    }
}

// Debugging: If script reaches here, it wasn't a POST request
echo '<h2>Invalid request method. Please use the form to submit data.</h2>';
exit;


?>
