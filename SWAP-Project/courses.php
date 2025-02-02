<?php
include('session_management.php');
include 'db_connection.php';

// Check if the user is authenticated
if (!isset($_SESSION['session_userid']) || !isset($_SESSION['session_roleid'])) {
    // Use JavaScript redirect if the user is unauthorized
    echo "<script>
            alert('Unauthorized user. Redirecting to login.');
            window.location.href = 'login.php'; // Redirect to login page
          </script>";
    exit;  // After this, exit to stop further code execution
}

$role_id = $_SESSION['session_roleid']; // Get role ID from the session
$user_id = $_SESSION['session_userid']; // Get user ID from the session

// Fetch Faculty's Department ID if the user is Faculty (role 2)
if ($role_id == 2) {
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
        error_log("No department found for faculty user_id: " . $user_id);
    }
}

// Build the SQL query dynamically based on user input for search and filters
$search_term = isset($_POST['search']) ? $_POST['search'] : '';
$search_term = htmlspecialchars($search_term, ENT_QUOTES, 'UTF-8');
$department_filter = isset($_POST['department']) ? $_POST['department'] : '';
$class_filter = isset($_POST['class']) ? $_POST['class'] : '';

// Initialize params array with search term (for LIKE query)
$params = ["%$search_term%"]; // Start with search term for LIKE query

// Initialize query
$query = "
    SELECT 
        course.course_id, 
        course.course_name, 
        department.department_name
    FROM course
    INNER JOIN department ON course.department_id = department.department_id
    LEFT JOIN class ON class.course_id = course.course_id
    WHERE course.course_name LIKE ?
";

if ($role_id == 1) { // Admin
    // Admin doesn’t need class filter, but might need department filter
    if (!empty($department_filter)) {
        $query .= " AND department.department_id = ?";
        $params[] = $department_filter; // Department filter for Admin
    }
}

// **Faculty Query** (Add department and class filters)
if ($role_id == 2) { // Faculty
    // Faculty-specific conditions
    $query .= "
        AND course.department_id = ?
        AND course.course_id IN (
            SELECT faculty_course.course_id
            FROM faculty_course
            INNER JOIN faculty ON faculty_course.faculty_id = faculty.faculty_id
            WHERE faculty.user_id = ?
        )
    ";
    $params[] = $_SESSION['session_facultydepartmentid']; // Faculty's department
    $params[] = $user_id; // Faculty's user_id
}

// Apply the class filter if a class has been selected
if (!empty($class_filter)) {
    $query .= " AND class.class_id = ?";
    $params[] = $class_filter; // Class filter
}

// Prepare the statement
$stmt = $conn->prepare($query);

// Dynamically determine the types for binding (e.g., 's' for string, 'i' for integer)
$bind_types = '';
foreach ($params as $param) {
    $bind_types .= is_int($param) ? 'i' : 's';
}

// Check if we have params to bind
if (!empty($params)) {
    $stmt->bind_param($bind_types, ...$params);
} else {
    error_log("Warning: No parameters to bind.");
}

// Execute the prepared statement
if ($stmt->execute()) {
    $result = $stmt->get_result();
} else {
    error_log("Query Execution Failed: " . $stmt->error);
    // Handle error, if needed
}


// Check if the query was successful
if ($result && $result->num_rows > 0) {
    $courses = mysqli_fetch_all($result, MYSQLI_ASSOC);
    error_log("Courses fetched: " . count($courses));
} else {
    $courses = [];
    error_log("No courses found or query failed.");
}

// Fetch departments for the filter dropdown (we need this for the form)
if ($role_id == 1) {
    // For Admin: Fetch all departments
    $departments_query = "SELECT * FROM department";
} else {
    // For Faculty: Fetch only departments that the faculty belongs to
    $departments_query = "
        SELECT department.department_id, department.department_name
        FROM department
        WHERE department.department_id = ?";
}

$departments_stmt = $conn->prepare($departments_query);
if ($role_id == 2) {
    $departments_stmt->bind_param('i', $_SESSION['session_facultydepartmentid']);
}
$departments_stmt->execute();
$departments_result = $departments_stmt->get_result();

// Fetch classes for the filter dropdown (we need this for the form)
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
        WHERE faculty.user_id = ?";
}

$classes_stmt = $conn->prepare($classes_query);
if ($role_id == 2) {
    $classes_stmt->bind_param('i', $user_id);
}
$classes_stmt->execute();
$classes_result = $classes_stmt->get_result();

// Check if the return button was pressed
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_course'])) {
    if($_SESSION['session_roleid']==1){
        header('Location: course_admin_insert_form.php');
    }elseif($_SESSION['session_roleid']==2){
    header('Location: course_insert_form.php');
    }
        exit;
}
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Josefin+Sans:wght@300;400;700&family=Source+Sans+Pro:wght@300;400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="css/courses.css">
</head>
<body>
<?php include('admin_header.php') ;?>
    <main class="main-content">
        <p class="page-title">Courses</p>

        <!-- Filters Section -->
        <section class="filters">
            <form method="post" action="">
                <input type="text" name="search" placeholder="Search for courses" value="<?php echo htmlspecialchars($search_term); ?>">

                <select name="department">
                    <option value="">Search Department</option>
                    <?php while ($department = mysqli_fetch_assoc($departments_result)): ?>
                        <option value="<?php echo $department['department_id']; ?>" <?php echo ($department_filter == $department['department_id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($department['department_name']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>

                <select name="class">
                    <option value="">Search Class</option>
                    <?php while ($class = mysqli_fetch_assoc($classes_result)): ?>
                        <option value="<?php echo $class['class_id']; ?>" <?php echo ($class_filter == $class['class_id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($class['class_name']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>

                <button type="submit">Apply</button>
            </form>
        </section>

        <!-- Container for Course Cards -->
        <section class="courses-container">
            <div class="courses">
                <?php foreach ($courses as $course): ?>
                    <a href="view_course.php?course_id=<?php echo urlencode($course['course_id']); ?>" class="course-card-link">
                        <div class="course-card">
                            <h3><?php echo htmlspecialchars($course['course_name']); ?></h3>
                            <p>Department: <?php echo htmlspecialchars($course['department_name']); ?></p>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- Button container for "Add Course" -->
        <form method="post" action="">
            <section class="create-course-btn-container">
                <button type="submit" name="add_course" class="add-course-btn">
                    <i class="fas fa-plus"></i>
                </button>
            </section>
        </form>
    </main>
</body>
</html>
<?php mysqli_close($conn); ?>