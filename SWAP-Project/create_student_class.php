<?php

// Include database connection and session management
include 'db_connection.php';
include 'csrf_protection.php';

$error_messages = "";
$error_message_logout = "";

// Check if the user is authenticated
if (!isset($_SESSION['session_userid']) || !isset($_SESSION['session_roleid'])) {
    $error_message_logout = "Session expired or unauthorized access. Please log in.";
}

// Ensure required session data is set
if (!isset($_SESSION['student_data'])) {
    $error_messages = "Student data missing in session.";
}
if (!isset($_SESSION['selected_courses']) || !is_array($_SESSION['selected_courses'])) {
    $error_messages = "No courses selected. Please restart the process.";
}

// Get selected courses from the session
$selected_courses = $_SESSION['selected_courses'];
$class_data = [];
$course_names = [];

// Fetch course names dynamically
foreach ($selected_courses as $course_id) {
    // Validate course ID
    if (!is_numeric($course_id)) {
        $error_messages = "Invalid course ID detected. Please restart the process.";
        exit;
    }
    // Fetch course names
    $stmt = $conn->prepare("SELECT course_name FROM course WHERE course_id = ?");
    $stmt->bind_param("i", $course_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $course_names[$course_id] = $result->num_rows > 0 ? $result->fetch_assoc()['course_name'] : 'Unknown Course';

    // Fetch class data for the course
    $stmt = $conn->prepare("SELECT class_id, class_name FROM class WHERE course_id = ?");
    $stmt->bind_param("i", $course_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $class_data[$course_id] = $result;
}
// Initialize error message
$error_message = "";

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error_messages = "Invalid CSRF token. Please reload the page and try again.";
        exit;
    }

    if (!isset($_POST['classes']) || !is_array($_POST['classes'])) {
        $error_message = "No classes were selected. Please try again.";
        exit;
    }

    $_SESSION['selected_classes'] = $_POST['classes'];

    // Redirect to submit_student.php
    include "submit_student.php";
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Select Classes</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="css/create_student_class.css">
</head>

<body>
    <?php include('admin_header.php'); ?>
    <main class="main-content">

        <div class="page-wrapper">
            <!-- Top Section with Back Button -->
            <div class="top-section">
                <form method="POST" action="create_student.php">
                    <button type="submit" class="back-button">← Back</button>
                </form>
            </div>
            <?php if (!empty($error_messages)): ?>
                <div class="error-modal" id="errorModal" style="display: flex;">
                    <div class="error-modal-content">
                        <h2>Error</h2>
                        <p><?php echo htmlspecialchars($error_message); ?></p>
                        <form method="POST" action="display_student.php">
                            <button type="submit">Go Back</button>
                        </form>
                    </div>
                </div>
            <?php else: ?>
                <?php if (!empty($error_message_logout)): ?>
                    <div class="error-modal" id="errorModal" style="display: flex;">
                        <div class="error-modal-content">
                            <h2>Error</h2>
                            <p><?php echo htmlspecialchars($error_message_logout); ?></p>
                            <form method="POST" action="logout.php">
                                <button type="submit">Go Back</button>
                            </form>
                        </div>
                    </div>
                <?php else: ?>
                    <main class="main-content">
                        <h1>Step 3: Select Classes</h1>
                        <div class="form-container">
                            <h2>Classes under courses</h2>
                            <?php if (!empty($error_message)): ?>
                                <div class="error-message">
                                    <?php echo htmlspecialchars($error_message); ?>
                                </div>
                            <?php endif; ?>
                            <form action="create_student_class.php" method="POST">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                <?php foreach ($selected_courses as $course_id): ?>
                                    <div class="course-container">
                                        <!-- Display course name -->
                                        <h3>Course: <?php echo htmlspecialchars($course_names[$course_id]); ?></h3>

                                        <!-- Add a horizontal rule for separation -->
                                        <hr>

                                        <!-- Display the class options -->
                                        <div class="radio-section">
                                            <?php
                                            // Ensure classes exist for each course
                                            if (isset($class_data[$course_id]) && $class_data[$course_id]->num_rows > 0):
                                                while ($class = $class_data[$course_id]->fetch_assoc()):
                                            ?>
                                                    <label>
                                                        <input type="radio" name="classes[<?php echo $course_id; ?>]" value="<?php echo $class['class_id']; ?>" required>
                                                        <?php echo htmlspecialchars($class['class_name']); ?>
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
                                <button type="submit">Done</button>
                            </form>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
                    </main>
        </div>
    </main>
</body>

</html>