<?php
include 'db_connection.php';
include 'session_management.php';
session_start();

// Check if the user is authenticated
if (!isset($_SESSION['session_userid']) || !isset($_SESSION['session_roleid'])) {
    echo "<h2>Unauthorized access. Please log in.</h2>";
    header('Refresh: 3; URL=login.php');
    exit;
}

$user_id = $_SESSION['session_userid'];
$role_id = $_SESSION['session_roleid'];

// CSRF Protection: Generate CSRF token if not set
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Ensure only Admin and Faculty can access
if (!in_array($role_id, [1, 2])) {
    echo " Unauthorised access. Please login";
    header('logout.php');
}

// Sanitize search input and class type filter
$search_term = filter_input(INPUT_POST, 'search', FILTER_SANITIZE_STRING) ?? '';
$class_type_filter = filter_input(INPUT_POST, 'class_type', FILTER_SANITIZE_STRING) ?? '';

// Fetch unique class types for the dropdown
$class_types_query = "SELECT DISTINCT class_type FROM class";
$class_types_result = mysqli_query($conn, $class_types_query);

// Prepare query to fetch classes
$query = "
    SELECT 
        c.class_id, 
        c.class_name, 
        c.class_type, 
        co.course_name 
    FROM class c
    LEFT JOIN course co ON c.course_id = co.course_id
    WHERE c.class_name LIKE ?
";

$params = ["%$search_term%"];
if (!empty($class_type_filter)) {
    $query .= " AND c.class_type = ?";
    $params[] = $class_type_filter;
}

if ($role_id == 2) { // Additional filter for faculty
    $query .= " AND c.faculty_id = (SELECT faculty_id FROM faculty WHERE user_id = ?)";
    $params[] = $user_id;
}

$stmt = $conn->prepare($query);
$bind_types = str_repeat('s', count($params));
$stmt->bind_param($bind_types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Classes</title>
    <link rel="stylesheet" href="css/classes.css">
</head>

<body>
    <?php include('admin_header.php'); ?>

    <div class="main-content">
        <h1>Classes</h1>

        <!-- Filters Section -->
        <section class="filters">
            <form method="POST">
                <input type="text" name="search" placeholder="Search by class name" value="<?php echo htmlspecialchars($search_term); ?>">
                <select name="class_type">
                    <option value="">Select Class Type</option>
                    <?php while ($type = mysqli_fetch_assoc($class_types_result)): ?>
                        <option value="<?php echo $type['class_type']; ?>" <?php echo ($class_type_filter == $type['class_type']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($type['class_type']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                <button type="submit">Apply</button>
            </form>
        </section>

        <!-- Classes Container -->
        <div class="classes-container">
            <div class="scrollable">
                <?php if ($result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <div class="class-card">
                            <!-- Card Actions -->
                            <div class="card-actions">
                                <!-- Edit Button -->
                                <form method="POST" action="editclass_form.php">
                                    <input type="hidden" name="class_id" value="<?php echo htmlspecialchars($row['class_id']); ?>">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                    <button type="submit" title="Edit">
                                        <img src="image/edit-button.png" alt="Edit">
                                    </button>
                                </form>
                                <!-- Delete Button -->
                                <form method="POST" action="deleteclass.php">
                                    <input type="hidden" name="class_id" value="<?php echo htmlspecialchars($row['class_id']); ?>">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                    <button type="submit" title="Delete">
                                        <img src="image/delete-button.png" alt="Delete">
                                    </button>
                                </form>
                            </div>
                            <!-- Class Details -->
                            <h3><?php echo htmlspecialchars($row['class_name']); ?></h3>
                            <p>Type: <?php echo htmlspecialchars($row['class_type']); ?></p>
                            <p>Course: <?php echo htmlspecialchars($row['course_name'] ?? 'N/A'); ?></p>
                            <!-- View Details Button -->
                            <div class="view-details">
                                <form method="POST" action="viewclass.php">
                                    <input type="hidden" name="class_id" value="<?php echo htmlspecialchars($row['class_id']); ?>">
                                    <button type="submit" class="view-button">View Details</button>
                                </form>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p>No classes found. Try modifying your search criteria.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Add Button -->
        <div class="add-button-container">
            <a href="createclass_form.php" class="add-button">
                <img src="image/add_button.png" alt="Add Class">
            </a>
        </div>
    </div>
</body>

</html>

<?php
// Close the database connection at the end of the script
mysqli_close($conn);
?>