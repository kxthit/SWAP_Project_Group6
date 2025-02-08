<?php

// Include the database connection
include 'db_connection.php';

include 'csrf_protection.php';

$error_message = ""; // Variable to store error messages
$error_message_logout = "";

// Check if the user is authenticated
if (!isset($_SESSION['session_userid']) || !isset($_SESSION['session_roleid'])) {
    $error_message_logout = "Unauthorized access. Please log in.";
}

// Ensure student_id is received via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['student_id']) && is_numeric($_POST['student_id'])) {
    $_SESSION['session_studentid'] = intval($_POST['student_id']); // Store in session for security
} elseif (!isset($_SESSION['session_studentid'])) {
    $error_message = "Invalid or missing student ID.";
}

// Get session details
$student_id = $_SESSION['session_studentid'];
$session_userid = $_SESSION['session_userid'];
$session_role = $_SESSION['session_roleid'];

// Authorization: Ensure only admins or faculty can view this page
if ($session_role != 1 && $session_role != 2) {
    $error_message = "Unauthorized access. You do not have permission to view this page.";
}

// Faculty: Restrict access to students in assigned courses
if ($session_role == 2) {
    if (!isset($_SESSION['session_facultyid'])) {
        $error_message_logout = "Faculty ID not found in session.";
    } else {
        $faculty_id = $_SESSION['session_facultyid'];

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

        // Check if the student is enrolled in faculty's assigned courses
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

if (!$student) {
    $error_message = "Error: Student with ID $student_id does not exist.";
}

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
    <link rel="stylesheet" href="css/display_student.css">
</head>

<body>

    <?php include('admin_header.php'); ?>
    <div class="page-wrapper">
        <main class="main-content">

            <!-- Top Section with Back Button -->
            <div class="top-section">
                <form method="POST" action="student.php">
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
                    <h1>Student Details</h1>
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
                            <?php if ($_SESSION['session_roleid'] == 1): ?>
                                <a href="edit_studentform1.php?student_id=<?php echo $student['student_id']; ?>">Edit</a>
                                <a href="#" onclick="openModal(<?php echo $student['student_id']; ?>)" class="delete-button">Delete</a>
                            <?php elseif ($_SESSION['session_roleid'] == 2): ?>
                                <a href="faculty_edit_studentform1.php?student_id=<?php echo $student['student_id']; ?>">Edit</a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
            <main class="main-content">

    </div>
    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <p>Are you sure you want to delete this student?</p>
            <div class="modal-buttons">
                <form id="deleteForm" method="POST" action="delete_student.php">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <input type="hidden" name="student_id" id="studentIdToDelete">
                    <button type="submit" class="btn-confirm">Yes, Delete</button>
                </form>
                <a href="#" onclick="closeModal()" class="btn-cancel">Cancel</a>
            </div>
        </div>
    </div>

    <script>
        let studentIdToDelete = null;
        let csrfToken = "<?php echo $_SESSION['csrf_token']; ?>";

        function openModal(studentId) {
            studentIdToDelete = studentId;
            document.getElementById('studentIdToDelete').value = studentId; // Set the student ID in the hidden input
            document.getElementById('deleteModal').style.display = 'block'; // Show the modal
        }

        function closeModal() {
            document.getElementById('deleteModal').style.display = 'none';
            studentIdToDelete = null;
        }
    </script>

</body>

</html>