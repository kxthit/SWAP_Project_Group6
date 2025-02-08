<?php
// Include the database connection and CSRF protection
include 'db_connection.php';
include 'csrf_protection.php';

// Ensure CSRF token is validated only for POST requests
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!isset($_POST['csrf_token']) || !validate_csrf_token($_POST['csrf_token'])) {
        regenerate_csrf_token(); // Generate a new CSRF token
        echo "<script>
            alert('Invalid CSRF token. Redirecting to login.');
            window.location.href = 'logout.php';
        </script>";
        exit;
    }
}

// Check if the user is authenticated
if (!isset($_SESSION['session_userid']) || !isset($_SESSION['session_roleid'])) {
    echo "<script>
        alert('You are not authorized. Redirecting to login.');
        window.location.href = 'logout.php';
    </script>";
    exit;
}

$user_id = $_SESSION['session_userid'];
$role_id = $_SESSION['session_roleid'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize input
    $class_id = intval($_POST['class_id']);
    $class_name = trim(htmlspecialchars($_POST['class_name']));
    $class_type = trim(htmlspecialchars($_POST['class_type']));
    $course_id = ($role_id == 1) ? intval($_POST['course_id']) : null;
    $faculty_id = ($role_id == 1) ? intval($_POST['faculty_id']) : null;

    // Input validation
    if (!preg_match('/^[a-zA-Z0-9\s]+$/', $class_name) || !in_array($class_type, ['Semester-Based', 'Term-Based'])) {
        echo "<script>
            alert('Invalid input. Please ensure all fields are correctly filled.');
            window.location.href = 'editclass_form.php?class_id=$class_id';
        </script>";
        exit;
    }

    // Ensure faculty and course belong to the same department (Admin Only)
    if ($role_id == 1 && $faculty_id && $course_id) {
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
            echo "<script>
                alert('The selected faculty and course must belong to the same department.');
                window.location.href = 'editclass_form.php?class_id=$class_id';
            </script>";
            exit;
        }
    }

    // Ensure faculty owns the class (Faculty Role)
    if ($role_id == 2) {
        $ownership_check_query = "
            SELECT COUNT(*) 
            FROM class 
            WHERE class_id = ? 
            AND faculty_id = (SELECT faculty_id FROM faculty WHERE user_id = ?)
        ";
        $ownership_stmt = $conn->prepare($ownership_check_query);
        $ownership_stmt->bind_param('ii', $class_id, $user_id);
        $ownership_stmt->execute();
        $ownership_result = $ownership_stmt->get_result()->fetch_row();
        if ($ownership_result[0] == 0) {
            echo "<script>
                alert('Unauthorized access to this class.');
                window.location.href = 'classes.php';
            </script>";
            exit;
        }
    }

    // Prepare update query
    $update_query = "
        UPDATE class 
        SET class_name = ?, class_type = ? 
        " . ($role_id == 1 ? ", course_id = ?, faculty_id = ?" : "") . " 
        WHERE class_id = ?
    ";
    $update_stmt = $conn->prepare($update_query);

    if ($role_id == 1) {
        $update_stmt->bind_param('ssiii', $class_name, $class_type, $course_id, $faculty_id, $class_id);
    } else {
        $update_stmt->bind_param('ssi', $class_name, $class_type, $class_id);
    }

    // Execute query and handle result
    if ($update_stmt->execute()) {
        echo "<script>
            alert('Class updated successfully!');
            window.location.href = 'classes.php';
        </script>";
    } else {
        echo "<script>
            alert('Error updating class. Please try again.');
            window.location.href = 'editclass_form.php?class_id=$class_id';
        </script>";
    }

    exit;
}

// Close the database connection
mysqli_close($conn);
?>
