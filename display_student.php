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

// Generate CSRF token if missing
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Check if student_id is in GET request and store it in session
if (isset($_GET['student_id']) && is_numeric($_GET['student_id'])) {
    $_SESSION['session_studentid'] = intval($_GET['student_id']); // Store in session for safety
    header("Location: display_student.php"); // Redirect to prevent student_id from appearing in the URL
    exit();
}

// Ensure student_id is available
if (!isset($_SESSION['session_studentid'])) {
    die("Invalid or missing student ID.");
}

// Get session details
$student_id = $_SESSION['session_studentid'];
$session_userid = $_SESSION['session_userid'];
$session_role = $_SESSION['session_role'];

// Authorization: Ensure only admins or faculty can view this page
if ($session_role != 1 && $session_role != 2) {
    $error_message = "Unauthorized access. You do not have permission to view this page.";
}

// Faculty can only view students under their assigned courses
if ($session_role == 2) {
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
}

// Fetch student details
$query = "
    SELECT 
        s.student_id,
        s.student_name,
        s.student_email,
        s.student_phone,
        u.admission_number,
        d.department_name
    FROM student s
    JOIN user u ON s.user_id = u.user_id
    JOIN department d ON s.department_id = d.department_id
    WHERE s.student_id = ?
";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();
$student = $result->fetch_assoc();

// Fetch courses with status
$course_query = "
    SELECT co.course_name, st.status_name 
    FROM student_course sc
    JOIN course co ON sc.course_id = co.course_id
    JOIN status st ON co.status_id = st.status_id
    WHERE sc.student_id = ?
";
$stmt = $conn->prepare($course_query);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$course_result = $stmt->get_result();

// Fetch classes with their respective courses
$class_query = "
    SELECT c.class_name, co.course_name
    FROM student_class sc
    JOIN class c ON sc.class_id = c.class_id
    JOIN course co ON c.course_id = co.course_id
    WHERE sc.student_id = ?
";
$stmt = $conn->prepare($class_query);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$class_result = $stmt->get_result();

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
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
            display: flex; /* Enables Flexbox */
            justify-content: center; /* Centers horizontally */
            align-items: center; /* Centers vertically */
            height: 100vh; /* Makes body take full viewport height */
        }

        .main-container {
            width: 300%;
            max-width: 1000px;
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            overflow: hidden; /* Prevents content from overflowing */
            margin-top: 100px;
            justify-self: center;
        }

        h1 {
            text-align: center;
            color: #0d2444;
        }


        .student-container {
            position: relative; /* Makes child elements position relative to this */
            background: #2c6485;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
            color: white;
            border: 2px solid #ecdfce;
        }

        .student-container h2, .student-container p {
            color: #f1eaeb;
        }

        .courses-container, .classes-container {
            background: #c3d9e5;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
            overflow-y: auto;
            max-height: 150px;
            border: 1px solid #ecdfce;
        }

        /* NEW: Add a scrollable wrapper for the table */
        .table-wrapper {
            max-height: 250px; /* Set the max height */
            overflow-y: auto; /* Enables vertical scrolling */
            overflow-x: hidden; /* Prevents horizontal scroll */
        }

        /* Make sure table headers stick */
        table {
            width: 100%;
            border-collapse: collapse;
        }

        /* Keep table header fixed while scrolling */
        thead {
            position: sticky;
            top: 0;
            background-color: #2c6485; /* Keep the header blue */
            color: white;
            z-index: 2;
        }

        /* Style the table rows */
        th, td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        th {
            background-color: #2c6485;
            color: white;
        }

        /* Ensure rows alternate colors */
        tr:nth-child(even) {
            background-color: white;
        }

        tr:nth-child(odd) {
            background-color: white;
        }

        /* Optional: Hover effect */
        tr:hover {
            background-color: #f5f5f5;
        }

        .status-icon {
            font-size: 0.875rem;
            padding: 4px 8px;
            border-radius: 5px;
            display: inline-block;
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
    <div class="main-container">
        <div class="student-container">
            <h2><?php echo htmlspecialchars($student['student_name']); ?></h2>
            <p><strong>Admission No:</strong> <?php echo htmlspecialchars($student['admission_number']); ?></p>
            <p><strong>Email:</strong> <?php echo htmlspecialchars($student['student_email']); ?></p>
            <p><strong>Phone:</strong> <?php echo htmlspecialchars($student['student_phone']); ?></p>
            <p><strong>Department:</strong> <?php echo htmlspecialchars($student['department_name']); ?></p>
        </div>

        <div class="courses-container">
            
            <table>
                <tr>
                    <th>Course</th>
                    <th>Status</th>
                </tr>
                <?php while ($course = $course_result->fetch_assoc()): ?>
                    <?php 
                        $status_class = 'status-blue';
                        if (strtolower($course['status_name']) == 'start') $status_class = 'status-green';
                        elseif (strtolower($course['status_name']) == 'in-progress') $status_class = 'status-yellow';
                        elseif (strtolower($course['status_name']) == 'ended') $status_class = 'status-red';
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($course['course_name']); ?></td>
                        <td><span class="status-icon <?php echo $status_class; ?>"><?php echo htmlspecialchars($course['status_name']); ?></span></td>
                    </tr>
                <?php endwhile; ?>
            </table>
        </div>

        <!-- Classes Section -->
        <div class="classes-container">
            
            <table>
                <tr>
                    <th>Class</th>
                    <th>Course</th>
                </tr>
                <?php while ($class = $class_result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($class['class_name']); ?></td>
                        <td><?php echo htmlspecialchars($class['course_name']); ?></td>
                    </tr>
                <?php endwhile; ?>
            </table>
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
