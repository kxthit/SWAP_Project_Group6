<?php

// Include the database connection
include 'db_connection.php';
include 'session_management.php';

// Check if the user is authenticated and authorized
if (!isset($_SESSION['session_userid']) || !isset($_SESSION['session_role']) || !in_array($_SESSION['session_role'], [1, 2])) {
    echo "<h2>Unauthorized access.</h2>";
    header('Refresh: 3; URL=login.php');
    exit;
}

// Get department from session
$department_id = $_SESSION['student_data']['department_id'] ?? null;
if (!$department_id) {
    echo "<h2>Department information is missing. Please go back and select a department.</h2>";
    header('Refresh: 3; URL=edit_student.php');
    exit;
}

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
        die("Something went wrong. Please try again.");
    }

    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

    if (!isset($_POST['courses']) || !is_array($_POST['courses'])) {
        die("Invalid course selection.");
    }

    $selected_courses = array_map('intval', $_POST['courses']);
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
    </style>
</head>
<body>
<?php include('admin_header.php'); ?>
<main class="main-content">
    <h1>Edit Courses</h1>
    <div class="form-container">
        <div class="form-card">
            <h2>Courses in the Selected Department</h2>
            <form action="edit_student_courseform.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token']; ?>">
                <div class="checkbox-group">
                    <?php if (!empty($courses)): ?>
                        <?php foreach ($courses as $course): ?>
                            <div>
                                <input 
                                    type="checkbox" 
                                    name="courses[]" 
                                    value="<?= $course['course_id'] ?>" 
                                    <?= in_array($course['course_id'], $selected_courses ?? []) ? 'checked' : '' ?>
                                >
                                <label>
                                    <?= htmlspecialchars($course['course_name']) ?> 
                                    [ <?= htmlspecialchars($course['status_name'] ?? 'Unassigned') ?> ]
                                </label>
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
</main>
</body>
</html>
