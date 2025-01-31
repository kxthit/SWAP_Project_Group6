<?php

session_start();
// Include the database connection
include 'db_connection.php';

// Define session timeout in seconds (e.g., 15 minutes)
define('SESSION_TIMEOUT', 900); // 15 minutes

// Check for session timeout
if (isset($_SESSION['last_activity'])) {
    $inactive_time = time() - $_SESSION['last_activity'];
    if ($inactive_time > SESSION_TIMEOUT) {
        session_unset();
        session_destroy();
        header('Location: session_timeout.php'); // Redirect to timeout page
        exit;
    }
}

// Update the last activity timestamp
$_SESSION['last_activity'] = time();

$error_message = ""; // Variable to store error messages

// Check if the user is authenticated
if (!isset($_SESSION['session_userid']) || !isset($_SESSION['session_role'])) {
    $error_message = "Unauthorized access. Please log in.";
}

// Validate student_id from URL
if (!isset($_GET['student_id']) || !is_numeric($_GET['student_id'])) {
    $error_message = "Invalid or missing student ID.";
}

// Generate CSRF token if missing
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Get session details
$student_id = intval($_GET['student_id']);
$session_userid = $_SESSION['session_userid'];
$session_role = $_SESSION['session_role'];

// Authorization: Ensure only admins or faculty can view this page
if ($session_role != 1 && $session_role != 2) {
    $error_message = "Unauthorized access. You do not have permission to view this page.";
}

// Admins can view all students
if ($session_role == 1) {
    $query = "
        SELECT 
            s.student_id,
            s.student_name,
            s.student_email,
            s.student_phone,
            u.admission_number,
            c.class_name,
            d.department_name,
            GROUP_CONCAT(CONCAT(co.course_name, ' [', st.status_name, ']') ORDER BY co.course_name SEPARATOR ', ') AS courses
        FROM student s
        JOIN user u ON s.user_id = u.user_id
        JOIN student_class sc ON s.student_id = sc.student_id
        JOIN class c ON sc.class_id = c.class_id
        JOIN department d ON s.department_id = d.department_id
        JOIN student_course sc2 ON s.student_id = sc2.student_id
        JOIN course co ON sc2.course_id = co.course_id
        JOIN status st ON co.status_id = st.status_id
        WHERE s.student_id = ?
        GROUP BY s.student_id
    ";
    $params = [$student_id];
} 

// Faculty can only view students under their assigned courses
elseif ($session_role == 2) {
    // Get faculty ID
    $faculty_query = "SELECT faculty_id FROM faculty WHERE user_id = ?";
    $stmt = $conn->prepare($faculty_query);
    $stmt->bind_param("i", $session_userid);
    $stmt->execute();
    $faculty_result = $stmt->get_result();

    if ($faculty_result->num_rows === 0) {
        $error_message = "Error: Faculty not found.";
    }
    $faculty_row = $faculty_result->fetch_assoc();
    $faculty_id = $faculty_row['faculty_id'];
    $stmt->close();

    // Get courses assigned to this faculty
    $courses_query = "SELECT course_id FROM faculty_course WHERE faculty_id = ?";
    $stmt = $conn->prepare($courses_query);
    $stmt->bind_param("i", $faculty_id);
    $stmt->execute();
    $courses_result = $stmt->get_result();

    $faculty_courses = [];
    while ($course_row = $courses_result->fetch_assoc()) {
        $faculty_courses[] = $course_row['course_id'];
    }
    $stmt->close();

    // Check if student is in the faculty’s assigned courses
    if (count($faculty_courses) > 0) {
        $placeholders = implode(',', array_fill(0, count($faculty_courses), '?'));
        $student_course_query = "
            SELECT sc.course_id 
            FROM student_course sc
            WHERE sc.student_id = ? AND sc.course_id IN ($placeholders)
        ";
        
        $stmt = $conn->prepare($student_course_query);
        $stmt->bind_param(str_repeat('i', count($faculty_courses) + 1), $student_id, ...$faculty_courses);
        $stmt->execute();
        $student_course_result = $stmt->get_result();

        // If student is NOT in faculty's courses, deny access
        if ($student_course_result->num_rows === 0) {
            $error_message = "Unauthorized access. This student is not in your assigned courses.";
        }
        $stmt->close();
    } else {
        $error_message = "You have no assigned courses.";
    }

    // Retrieve student details
    $query = "
        SELECT 
            s.student_id,
            s.student_name,
            s.student_email,
            s.student_phone,
            u.admission_number,
            c.class_name,
            d.department_name,
            GROUP_CONCAT(CONCAT(co.course_name, ' [', st.status_name, ']') ORDER BY co.course_name SEPARATOR ', ') AS courses
        FROM student s
        JOIN user u ON s.user_id = u.user_id
        JOIN student_class sc ON s.student_id = sc.student_id
        JOIN class c ON sc.class_id = c.class_id
        JOIN department d ON s.department_id = d.department_id
        JOIN student_course sc2 ON s.student_id = sc2.student_id
        JOIN course co ON sc2.course_id = co.course_id
        JOIN status st ON co.status_id = st.status_id
        WHERE s.student_id = ?
        GROUP BY s.student_id
    ";
    $params = [$student_id];
}

// Prepare and execute the query
$stmt = $conn->prepare($query);
$stmt->bind_param("i", ...$params);
$stmt->execute();
$result = $stmt->get_result();
$student = $result->fetch_assoc();

// Handle unauthorized access
if (!$student) {
    $error_message = "Student not found or unauthorized access.";
}

?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Details</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* General Reset */
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }

        h1 {
            text-align: center;
            color: #0d2444;
        }

        /* Student Card Fixed Size & Centered */
        .student-card {
            width: 800px; /* Fixed width */
            min-height: 400px; /* Adjust as needed */
            margin: 0 auto; /* Center the card */
            background: #2c6485;
            border-radius: 10px;
            padding: 20px;
            color: white;
            border: 2px solid #ecdfce;
        }


        .student-card h2 {
            color: #f1eaeb;
            margin-bottom: 10px;
        }

        .student-card p {
            margin: 5px 0;
            font-size: 16px;
            color: #ecdfce;
        }

        .badges {
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin-top: 10px;
        }

        .status-icon {
            font-size: 0.875rem;
            line-height: 1.25rem;
            padding: 4px 0.75rem;
            border-radius: 0.375rem;
            border: 1px solid transparent;
            text-align: center;
            display: inline-block;
            font-weight: bold;
        }

        .status-green {
            background-color: rgba(34, 197, 94, 0.10);
            color: rgb(34, 197, 94);
            border-color: rgb(34, 197, 94);
        }

        .status-yellow {
            background-color: rgba(255, 200, 35, 0.1);
            color: rgb(234, 179, 8);
            border-color: rgb(234, 179, 8);
        }

        .status-red {
            background-color: rgba(239, 68, 68, 0.10);
            color: rgb(239, 68, 68);
            border-color: rgb(239, 68, 68);
        }

        .status-blue {
            background-color: rgba(59, 130, 246, 0.10);
            color: rgb(59, 130, 246);
            border-color: rgb(59, 130, 246);
        }

        .class-item {
            font-size: 16px;
            font-weight: bold;
            color: white;
            background: rgba(255, 255, 255, 0.2); /* Light transparent background */
            padding: 6px 12px;
            border-radius: 6px;
            display: inline-block;
            border: 1px solid white;
        }


        .class-item {
            font-size: 16px;
            font-weight: bold;
            color: white;
            background: rgba(255, 255, 255, 0.2);
            padding: 6px 12px;
            border-radius: 6px;
            display: inline-block;
            border: 1px solid white;
        }

        /* Ensure Edit Button is Fixed in Position */
        .action-buttons {
            text-align: center;
            margin-top: 20px;
        }

        .action-buttons a {
            padding: 10px 20px;
            text-decoration: none;
            color: white;
            background-color: #22303f;
            border-radius: 5px;
            font-size: 1rem;
        }

        .action-buttons a:hover {
            background-color: #10171e;
        }

        .delete-button {
            background-color: #dc3545;
        }

        .delete-button:hover {
            background-color: #b52b3a;
        }

        .modal {
            display: none; /* Hidden by default */
            position: fixed; 
            z-index: 1; 
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.4); /* Black with transparency */
            align-content: center;
        }

        .modal-content {
            background-color: #f4f4f4;
            margin: 15% auto; /* 15% from the top and centered */
            padding: 20px;
            border: 1px solid #888;
            width: 30%; /* Could be more or less, depending on screen size */
            text-align: center;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        .modal-buttons {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 20px;
        }

        .modal-buttons a {
            padding: 8px 16px;
            background-color: #2c6485;
            color: white;
            text-decoration: none;
            border-radius: 4px;
        }

        .modal-buttons a:hover {
            background-color: #22303f;
        }

        .modal-buttons .btn-cancel {
            background-color: #6c757d;
        }

        .modal-buttons .btn-cancel:hover {
            background-color: #545b62;
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

    </style>
</head>
<body>

<?php include('admin_header.php'); ?>
<main class="main-content">
    <h1>Student Details</h1>
    <?php if (!empty($error_message)): ?>
        <div class="error-modal" id="errorModal" style="display: flex;">
            <div class="error-modal-content">
                <h2>Error</h2>
                <p><?php echo htmlspecialchars($error_message); ?></p>
                <button onclick="window.location.href='student.php'">Go Back</button>
            </div>
        </div>
    <?php else: ?>
    <div class="container">
        <div class="student-card">
            <h2><?php echo htmlspecialchars($student['student_name']); ?></h2>
            <p><strong>Admission No:</strong> <?php echo htmlspecialchars($student['admission_number']); ?></p>
            <p><strong>Email:</strong> <?php echo htmlspecialchars($student['student_email']); ?></p>
            <p><strong>Phone:</strong> <?php echo htmlspecialchars($student['student_phone']); ?></p>
            <p><strong>Department:</strong> <?php echo htmlspecialchars($student['department_name']); ?></p>

            <!-- Courses Section -->
            <h3>Courses:</h3>
            <div class="badges">
                <?php 
                $courses = explode(',', $student['courses']);
                foreach ($courses as $course): 
                    $course_parts = explode(' [', $course);
                    $course_name = trim($course_parts[0]);
                    $course_status = isset($course_parts[1]) ? rtrim($course_parts[1], ']') : '';

                    $status_class = 'status-blue';
                    if (strtolower($course_status) == 'start') $status_class = 'status-green';
                    elseif (strtolower($course_status) == 'in-progress') $status_class = 'status-yellow';
                    elseif (strtolower($course_status) == 'ended') $status_class = 'status-red';
                ?>
                    <p>
                        <strong><?php echo htmlspecialchars($course_name); ?></strong> 
                        <span class="status-icon <?php echo $status_class; ?>">
                            <?php echo htmlspecialchars($course_status); ?>
                        </span>
                    </p>
                <?php endforeach; ?>
            </div>

            <!-- Classes Section -->
            <h3>Classes:</h3>
            <div class="class-list">
                <?php 
                $classes = explode(',', $student['class_name']);
                foreach ($classes as $class): ?>
                    <p class="class-item"><?php echo htmlspecialchars(trim($class)); ?></p>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="action-buttons">
            <?php if ($_SESSION['session_role'] == 1): ?>
                <a href="edit_studentform1.php?student_id=<?php echo $student['student_id']; ?>">Edit</a>
                <a href="#" onclick="openModal(<?php echo $student['student_id']; ?>)" class="delete-button">Delete</a>
            <?php elseif ($_SESSION['session_role'] == 2): ?>
                <a href="faculty_edit_studentform1.php?student_id=<?php echo $student['student_id']; ?>">Edit</a>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</main>

<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="modal">
    <div class="modal-content">
        <p>Are you sure you want to delete this student?</p>
        <div class="modal-buttons">
            <a href="#" id="confirmDelete" class="btn-confirm">Yes, Delete</a>
            <a href="#" onclick="closeModal()" class="btn-cancel">Cancel</a>
        </div>
    </div>
</div>

<script>
    let studentIdToDelete = null;
    let csrfToken = "<?php echo $_SESSION['csrf_token']; ?>"; 

    function openModal(studentId) {
        studentIdToDelete = studentId;
        document.getElementById('deleteModal').style.display = 'block';
        document.getElementById('confirmDelete').href = `delete_student.php?student_id=${encodeURIComponent(studentId)}&csrf_token=${csrfToken}`;
    }

    function closeModal() {
        document.getElementById('deleteModal').style.display = 'none';
        studentIdToDelete = null;
    }
</script>

</body>
</html>