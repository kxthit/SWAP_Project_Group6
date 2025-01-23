<?php
session_start();

// Check if user is authenticated and student data is present
if (!isset($_SESSION['session_userid'], $_SESSION['session_role'], $_SESSION['student_data'])) {
    echo "<h2>Session expired or unauthorized access. Please restart the registration process.</h2>";
    header('Refresh: 3; URL=create_student.php');
    exit;
}

// Ensure department_id exists and is valid before proceeding
if (!isset($_SESSION['student_data']['department_id']) || empty($_SESSION['student_data']['department_id'])) {
    echo "<h2>Department data is missing or invalid. Please restart the registration process.</h2>";
    header('Refresh: 3; URL=create_student.php');
    exit;
}

// Database connection
include 'db_connection.php';

// Fetch department courses
$department_id = $_SESSION['student_data']['department_id'];

$stmt = $conn->prepare("SELECT course_id, course_name FROM course WHERE department_id = ?");
$stmt->bind_param("i", $department_id);
$stmt->execute();
$courses_result = $stmt->get_result();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['courses']) && is_array($_POST['courses']) && !empty($_POST['courses'])) {
        $_SESSION['selected_courses'] = $_POST['courses']; // Save selected courses in session
        header("Location: create_student_class.php");
        exit;
    } else {
        echo "<h2 style='color:red;'>Please select at least one course before proceeding.</h2>";
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
        .checkbox-group {
            display: flex;
            flex-direction: column; /* Stack items vertically */
            gap: 1rem; /* Space between checkboxes */
        }

        button {
            display: block;
            width: 100%;
            padding: 1rem;
            background-color: #6495ed;
            color: #fff;
            border: none;
            border-radius: 5px;
            font-size: 1rem;
            cursor: pointer;
            margin-top: 1rem;
        }

        button:hover {
            background-color: #5a52d4;
        }
    </style>
</head>
<body>
<?php include('admin_header.php'); ?>
<main class="main-content">
    <h1>Select Courses</h1>
    <div class="form-container">
        <div class="form-card">
            <h2>Courses in your department</h2>
            <form action="create_student_course.php" method="POST">
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
</body>
</html>
