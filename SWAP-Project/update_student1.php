<?php

// Include database connection
include 'db_connection.php';
include 'csrf_protection.php';

// Check if the user is authenticated and authorized
if (!isset($_SESSION['session_userid']) || !isset($_SESSION['session_roleid']) || !in_array($_SESSION['session_roleid'], [1, 2])) {
    echo "<h2>Unauthorized access.</h2>";
    header('Refresh: 3; URL=login.php');
    exit;
}


// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF Token Validation
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Something went wrong. Please try again.");
    }

    // Regenerate CSRF token after validation
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

    // Ensure session contains the required data
    if (!isset($_SESSION['student_data'], $_SESSION['selected_courses'], $_SESSION['selected_classes'])) {
        echo '<h2>Session data missing. Please restart the process.</h2>';
        exit;
    }

    $student_data = $_SESSION['student_data'];
    $selected_courses = $_SESSION['selected_courses'];
    $selected_classes = $_SESSION['selected_classes'];

    // Begin transaction
    $conn->begin_transaction();

    try {
        // Update student details
        $stmt = $conn->prepare("UPDATE student SET student_name = ?, student_email = ?, student_phone = ?, department_id = ? WHERE student_id = ?");
        $stmt->bind_param("sssii", $student_data['student_name'], $student_data['student_email'], $student_data['student_phone'], $student_data['department_id'], $student_data['student_id']);
        $stmt->execute();
        $stmt->close();

        // Update user admission number
        $stmt = $conn->prepare("UPDATE user SET admission_number = ? WHERE user_id = ?");
        $stmt->bind_param("si", $student_data['admission_number'], $student_data['user_id']);
        $stmt->execute();
        $stmt->close();

        // Delete old course mappings
        $stmt = $conn->prepare("DELETE FROM student_course WHERE student_id = ?");
        $stmt->bind_param("i", $student_data['student_id']);
        $stmt->execute();
        $stmt->close();

        // Insert new course mappings
        foreach ($selected_courses as $course_id) {
            $stmt = $conn->prepare("INSERT INTO student_course (student_id, course_id) VALUES (?, ?)");
            $stmt->bind_param("ii", $student_data['student_id'], $course_id);
            $stmt->execute();
            $stmt->close();
        }

        // Delete old class mappings
        $stmt = $conn->prepare("DELETE FROM student_class WHERE student_id = ?");
        $stmt->bind_param("i", $student_data['student_id']);
        $stmt->execute();
        $stmt->close();

        // Insert new class mappings
        foreach ($selected_classes as $course_id => $class_ids) {
            foreach ($class_ids as $class_id) {
                $stmt = $conn->prepare("INSERT INTO student_class (student_id, class_id) VALUES (?, ?)");
                $stmt->bind_param("ii", $student_data['student_id'], $class_id);
                $stmt->execute();
                $stmt->close();
            }
        }

        // Commit transaction if all queries succeeded
        $conn->commit();


        // Redirect to the student page
        header('Location: display_student.php');
        exit;
    } catch (mysqli_sql_exception $e) {
        // Rollback transaction in case of error
        $conn->rollback();
        die("<h2>Error updating student details. Please try again later.</h2>");
    }
}

// Fetch department name
$student_data = $_SESSION['student_data'];
$stmt = $conn->prepare("SELECT department_name FROM department WHERE department_id = ?");
$stmt->bind_param("i", $student_data['department_id']);
$stmt->execute();
$stmt->bind_result($department_name);
$stmt->fetch();
$stmt->close();

// Fetch course names and statuses
$courses = [];
foreach ($_SESSION['selected_courses'] as $course_id) {
    $stmt = $conn->prepare("SELECT course_name, status_name FROM course c JOIN status s ON c.status_id = s.status_id WHERE course_id = ?");
    $stmt->bind_param("i", $course_id);
    $stmt->execute();
    $stmt->bind_result($course_name, $status_name);
    $stmt->fetch();
    $stmt->close();
    $courses[] = [
        'name' => htmlspecialchars($course_name),
        'status' => htmlspecialchars($status_name)
    ];
}

// Fetch class names grouped by course
$classes = [];
foreach ($_SESSION['selected_classes'] as $course_id => $class_ids) {
    $class_list = [];
    foreach ($class_ids as $class_id) {
        $stmt = $conn->prepare("SELECT class_name FROM class WHERE class_id = ?");
        $stmt->bind_param("i", $class_id);
        $stmt->execute();
        $stmt->bind_result($class_name);
        $stmt->fetch();
        $stmt->close();
        $class_list[] = htmlspecialchars($class_name);
    }
    $classes[$course_id] = $class_list;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Student Details</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/update_student1.css">
</head>

<body>

    <?php include('admin_header.php'); ?>
    <main class="main-content">

        <div class="page-wrapper">
            <!-- Top Section with Back Button -->
            <div class="top-section">
                <a href="edit_student_classform.php" class="back-button">← Back</a>
            </div>
            <main class="main-content">
                <h1>Final Student Details</h1>
                <form action="update_student1.php" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token']; ?>">

                    <div class="student-card">
                        <h2><?= htmlspecialchars($student_data['student_name']) ?></h2>
                        <p><strong>Admission No:</strong> <?= htmlspecialchars($student_data['admission_number']) ?></p>
                        <p><strong>Email:</strong> <?= htmlspecialchars($student_data['student_email']) ?></p>
                        <p><strong>Phone:</strong> <?= htmlspecialchars($student_data['student_phone']) ?></p>
                        <p><strong>Department:</strong> <?= htmlspecialchars($department_name) ?></p>

                        <!-- Courses Section -->
                        <h3>Courses:</h3>
                        <div class="badges">
                            <?php foreach ($courses as $course): ?>
                                <?php
                                $status_class = 'status-blue';
                                if (strtolower($course['status']) == 'start') $status_class = 'status-green';
                                elseif (strtolower($course['status']) == 'in-progress') $status_class = 'status-yellow';
                                elseif (strtolower($course['status']) == 'ended') $status_class = 'status-red';
                                ?>
                                <p><strong><?= $course['name'] ?></strong> <span class="status-icon <?= $status_class; ?>"><?= $course['status'] ?></span></p>
                            <?php endforeach; ?>
                        </div>

                        <!-- Classes Section -->
                        <h3>Classes:</h3>
                        <div class="badges">
                            <?php foreach ($classes as $course_id => $class_list): ?>
                                <?php foreach ($class_list as $class): ?>
                                    <p class="class-item"><?= $class ?></p>
                                <?php endforeach; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="action-buttons">
                        <button type="submit" class="submit-btn">Submit</button>
                        <button type="button" class="cancel-btn" onclick="window.location.href='display_student.php?student_id=<?= $student_data['student_id'] ?>'">Cancel</button>
                    </div>
                </form>
            </main>
        </div>
    </main>
</body>

</html>