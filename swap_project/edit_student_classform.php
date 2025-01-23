<?php
session_start();

// Check if the user is authenticated
if (!isset($_SESSION['session_userid']) || !isset($_SESSION['session_role'])) {
    echo "<h2>Unauthorized access. Please log in.</h2>";
    header('Refresh: 3; URL=login.php');
    exit;
}

// Include the database connection
include 'db_connection.php';

// Get selected courses from session
$selected_courses = $_SESSION['selected_courses'];
$class_data = [];

// Fetch classes for each selected course
foreach ($selected_courses as $course_id) {
    $stmt = $conn->prepare("SELECT class_id, class_name FROM class WHERE course_id = ?");
    $stmt->bind_param("i", $course_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $class_data[$course_id] = $result;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $_SESSION['selected_classes'] = $_POST['classes'];
    header('Location: update_student1.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Select Classes</title>
</head>
<body>
    <form action="edit_student_classform.php" method="POST">
        <h3>Select Classes</h3>
        <?php foreach ($selected_courses as $course_id): ?>
            <h4>Course: <?= htmlspecialchars($course_id) ?></h4>
            <?php if (isset($class_data[$course_id])): ?>
                <?php while ($class = $class_data[$course_id]->fetch_assoc()): ?>
                    <input type="checkbox" name="classes[<?= $course_id ?>][]" value="<?= $class['class_id'] ?>"> <?= $class['class_name'] ?><br>
                <?php endwhile; ?>
            <?php endif; ?>
        <?php endforeach; ?>

        <input type="submit" value="Next">
    </form>
</body>
</html>
