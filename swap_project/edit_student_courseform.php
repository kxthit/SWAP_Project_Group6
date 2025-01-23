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

// Get department from session
$department_id = $_SESSION['student_data']['department_id'] ?? null;
if (!$department_id) {
    echo "<h2>Department information is missing. Please go back and select a department.</h2>";
    header('Refresh: 3; URL=edit_student.php');
    exit;
}

// Fetch courses under selected department
$courses = [];
$stmt = $conn->prepare("SELECT course_id, course_name FROM course WHERE department_id = ?");
$stmt->bind_param("i", $department_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $courses[] = $row;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['courses']) && !empty($_POST['courses'])) {
        $_SESSION['selected_courses'] = $_POST['courses'];
        header('Location: edit_student_classform.php');
        exit;
    } else {
        $error_message = "Please select at least one course.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Select Courses</title>
</head>
<body>
    <h2>Select Courses</h2>
    <?php if (isset($error_message)): ?>
        <p style="color:red;"><?= $error_message ?></p>
    <?php endif; ?>

    <form action="edit_student_courseform.php" method="POST">
        <h3>Available Courses for Selected Department:</h3>
        <?php if (!empty($courses)): ?>
            <?php foreach ($courses as $course): ?>
                <input type="checkbox" name="courses[]" value="<?= $course['course_id'] ?>"> <?= htmlspecialchars($course['course_name']) ?><br>
            <?php endforeach; ?>
        <?php else: ?>
            <p>No courses available for the selected department.</p>
        <?php endif; ?>

        <input type="submit" value="Next">
    </form>
</body>
</html>
