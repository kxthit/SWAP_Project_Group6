<?php

// Include the database connection
include 'db_connection.php';
include 'session_management.php';

// Check if the user is authenticated
if (!isset($_SESSION['session_userid']) || !isset($_SESSION['session_role'])) {
    echo "<h2>Unauthorized access. Please log in.</h2>";
    header('Refresh: 3; URL=login.php');
    exit;
}

// Restrict access to only Admins and Faculty
if ($_SESSION['session_role'] !== 2) {
    echo "<h2>Unauthorized access.</h2>";
    exit;
}

// Initialize error message array
$errors = [];

// Faculty Restriction: Ensure faculty can only edit students under their assigned courses
if (isset($_GET['student_id'])) {
    $student_id = intval($_GET['student_id']);
    $session_userid = $_SESSION['session_userid'];
    $session_role = $_SESSION['session_role'];

    // Get faculty ID
    $faculty_query = "SELECT faculty_id FROM faculty WHERE user_id = ?";
    $stmt = $conn->prepare($faculty_query);
    $stmt->bind_param("i", $session_userid);
    $stmt->execute();
    $faculty_result = $stmt->get_result();
    
    if ($faculty_result->num_rows === 0) {
        die("<h2>Error: Faculty not found.</h2>");
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
            die("<h2>Unauthorized access. This student is not in your assigned courses.</h2>");
        }
        $stmt->close();
    } else {
        die("<h2>You have no assigned courses.</h2>");
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
        die("<h2>Error: Student not found.</h2>");
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
}

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
            max-width: 1000px;
            margin: 1rem auto;
            background-color: #fff;
            border-radius: 10px;
            padding: 2rem;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        h1 {
            color: #1a1a1a;
            margin-bottom: 1.5rem;
            text-align: center;
            margin-top: -20px;
        }

        h2 {
            background-color: #6495ed;
            color: white;
            padding: 1rem;
            border-radius: 10px 10px 0 0;
            margin-left: -32px;
            text-align: left;
            width: 103.2%;
            margin-top: -30px;
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

        label {
            font-weight: bold;
        }

        input, textarea {
            border: 1px solid #ccc;
            border-radius: 5px;
            padding: 0.8rem;
            font-size: 1rem;
            outline: none;
            width: 100%;
            box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        input:focus, textarea:focus {
            border-color: #6c63ff;
        }

        textarea {
            resize: none;
        }

        select {
            width: 300px;
            padding: 10px;
            margin: 10px 0;
            border-radius: 10px;
            border: 1px solid #ddd;
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
            margin-left: 370px;
        }

        button:hover {
            background-color: #5a52d4;
        }

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
        <h1>Edit Student Details</h1>
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
    </main>
</body>
</html>
