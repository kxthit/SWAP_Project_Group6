<?php

// Include the database connection
include 'db_connection.php';
include 'session_management.php';

// Check if the user is authenticated and authorized (Only Admins & Faculty)
if (!isset($_SESSION['session_userid']) || !isset($_SESSION['session_role']) || !in_array($_SESSION['session_role'], [1, 2])) {
    echo "<h2>Unauthorized access.</h2>";
    header('Refresh: 3; URL=login.php');
    exit;
}

// Get department and student ID from session
$department_id = $_SESSION['student_data']['department_id'] ?? null;
$student_id = $_SESSION['student_data']['student_id'] ?? null;
$session_userid = $_SESSION['session_userid'];

if (!$department_id || !$student_id) {
    echo "<h2>Department information is missing. Please go back and select a department.</h2>";
    header('Refresh: 3; URL=edit_studentform1.php');
    exit;
}

// Initialize error message array
$errors = [];

// Fetch the student's currently assigned courses and their statuses
$student_courses = [];
$stmt = $conn->prepare("
    SELECT sc.course_id, c.course_name, s.status_name 
    FROM student_course sc
    JOIN course c ON sc.course_id = c.course_id
    LEFT JOIN status s ON c.status_id = s.status_id
    WHERE sc.student_id = ?
");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $student_courses[] = $row;
}
$stmt->close();

// Determine if reassignment is allowed
$restricted_statuses = ['Start', 'In-Progress', 'Ended'];
$can_reassign = true;
$student_current_courses_display = [];

foreach ($student_courses as $course) {
    if (in_array($course['status_name'], $restricted_statuses)) {
        $can_reassign = false;
        $errors[] = "The student is currently enrolled in <strong>{$course['course_name']}</strong> with status '{$course['status_name']}'. Reassignment is not allowed.";
        $student_current_courses_display[] = "{$course['course_name']} [ {$course['status_name']} ]";
    }
}

// If faculty role (2), fetch only their assigned courses
$faculty_courses = [];
if ($_SESSION['session_role'] == 2 && $can_reassign){
    $stmt = $conn->prepare("
        SELECT fc.course_id, c.course_name, s.status_name 
        FROM faculty_course fc
        JOIN course c ON fc.course_id = c.course_id
        LEFT JOIN status s ON c.status_id = s.status_id
        WHERE fc.faculty_id = (SELECT faculty_id FROM faculty WHERE user_id = ?)
        AND c.department_id = ?
        AND (s.status_name IS NULL OR s.status_name = 'Unassigned')
    ");
    $stmt->bind_param("ii", $session_userid, $department_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $faculty_courses[] = $row;
    }
    $stmt->close();
}

// Handle form submission securely
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Something went wrong. Please try again.");
    }

    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

    if ($can_reassign) {
        if (!isset($_POST['courses']) || !is_array($_POST['courses'])) {
            die("Invalid course selection.");
        }
        $selected_courses = array_map('intval', $_POST['courses']);
    } else {
        // If reassignment is not allowed, keep the student's existing course(s)
        $selected_courses = array_column($student_courses, 'course_id');
    }

    $_SESSION['selected_courses'] = $selected_courses;

    header('Location: edit_student_classform.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Courses</title>
    <link rel="stylesheet" href="style.css">
    <style>
         /* General Reset */
         body {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f5f7fc;
        }

        /* Container Styles */
        .form-container {
            width: 100%;
            max-width: 768px;
            margin: 2rem auto;
            background-color: #fff;
            border-radius: 10px;
            padding: 2rem;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        h1 {
            color: #1a1a1a;
            margin-bottom: 3rem;
            text-align: center;
        }

        h2 {
            background-color: #6495ed;
            color: white;
            padding: 1rem;
            border-radius: 10px 10px 0 0;
            margin-left: -32px;
            text-align: left;
            width: 800px;
            margin-top: -32px;
        }

        /* Form Styles */
        .checkbox-group {
            display: flex;
            flex-direction: column; /* Stack items vertically */
            gap: 1rem; /* Space between checkboxes */
            margin-top: 50px;
        }

        button {
            display: block;
            width: 20%;
            padding: 0.5rem;
            background-color: #6495ed;
            color: #fff;
            border: none;
            border-radius: 5px;
            font-size: 1rem;
            cursor: pointer;
            margin-top: 3rem;
            margin-left: 300px;
        }

        button:hover {
            background-color: #5a52d4;
        }

        .readonly-text {
            font-weight: bold;
            color: #555;
        }

        /* Error Message Styling */
        .error-messages {
            background-color: #ffdddd;
            color: #d8000c;
            border: 1px solid #d8000c;
            border-radius: 5px;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }
        .error-messages ul {
            list-style-type: disc;
            padding-left: 20px;
            margin: 0;
        }
        .error-messages li {
            font-size: 1rem;
            line-height: 1.5;
        }

    </style>
</head>
<body>
<?php include('admin_header.php'); ?>
<main class="main-content">
    <h1>Edit Courses</h1>
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

            <form action="faculty_edit_student_course.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token']; ?>">
                <div class="checkbox-group">
                    <?php if (!$can_reassign): ?>
                        <p class="readonly-text"><strong>Course:</strong> <?= htmlspecialchars(implode(", ", $student_current_courses_display)) ?></p>
                    <?php else: ?>
                        <?php foreach ($faculty_courses as $course): ?>
                            <div>
                                <input type="checkbox" name="courses[]" value="<?= $course['course_id'] ?>">
                                <label><?= htmlspecialchars($course['course_name']) ?> [ <?= htmlspecialchars($course['status_name'] ?? 'Unassigned') ?> ]</label>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <button type="submit">Next</button>
            </form>
        </div>
    </div>
</main>
</body>
</html>
