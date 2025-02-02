<?php
include('csrf_protection.php');
include('db_connection.php');

// Check if the user is authenticated
if (!isset($_SESSION['session_userid']) || !isset($_SESSION['session_roleid'])) {
    echo "<h2>Unauthorized access. Please log in.</h2>";
    header('Refresh: 3; URL=login.php');
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
    // Handle form submission

    $user_id = $_SESSION['session_userid'];
    $role_id = $_SESSION['session_roleid'];

    $class_id = intval($_POST['class_id']);
    $class_name = trim($_POST['class_name']);
    $class_type = trim($_POST['class_type']);
    $course_id = $role_id == 1 ? intval($_POST['course_id']) : null;
    $faculty_id = $role_id == 1 ? intval($_POST['faculty_id']) : null;

    // Input validation
    if (!preg_match('/^[a-zA-Z0-9\s]+$/', $class_name) || !in_array($class_type, ['Semester-Based', 'Term-Based'])) {
        die("Invalid input.");
    }

    // Ensure faculty and course belong to the same department
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

    if ($role_id == 2) {
        $ownership_check_query = "SELECT COUNT(*) FROM class WHERE class_id = ? AND faculty_id = (SELECT faculty_id FROM faculty WHERE user_id = ?)";
        $ownership_stmt = $conn->prepare($ownership_check_query);
        $ownership_stmt->bind_param('ii', $class_id, $user_id);
        $ownership_stmt->execute();
        $ownership_result = $ownership_stmt->get_result()->fetch_row();
        if ($ownership_result[0] == 0) {
            die("Unauthorized access.");
        }
    }

    $update_query = "UPDATE class SET class_name = ?, class_type = ?" . ($role_id == 1 ? ", course_id = ?, faculty_id = ?" : "") . " WHERE class_id = ?";
    $update_stmt = $conn->prepare($update_query);
    if ($role_id == 1) {
        $update_stmt->bind_param('ssiii', $class_name, $class_type, $course_id, $faculty_id, $class_id);
    } else {
        $update_stmt->bind_param('ssi', $class_name, $class_type, $class_id);
    }

    if ($update_stmt->execute()) {
        echo "<script>alert('Class updated successfully!'); window.location.href = 'classes.php';</script>";
    } else {
        echo "<script>alert('Error updating class.'); window.location.href = 'classes.php';</script>";
    }
}
?>

<?php
// Close the database connection at the end of the script
mysqli_close($conn);
?>