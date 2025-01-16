<!DOCTYPE html>
<html>

<head>
    <title>View All Grades</title>
</head>

<body>
    <h1>All Student Grades</h1>
    <a href="create_grade.php"><button>Insert New Grade</button></a>
    <?php
    // Connect to the database
    $con = mysqli_connect("localhost", "root", "", "xyzpoly");

    if (!$con) {
        die('Could not connect: ' . mysqli_connect_error());
    }

    // SQL Query to fetch relevant details for all students and their grades
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
    ";

    $result = mysqli_query($con, $query);

    if ($result) {
        echo "<table border='1'>
                <tr>
                    <th>Student Name</th>
                    <th>Course Name</th>
                    <th>Grade</th>
                    <th>GPA</th>
                    <th>Actions</th>
                </tr>";

        // Display all rows
        while ($row = mysqli_fetch_assoc($result)) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['student_name']) . "</td>";
            echo "<td>" . htmlspecialchars($row['course_name']) . "</td>";
            echo "<td>" . htmlspecialchars($row['grade_letter']) . "</td>"; // Displaying grade letter
            echo "<td>" . htmlspecialchars($row['gpa_point']) . "</td>"; // Displaying GPA point
            echo "<td><a href='update_grade.php?id=" . $row['student_course_grade_id'] . "'>Update</a> | 
                      <a href='delete_grade.php?id=" . $row['student_course_grade_id'] . "'>Delete</a></td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "Error: " . mysqli_error($con);
    }

    mysqli_close($con);
    ?>
</body>

</html>