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

$error_message="";

// Check if the user is authenticated
if (!isset($_SESSION['session_userid']) || !isset($_SESSION['session_role'])) {
    $error_message= "Unauthorized access. Please log in.";
    header('Refresh: 3; URL=logout.php');
    exit;
}

// Restrict access to only Admins and Faculty
if ($_SESSION['session_role'] !== 1) {
    $error_message= "Unauthorized access.";
    exit;
}

// Handle student_id safely
if (isset($_GET['student_id']) && is_numeric($_GET['student_id'])) {
    $_SESSION['session_studentid'] = intval($_GET['student_id']); // Store in session
    header("Location: edit_studentform1.php"); // Redirect to remove `student_id` from the URL
    exit();
}

// Ensure student_id is available
if (!isset($_SESSION['session_studentid'])) {
    die("Invalid or missing student ID.");
}

$student_id = $_SESSION['session_studentid'];
// Initialize error message array
$errors = [];

// Fetch departments for selection
$departments = [];
$stmt = $conn->prepare("SELECT department_id, department_name FROM department");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $departments[] = $row;
}
$stmt->close();

// Fetch existing student details if student_id is provided
$stmt = $conn->prepare("SELECT student.student_name, student.student_email, student.student_phone, student.department_id, user.user_id, user.admission_number 
                        FROM student 
                        JOIN user ON student.user_id = user.user_id 
                        WHERE student.student_id = ?");
$stmt->bind_param('i', $student_id);
$stmt->execute();
$student_result = $stmt->get_result();

if ($student_result->num_rows > 0) {
    $student = $student_result->fetch_assoc();

    // Store all student data including student_id in the session
    $_SESSION['student_data'] = [
        'student_id' => $student_id,
        'user_id' => $student['user_id'],
        'student_name' => $student['student_name'],
        'admission_number' => $student['admission_number'],
        'student_email' => $student['student_email'],
        'student_phone' => $student['student_phone'],
        'department_id' => $student['department_id'],
    ];
} else {
    error_log("Student ID $student_id not found - " . date('Y-m-d H:i:s') . "\n", 3, 'error_log.txt');
    $error_message= "An error occurred. Please try again later.";
    exit;
}
$stmt->close();

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
            'department_id' => htmlspecialchars($department_id),
        ];
        header('Location: edit_student_courseform.php');
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
            padding: 10px;
            max-width: 1000px;
            margin: 20px auto;
            text-align: center;
            border: 2px solid #ecdfce;
            position: relative;
            margin-left: 20px;
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

        /* Form Styles */
        form {
            padding: 1.5rem;
        }

        .form-row {
            display: flex;
            gap: 4rem; /* Space between columns */
            flex-wrap: wrap;
            margin-bottom: 1.5rem;
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

                <form action="edit_studentform1.php" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

                    <!-- Row with Details Table -->
                    <div class="form-row">
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
                                    <label for="department_id">Department *</label>
                                    <select id="department_id" name="department_id" required>
                                        <option value="">Select Department</option>
                                        <?php foreach ($departments as $department): ?>
                                            <option value="<?= $department['department_id'] ?>"
                                                <?= (($_POST['department_id'] ?? $_SESSION['student_data']['department_id'] ?? '') == $department['department_id']) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($department['department_name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
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
