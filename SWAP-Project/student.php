<?php

include 'db_connection.php';
include 'csrf_protection.php';

$error_message_logout = "";
$error_message = "";

// Check if the user is authenticated
if (!isset($_SESSION['session_userid']) || !isset($_SESSION['session_roleid'])) {
    $error_message_logout = "Unauthorized access. Please log in.";
}

$role_id = $_SESSION['session_roleid']; // Get role ID from the session
$user_id = $_SESSION['session_userid']; // Get user ID from the session

// Prepare and execute query for departments
$departments_query = "SELECT * FROM department";
$departments_stmt = mysqli_prepare($conn, $departments_query);
mysqli_stmt_execute($departments_stmt);
$departments_result = mysqli_stmt_get_result($departments_stmt);

// Prepare and execute query for courses
$courses_query = "SELECT * FROM course";
$courses_stmt = mysqli_prepare($conn, $courses_query);
mysqli_stmt_execute($courses_stmt);
$courses_result = mysqli_stmt_get_result($courses_stmt);

// Prepare and execute query for classes
$classes_query = "SELECT * FROM class";
$classes_stmt = mysqli_prepare($conn, $classes_query);
mysqli_stmt_execute($classes_stmt);
$classes_result = mysqli_stmt_get_result($classes_stmt);

// Close the statements
mysqli_stmt_close($departments_stmt);
mysqli_stmt_close($courses_stmt);
mysqli_stmt_close($classes_stmt);

// Build the SQL query dynamically based on user input for search and filters
$search_term = isset($_POST['search']) ? $_POST['search'] : '';
$department_filter = isset($_POST['department']) ? $_POST['department'] : '';
$course_filter = isset($_POST['course']) ? $_POST['course'] : '';
$class_filter = isset($_POST['class']) ? $_POST['class'] : '';

// Validate filters
if ($department_filter && !ctype_digit($department_filter)) {
    $error_message = "Invalid department filter.";
}
if ($course_filter && !ctype_digit($course_filter)) {
    $error_message = "Invalid course filter.";
}
if ($class_filter && !ctype_digit($class_filter)) {
    $error_message = "Invalid class filter.";
}

// Base query for fetching students
$query = "
    SELECT 
        student.student_id, 
        student.student_name, 
        student.profile_picture, 
        user.admission_number, 
        department.department_name, 
        class.class_name
    FROM student 
    JOIN user ON student.user_id = user.user_id
    JOIN student_course sc ON student.student_id = sc.student_id
    JOIN class ON sc.course_id = class.course_id
    JOIN department ON student.department_id = department.department_id
    JOIN course ON sc.course_id = course.course_id
    WHERE student.student_name LIKE ?
";


$params = ["%$search_term%"];

// If the user is a faculty (role_id == 2), restrict students to their courses
if ($role_id == 2) {
    // Get the faculty_id from the session
    $faculty_id = $_SESSION['session_facultyid']; // Assuming the faculty_id is already stored in the session

    // Modify the query to include courses taught by the faculty
    $query .= "
        AND sc.course_id IN (
            SELECT fc.course_id
            FROM faculty_course fc
            WHERE fc.faculty_id = ?
        )
    ";
    $params[] = $faculty_id;
}

// Apply department filter if provided
if ($department_filter) {
    $query .= " AND department.department_id = ?";
    $params[] = $department_filter;
}

// Apply course filter
if ($course_filter) {
    $query .= " AND course.course_id = ?";
    $params[] = $course_filter;
}

// Apply class filter if provided
if ($class_filter) {
    $query .= " AND class.class_id = ?";
    $params[] = $class_filter;
}

// Prepare and execute the query
$stmt = $conn->prepare($query);
$stmt->bind_param(str_repeat('s', count($params)), ...$params);
$stmt->execute();
$result = $stmt->get_result();

// Check if the query was successful
if ($result) {
    $students = mysqli_fetch_all($result, MYSQLI_ASSOC);
} else {
    $error_message = "Error fetching students data";
    $students = [];
}

// Validate CSRF token (Changed to POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['csrf_token']) && $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    $error_message = "Invalid CSRF token. Please reload the page and try again.";
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="stylesheet" href="css/student.css">

</head>

<body>
    <?php include('admin_header.php'); ?>
    <main class="main-content">
        <h1>Viewing All Student Records</h1>
        <?php if (!empty($error_message)): ?>
            <div class="error-modal" id="errorModal" style="display: flex;">
                <div class="error-modal-content">
                    <h2>Error</h2>
                    <p><?php echo htmlspecialchars($error_message); ?></p>
                    <form method="POST" action="student.php">
                        <button type="submit">Go Back</button>
                    </form>
                </div>
            </div>
        <?php else: ?>
            <?php if (!empty($error_message_logout)): ?>
                <div class="error-modal" id="errorModal" style="display: flex;">
                    <div class="error-modal-content">
                        <h2>Error</h2>
                        <p><?php echo htmlspecialchars($error_message_logout); ?></p>
                        <form method="POST" action="logout.php">
                            <button type="submit">Go Back</button>
                        </form>
                    </div>
                </div>
            <?php else: ?>
                <!-- Main Content -->
                <main class="main-content">

                    <!-- Filters Section -->
                    <section class="filters">
                        <form method="POST" action="">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                            <input type="text" name="search" placeholder="Search by name" value="<?php echo htmlspecialchars($search_term); ?>">

                            <select name="department">
                                <option value="">Select Department</option>
                                <?php while ($department = mysqli_fetch_assoc($departments_result)): ?>
                                    <option value="<?php echo $department['department_id']; ?>" <?php echo ($department_filter == $department['department_id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($department['department_name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>

                            <select name="course">
                                <option value="">Select Course</option>
                                <?php while ($course = mysqli_fetch_assoc($courses_result)): ?>
                                    <option value="<?php echo $course['course_id']; ?>" <?php echo ($course_filter == $course['course_id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($course['course_name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>

                            <select name="class">
                                <option value="">Select Class</option>
                                <?php while ($class = mysqli_fetch_assoc($classes_result)): ?>
                                    <option value="<?php echo $class['class_id']; ?>" <?php echo ($class_filter == $class['class_id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($class['class_name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>

                            <button type="submit">Apply Filters</button>
                            <a href="create_student.php" class="create-student-btn">Create Student</a>
                        </form>
                    </section>


                    <section class="students-container">
                        <div class="students">
                            <?php foreach ($students as $student): ?>
                                <form method="POST" action="display_student.php" class="student-card-form">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                    <input type="hidden" name="student_id" value="<?php echo htmlspecialchars($student['student_id']); ?>">
                                    <button type="submit" class="student-card-button">
                                        <div class="student-card">
                                            <img src="<?php echo htmlspecialchars($student['profile_picture'] ?: 'image/default_pfp.jpeg'); ?>" alt="Profile Picture" class="student-profile-pic">
                                            <h3><?php echo htmlspecialchars($student['student_name']); ?></h3>
                                            <p>Admission No: <?php echo htmlspecialchars($student['admission_number']); ?></p>
                                        </div>
                                    </button>
                                </form>
                            <?php endforeach; ?>
                        </div>
                    </section>
                <?php endif; ?>
            <?php endif; ?>
                </main>


    </main>

</body>

</html>