<?php
session_start();

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

// Database connection
include 'db_connection.php';

// Get selected courses from the session
$selected_courses = $_SESSION['selected_courses'];
$class_data = [];
$course_names = [];

// Fetch course names dynamically
foreach ($selected_courses as $course_id) {
    $stmt = $conn->prepare("SELECT course_name FROM course WHERE course_id = ?");
    $stmt->bind_param("i", $course_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $course_names[$course_id] = $result->num_rows > 0 ? $result->fetch_assoc()['course_name'] : 'Unknown Course';

    $stmt = $conn->prepare("SELECT class_id, class_name FROM class WHERE course_id = ?");
    $stmt->bind_param("i", $course_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $class_data[$course_id] = $result; 
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['classes']) || !is_array($_POST['classes'])) {
        echo "<h2>No classes were selected. Please try again.</h2>";
        exit;
    }

    $_SESSION['selected_classes'] = $_POST['classes'];

    // Include the code from submit_student.php
    include 'faculty_submit_student.php';
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
            max-width: 768px;
            margin: 2rem auto;
            background-color: #fff;
            border-radius: 10px;
            padding: 2rem;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        h1 {
            color: #1a1a1a;
            margin-bottom: 3rem;
            margin-left: 10px;
        }

        h2 {
            background-color: #6495ed;
            color: white;
            padding: 1rem;
            border-radius: 10px 10px 0 0;
            margin-left: -32px;
            text-align: left;
            width: 800px;
            margin-top: -32px;
        }

        /* Form Styles */
        form {
            padding: 1.5rem;
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
            border-top: 2px solid #6495ed;
            margin: 0.5rem 0 1rem;
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
            margin-left: 300px;
        }

        button:hover {
            background-color: #5a52d4;
        }


    </style>
</head>
<body>
<?php include('faculty_header.php'); ?>
    <main class="main-content">
        <h1>Step 3: Select Classes</h1>
        <div class="form-container">
            <h2>Classes under courses</h2>
            <form action="faculty_create_student_class.php" method="POST">
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
                <button type="submit">Next</button>
            </form>
        </div>
    </main>
</body>
</html>
