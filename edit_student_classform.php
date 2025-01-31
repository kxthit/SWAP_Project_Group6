<?php

// Include the database connection
include 'db_connection.php';
include 'session_management.php';

$error_message="";

// Check if the user is authenticated and authorized
if (!isset($_SESSION['session_userid']) || !isset($_SESSION['session_role']) || !in_array($_SESSION['session_role'], [1, 2])) {
    $error_message= "Unauthorized access. Please log in.";
    header('Refresh: 3; URL=login.php');
    exit;
}

// Get selected courses from session
$selected_courses = $_SESSION['selected_courses'];
$class_data = [];
$course_names = [];

// Validate session data
if (empty($selected_courses)) {
    $error_message= "No courses selected. Please go back and select a course.";
    header('Refresh: 3; URL=edit_student_courseform.php');
    exit;
}

// Fetch course names and classes for each selected course
foreach ($selected_courses as $course_id) {
    // Fetch course name
    $stmt = $conn->prepare("SELECT course_name FROM course WHERE course_id = ?");
    $stmt->bind_param("i", $course_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $course_names[$course_id] = $result->num_rows > 0 ? $result->fetch_assoc()['course_name'] : 'Unknown Course';
}

// Fetch classes for each selected course
foreach ($selected_courses as $course_id) {
    $stmt = $conn->prepare("SELECT class_id, class_name FROM class WHERE course_id = ?");
    $stmt->bind_param("i", $course_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $class_data[$course_id] = $result;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF Token Validation
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error_message="Something went wrong. Please try again.";
    }

    // Regenerate CSRF token after validation
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

    // Validate class selection
    if (!isset($_POST['classes']) || !is_array($_POST['classes'])) {
        $errors="Invalid class selection.";
    }

    // Ensure only numeric class IDs are stored
    $selected_classes = [];
    foreach ($_POST['classes'] as $course_id => $class_ids) {
        $selected_classes[$course_id] = array_map('intval', $class_ids);
    }

    $_SESSION['selected_classes'] = $selected_classes;

    header('Location: update_student1.php');
    exit;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Classes</title>
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

        /* Checkbox styling */
        .radio-section label {
            font-size: 18px;
            display: flex;
            align-items: center;
            cursor: pointer;
        }

        .radio-section input[type="checkbox"] {
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

        .error-messages {
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

    </style>
</head>
<body>
<?php include('admin_header.php'); ?>
    <main class="main-content">
        <h1>Edit Classes</h1>
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
            <h2>Classes for Selected Courses</h2>
            
            <?php if (!empty($errors)): ?>
                <div class="error-messages">
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form action="edit_student_classform.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token']; ?>">

                <?php foreach ($selected_courses as $course_id): ?>
                    <div class="course-container">
                        <!-- Display course name -->
                        <h3>Course: <?= htmlspecialchars($course_names[$course_id]) ?></h3>
                        
                        <!-- Add a horizontal rule for separation -->
                        <hr>
                        
                        <!-- Display the class options -->
                        <div class="radio-section">
                            <?php 
                            if (isset($class_data[$course_id]) && $class_data[$course_id]->num_rows > 0): 
                                while ($class = $class_data[$course_id]->fetch_assoc()): 
                            ?>
                                <label>
                                    <input 
                                        type="checkbox" 
                                        name="classes[<?= $course_id ?>][]" 
                                        value="<?= $class['class_id'] ?>" 
                                        <?= in_array($class['class_id'], $selected_classes[$course_id] ?? []) ? 'checked' : '' ?>
                                    >
                                    <?= htmlspecialchars($class['class_name']) ?>
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
                <button type="submit">Next</button>
            </form>
        </div>
        <?php endif; ?>
    </main>
</body>
</html>
