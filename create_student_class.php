<?php

// Include database connection and session management
include 'db_connection.php';
include 'session_management.php';

// Check if the user is authenticated
if (!isset($_SESSION['session_userid']) || !isset($_SESSION['session_role'])) {
    echo "<h2>Unauthorized access. Please log in.</h2>";
    header('Refresh: 3; URL=login.php');
    exit;
}

// Ensure required session data is set
if (!isset($_SESSION['student_data'])) {
    echo "<h2>Student data missing in session.</h2>";
    exit;
}
if (!isset($_SESSION['selected_courses']) || !is_array($_SESSION['selected_courses'])) {
    echo "<h2>No courses selected. Please restart the process.</h2>";
    exit;
}

// Generate CSRF token if missing
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Get selected courses from the session
$selected_courses = $_SESSION['selected_courses'];
$class_data = [];
$course_names = [];

// Fetch course names dynamically
foreach ($selected_courses as $course_id) {
    // Validate course ID
    if (!is_numeric($course_id)) {
        echo "<h2>Invalid course ID detected. Please restart the process.</h2>";
        exit;
    }
    // Fetch course names
    $stmt = $conn->prepare("SELECT course_name FROM course WHERE course_id = ?");
    $stmt->bind_param("i", $course_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $course_names[$course_id] = $result->num_rows > 0 ? $result->fetch_assoc()['course_name'] : 'Unknown Course';

    // Fetch class data for the course
    $stmt = $conn->prepare("SELECT class_id, class_name FROM class WHERE course_id = ?");
    $stmt->bind_param("i", $course_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $class_data[$course_id] = $result; 
}
// Initialize error message
$error_message = "";

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die('Invalid CSRF token. Please reload the page and try again.');
    }

    if (!isset($_POST['classes']) || !is_array($_POST['classes'])) {
        echo "<h2>No classes were selected. Please try again.</h2>";
        exit;
    }

    $_SESSION['selected_classes'] = $_POST['classes'];

    // Redirect to submit_student.php
    include "submit_student.php";
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Select Classes</title>
    <link rel="stylesheet" href="style.css">
    <style>
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
        }

        h2 {
            font-size: 22px;
            color: #112633;
        }

        /* Form Styles */
        form {
            padding: 1.5rem;
            text-align: left;
        }

        .form-group {
            margin-bottom: 1rem;
        }
 
        .radio-section {
            margin-bottom: 2rem; /* Adjust the space between last radio option and submit */
        }


        /* Style the horizontal line under each course name */
        .course-container hr {
            border: none;
            border-top: 2px solid #3b667e;
            margin: 0.5rem 0 1rem;
        }

        /* Radio button styling */
        .radio-section label {
            font-size: 18px;
            display: flex;
            align-items: center;
            cursor: pointer;
        }

        .radio-section input[type="radio"] {
            margin-right: 10px;
            transform: scale(1.2);
        }

        button {
            display: block;
            width: 40%;
            padding: 0.8rem;
            background-color: #3b667e;
            color: white;
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

        .error-message {
            background-color: #ffdddd;
            color: #d8000c;
            border: 1px solid #d8000c;
            border-radius: 5px;
            padding: 1rem;
            margin-bottom: 1rem;
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

    </style>
</head>
<body>
<?php include('admin_header.php'); ?>
    <main class="main-content">
        <h1>Step 3: Select Classes</h1>
        <div class="form-container">
            <h2>Classes under courses</h2>
            <?php if (!empty($error_message)): ?>
                <div class="error-message">
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>
            <form action="create_student_class.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                <?php foreach ($selected_courses as $course_id): ?>
                    <div class="course-container">
                        <!-- Display course name -->
                        <h3>Course: <?php echo htmlspecialchars($course_names[$course_id]); ?></h3>
                        
                        <!-- Add a horizontal rule for separation -->
                        <hr>
                        
                        <!-- Display the class options -->
                        <div class="radio-section">
                            <?php 
                            // Ensure classes exist for each course
                            if (isset($class_data[$course_id]) && $class_data[$course_id]->num_rows > 0): 
                                while ($class = $class_data[$course_id]->fetch_assoc()): 
                            ?>
                                <label>
                                    <input type="radio" name="classes[<?php echo $course_id; ?>]" value="<?php echo $class['class_id']; ?>" required>
                                    <?php echo htmlspecialchars($class['class_name']); ?>
                                </label><br>
                            <?php 
                                endwhile; 
                            else: 
                            ?>
                                <p>No classes available for this course.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>

                <!-- Submit button -->
                <button type="submit">Done</button>
            </form>
        </div>
    </main>
</body>
</html>
