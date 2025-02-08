<?php

// Include the database connection
include 'db_connection.php';
include 'csrf_protection.php';

$error_message = "";
$errors = [];

// Check if the user is authenticated and authorized
if (!isset($_SESSION['session_userid']) || !isset($_SESSION['session_roleid']) || !in_array($_SESSION['session_roleid'], [1, 2])) {
    $error_message = "Unauthorized access. Please log in.";
    header('Refresh: 3; URL=login.php');
    exit;
}

// Get selected courses from session
$selected_courses = $_SESSION['selected_courses'];
$class_data = [];
$course_names = [];

// Validate session data
if (empty($selected_courses)) {
    $error_message = "No courses selected. Please go back and select a course.";
    header('Refresh: 3; URL=edit_student_courseform.php');
    exit;
}

// Fetch course names and classes for each selected course
foreach ($selected_courses as $course_id) {
    // Fetch course name
    $stmt = $conn->prepare("SELECT course_name FROM course WHERE course_id = ?");
    $stmt->bind_param("i", $course_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $course_names[$course_id] = $result->num_rows > 0 ? $result->fetch_assoc()['course_name'] : 'Unknown Course';
}

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
    // CSRF Token Validation
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error_message = "Something went wrong. Please try again.";
    }

    // Regenerate CSRF token after validation
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

    // Validate class selection
    if (!isset($_POST['classes']) || !is_array($_POST['classes']) || empty($_POST['classes'])) {
        $errors[] = "Please select at least one class for each course.";
    }
    foreach ($selected_courses as $course_id) {
        if (!isset($_POST['classes'][$course_id]) || empty($_POST['classes'][$course_id])) {
            $errors[] = "You must select a class for the course: " . htmlspecialchars($course_names[$course_id]);
        }
    }

    // Ensure only numeric class IDs are stored
    $selected_classes = [];
    foreach ($_POST['classes'] as $course_id => $class_ids) {
        $selected_classes[$course_id] = array_map('intval', $class_ids);
    }

    $_SESSION['selected_classes'] = $selected_classes;

    header('Location: update_student1.php');
    exit;
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Classes</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="css/edit_student_class.css">
</head>

<body>
    <?php include('admin_header.php'); ?>
    <main class="main-content">

        <div class="page-wrapper">
            <!-- Top Section with Back Button -->
            <div class="top-section">
                <a href="<?php echo ($_SESSION['session_roleid'] == 1) ? 'edit_student_courseform.php' : 'faculty_edit_student_course.php'; ?>" class="back-button">
                    ← Back
                </a>
            </div>
            <main class="main-content">
                <h1>Edit Classes</h1>
                <?php if (!empty($error_message)): ?>
                    <div class="error-modal" id="errorModal" style="display: flex;">
                        <div class="error-modal-content">
                            <h2>Error</h2>
                            <p><?php echo htmlspecialchars($error_message); ?></p>
                            <button onclick="window.location.href='student.php'">Go Back</button>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="form-container">
                        <h2>Classes for Selected Courses</h2>

                        <?php if (!empty($errors)): ?>
                            <div class="error-messages">
                                <ul>
                                    <?php foreach ($errors as $error): ?>
                                        <li><?php echo htmlspecialchars($error); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <form action="edit_student_classform.php" method="POST">
                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token']; ?>">

                            <?php foreach ($selected_courses as $course_id): ?>
                                <div class="course-container">
                                    <!-- Display course name -->
                                    <h3>Course: <?= htmlspecialchars($course_names[$course_id]) ?></h3>

                                    <!-- Add a horizontal rule for separation -->
                                    <hr>

                                    <!-- Display the class options -->
                                    <div class="radio-section">
                                        <?php
                                        if (isset($class_data[$course_id]) && $class_data[$course_id]->num_rows > 0):
                                            while ($class = $class_data[$course_id]->fetch_assoc()):
                                        ?>
                                                <label>
                                                    <input
                                                        type="radio"
                                                        name="classes[<?= $course_id ?>][]"
                                                        value="<?= $class['class_id'] ?>"
                                                        required
                                                        <?= isset($selected_classes[$course_id]) && $selected_classes[$course_id] == $class['class_id'] ? 'checked' : '' ?>>
                                                    <?= htmlspecialchars($class['class_name']) ?>
                                                </label><br>
                                            <?php
                                            endwhile;
                                        else:
                                            ?>
                                            <p>No classes available for this course.</p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>

                            <!-- Submit button -->
                            <button type="submit">Next</button>
                        </form>
                    </div>
                <?php endif; ?>
            </main>
        </div>
    </main>
</body>

</html>