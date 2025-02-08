<?php

// Include necessary files
include 'db_connection.php';
include 'csrf_protection.php';

$error_message = "";
$error_message_logout = "";

// Check if user is authenticated and student data is present
if (!isset($_SESSION['session_userid'], $_SESSION['session_roleid'], $_SESSION['student_data'])) {
    $error_message_logout = "Session expired or unauthorized access. Please log in.";
}

// Ensure department_id exists and is valid before proceeding
if (!isset($_SESSION['student_data']['department_id']) || empty($_SESSION['student_data']['department_id'])) {
    $error_message = "Department data is missing or invalid. Please restart the registration process.";
}


// Fetch department courses
$department_id = $_SESSION['student_data']['department_id'];
$stmt = $conn->prepare("SELECT course_id, course_name FROM course WHERE department_id = ?");
$stmt->bind_param("i", $department_id);
$stmt->execute();
$courses_result = $stmt->get_result();

// Initialize errors array
$errors = [];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error_message = "Invalid CSRF token. Please reload the page and try again.";
    }
    // Validate selected courses
    if (isset($_POST['courses']) && is_array($_POST['courses']) && !empty($_POST['courses'])) {
        $_SESSION['selected_courses'] = $_POST['courses']; // Save selected courses in session
        header("Location: create_student_class.php");
        exit;
    } else {
        $errors[] = "Please select at least one course before proceeding.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Select Courses</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="css/create_student_course.css">
</head>

<body>
    <?php include('admin_header.php'); ?>

    <div class="page-wrapper">
        <!-- Top Section with Back Button -->
        <div class="top-section">
            <form method="POST" action="create_student.php">
                <button type="submit" class="back-button">← Back</button>
            </form>
        </div>
        <?php if (!empty($error_message)): ?>
            <div class="error-modal" id="errorModal" style="display: flex;">
                <div class="error-modal-content">
                    <h2>Error</h2>
                    <p><?php echo htmlspecialchars($error_message); ?></p>
                    <form method="POST" action="student.php">
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
                    <h1>Select Courses</h1>
                    <div class="form-container">
                        <div class="form-card">
                            <h2>Courses in your department</h2>
                            <?php if (!empty($errors)): ?>
                                <div class="error-messages">
                                    <ul>
                                        <?php foreach ($errors as $error): ?>
                                            <li><?php echo htmlspecialchars($error); ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>
                            <form action="create_student_course.php" method="POST">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                <div class="checkbox-group">
                                    <?php if ($courses_result && $courses_result->num_rows > 0): ?>
                                        <?php while ($course = $courses_result->fetch_assoc()): ?>
                                            <div>
                                                <input type="checkbox" name="courses[]" value="<?php echo htmlspecialchars($course['course_id']); ?>">
                                                <label><?php echo htmlspecialchars($course['course_name']); ?></label>
                                            </div>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <p>No courses available for the selected department.</p>
                                    <?php endif; ?>
                                </div>
                                <button type="submit">Next</button>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
                </main>
    </div>
</body>

</html>