<?php

// Include the database connection
include 'db_connection.php';
include 'csrf_protection.php';

$error_message = "";

// Check if the user is authenticated
if (!isset($_SESSION['session_userid']) || !isset($_SESSION['session_roleid'])) {
    $error_message= "Unauthorized access. Please log in.";
    header('Refresh: 3; URL=login.php');
    exit;
}

// Restrict access to only Admins and Faculty
if ($_SESSION['session_roleid'] !== 2) {
    $error_message= "Unauthorized access.";
    exit;
}

// Handle `student_id` securely (using POST instead of GET)
if (isset($_POST['student_id']) && is_numeric($_POST['student_id'])) {
    $_SESSION['session_studentid'] = intval($_POST['student_id']); // Store in session
    header("Location: faculty_edit_studentform1.php"); // Redirect to remove `student_id` from the URL
    exit();
}
// Ensure student_id is available
if (!isset($_SESSION['session_studentid'])) {
    die("Invalid or missing student ID.");
}

// Initialize error message array
$errors = [];

// Faculty Restriction: Ensure faculty can only edit students under their assigned courses
$student_id = $_SESSION['session_studentid'];
$session_userid = $_SESSION['session_userid'];
$session_role = $_SESSION['session_role'];


// Faculty Restriction: Ensure faculty can only edit students under their assigned courses
$student_id = $_SESSION['session_studentid'];
$session_userid = $_SESSION['session_userid'];
$session_role = $_SESSION['session_role'];

// Fetch faculty ID from session
$faculty_id = $_SESSION['session_facultyid'] ?? null; // Get faculty_id from session
if (!$faculty_id) {
    $error_message = "Error: Faculty not found in session.";
    exit;
}

// Get faculty's assigned courses
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

// Ensure the student is in one of the faculty's courses
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

    // Deny access if student is not in faculty's courses
    if ($student_course_result->num_rows === 0) {
        $error_message = "Unauthorized access. This student is not in your assigned courses.";
    }
    $stmt->close();
} else {
    $error_message = "You have no assigned courses.";
}

// Fetch student details including department
$stmt = $conn->prepare("
    SELECT 
        s.student_name, 
        s.student_email, 
        s.student_phone, 
        s.department_id, 
        d.department_name, 
        u.user_id, 
        u.admission_number
    FROM student s
    JOIN user u ON s.user_id = u.user_id
    JOIN department d ON s.department_id = d.department_id
    WHERE s.student_id = ?
");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$student_result = $stmt->get_result();
$stmt->close();

if ($student_result->num_rows === 0) {
    $error_message = "Error: Student not found.";
}
$student = $student_result->fetch_assoc();

// Store student data in session
$_SESSION['student_data'] = [
    'student_id' => $student_id,
    'user_id' => $student['user_id'],
    'student_name' => $student['student_name'],
    'admission_number' => $student['admission_number'],
    'student_email' => $student['student_email'],
    'student_phone' => $student['student_phone'],
    'department_id' => $student['department_id'],
    'department_name' => $student['department_name'], // Store department name for display
];

// Handle form submission for student update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF token validation
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $errors[] = "Invalid session token. Please try again.";
    } else {
        // Regenerate CSRF token after submission
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    // Extract form data
    $student_name = trim($_POST['student_name'] ?? '');
    $admission_number = trim($_POST['admission_number'] ?? '');
    $student_email = trim($_POST['student_email'] ?? '');
    $student_phone = trim($_POST['student_phone'] ?? '');
    $department_id = trim($_POST['department_id'] ?? '');

    // Validate inputs
    if (empty($student_name) || empty($admission_number) || empty($student_email) || empty($student_phone) || empty($department_id)) {
        $errors[] = "All fields must be filled up.";
    }
    if (!preg_match('/^[a-zA-Z\s]+$/', $student_name)) {
        $errors[] = "Invalid name. Only letters and spaces are allowed.";
    }
    if (!preg_match('/^\d{7}[A-Z]$/', $admission_number)) {
        $errors[] = "Invalid admission number. It should be 7 digits followed by an uppercase letter.";
    }
    if (!preg_match('/^\d{8}$/', $student_phone)) {
        $errors[] = "Invalid phone number. It should be exactly 8 digits.";
    }
    if (!filter_var($student_email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    }
    $allowed_domains = ['@gmail.com', '@yahoo.com', '@hotmail.com', '@outlook.com'];
    $email_valid = false;
    foreach ($allowed_domains as $domain) {
        if (str_ends_with($student_email, $domain)) {
            $email_valid = true;
            break;
        }
    }
    if (!$email_valid) {
        $errors[] = "Invalid email. It must end with @gmail.com, @yahoo.com, @hotmail.com, or @outlook.com.";
    }

    // If no errors, save the data in session and redirect
    if (empty($errors)) {
        $_SESSION['student_data'] = [
            'student_id' => $_SESSION['student_data']['student_id'],
            'user_id' => $_SESSION['student_data']['user_id'],
            'student_name' => htmlspecialchars($student_name),
            'admission_number' => htmlspecialchars($admission_number),
            'student_email' => htmlspecialchars($student_email),
            'student_phone' => htmlspecialchars($student_phone),
            'department_id' => $_SESSION['student_data']['department_id'],
            'department_name' => $_SESSION['student_data']['department_name'],
        ];
        header('Location: faculty_edit_student_course.php');
        exit;
    }
}

?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Student</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="css/faculty_edit_student.css">
</head>
<body>
<?php include('admin_header.php'); ?>
<div class="page-wrapper">
<!-- Top Section with Back Button -->
    <div class="top-section">
        <a href="display_student.php" class="back-button">← Back</a>
    </div>
    <main class="main-content">
        <h1>Edit Student Details</h1>
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
                <h2>Student Details</h2>
                <?php if (!empty($errors)): ?>
                    <div class="error-messages">
                        <ul>
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                <form action="faculty_edit_studentform1.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                <!-- Form fields here (same as before, including value retention) -->
                    <!-- Row with Photo and Table -->
                    <div class="form-row">
                        <!-- Right Column: Table -->
                        <table class="details-table">
                            <tr>
                                <td>
                                    <label for="student_name">Full Name *</label>
                                    <input type="text" id="student_name" name="student_name" value="<?= htmlspecialchars($_POST['student_name'] ?? $_SESSION['student_data']['student_name'] ?? '') ?>" required>
                                </td>
                                <td>
                                    <label for="admission_number">Admission No. *</label>
                                    <input type="text" id="admission_number" name="admission_number" value="<?= htmlspecialchars($_POST['admission_number'] ?? $_SESSION['student_data']['admission_number'] ?? '') ?>" required>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <label for="student_phone">Phone *</label>
                                    <input type="text" id="student_phone" name="student_phone" value="<?= htmlspecialchars($_POST['student_phone'] ?? $_SESSION['student_data']['student_phone'] ?? '') ?>" required>
                                </td>
                                <td>
                                    <label for="student_email">Email *</label>
                                    <input type="email" id="student_email" name="student_email" value="<?= htmlspecialchars($_POST['student_email'] ?? $_SESSION['student_data']['student_email'] ?? '') ?>" required>
                                </td>
                            </tr>
                            <tr>
                                <td colspan="2">
                                    <label>Department *</label>
                                    <input type="text" value="<?= htmlspecialchars($_SESSION['student_data']['department_name']) ?>" readonly>
                                    <input type="hidden" name="department_id" value="<?= htmlspecialchars($_SESSION['student_data']['department_id']) ?>">
                                </td>
                            </tr>
                        </table>
                    </div>
                    <button type="submit">Next</button>
                </form>
            </div>
        </div>
        <?php endif; ?>
    </main>
</div>
</body>
</html>
