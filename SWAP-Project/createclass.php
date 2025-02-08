<?php
// Include necessary files
include 'db_connection.php';
include 'csrf_protection.php';

// Check if user is authenticated
if (!isset($_SESSION['session_userid']) || !isset($_SESSION['session_roleid'])) {
    header('Location: logout.php'); // Redirect unauthorized users
    exit;
}

$user_id = $_SESSION['session_userid'];
$role_id = $_SESSION['session_roleid'];

// Validate CSRF token only on POST
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!isset($_POST['csrf_token']) || !validate_csrf_token($_POST['csrf_token'])) {
        regenerate_csrf_token(); // Generate a new CSRF token
        die("Invalid CSRF token. <a href='logout.php'>Try again</a>");
    }
}


// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize input
    $class_id = intval($_POST['class_id']);
    $class_name = trim(htmlspecialchars($_POST['class_name']));
    $class_type = trim(htmlspecialchars($_POST['class_type']));
    $course_id = intval($_POST['course_id']);

    // Validate Class Name
    if (!preg_match('/^[a-zA-Z0-9\s]+$/', $class_name)) {
        $_SESSION['error_message'] = "Invalid class name. Only alphanumeric characters and spaces are allowed.";
        header("Location: createclass_form.php");
        exit;
    }

    // Validate Class Type
    if (!in_array($class_type, ['Semester-Based', 'Term-Based'])) {
        $_SESSION['error_message'] = "Invalid class type selected.";
        header("Location: createclass_form.php");
        exit;
    }

    // Faculty auto-assign for non-admins
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
            $_SESSION['error_message'] = "Faculty record not found.";
            header("Location: createclass_form.php");
            exit;
        }

        $faculty_id = $faculty_data['faculty_id'];
    }

    // Check for duplicate class name using EXISTS()
    $duplicate_check_query = "SELECT EXISTS(SELECT 1 FROM class WHERE class_name = ?)";
    $duplicate_check_stmt = $conn->prepare($duplicate_check_query);
    $duplicate_check_stmt->bind_param('s', $class_name);
    $duplicate_check_stmt->execute();
    $duplicate_check_stmt->bind_result($is_duplicate);
    $duplicate_check_stmt->fetch();
    $duplicate_check_stmt->close();

    if ($is_duplicate) {
        $_SESSION['error_message'] = "Class name already exists.";
        header("Location: createclass_form.php");
        exit;
    }

    // Ensure Faculty & Course belong to the same department (only for Faculty)
    if ($role_id == 2) {
        $validation_query = "
            SELECT c.department_id AS course_dept, f.department_id AS faculty_dept 
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
            $_SESSION['error_message'] = "Course and Faculty must belong to the same department.";
            header("Location: createclass_form.php");
            exit;
        }
    }

    // Insert new class record
    $insert_query = "
        INSERT INTO class (class_id, class_name, class_type, course_id, faculty_id) 
        VALUES (?, ?, ?, ?, ?)
    ";
    $insert_stmt = $conn->prepare($insert_query);
    $insert_stmt->bind_param('issii', $class_id, $class_name, $class_type, $course_id, $faculty_id);

    if ($insert_stmt->execute()) {
        $_SESSION['success_message'] = "Class created successfully!";
        header("Location: classes.php?status=success");
    } else {
        $_SESSION['error_message'] = "An error occurred while creating the class. Please try again.";
        header("Location: createclass_form.php");
    }
}
?>

<?php
// Close the database connection
mysqli_close($conn);
?>
