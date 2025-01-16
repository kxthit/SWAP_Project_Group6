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

// Check if the student_id is set in the URL
if (isset($_GET['student_id'])) {
    $student_id = $_GET['student_id'];

    // Prepare the query to get the student details based on student_id
    $stmt = $conn->prepare("SELECT 
                                s.student_id, 
                                s.student_name, 
                                s.student_email, 
                                s.student_phone, 
                                u.admission_number, 
                                c.class_name, 
                                d.department_name, 
                                GROUP_CONCAT(co.course_name ORDER BY co.course_name SEPARATOR ', ') AS courses,
                                s.profile_picture
                            FROM student s
                            JOIN user u ON s.user_id = u.user_id
                            JOIN student_class sc ON s.student_id = sc.student_id
                            JOIN class c ON sc.class_id = c.class_id
                            JOIN department d ON s.department_id = d.department_id
                            JOIN student_course sc2 ON s.student_id = sc2.student_id
                            JOIN course co ON sc2.course_id = co.course_id
                            WHERE s.student_id = ?
                            GROUP BY s.student_id, s.student_name, s.student_email, s.student_phone, u.admission_number, c.class_name, d.department_name");

    // Bind parameters and execute the query
    $stmt->bind_param("i", $student_id);
    $stmt->execute();

    // Fetch the student data
    $result = $stmt->get_result();
    $student = $result->fetch_assoc();
} else {
    echo "Student ID not provided.";
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Student Details</title>
  <link rel="stylesheet" href="style.css">
  <style>
    body {
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: 100vh;
        margin: 0;
    }

    h2 {
        text-align: left;
        margin-bottom: 20px;
    }

    .student-details-container {
        display: flex;
        gap: 20px;
        max-width: 800px;
        padding: 20px;
        border: 2px solid #ddd;
        border-radius: 10px;
        background-color: #f9f9f9;
    }

    .profile-picture img {
        width: 150px;
        height: 150px;
        object-fit: cover;
        border-radius: 50%;
    }

    .details-box {
        width: 100%;
        background-color: #fff;
        border: 1px solid #ddd;
        border-radius: 10px;
        padding: 10px;
    }

    .student-table {
        width: 100%;
        border-collapse: collapse;
    }

    .student-table td {
        padding: 10px;
    }

    .action-buttons {
        display: flex;
        justify-content: center;
        gap: 10px;
    }

    button {
        width: 45%;
        padding: 10px;
        background-color: #007BFF;
        color: white;
        border: none;
        border-radius: 5px;
        cursor: pointer;
    }

    button:hover {
        background-color: #0056b3;
    }
  </style>
</head>
<body>

    <?php include('header.php'); ?>

    <main class="main-content">
        <?php if ($student): ?>  
            <h2>Student Details</h2>
            <div class="student-details-container">
                <div class="profile-picture">
                    <img src="<?php echo htmlspecialchars($student['profile_picture']); ?>" alt="Profile Picture">
                </div>
                <div class="details-box">
                    <table class="student-table">
                        <tr><td><strong>Student Name:</strong></td><td><?php echo htmlspecialchars($student['student_name']); ?></td></tr>
                        <tr><td><strong>Admission Number:</strong></td><td><?php echo htmlspecialchars($student['admission_number']); ?></td></tr>
                        <tr><td><strong>Email:</strong></td><td><?php echo htmlspecialchars($student['student_email']); ?></td></tr>
                        <tr><td><strong>Phone:</strong></td><td><?php echo htmlspecialchars($student['student_phone']); ?></td></tr>
                        <tr><td><strong>Department:</strong></td><td><?php echo htmlspecialchars($student['department_name']); ?></td></tr>
                        <tr><td><strong>Courses:</strong></td><td><?php echo htmlspecialchars($student['courses']); ?></td></tr>
                        <tr><td><strong>Class:</strong></td><td><?php echo htmlspecialchars($student['class_name']); ?></td></tr>
                    </table>
                    <div class="action-buttons">
                        <button onclick="window.location.href='edit_studentform.php?student_id=<?php echo urlencode($student['student_id']); ?>'">Edit</button>
                        <button onclick="confirmDelete(<?php echo $student['student_id']; ?>)">Delete</button>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <p>Student not found. Please check the student ID.</p>
        <?php endif; ?>
    </main>

    <script>
        function confirmDelete(studentId) {
            if (confirm("Are you sure you want to delete this student?")) {
                window.location.href = 'delete_student.php?student_id=' + studentId;
            }
        }
    </script>

</body>
</html>
