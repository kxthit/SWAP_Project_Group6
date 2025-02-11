<?php
include 'db_connection.php';
include 'csrf_protection.php';

// Check if the user is authenticated
if (!isset($_SESSION['session_userid']) || !isset($_SESSION['session_roleid'])) {
    $_SESSION['error_message'] = "Unauthorized access.";
    header('Location: logout.php');
    exit;
}

$user_id = $_SESSION['session_userid'];
$role_id = $_SESSION['session_roleid'];

// CSRF Protection: Validate CSRF token only for POST requests
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!isset($_POST['csrf_token']) || !validate_csrf_token($_POST['csrf_token'])) {
        regenerate_csrf_token(); // Generate a new CSRF token
        die("Invalid CSRF token. <a href='logout.php'>Try again</a>");
    }
}

// Validate and sanitize class_id from POST
$class_id = isset($_POST['class_id']) ? filter_var($_POST['class_id'], FILTER_VALIDATE_INT) : 0;
if (!$class_id) {
    $_SESSION['error_message'] = "Invalid class ID.";
    header('Location: classes.php');
    exit;
}

// Prepare SQL query for fetching class details
$query = "
    SELECT 
        c.class_id, 
        c.class_name, 
        c.class_type, 
        co.course_name, 
        f.faculty_name, 
        d.department_name
    FROM class c
    LEFT JOIN course co ON c.course_id = co.course_id
    LEFT JOIN faculty f ON c.faculty_id = f.faculty_id
    LEFT JOIN department d ON co.department_id = d.department_id
    WHERE c.class_id = ?
";

// Restrict faculty to view only assigned classes
if ($role_id == 2) { 
    $query .= " AND c.faculty_id = (SELECT faculty_id FROM faculty WHERE user_id = ?)";
}

$stmt = $conn->prepare($query);

// Bind parameters
if ($role_id == 2) {
    $stmt->bind_param("ii", $class_id, $user_id);
} else {
    $stmt->bind_param("i", $class_id);
}

$stmt->execute();
$result = $stmt->get_result();

// Handle unauthorized access
if ($result->num_rows === 0) {
    $_SESSION['error_message'] = "Unauthorized access or class does not exist.";
    header('Location: classes.php');
    exit;
}

$class_details = $result->fetch_assoc();
session_write_close(); // Close session handling early for performance
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Class Details</title>
    <link rel="stylesheet" href="css/viewclass.css"> <!-- Standardized CSS path -->
</head>
<body>
    <?php
        if ($role_id == 1) {
            include('admin_header.php');
        } elseif ($role_id == 2) {
            include('faculty_header.php');
        } else {
            include('student_header.php');
        }
    ?>

    <div class="main-content">
        <h1>Class Details</h1>

        <!-- Class Details Container -->
        <div class="class-details-container">
            <div class="class-card">
                <h3><?php echo htmlspecialchars($class_details['class_name']); ?></h3>
                <p>Type: <?php echo htmlspecialchars($class_details['class_type']); ?></p>
                <p>Course: <?php echo htmlspecialchars($class_details['course_name']); ?></p>
                <p>Faculty: <?php echo htmlspecialchars($class_details['faculty_name']); ?></p>
                <p>Department: <?php echo htmlspecialchars($class_details['department_name']); ?></p>
            </div>
        </div>

        <!-- Back Button -->
        <form method="POST" action="classes.php">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            <button type="submit" class="back-button">Back to Classes</button>
        </form>
    </div>
</body>
</html>

<?php
// Close the database connection
mysqli_close($conn);
?>
