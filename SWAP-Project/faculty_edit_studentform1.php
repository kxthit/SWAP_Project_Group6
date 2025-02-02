<?php

// Include the database connection
include 'csrf_protection.php';
include_once 'db_connection.php';

// Check for POST submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // CSRF check
    if (!isset($_POST['csrf_token']) || !validate_csrf_token($_POST['csrf_token'])) {
        regenerate_csrf_token();
        die("Invalid CSRF token. <a href='logoutform.php'>Try again</a>");
    }

$error_message = "";

// Check if the user is authenticated
if (!isset($_SESSION['session_userid']) || !isset($_SESSION['session_role'])) {
    $error_message= "Unauthorized access. Please log in.";
    header('Refresh: 3; URL=logout.php');
    exit;
}   

// Restrict access to only Admins and Faculty
if ($_SESSION['session_role'] !== 2) {
    $error_message= "Unauthorized access.";
    exit;
}

// Handle `student_id` securely
if (isset($_GET['student_id']) && is_numeric($_GET['student_id'])) {
    $_SESSION['session_studentid'] = intval($_GET['student_id']); // Store in session
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

        h1 {
            color: #1a1a1a;
            margin-bottom: 1.5rem;
            text-align: center;
            margin-top: -28px;
        }

        h2 {
            font-size: 22px;
            color: #112633;
        }

        /* Form Styles */
        form {
            padding: 1.5rem;
        }

        .form-row {
            display: flex;
            gap: 4rem; /* Space between photo and table */
            flex-wrap: wrap;
            margin-bottom: 1.5rem;
        }

        .photo-upload {
            flex: 0 0 10%; /* Photo box column takes 30% width */
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-start;
            height: 100px;
            margin-left: 15px;
            margin-top: 12px;
        }
        .photo-upload label {
            align-self: flex-start; /* Ensure the label aligns to the left */ 
            margin-left: -30px;
        }

        .photo-box {
            border: 2px dashed #ccc;
            border-radius: 5px;
            padding: 2rem;
            text-align: center;
            color: #888;
            cursor: pointer;
            height: 200px; /* Adjust height as needed */
            width: 100%;
        }

        #image-preview {
            margin-top: 20px;
            border: 1px solid #ccc;
            display: block;
        }

        .details-table {
            flex: 1; /* Table takes up the remaining space */
            width: 100%;
            border-collapse: collapse;
        }

        .details-table th, .details-table td {
            text-align: left;
            padding: 0.8rem;
        }

        .details-table td {
            border-bottom: none;
            padding: 0.8rem 1.5rem; /* Increase horizontal padding */
        }

        label, p {
            font-size: 18px;
            
        }

        /* Input Fields */
        input, select, button {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border-radius: 5px;
            font-size: 15px;
        }

        input {
            border: 1px solid #ccc;
            border-radius: 5px;
            padding: 0.8rem;
            font-size: 1rem;
            outline: none;
            box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        input:focus {
            border-color: #6c63ff;
        }

        select {
            width: 300px;
            padding: 10px;
            margin: 10px 0;
            border-radius: 10px;
            border: 1px solid #ddd;
        }


        button {
            background-color: #3b667e;
            color: white;
            border: none;
            cursor: pointer;
            width: 400px;
        }

        button:hover {
            background-color: #ecdfce;
            color: #2b2d42;
            box-shadow: 0 0 15px 4px #3D5671;
        }

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
            margin-top: 50px;
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
