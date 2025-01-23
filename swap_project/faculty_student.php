<?php
// Include the database connection
include 'db_connection.php';

session_start();

// Check if the user is authenticated and is a faculty
if (!isset($_SESSION['session_userid']) || !isset($_SESSION['session_role'])) {
    echo "<h2>Unauthorized access. Please log in as a faculty member.</h2>";
    header('Refresh: 3; URL=login.php');
    exit;
}

// Get the logged-in faculty's user ID from the session
$faculty_user_id = $_SESSION['session_userid'];

// Fetch students enrolled in the faculty's courses based on the logged-in faculty's courses
$search_term = isset($_GET['search']) ? $_GET['search'] : '';

// Base query for fetching students under the faculty's courses
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
    JOIN department ON student.department_id = department.department_id
    JOIN class ON sc.course_id = class.course_id  -- Join on course_id instead of class_id
    WHERE sc.course_id IN (
        SELECT fc.course_id
        FROM faculty_course fc
        JOIN faculty f ON fc.faculty_id = f.faculty_id
        WHERE f.user_id = ?
    )
    AND student.student_name LIKE ?
";

$params = [$faculty_user_id, "%$search_term%"];
$stmt = $conn->prepare($query);
$stmt->bind_param('is', ...$params);
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
        width: 100px;
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

    .filters input[type="text"] {
        width: 300px;
        padding: 5px;
        border-radius: 5px;
        border: 1px solid #ccc;
        font-size: 13px;
    }

    .filters button {
        padding: 5px 20px;
        background-color: #6495ed; 
        color: white;
        border: none;
        border-radius: 25px;
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
    <?php include('faculty_header.php'); ?>

    <!-- Main Content -->
    <main class="main-content">

        <!-- Search Section -->
        <section class="filters">
            <form method="get" action="">
                <input type="text" name="search" placeholder="Search by name" value="<?php echo htmlspecialchars($search_term); ?>">
                <button type="submit">Search</button>
            </form>
        </section>
        
        <section class="students">
            <?php foreach ($students as $student): ?>
                <a href="faculty_display_student.php?student_id=<?php echo urlencode($student['student_id']); ?>" class="student-card-link">
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
        <a href="faculty_create_student.php">
            <img src="image/add_button.png" alt="Create Student">
        </a>
    </div>

</body>
</html>
