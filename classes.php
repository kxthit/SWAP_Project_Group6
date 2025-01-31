<?php
// Include the database connection
include 'db_connection.php';
include 'session_management.php';
session_start();

// Check if the user is authenticated
if (!isset($_SESSION['session_userid']) || !isset($_SESSION['session_roleid'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['session_userid'];
$role_id = $_SESSION['session_roleid'];

// Fetch unique class types for the dropdown
$class_types_query = "SELECT DISTINCT class_type FROM class";
$class_types_result = mysqli_query($conn, $class_types_query);

// Search and filter handling
$search_term = isset($_GET['search']) ? $_GET['search'] : '';
$class_type_filter = isset($_GET['class_type']) ? $_GET['class_type'] : '';

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

// Add class type filter if selected
$params = ["%$search_term%"];
if (!empty($class_type_filter)) {
    $query .= " AND c.class_type = ?";
    $params[] = $class_type_filter;
}

// Add faculty-specific filters if the role is faculty
if ($role_id == 2) {
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
    <link rel="stylesheet" href="classes.css">
</head>
<body>
    <?php include('admin_header.php'); ?>

    <div class="main-content">
        <h1>Classes</h1>

        <!-- Filters Section -->
        <section class="filters">
            <form method="get">
                <input type="text" name="search" placeholder="Search by class name" value="<?php echo htmlspecialchars($search_term); ?>">
                <select name="class_type">
                    <option value="">Select Class Type</option>
                    <?php while ($type = mysqli_fetch_assoc($class_types_result)): ?>
                        <option value="<?php echo $type['class_type']; ?>" <?php echo ($class_type_filter == $type['class_type']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($type['class_type']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
                <button type="submit">Apply</button>
            </form>
        </section>

        <!-- Classes Container -->
        <div class="classes-container">
            <div class="scrollable">
                <?php while ($row = $result->fetch_assoc()): ?>
                    <div class="class-card">
                        <div class="card-actions">
                            <a href="editclass_form.php?class_id=<?php echo urlencode($row['class_id']); ?>" class="edit-icon">
                                <img src="image/edit-button.png" alt="Edit">
                            </a>
                            <a href="deleteclass.php?class_id=<?php echo urlencode($row['class_id']); ?>" class="delete-icon">
                                <img src="image/delete-button.png" alt="Delete">
                            </a>
                        </div>
                        <h3><?php echo htmlspecialchars($row['class_name']); ?></h3>
                        <p>Type: <?php echo htmlspecialchars($row['class_type']); ?></p>
                        <p>Course: <?php echo htmlspecialchars($row['course_name'] ?? 'N/A'); ?></p>
                        <a href="viewclass.php?class_id=<?php echo urlencode($row['class_id']); ?>" class="view-details">View Details</a>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>

        <!-- Add Button (Separate Div) -->
        <div class="add-button-container">
            <a href="createclass_form.php" class="add-button">
                <img src="image/add_button.png" alt="Add Class">
            </a>
        </div>
    </div>
</body>
</html>
