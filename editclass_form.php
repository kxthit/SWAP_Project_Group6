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
$class_id = isset($_GET['class_id']) ? intval($_GET['class_id']) : 0;

// Fetch class details
$query = "
    SELECT 
        c.class_id, 
        c.class_name, 
        c.class_type, 
        c.course_id, 
        c.faculty_id, 
        co.course_name, 
        f.faculty_name 
    FROM class c
    LEFT JOIN course co ON c.course_id = co.course_id
    LEFT JOIN faculty f ON c.faculty_id = f.faculty_id
    WHERE c.class_id = ?
";
$stmt = $conn->prepare($query);
$stmt->bind_param('i', $class_id);
$stmt->execute();
$result = $stmt->get_result();
$class_details = $result->fetch_assoc();

if (!$class_details) {
    die("Class not found.");
}

// Fetch courses and faculties for dropdowns (only for admin)
if ($role_id == 1) { // Admin
    $courses_query = "SELECT course_id, course_name FROM course";
    $courses_result = $conn->query($courses_query);

    $faculties_query = "SELECT faculty_id, faculty_name FROM faculty";
    $faculties_result = $conn->query($faculties_query);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Class</title>
    <link rel="stylesheet" href="editclass_form.css">
</head>
<body>
    <?php include('admin_header.php'); ?>

    <div class="main-content">
        <h1>Edit Class</h1>
        <form class="edit-class-form" method="POST" action="editclass.php">
            <input type="hidden" name="class_id" value="<?php echo htmlspecialchars($class_id); ?>">

            <!-- Class Name -->
            <div class="form-group">
                <label for="class_name">Class Name</label>
                <input type="text" id="class_name" name="class_name" value="<?php echo htmlspecialchars($class_details['class_name']); ?>" required>
            </div>

            <!-- Class Type -->
            <div class="form-group">
                <label for="class_type">Class Type</label>
                <input type="text" id="class_type" name="class_type" value="<?php echo htmlspecialchars($class_details['class_type']); ?>" required>
            </div>

            <?php if ($role_id == 1): // Admin Section ?>
                <!-- Course -->
                <div class="form-group">
                    <label for="course_id">Course</label>
                    <select id="course_id" name="course_id">
                        <?php while ($course = $courses_result->fetch_assoc()): ?>
                            <option value="<?php echo $course['course_id']; ?>" <?php echo ($course['course_id'] == $class_details['course_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($course['course_name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <!-- Faculty -->
                <div class="form-group">
                    <label for="faculty_id">Faculty</label>
                    <select id="faculty_id" name="faculty_id">
                        <?php while ($faculty = $faculties_result->fetch_assoc()): ?>
                            <option value="<?php echo $faculty['faculty_id']; ?>" <?php echo ($faculty['faculty_id'] == $class_details['faculty_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($faculty['faculty_name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
            <?php else: // Faculty Section ?>
                <!-- Course (Read-only for faculty) -->
                <div class="form-group">
                    <label>Course</label>
                    <input type="text" value="<?php echo htmlspecialchars($class_details['course_name']); ?>" readonly>
                </div>

                <!-- Faculty (Read-only for faculty) -->
                <div class="form-group">
                    <label>Faculty</label>
                    <input type="text" value="<?php echo htmlspecialchars($class_details['faculty_name']); ?>" readonly>
                </div>
            <?php endif; ?>

            <!-- Submit Button -->
            <div class="form-group">
                <button class="btn-submit" type="submit">Save Changes</button>
            </div>
        </form>
    </div>
</body>
</html>
