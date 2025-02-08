<?php

// Include the database connection
include 'db_connection.php';
include 'csrf_protection.php';

$error_message = "";

// Check if the user is authenticated and authorized
if (!isset($_SESSION['session_userid']) || !isset($_SESSION['session_roleid']) || !in_array($_SESSION['session_roleid'], [1, 2])) {
    $error_message = "Unauthorized access. Please log in.";
    header('Refresh: 3; URL=login.php');
    exit;
}

// Get department from session
$department_id = $_SESSION['student_data']['department_id'] ?? null;
if (!$department_id) {
    $error_message = "Department information is missing. Please go back and select a department.";
    header('Refresh: 3; URL=edit_student.php');
    exit;
}

$errors = [];

// Fetch courses under the selected department with their status
$courses = [];
$stmt = $conn->prepare("
    SELECT c.course_id, c.course_name, s.status_name 
    FROM course c
    LEFT JOIN status s ON c.status_id = s.status_id
    WHERE c.department_id = ?
");
$stmt->bind_param("i", $department_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $courses[] = $row;
}
$stmt->close();


// Handle form submission securely
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error_message = "Something went wrong. Please try again.";
    }

    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

    if (!isset($_POST['courses']) || !is_array($_POST['courses']) || empty($_POST['courses'])) {
        $errors[] = "Please select at least one course.";
    } else {
        $selected_courses = array_map('intval', $_POST['courses']);
        $_SESSION['selected_courses'] = $selected_courses;

        header('Location: edit_student_classform.php');
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Courses</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="css/edit_student_course.css">
</head>

<body>
    <?php include('admin_header.php'); ?>
    <main class="main-content">

        <div class="page-wrapper">
            <!-- Top Section with Back Button -->
            <div class="top-section">
                <a href="edit_studentform1.php" class="back-button">← Back</a>
            </div>
            <main class="main-content">
                <h1>Edit Courses</h1>
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
                        <div class="form-card">
                            <h2>Courses in the Selected Department</h2>

                            <!-- Display Error Messages -->
                            <?php if (!empty($errors)): ?>
                                <div class="error-messages">
                                    <ul>
                                        <?php foreach ($errors as $error): ?>
                                            <li><?php echo htmlspecialchars($error); ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>

                            <form action="edit_student_courseform.php" method="POST">
                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token']; ?>">

                                <div class="checkbox-group">
                                    <?php if (!empty($courses)): ?>
                                        <?php foreach ($courses as $course):
                                            $status_class = 'status-blue';
                                            if (strtolower($course['status_name'] ?? '') == 'start') {
                                                $status_class = 'status-green';
                                            } elseif (strtolower($course['status_name'] ?? '') == 'in-progress') {
                                                $status_class = 'status-yellow';
                                            } elseif (strtolower($course['status_name'] ?? '') == 'ended') {
                                                $status_class = 'status-red';
                                            }
                                        ?>
                                            <div>
                                                <input
                                                    type="checkbox"
                                                    name="courses[]"
                                                    value="<?= $course['course_id'] ?>"
                                                    <?= in_array($course['course_id'], $selected_courses ?? []) ? 'checked' : '' ?>>
                                                <label>
                                                    <?= htmlspecialchars($course['course_name']) ?>
                                                </label>
                                                <span class="status-icon <?= $status_class; ?>">
                                                    <?= htmlspecialchars($course['status_name'] ?? 'Unassigned') ?>
                                                </span>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <p>No courses available for the selected department.</p>
                                    <?php endif; ?>
                                </div>

                                <button type="submit">Next</button>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>
            </main>
        </div>
    </main>
</body>

</html>