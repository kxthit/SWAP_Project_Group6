<?php

// Include necessary files for session management and database connection
include 'csrf_protection.php';
include 'db_connection.php';

// Function to redirect with an alert and a custom URL
function redirect($alert, $redirect)
{
    echo "<script>
            alert('$alert'); // Alert Message 
            window.location.href = '$redirect'; // Redirect to the given URL
        </script>";
    exit;
}

// Check if the user is authenticated , if not authenticated redirect to login page.
if (!isset($_SESSION['session_userid']) || !isset($_SESSION['session_roleid'])) {
    redirect('Unauthorized user. Redirecting To Login.', 'logout.php');
}

// Check if the user has a valid role (Admin or Faculty) , if not redirect to login page.
if ($_SESSION['session_roleid'] != 1 && $_SESSION['session_roleid'] != 2) {
    redirect('You Do Not Have Permission To Access This.', 'logout.php');
}

// Retrieve user and session information
$role_id = $_SESSION['session_roleid']; // Get role ID from the session
$user_id = $_SESSION['session_userid']; // Get user ID from the session

// Fetch Faculty's Department ID if the user is Faculty (role 2)
if ($role_id == 2) {

    // Use faculty_id directly from the session
    if (!isset($_SESSION['session_facultyid'])) {
        echo "<p style='text-align:center;'>Faculty information not found in the session.</p>";
        exit;
    }
    $faculty_id = $_SESSION['session_facultyid']; //Get faculty ID from the session
    $query = "
        SELECT faculty.department_id
        FROM faculty
        WHERE faculty.user_id = ?
    ";

    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    // Store department_id in session for future use
    if ($row = $result->fetch_assoc()) {
        $_SESSION['session_facultydepartmentid'] = $row['department_id'];
    } else {
        echo ("No department found for faculty user_id: " . $user_id);
    }
}

// Fetch departments and classes for filter options using prepared statements

// Query to fetch all departments for filter dropdown
$departments_query = "SELECT * FROM department";
$departments_stmt = mysqli_prepare($conn, $departments_query);
mysqli_stmt_execute($departments_stmt);
$departments_result = mysqli_stmt_get_result($departments_stmt);

// Query to fetch all classes for filter dropdown
$classes_query = "SELECT * FROM class";
$classes_stmt = mysqli_prepare($conn, $classes_query);
mysqli_stmt_execute($classes_stmt);
$classes_result = mysqli_stmt_get_result($classes_stmt);

// Close statements
mysqli_stmt_close($departments_stmt);
mysqli_stmt_close($classes_stmt);


// Prepare the SQL query dynamically based on user input for search and filters

$search_term = isset($_POST['search']) ? $_POST['search'] : ''; // Get search term
$search_term = htmlspecialchars($search_term, ENT_QUOTES, 'UTF-8'); // Sanitize the search term

// Retrieve department and class filters
$department_filter = isset($_POST['department']) ? $_POST['department'] : '';
$class_filter = isset($_POST['class']) ? $_POST['class'] : '';

// Input validation for department and class filters (ensure they are integers)
if ($department_filter && !filter_var($department_filter, FILTER_VALIDATE_INT)) {
    $department_filter = ''; // Reset if not a valid integer
}
if ($class_filter && !filter_var($class_filter, FILTER_VALIDATE_INT)) {
    $class_filter = ''; // Reset if not a valid integer
}

// Initialize query parameters with the sanitized search term
$params = ["%$search_term%"];

// Base SQL query to fetch students
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
    WHERE student.student_name LIKE ?
";

// If the user is a faculty member (role 2), restrict the results to students in their courses
if ($role_id == 2) {
    $query .= "
        AND sc.course_id IN (
            SELECT fc.course_id
            FROM faculty_course fc
            JOIN faculty f ON fc.faculty_id = f.faculty_id
            WHERE f.user_id = ?
        )
    ";

    $params[] = $user_id;  // Add faculty user ID to the query parameters
}

// Apply department filter if provided
if ($department_filter) {
    $query .= " AND department.department_id = ?"; // Filter by department
    $params[] = $department_filter; // Add department filter to the parameters
}

// Apply class filter if provided
if ($class_filter) {
    $query .= " AND class.class_id = ?";  // Filter by class
    $params[] = $class_filter;  // Add class filter to the parameters
}

// Prepare and execute the SQL statement for students
$stmt = $conn->prepare($query);
$stmt->bind_param(str_repeat('s', count($params)), ...$params);
$stmt->execute();
$result = $stmt->get_result(); // Get the query result

// Check if the query was successful
if ($result) {
    $students = mysqli_fetch_all($result, MYSQLI_ASSOC); // Fetch all students
} else {
    redirect('Query Unsuccessful.', 'grades.php');  // Redirect if query fails
    $students = [];  // Initialize as an empty array
}

// Fetch departments for the filter dropdown (based on user role)

if ($role_id == 1) {
    // For Admin: Fetch all departments
    $departments_query = "SELECT * FROM department";
} else {
    // For Faculty: Fetch only departments that the faculty belongs to
    $departments_query = "
        SELECT department.department_id, department.department_name
        FROM department
        WHERE department.department_id = ?
    ";
}

$departments_stmt = $conn->prepare($departments_query);
if ($role_id == 2) {
    $departments_stmt->bind_param('i', $_SESSION['session_facultydepartmentid']); // Bind faculty department ID for faculty users
}
$departments_stmt->execute();
$departments_result = $departments_stmt->get_result(); // Get department filter results

// Fetch classes for the filter dropdown (based on user role)

if ($role_id == 1) {
    // For Admin: Fetch all classes
    $classes_query = "SELECT class_id, class_name FROM class";
} else {
    // For Faculty: Fetch only the classes they are assigned to
    $classes_query = "
        SELECT class.class_id, class.class_name
        FROM class
        INNER JOIN faculty_course ON class.course_id = faculty_course.course_id
        INNER JOIN faculty ON faculty_course.faculty_id = faculty.faculty_id
        WHERE faculty.user_id = ?
    ";
}

$classes_stmt = $conn->prepare($classes_query);
if ($role_id == 2) {
    $classes_stmt->bind_param('i', $user_id);  // Bind user ID for faculty users
}
$classes_stmt->execute();
$classes_result = $classes_stmt->get_result();  // Get class filter results


// End of PHP Script
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Josefin+Sans:wght@300;400;700&family=Source+Sans+Pro:wght@300;400;700&display=swap" rel="stylesheet">
    <title>Dashboard</title>
    <link rel='stylesheet' href="css/grades.css">
</head>

<body>

    <?php include('admin_header.php'); ?>

    <main class="main-content">

        <!-- Filters Section -->
        <h1>Viewing All Student Grades</h1>
        <section class="filters">
            <form method="POST" action="">
                <!-- Search input for student names -->
                <input type="text" name="search" placeholder="Search by name" value="<?php echo htmlspecialchars($search_term); ?>">

                <!-- Dropdown for selecting department filter -->
                <select name="department">
                    <option value="">Search Department</option>
                    <!-- Loop through all departments and populate options -->
                    <?php while ($department = mysqli_fetch_assoc($departments_result)): ?>
                        <option value="<?php echo htmlspecialchars($department['department_id']); ?>" <?php echo htmlspecialchars(($department_filter == $department['department_id'])) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($department['department_name']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>

                <!-- Dropdown for selecting class filter -->
                <select name="class">
                    <option value="">Search Class</option>
                    <!-- Loop through all classes and populate options -->
                    <?php while ($class = mysqli_fetch_assoc($classes_result)): ?>
                        <option value="<?php echo htmlspecialchars($class['class_id']); ?>" <?php echo htmlspecialchars(($class_filter == $class['class_id'])) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($class['class_name']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>

                <!-- Apply button to submit the filters -->
                <button type="submit">Apply</button>
            </form>
        </section>

        <!-- Admin Grade Analytics Dashboard Button -->
        <div class="button-container">
            <?php
            // Check if the user role ID is 1 (admin)
            if ($role_id == 1) {
                // Show the button only if the user is Admin
                echo '<a href="admin_gradereport.php">
                <button class="analytics-button">View Reports</button>
                </a>';
            }
            ?>
        </div>

        <!-- Container for Student Cards -->
        <section class="students-container">
            <div class="students">
                <!-- Loop through each student and create a student card -->
                <?php foreach ($students as $student): ?>
                    <a href="display_grades.php?student_id=<?php echo htmlspecialchars($student['student_id']); ?>" class="student-card-link">
                        <div class="student-card">
                            <img src="<?php echo htmlspecialchars($student['profile_picture']); ?>" alt="Profile Picture" class="student-profile-pic"> <!-- Display student's profile picture -->
                            <h3><?php echo htmlspecialchars($student['student_name']); ?></h3> <!-- Display student's name -->
                            <p>Admission No: <?php echo htmlspecialchars($student['admission_number']); ?></p> <!-- Display student's admission number -->
                            <p>Department: <?php echo htmlspecialchars($student['department_name']); ?></p> <!-- Display student's department -->
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>
    </main>

</body>

</html>

<?php
// Close the connection
mysqli_close($conn);
?>