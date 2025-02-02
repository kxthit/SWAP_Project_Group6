<?php
// Include the database connection and session management
include 'db_connection.php';
include 'session_management.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Ensure the user is authenticated
if (!isset($_SESSION['session_userid']) || !isset($_SESSION['session_roleid'])) {
    echo "<h2>Unauthorized access. Please log in.</h2>";
    header('Refresh: 3; URL=login.php');
    exit;
}

$user_id = $_SESSION['session_userid'];
$role_id = $_SESSION['session_roleid'];

// Validate and sanitize class_id from the URL
$class_id = isset($_POST['class_id']) ? intval($_POST['class_id']) : 0;
if ($class_id <= 0) {
    echo "<script>
            alert('Invalid access. Class ID is missing or invalid.');
            window.location.href = 'classes.php';
          </script>";
    exit;
}

// Fetch class details with role-based restrictions
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

$params = [$class_id];

// Restrict faculty users to view only their assigned classes
if ($role_id == 2) {
    $query .= " AND c.faculty_id = (SELECT faculty_id FROM faculty WHERE user_id = ?)";
    $params[] = $user_id;
}

$stmt = $conn->prepare($query);
$bind_types = str_repeat('i', count($params));
$stmt->bind_param($bind_types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

// If no results found, handle access denial
if ($result->num_rows == 0) {
    echo "<script>
            alert('You do not have access to view this class or it does not exist.');
            window.location.href = 'classes.php';
          </script>";
    exit;
}

$class_details = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Class Details</title>
    <link rel="stylesheet" href="css/viewclass.css">
</head>

<body>
    <?php include('admin_header.php'); ?>

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
        <a href="classes.php" class="back-button">Back to Classes</a>
    </div>
</body>

</html>

<?php
// Close the database connection at the end of the script
mysqli_close($conn);
?>