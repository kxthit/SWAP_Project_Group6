<?php
include 'db_connection.php';
include 'csrf_protection.php';

$error_message = "";

// Check if the user is authenticated
if (!isset($_SESSION['session_userid']) || !isset($_SESSION['session_roleid'])) {
    $error_message = "Unauthorized access. Please log in.";
    header('Refresh: 3; URL=login.php');
    exit;
}

// Fetch student_id from the database based on the logged-in user's user_id
if (!isset($_SESSION['session_studentid'])) {
    $stmt = $conn->prepare("SELECT student_id FROM student WHERE user_id = ?");
    $stmt->bind_param("i", $_SESSION['session_userid']);
    $stmt->execute();
    $result = $stmt->get_result();
    $student_data = $result->fetch_assoc();

    if ($student_data) {
        $_SESSION['session_studentid'] = $student_data['student_id'];
    } else {
        $error_message = "Student record not found for this user.";
    }
}
$student_id = $_SESSION['session_studentid'];

// Fetch student details, courses, and classes
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
    <link rel="stylesheet" href="css/student_profile.css">
</head>

<body>
    <?php include('student_header.php'); ?>
    <main class="main-container">
        <h1>View Your Details</h1>
        <?php if (!empty($error_message)): ?>
            <div class="error-modal" id="errorModal" style="display: flex;">
                <div class="error-modal-content">
                    <h2>Error</h2>
                    <p><?php echo htmlspecialchars($error_message); ?></p>
                    <button onclick="window.location.href='student.php'">Go Back</button>
                </div>
            </div>
        <?php else: ?>
            <div class="student-container">
                <h2><?php echo htmlspecialchars($student['student_name']); ?></h2>
                <a href="change_password_form.php" class="change-password-btn">Change Password</a>
                <p><strong>Admission No:</strong> <?php echo htmlspecialchars($student['admission_number']); ?></p>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($student['student_email']); ?></p>
                <p><strong>Phone:</strong> <?php echo htmlspecialchars($student['student_phone']); ?></p>
                <p><strong>Department:</strong> <?php echo htmlspecialchars($student['department_name']); ?></p>
            </div>

            <!-- Courses Section -->
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
        <?php endif; ?>
    </main>
</body>

</html>