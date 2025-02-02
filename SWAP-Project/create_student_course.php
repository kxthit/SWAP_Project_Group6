<?php

// Include necessary files
include 'db_connection.php';
include 'session_management.php';

// Check if user is authenticated and student data is present
if (!isset($_SESSION['session_userid'], $_SESSION['session_role'], $_SESSION['student_data'])) {
    echo "<h2>Session expired or unauthorized access. Please restart the registration process.</h2>";
    header('Refresh: 3; URL=create_student.php');
    exit;
}

// Generate CSRF token if missing
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Ensure department_id exists and is valid before proceeding
if (!isset($_SESSION['student_data']['department_id']) || empty($_SESSION['student_data']['department_id'])) {
    echo "<h2>Department data is missing or invalid. Please restart the registration process.</h2>";
    header('Refresh: 3; URL=create_student.php');
    exit;
}


// Fetch department courses
$department_id = $_SESSION['student_data']['department_id'];
$stmt = $conn->prepare("SELECT course_id, course_name FROM course WHERE department_id = ?");
$stmt->bind_param("i", $department_id);
$stmt->execute();
$courses_result = $stmt->get_result();

// Initialize errors array
$errors = [];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die('<h2 style="color: red;">Invalid CSRF token. Please reload the page and try again.</h2>');
    }
    // Validate selected courses
    if (isset($_POST['courses']) && is_array($_POST['courses']) && !empty($_POST['courses'])) {
        $_SESSION['selected_courses'] = $_POST['courses']; // Save selected courses in session
        header("Location: create_student_class.php");
        exit;
    } else {
        $errors[] = "Please select at least one course before proceeding.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Select Courses</title>
    <link rel="stylesheet" href="style.css">
    <style>
         /* General Reset */
         body {
            font-family: 'Source Sans Pro', sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f5f7fc;
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
            width: 200%;
            margin-right: 600px;
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

        /* Form Styles */
        .checkbox-group {
            display: flex;
            flex-direction: column;
            gap: 1rem; /* Space between checkboxes */
            text-align: left;
            margin-top: 20px;
        }

        .checkbox-group div {
            display: flex;
            align-items: center;
        }

        .checkbox-group input[type="checkbox"] {
            margin-right: 10px;
            transform: scale(1.2);
        }

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
        <a href="create_student.php" class="back-button">← Back</a>
    </div>
    <main class="main-content">
        <h1>Select Courses</h1>
        <div class="form-container">
            <div class="form-card">
                <h2>Courses in your department</h2>
                <?php if (!empty($errors)): ?>
                    <div class="error-messages">
                        <ul>
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                <form action="create_student_course.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                    <div class="checkbox-group">
                        <?php if ($courses_result && $courses_result->num_rows > 0): ?>
                            <?php while ($course = $courses_result->fetch_assoc()): ?>
                                <div>
                                    <input type="checkbox" name="courses[]" value="<?php echo htmlspecialchars($course['course_id']); ?>">
                                    <label><?php echo htmlspecialchars($course['course_name']); ?></label>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <p>No courses available for the selected department.</p>
                        <?php endif; ?>
                    </div>
                    <button type="submit">Next</button>
                </form>
            </div>
        </div>
    </main>
</div>
</body>
</html>
