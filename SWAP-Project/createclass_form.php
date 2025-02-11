<?php
// Include necessary files
include 'db_connection.php';
include 'csrf_protection.php';

// Regenerate token only if not set
if (!isset($_SESSION['csrf_token'])) {
    regenerate_csrf_token();
}

// Check if user is authenticated
if (!isset($_SESSION['session_userid']) || !isset($_SESSION['session_roleid'])) {
    header('Location: logout.php'); // Redirect unauthorized users
    exit;
}

$user_id = $_SESSION['session_userid'];
$role_id = $_SESSION['session_roleid'];

// Fetch courses and faculties for dropdowns
$courses_query = "SELECT course_id, course_name FROM course";
$courses_result = $conn->query($courses_query);

$faculties_query = "SELECT faculty_id, faculty_name FROM faculty";
$faculties_result = $conn->query($faculties_query);

// Validate CSRF token and form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!isset($_POST['csrf_token']) || !validate_csrf_token($_POST['csrf_token'])) {
        regenerate_csrf_token(); // Generate a new CSRF token
        die("Invalid CSRF token. <a href='logout.php'>Try again</a>");
    }

    // Sanitize inputs
    $class_name = trim(htmlspecialchars($_POST['class_name']));
    $class_type = trim(htmlspecialchars($_POST['class_type']));
    $course_id = intval($_POST['course_id']);
    $faculty_id = intval($_POST['faculty_id']);

    // Validate inputs
    if (!preg_match('/^[a-zA-Z0-9\s]+$/', $class_name) || !in_array($class_type, ['Semester-Based', 'Term-Based'])) {
        echo "<script>
            alert('Invalid input. Please ensure all fields are filled correctly.');
            window.location.href = 'createclass_form.php';
        </script>";
        exit;
    }

    // Ensure the course and faculty belong to the same department
    $validation_query = "
        SELECT c.department_id AS course_dept, f.department_id AS faculty_dept
        FROM course c
        INNER JOIN faculty f ON f.department_id = c.department_id
        WHERE c.course_id = ? AND f.faculty_id = ?
    ";
    $validation_stmt = $conn->prepare($validation_query);
    $validation_stmt->bind_param('ii', $course_id, $faculty_id);
    $validation_stmt->execute();
    $validation_result = $validation_stmt->get_result();
    $validation_data = $validation_result->fetch_assoc();

    if (!$validation_data || $validation_data['course_dept'] !== $validation_data['faculty_dept']) {
        echo "<script>
            alert('The selected course and faculty do not belong to the same department.');
            window.location.href = 'createclass_form.php';
        </script>";
        exit;
    }

    // Insert class into the database
    $insert_query = "INSERT INTO class (class_name, class_type, course_id, faculty_id) VALUES (?, ?, ?, ?)";
    $insert_stmt = $conn->prepare($insert_query);
    $insert_stmt->bind_param('ssii', $class_name, $class_type, $course_id, $faculty_id);

    if ($insert_stmt->execute()) {
        echo "<script>
            alert('Class created successfully.');
            window.location.href = 'classes.php';
        </script>";
    } else {
        echo "<script>
            alert('Error creating class. Please try again.');
            window.location.href = 'createclass_form.php';
        </script>";
    }

    $insert_stmt->close();
}

session_write_close(); // Close session handling
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Class</title>
    <link rel="stylesheet" href="css/createclass_form.css"> <!-- Standardized CSS Path -->
</head>
<body>
    <!-- Include dynamic header -->
    <?php
    if ($role_id == 1) {
        include('admin_header.php');
    } elseif ($role_id == 2) {
        include('faculty_header.php');
    } else {
        include('student_header.php');
    }
    ?>

    <div class="main-content">
        <!-- Back Button -->
        <div class="back-button-container">
            <a href="classes.php" class="back-button">
                <img src="image/back_arrow.png" alt="Back">
            </a>
        </div>

        <h1>Create Class</h1>
        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

            <!-- Class Name -->
            <div class="form-group">
                <label for="class_name">Class Name</label>
                <input type="text" id="class_name" name="class_name" required pattern="[a-zA-Z0-9\s]+" title="Only alphanumeric characters and spaces allowed">
            </div>

            <!-- Class Type Dropdown -->
            <div class="form-group">
                <label for="class_type">Class Type</label>
                <select id="class_type" name="class_type" required>
                    <option value="" disabled selected>Select Class Type</option>
                    <option value="Semester-Based">Semester-Based</option>
                    <option value="Term-Based">Term-Based</option>
                </select>
            </div>

            <!-- Course -->
            <div class="form-group">
                <label for="course_id">Course</label>
                <select id="course_id" name="course_id" required>
                    <option value="" disabled selected>Select Course</option>
                    <?php while ($course = $courses_result->fetch_assoc()): ?>
                        <option value="<?php echo $course['course_id']; ?>">
                            <?php echo htmlspecialchars($course['course_name']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <!-- Faculty -->
            <div class="form-group">
                <label for="faculty_id">Faculty</label>
                <select id="faculty_id" name="faculty_id" required>
                    <option value="" disabled selected>Select Faculty</option>
                    <?php while ($faculty = $faculties_result->fetch_assoc()): ?>
                        <option value="<?php echo $faculty['faculty_id']; ?>">
                            <?php echo htmlspecialchars($faculty['faculty_name']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <!-- Submit Button -->
            <div class="form-actions">
                <button type="submit" class="btn-submit">Create Class</button>
            </div>
        </form>
    </div>
</body>
</html>
