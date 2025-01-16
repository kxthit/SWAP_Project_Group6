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

// Query to fetch student details
$query = "SELECT student.student_id, student.profile_picture, student.student_name, user.admission_number
          FROM student
          INNER JOIN user ON student.user_id = user.user_id";

$result = mysqli_query($conn, $query);
if (!$result) {
    die("Error fetching data: " . mysqli_error($conn));
}

$students = mysqli_fetch_all($result, MYSQLI_ASSOC);

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
    display: grid;
    grid-template-columns: repeat(4, 1fr); /* 4 equal columns */
    gap: 20px; /* Space between items */
    margin: 0;
    padding: 0;
}

.student-card:nth-child(1) {
    grid-column-start: 1; /* Force the first card to start at the first column */
}

.student-card {
    background-color: #f4f4f4;
    border-radius: 10px;
    padding: 15px;
    text-align: center;
    height: 150px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.profile-pic {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    object-fit: cover;
}

.student-card h3 {
    margin: 10px 0 5px;
    font-size: 18px;
}

.student-card p {
    margin: 0;
    font-size: 14px;
    color: #555;
}

.student-card-link {
    text-decoration: none;
    color: inherit;
    display: inline-block;
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

    <?php include('header.php'); ?>

    <main class="main-content">
        <section class="students">
            <?php foreach ($students as $student): ?>
                <a href="display_student.php?student_id=<?php echo urlencode($student['student_id']); ?>" class="student-card-link">
                    <div class="student-card">
                        <img src="<?php echo htmlspecialchars($student['profile_picture']); ?>" alt="Profile Picture" class="profile-pic">
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
