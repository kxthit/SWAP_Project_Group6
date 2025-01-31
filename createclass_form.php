<?php
// Include the database connection and session management
include 'db_connection.php';
include 'session_management.php';
session_start();

// Check if the user is authenticated
if (!isset($_SESSION['session_userid']) || !isset($_SESSION['session_roleid'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['session_userid'];
$role_id = $_SESSION['session_roleid'];

// Auto-generate the next available class ID
$next_class_id_query = "SELECT MAX(class_id) + 1 AS next_id FROM class";
$next_class_id_result = $conn->query($next_class_id_query);
$next_class_id = $next_class_id_result->fetch_assoc()['next_id'] ?? 1;

// Fetch courses and faculties for dropdowns
if ($role_id == 1) { // Admin sees all courses and faculties
    $courses_query = "SELECT course_id, course_name FROM course";
    $courses_result = $conn->query($courses_query);

    $faculties_query = "SELECT faculty_id, faculty_name FROM faculty";
    $faculties_result = $conn->query($faculties_query);
} elseif ($role_id == 2) { // Faculty sees only their courses
    $courses_query = "
        SELECT c.course_id, c.course_name
        FROM course c
        INNER JOIN faculty_course fc ON c.course_id = fc.course_id
        INNER JOIN faculty f ON fc.faculty_id = f.faculty_id
        WHERE f.user_id = ?
    ";
    $courses_stmt = $conn->prepare($courses_query);
    $courses_stmt->bind_param('i', $user_id);
    $courses_stmt->execute();
    $courses_result = $courses_stmt->get_result();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Class</title>
    <link rel="stylesheet" href="createclass_form.css">
</head>
<body>
    <?php include('admin_header.php'); ?>

    <div class="main-content">
        <h1>Create Class</h1>
        <form method="POST" action="createclass.php">
            <!-- Display Next Available Class ID (Read-Only) -->
            <div class="form-group">
                <label for="class_id">Class ID</label>
                <input type="text" id="class_id" name="class_id" value="<?php echo $next_class_id; ?>" readonly>
            </div>

            <!-- Class Name -->
            <div class="form-group">
                <label for="class_name">Class Name</label>
                <input type="text" id="class_name" name="class_name" required>
            </div>

            <!-- Class Type -->
            <div class="form-group">
                <label for="class_type">Class Type</label>
                <select id="class_type" name="class_type" required>
                    <option value="">Select Class Type</option>
                    <option value="Semester-Based">Semester-Based</option>
                    <option value="Term-Based">Term-Based</option>
                </select>
            </div>

            <!-- Course -->
            <div class="form-group">
                <label for="course_id">Course</label>
                <select id="course_id" name="course_id" required>
                    <option value="">Select Course</option>
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
                        <option value="">Select Faculty</option>
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
