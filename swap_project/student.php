<?php
// Include the database connection
include 'db_connection.php';

session_start();

// Check if the user is authenticated
if (!isset($_SESSION['session_userid']) || !isset($_SESSION['session_role'])) {
    echo "<h2>Unauthorized access. Please log in.</h2>";
    header('Refresh: 3; URL=login.php');
    exit;
}

// Fetch departments and classes for filter options
$departments_query = "SELECT * FROM department";
$departments_result = mysqli_query($conn, $departments_query);

$classes_query = "SELECT * FROM class";
$classes_result = mysqli_query($conn, $classes_query);


// Build the SQL query dynamically based on user input for search and filters
$search_term = isset($_GET['search']) ? $_GET['search'] : '';
$department_filter = isset($_GET['department']) ? $_GET['department'] : '';
$class_filter = isset($_GET['class']) ? $_GET['class'] : '';

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
    JOIN student_class sc ON student.student_id = sc.student_id
    JOIN department ON student.department_id = department.department_id
    JOIN class ON sc.class_id = class.class_id
    WHERE student.student_name LIKE ? 
";

$params = ["%$search_term%"];


// Apply department filter if provided
if ($department_filter) {
    $query .= " AND department.department_id = ?";
    $params[] = $department_filter;
}

// Apply class filter if provided
if ($class_filter) {
    $query .= " AND class.class_id = ?";
    $params[] = $class_filter;
}

$stmt = $conn->prepare($query);
$stmt->bind_param(str_repeat('s', count($params)), ...$params);
$stmt->execute();
$result = $stmt->get_result();

// Check if the query was successful
if ($result) {
    $students = mysqli_fetch_all($result, MYSQLI_ASSOC);
} else {
    echo "<h2>Error fetching students data: " . mysqli_error($conn) . "</h2>";
    $students = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard</title>
  <link rel="stylesheet" href="style.css">
  <style>
    .students {
        display: flex;
        flex-wrap: wrap;
        gap: 15px;
        justify-content: left;
        padding: 5px;
    }

    .student-card-link {
        text-decoration: none;
        color: inherit;
    }

    .student-card {
        width: 180px;
        height: 200px;
        background-color: #f4f4f4;
        border-radius: 20px;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        text-align: center;
        padding: 20px;
        transition: transform 0.2s ease-in-out;
    }

    .student-card:hover {
        transform: scale(1.05);
    }

    .student-profile-pic {
        width: 100x;
        height: 100px;
        border-radius: 50%;
        object-fit: cover;
        margin-bottom: 10px;
    }

    .student-card h3 {
        font-size: 18px;
        margin: 10px 0;
    }

    .student-card p {
        font-size: 14px;
        color: #555;
    }

    .filters {
        margin-bottom: 20px;
        display: flex;
        gap: 20px;
    }

    /* Styling for the search bar */
    .filters input[type="text"] {
        width: 300px;  /* Adjust the width to make the search bar longer */
        padding: 5px;
        border-radius: 5px;  /* Optional: add some rounding to the corners */
        border: 1px solid #ccc;
        font-size: 13px;
    }

        /* Styling for the dropdown filters */
    .filters select {
        padding: 5px; /* Increases the space inside the dropdown, making it thicker */
        font-size: 13px; /* Adjust font size if needed */
        border-radius: 5px; /* Optional: rounded corners */
        border: 1px solid #ccc; /* Border styling */
        height: 30px; /* Increases the height of the dropdown */
    }

    /* Optional: Add styling for the dropdown options */
    .filters select option {
        padding: 10px; /* Add padding to the options if needed */
    }

    /* Styling for the apply filters button */
    .filters button {
        padding: 5px 20px;
        background-color: #6495ed; 
        color: white;
        border: none;
        border-radius: 25px;  /* This makes the button rounder */
        cursor: pointer;
        font-size: 13px;
        transition: background-color 0.3s;
    }

    .create-button {
        position: fixed;
        bottom: 20px;
        right: 40px;
        z-index: 1000;
    }

    .create-button img {
        width: 60px;
        height: 60px;
        cursor: pointer;
    }

  </style>
</head>
<body>
    <?php include('admin_header.php'); ?>

    <!-- Main Content -->
    <main class="main-content">

            <!-- Filters Section -->
        <section class="filters">
            <form method="get" action="">
                <input type="text" name="search" placeholder="Search by name" value="<?php echo htmlspecialchars($search_term); ?>">
                
                <select name="department">
                    <option value="">Select Department</option>
                    <?php while ($department = mysqli_fetch_assoc($departments_result)): ?>
                        <option value="<?php echo $department['department_id']; ?>" <?php echo ($department_filter == $department['department_id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($department['department_name']); ?>
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
            </form>
        </section>
        <section class="students">
            <?php foreach ($students as $student): ?>
                <a href="display_student.php?student_id=<?php echo urlencode($student['student_id']); ?>" class="student-card-link">
                    <div class="student-card">
                        <img src="<?php echo htmlspecialchars($student['profile_picture']); ?>" alt="Profile Picture" class="student-profile-pic">
                        <h3><?php echo htmlspecialchars($student['student_name']); ?></h3>
                        <p>Admission No: <?php echo htmlspecialchars($student['admission_number']); ?></p>
                    </div>
                </a>
            <?php endforeach; ?>
        </section>
    </main>

    <div class="create-button">
        <a href="create_student.php">
            <img src="image/add_button.png" alt="Create Student">
        </a>
    </div>

</body>
</html>
