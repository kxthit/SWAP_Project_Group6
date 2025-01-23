<?php
session_start();

// Check if the user is authenticated
if (!isset($_SESSION['session_userid']) || !isset($_SESSION['session_role'])) {
    echo "<h2>Unauthorized access. Please log in.</h2>";
    header('Refresh: 3; URL=login.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Grades</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <h1>Student Grades</h1>
    <a href="create_grade.php"><button>Insert New Grade</button></a>

    <?php
    // Check if the student ID is passed via URL
    if (isset($_GET['student_id'])) {
        $student_id = (int)$_GET['student_id']; // Cast to integer to prevent SQL injection

        // Connect to the database
        include 'db_connection.php';

        // SQL Query to fetch the grades for the specific student
        $query = "
            SELECT 
                scg.student_course_grade_id, 
                s.student_name, 
                c.course_name, 
                g.grade_letter, 
                g.gpa_point
            FROM student_course_grade scg
            JOIN student s ON scg.student_id = s.student_id
            JOIN course c ON scg.course_id = c.course_id
            JOIN grade g ON scg.grade_id = g.grade_id
            WHERE s.student_id = ?
        ";

        $stmt = $conn->prepare($query);
        $stmt->bind_param('i', $student_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            echo "<table border='1'>
                    <tr>
                        <th>Student Name</th>
                        <th>Course Name</th>
                        <th>Grade</th>
                        <th>GPA</th>
                        <th>Actions</th>
                    </tr>";

            // Display all rows
            while ($row = $result->fetch_assoc()) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($row['student_name']) . "</td>";
                echo "<td>" . htmlspecialchars($row['course_name']) . "</td>";
                echo "<td>" . htmlspecialchars($row['grade_letter']) . "</td>";
                echo "<td>" . htmlspecialchars($row['gpa_point']) . "</td>";
                echo "<td><a href='update_grade.php?id=" . $row['student_course_grade_id'] . "'>Update</a> | 
                          <a href='delete_grade.php?id=" . $row['student_course_grade_id'] . "'>Delete</a></td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p>No grades found for this student.</p>";
        }

        $stmt->close();
        mysqli_close($conn);
    } else {
        echo "<p>No student selected.</p>";
    }
    ?>

</body>
</html>
