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

// Validate student_id from URL
if (!isset($_GET['student_id']) || !is_numeric($_GET['student_id'])) {
    echo "Invalid or missing student ID.";
    exit;
}

$student_id = $_GET['student_id'];

// Query to fetch student details
$query = "
    SELECT 
        s.student_id,
        s.student_name,
        s.student_email,
        s.student_phone,
        u.admission_number,
        c.class_name,
        d.department_name,
        GROUP_CONCAT(co.course_name ORDER BY co.course_name SEPARATOR ', ') AS courses
    FROM student s
    JOIN user u ON s.user_id = u.user_id
    JOIN student_class sc ON s.student_id = sc.student_id
    JOIN class c ON sc.class_id = c.class_id
    JOIN department d ON s.department_id = d.department_id
    JOIN student_course sc2 ON s.student_id = sc2.student_id
    JOIN course co ON sc2.course_id = co.course_id
    WHERE s.student_id = ?
    GROUP BY s.student_id
";

// Prepare and execute the query
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();
$student = $result->fetch_assoc();

// Query to fetch grades for the student
$grades_query = "
    SELECT 
        co.course_name,
        g.grade_letter,
        g.gpa_point
    FROM student_course_grade scg
    JOIN course co ON scg.course_id = co.course_id
    JOIN grade g ON scg.grade_id = g.grade_id
    WHERE scg.student_id = ?
";

$grades_stmt = $conn->prepare($grades_query);
$grades_stmt->bind_param("i", $student_id);
$grades_stmt->execute();
$grades_result = $grades_stmt->get_result();

// Calculate the average GPA point
$total_gpa = 0;
$total_courses = 0;
$grades = [];
while ($row = $grades_result->fetch_assoc()) {
    $grades[] = $row;
    $total_gpa += $row['gpa_point'];
    $total_courses++;
}
$average_gpa = $total_courses > 0 ? $total_gpa / $total_courses : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Details</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }

        table {
            width: 50%;
            border-collapse: collapse;
            margin: 0 auto;
        }

        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }

        th {
            background-color: #f2f2f2;
        }

        .back-button {
            display: block;
            width: 100px;
            margin: 10px auto;
            text-align: center;
            padding: 8px;
            background-color: #f2f2f2;
            border: 1px solid #ddd;
            text-decoration: none;
            color: black;
        }

        .back-button:hover {
            background-color: #ddd;
        }

        .action-buttons {
            text-align: center;
            margin-top: 20px;
        }

        .action-buttons a {
            margin: 0 10px;
            padding: 8px 16px;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 4px;
        }

        .action-buttons a:hover {
            background-color: #0056b3;
        }

    </style>
</head>
<body>
<a href="faculty_student.php" class="back-button">← Back</a>
<?php if ($student): ?>
    <table>
        <tr>
            <th>Name</th>
            <td><?php echo htmlspecialchars($student['student_name']); ?></td>
        </tr>
        <tr>
            <th>Admission No</th>
            <td><?php echo htmlspecialchars($student['admission_number']); ?></td>
        </tr>
        <tr>
            <th>Email</th>
            <td><?php echo htmlspecialchars($student['student_email']); ?></td>
        </tr>
        <tr>
            <th>Phone</th>
            <td><?php echo htmlspecialchars($student['student_phone']); ?></td>
        </tr>
        <tr>
            <th>Department</th>
            <td><?php echo htmlspecialchars($student['department_name']); ?></td>
        </tr>
        <tr>
            <th>Courses</th>
            <td>
                <?php 
                $courses = explode(',', $student['courses']);
                echo '<ul>';
                foreach ($courses as $course) {
                    echo '<li>' . htmlspecialchars(trim($course)) . '</li>';
                }
                echo '</ul>';
                ?>
            </td>
        </tr>
        <tr>
            <th>Classes</th>
            <td>
                <?php 
                $classes = explode(',', $student['class_name']);
                echo '<ul>';
                foreach ($classes as $class) {
                    echo '<li>' . htmlspecialchars(trim($class)) . '</li>';
                }
                echo '</ul>';
                ?>
            </td>
        </tr>
    </table>

    <div class="action-buttons">
        <a href="faculty_edit_studentform1.php?student_id=<?php echo $student['student_id']; ?>">Edit</a>
    </div>

    <h3 style="text-align: center;">Grades</h3>
    <table>
        <tr>
            <th>Course Name</th>
            <th>Grade</th>
        </tr>
        <?php foreach ($grades as $grade): ?>
            <tr>
                <td><?php echo htmlspecialchars($grade['course_name']); ?></td>
                <td><?php echo htmlspecialchars($grade['grade_letter']); ?></td>
            </tr>
        <?php endforeach; ?>
        <tr>
            <th>Average GPA</th>
            <td><?php echo number_format($average_gpa, 2); ?></td>
        </tr>
    </table>
<?php else: ?>
    <p style="text-align: center;">Student not found.</p>
<?php endif; ?>

</body>
</html>
