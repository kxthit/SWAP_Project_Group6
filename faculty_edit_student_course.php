<?php

// Include the database connection
include 'db_connection.php';
include 'session_management.php';

$error_message="";

// Check if the user is authenticated and authorized (Only Admins & Faculty)
if (!isset($_SESSION['session_userid']) || !isset($_SESSION['session_role']) || !in_array($_SESSION['session_role'], [1, 2])) {
    $error_message= "Unauthorized access. Please log in.";
    header('Refresh: 3; URL=login.php');
    exit;
}

// Get department and student ID from session
$department_id = $_SESSION['student_data']['department_id'] ?? null;
$student_id = $_SESSION['student_data']['student_id'] ?? null;
$session_userid = $_SESSION['session_userid'];

if (!$department_id || !$student_id) {
    $error_message= "Department information is missing. Please go back and select a department.";
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
        $errors[] = "The student is currently enrolled in {$course['course_name']} with status '{$course['status_name']}'. Reassignment is not allowed.";
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
        $error_message="Something went wrong. Please try again.";
    }

    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

    if ($can_reassign) {
        if (!isset($_POST['courses']) || !is_array($_POST['courses'])) {
            $error_message="Invalid course selection.";
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
            font-family: 'Source Sans Pro', sans-serif;
            background-color: #f5f7fc;
            margin: 0;
            padding: 0;
        }

        /* Container Styles */
        .form-container {
            background: #c3d9e5;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            padding: 20px;
            max-width: 1000px;
            margin: 40px auto;
            text-align: center;
            border: 2px solid #ecdfce;
        }

        form {
            padding: 1.5rem;
        }

        h1 {
            color: #1a1a1a;
            margin-bottom: 1.5rem;
            text-align: center;
        }

        h2 {
            font-size: 22px;
            color: #112633;
        }

        /* Error Messages */
        .error-messages {
            background-color: #ffdddd;
            color: #d8000c;
            border: 1px solid #d8000c;
            border-radius: 5px;
            padding: 1rem;
            margin-bottom: 1.5rem;
            text-align: left;
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

        /* Form Styles */
        .checkbox-group {
            display: flex;
            flex-direction: column;
            gap: 10px;
            text-align: left;
            margin-top: 20px;
        }

        .checkbox-group div {
            display: flex;
            align-items: center;
            justify-content: space-between;
            border: 1px solid #ddd;
            background: #f9f9f9;
            padding: 10px;
            border-radius: 5px;
        }

        .checkbox-group input[type="checkbox"] {
            margin-right: 10px;
            transform: scale(1.2);
        }

        /* Status Button Styling */
        .badges {
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin-top: 10px;
        }

        .status-icon {
            font-size: 0.875rem;
            line-height: 1.25rem;
            padding: 6px 12px;
            border-radius: 8px;
            border: 2px solid transparent;
            text-align: center;
            display: inline-block;
            font-weight: bold;
        }

        .status-green {
            background-color: rgba(34, 197, 94, 0.10);
            color: rgb(34, 197, 94);
            border: 2px solid rgb(34, 197, 94);
        }

        .status-yellow {
            background-color: rgba(255, 200, 35, 0.1);
            color: rgb(234, 179, 8);
            border: 2px solid rgb(234, 179, 8);
        }

        .status-red {
            background-color: rgba(239, 68, 68, 0.10);
            color: rgb(239, 68, 68);
            border: 2px solid rgb(239, 68, 68);
        }

        .status-blue {
            background-color: rgba(59, 130, 246, 0.10);
            color: rgb(59, 130, 246);
            border: 2px solid rgb(59, 130, 246);
        }

        /* Readonly Text */
        .readonly-text {
            font-weight: bold;
            color: #555;
            padding: 10px;
            border-radius: 5px;
            background: rgba(255, 255, 255, 0.7);
        }

        /* Button Styling */
        button {
            display: block;
            width: 40%;
            padding: 0.8rem;
            background-color: #3b667e;
            color: #fff;
            border: none;
            border-radius: 5px;
            font-size: 1rem;
            cursor: pointer;
            margin-top: 2rem;
            margin-left: auto;
            margin-right: auto;
            text-transform: uppercase;
            font-weight: bold;
        }

        button:hover {
            background-color: #ecdfce;
            color: #2b2d42;
            box-shadow: 0 0 15px 4px #3D5671;
        }

        /* Error Modal */
        .error-modal {
            display: flex;
            justify-content: center;
            align-items: center;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
        }

        .error-modal-content {
            background-color: white;
            padding: 2rem;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.3);
            max-width: 500px;
            width: 90%;
        }

        .error-modal-content h2 {
            color: #d8000c;
            margin-bottom: 1rem;
        }

        .error-modal-content button {
            background-color: #2c6485;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            font-size: 1rem;
            cursor: pointer;
        }

        .error-modal-content button:hover {
            background-color: #22303f;
        }

    </style>
</head>
<body>
<?php include('admin_header.php'); ?>
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

            <form action="faculty_edit_student_course.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token']; ?>">

                <div class="checkbox-group">
                    <?php if (!$can_reassign): ?>
                        <p class="readonly-text">
                            <strong>Course:</strong> <?= htmlspecialchars(implode(", ", $student_current_courses_display)) ?>
                        </p>
                    <?php else: ?>
                        <?php foreach ($faculty_courses as $course): 
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
                                <label>
                                    <input type="checkbox" name="courses[]" value="<?= $course['course_id'] ?>">
                                    <?= htmlspecialchars($course['course_name']) ?>
                                </label>
                                <span class="status-icon <?= $status_class; ?>">
                                    <?= htmlspecialchars($course['status_name'] ?? 'Unassigned') ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <button type="submit">Next</button>
            </form>
        </div>
    </div>
    <?php endif; ?>
</main>
</body>
</html>
