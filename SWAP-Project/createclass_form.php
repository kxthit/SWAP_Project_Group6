<?php
// Include the database connection and session management
include 'db_connection.php';
include 'session_management.php';

// Start session only if it's not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if the user is authenticated
if (!isset($_SESSION['session_userid']) || !isset($_SESSION['session_roleid'])) {
    echo "<h2>Unauthorized access. Please log in.</h2>";
    header('Refresh: 3; URL=login.php');
    exit;
}

$user_id = $_SESSION['session_userid'];
$role_id = $_SESSION['session_roleid'];

// Generate CSRF token if not already set
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Auto-generate the next available class ID
$next_class_id_query = "SELECT COALESCE(MAX(class_id) + 1, 1) AS next_id FROM class";
$next_class_id_result = $conn->query($next_class_id_query);
$next_class_id = $next_class_id_result->fetch_assoc()['next_id'];

// Fetch courses and faculties for dropdowns based on role
if ($role_id == 1) { // Admin sees all courses and faculties
    $courses_query = "SELECT course_id, course_name FROM course";
    $courses_result = $conn->query($courses_query);

    $faculties_query = "SELECT faculty_id, faculty_name FROM faculty";
    $faculties_result = $conn->query($faculties_query);
} elseif ($role_id == 2) { // Faculty sees only their assigned courses
    $courses_query = "
        SELECT c.course_id, c.course_name
        FROM course c
        INNER JOIN department d ON c.department_id = d.department_id
        INNER JOIN faculty f ON f.department_id = d.department_id
        WHERE f.user_id = ?
    ";
    $courses_stmt = $conn->prepare($courses_query);
    $courses_stmt->bind_param('i', $user_id);
    $courses_stmt->execute();
    $courses_result = $courses_stmt->get_result();
}

session_write_close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Class</title>
    <link rel="stylesheet" href="css/createclass_form.css">
</head>

<body>
    <?php include('admin_header.php'); ?>

    <!-- Back Button -->
    <div class="back-button-container">
        <a href="classes.php" class="back-button">
            <img src="image/back_arrow.png" alt="Back">
        </a>
    </div>

    <div class="main-content">
        <h1>Create Class</h1>
        <form method="POST" action="createclass.php">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            <!-- Display Next Available Class ID (Read-Only) -->
            <div class="form-group">
                <input type="hidden" id="class_id" name="class_id" value="<?php echo $next_class_id; ?>" readonly>
            </div>
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
            <!-- Faculty (only for admin) -->
            <?php if ($role_id == 1): ?>
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
            <?php endif; ?>
            <!-- Submit Button -->
            <div class="form-actions">
                <button type="submit" class="btn-submit">Create Class</button>
            </div>
        </form>
    </div>
</body>

</html>