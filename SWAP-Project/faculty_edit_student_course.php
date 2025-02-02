<?php

// Include the database connection
include 'db_connection.php';
include 'session_management.php';

$error_message = "";

// Check if the user is authenticated and authorized (Only Admins & Faculty)
if (!isset($_SESSION['session_userid']) || !isset($_SESSION['session_role']) || !in_array($_SESSION['session_role'], [1, 2])) {
    $error_message = "Unauthorized access. Please log in.";
    header('Refresh: 3; URL=login.php');
    exit;
}

// Get department and student ID from session
$department_id = $_SESSION['student_data']['department_id'] ?? null;
$student_id = $_SESSION['student_data']['student_id'] ?? null;
$session_userid = $_SESSION['session_userid'];

if (!$department_id || !$student_id) {
    $error_message = "Department information is missing. Please go back and select a department.";
    header('Refresh: 3; URL=edit_studentform1.php');
    exit;
}


// Fetch the student's currently assigned courses and their statuses
$locked_courses = [];
$reassignable_courses = [];
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
    if (in_array($row['status_name'], ['Start', 'In-Progress', 'Ended'])) {
        $locked_courses[] = $row; // Keep these courses
    } else {
        $reassignable_courses[] = $row; // These can be changed
    }
}
$stmt->close();

// Fetch faculty's available unassigned courses
$faculty_available_courses = [];
if ($_SESSION['session_role'] == 2) {
    $stmt = $conn->prepare("
        SELECT fc.course_id, c.course_name
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
        $faculty_available_courses[] = $row;
    }
    $stmt->close();
}

// Handle form submission securely
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error_message = "Something went wrong. Please try again.";
    }

    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

    // Retrieve reassigned courses
    $selected_courses = [];
    
    foreach ($reassignable_courses as $course) {
        if (!empty($_POST['new_courses'][$course['course_id']])) {
            $selected_courses[] = intval($_POST['new_courses'][$course['course_id']]);
        } else {
            $selected_courses[] = intval($course['course_id']); // Keep original if no change
        }
    }

    // **KEEP LOCKED COURSES** (Start, In-Progress, Ended)
    foreach ($locked_courses as $course) {
        $selected_courses[] = intval($course['course_id']);
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
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f5f7fc;
            margin: 0;
            padding: 0;
        }

        .form-container {
            background: #c3d9e5;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            padding: 20px;
            max-width: 1000px;
            margin: 40px auto;
            text-align: center;
            border: 2px solid #ecdfce;
            width: 200%;
            margin-right: 550px;
        }

        h1 {
            color: #1a1a1a;
            margin-bottom: 1.5rem;
            text-align: center;
        }

        .course-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .course-table th, .course-table td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        .course-table th {
            background-color: #2c6485;
            color: white;
            font-weight: bold;
        }

        .status-icon {
            padding: 6px 12px;
            border-radius: 8px;
            font-weight: bold;
        }

        .status-green {
            background-color: rgba(34, 197, 94, 0.10);
            color: rgb(34, 197, 94);
        }

        .status-yellow {
            background-color: rgba(255, 200, 35, 0.1);
            color: rgb(234, 179, 8);
        }

        .status-red {
            background-color: rgba(239, 68, 68, 0.10);
            color: rgb(239, 68, 68);
        }

        .status-blue {
            background-color: rgba(59, 130, 246, 0.10);
            color: rgb(59, 130, 246);
        }

        button {
            width: 40%;
            padding: 10px;
            background-color: #3b667e;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin-top: 20px;
            text-transform: uppercase;
            font-weight: bold;
        }

        button:hover {
            background-color: #ecdfce;
            color: #2b2d42;
        }

        
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

        .error-modal-content p {
            font-size: 1rem;
            margin-bottom: 1.5rem;
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

        /* Container for Back Button + Main Content */
        .page-wrapper {
            display: flex;
            flex-direction: column;
            align-items: center; /* Centers the student details */
            width: 100%;
            max-width: 1200px;
            margin: 0 auto; /* Centers content horizontally */
            padding-top: 20px;
            position: relative; /* Ensures proper alignment */
        }

        /* Flexbox for Back Button */
        .top-section {
            display: flex;
            justify-content: flex-start; /* Aligns Back button to the left */
            width: 100%;
            margin-top: -50px;
            margin-bottom: -100px;
            margin-left:-400px;
        }

        /* Back Button Styling */
        .back-button {
            padding: 10px 15px;
            background-color: #3b667e;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: bold;
            border: none;
            cursor: pointer;
        }

        .back-button:hover {
            background-color: #ecdfce;
            color: #2b2d42;
            box-shadow: 0 0 10px 2px #3D5671;
        }

    </style>
</head>
<body>

<?php include('admin_header.php'); ?>
<?php include('admin_header.php'); ?>
<div class="page-wrapper">
<!-- Top Section with Back Button -->
    <div class="top-section">
        <a href="faculty_edit_studentform1.php" class="back-button">← Back</a>
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
            <h2>Locked Courses (Cannot be changed)</h2>
            <table class="course-table">
                <tr><th>Course Name</th><th>Status</th></tr>
                <?php foreach ($locked_courses as $course): ?>
                    <tr>
                        <td><?= htmlspecialchars($course['course_name']) ?></td>
                        <td><span class="status-icon status-red"><?= htmlspecialchars($course['status_name']) ?></span></td>
                    </tr>
                <?php endforeach; ?>
            </table>

            <h2>Reassignable Courses</h2>
            <form action="faculty_edit_student_course.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token']; ?>">

                <table class="course-table">
                    <tr><th>From (Current Course)</th><th>To (Select New Course)</th></tr>
                    <?php foreach ($reassignable_courses as $course): ?>
                        <tr>
                            <td><?= htmlspecialchars($course['course_name']) ?></td>
                            <td>
                                <select name="new_courses[<?= $course['course_id'] ?>]">
                                    <option value="">-- Keep Current --</option>
                                    <?php foreach ($faculty_available_courses as $new_course): ?>
                                        <option value="<?= $new_course['course_id'] ?>">
                                            <?= htmlspecialchars($new_course['course_name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </table>

                <button type="submit">Next</button>
            </form>
        </div>
        <?php endif; ?>
    </main>
</div>
</body>
</html>

