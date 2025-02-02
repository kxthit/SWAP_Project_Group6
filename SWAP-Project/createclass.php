<?php
include 'csrf_protection.php';
include_once 'db_connection.php';

// Check if the user is authenticated
if (!isset($_SESSION['session_userid']) || !isset($_SESSION['session_roleid'])) {
    echo "<h2>Unauthorized access. Please log in.</h2>";
    header('Refresh: 3; URL=login.php');
    exit;
}

// Check for POST submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // CSRF check
    if (!isset($_POST['csrf_token']) || !validate_csrf_token($_POST['csrf_token'])) {
        regenerate_csrf_token();
        die("Invalid CSRF token. <a href='form.php'>Try again</a>");
    }

$user_id = $_SESSION['session_userid'];
$role_id = $_SESSION['session_roleid'];

// Validate CSRF token
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    die("CSRF token validation failed.");
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $class_id = intval($_POST['class_id']); // Auto-generated from the form
    $class_name = trim($_POST['class_name']);
    $class_type = trim($_POST['class_type']);
    $course_id = intval($_POST['course_id']);

    // Input Validation
    if (!preg_match('/^[a-zA-Z0-9\s]+$/', $class_name)) {
        echo "<script>alert('Invalid class name. Only alphanumeric characters and spaces are allowed.'); window.history.back();</script>";
        exit;
    }

    if (!in_array($class_type, ['Semester-Based', 'Term-Based'])) {
        echo "<script>alert('Invalid class type.'); window.history.back();</script>";
        exit;
    }

    // Admin assigns faculty, faculty gets auto-assigned
    if ($role_id == 1) {
        $faculty_id = intval($_POST['faculty_id']);
    } else {
        $faculty_query = "SELECT faculty_id FROM faculty WHERE user_id = ?";
        $faculty_stmt = $conn->prepare($faculty_query);
        $faculty_stmt->bind_param('i', $user_id);
        $faculty_stmt->execute();
        $faculty_result = $faculty_stmt->get_result();
        $faculty_data = $faculty_result->fetch_assoc();

        if (!$faculty_data) {
            echo "<script>alert('Faculty record not found.'); window.history.back();</script>";
            exit;
        }

        $faculty_id = $faculty_data['faculty_id'];
    }

    // Check for duplicate class name
    $duplicate_check_query = "SELECT COUNT(*) FROM class WHERE class_name = ?";
    $duplicate_check_stmt = $conn->prepare($duplicate_check_query);
    $duplicate_check_stmt->bind_param('s', $class_name);
    $duplicate_check_stmt->execute();
    $duplicate_check_result = $duplicate_check_stmt->get_result()->fetch_row();

    if ($duplicate_check_result[0] > 0) {
        echo "<script>alert('Class name already exists.'); window.history.back();</script>";
        exit;
    }

    // Ensure faculty and course belong to the same department (only for faculty)
    if ($role_id == 2) {
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

        if (!$validation_data || $validation_data['course_dept'] != $validation_data['faculty_dept']) {
            echo "<script>alert('Course and Faculty must belong to the same department.'); window.history.back();</script>";
            exit;
        }
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
        echo "<script>alert('An error occurred while creating the class. Please try again later.'); window.history.back();</script>";
    }
}
}
?>

<?php
// Close the database connection at the end of the script
mysqli_close($conn);
?>