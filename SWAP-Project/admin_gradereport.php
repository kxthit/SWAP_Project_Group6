<?php
// Include necessary files for session management and database connection
include 'session_management.php';
include 'db_connection.php';


// Function to redirect with an alert and a custom URL
function redirect($alert, $redirect)
{
    echo "<script>
            alert('$alert'); // Alert Message 
            window.location.href = '$redirect'; // Redirect to the given URL
        </script>";
    exit;
}

// Check if the user is authenticated , if not authenticated redirect to login page.
if (!isset($_SESSION['session_userid']) || !isset($_SESSION['session_roleid'])) {
    redirect('Unauthorized user. Redirecting To Login.', 'logout.php');
}

// Check if the user has a valid role (Admin) , if not redirect to login page.
if ($_SESSION['session_roleid'] != 1) {
    redirect('You Do Not Have Permission To Access This.', 'logout.php');
}


// Sanitize output by encoding special characters
function sanitize_output($data)
{
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}

// Secure query execution using prepared statements 

// Fetch total count of students
$total_students_stmt = $conn->prepare("SELECT COUNT(*) AS count FROM student");
$total_students_stmt->execute();
$total_students = $total_students_stmt->get_result()->fetch_assoc()['count'];

// Fetch total count of courses 
$total_courses_stmt = $conn->prepare("SELECT COUNT(*) AS count FROM course");
$total_courses_stmt->execute();
$total_courses = $total_courses_stmt->get_result()->fetch_assoc()['count'];

// Fetch total count of classes
$total_classes_stmt = $conn->prepare("SELECT COUNT(*) AS count FROM class");
$total_classes_stmt->execute();
$total_classes = $total_classes_stmt->get_result()->fetch_assoc()['count'];

// Query to calculate average CGPA by department
$cgpa_by_department_stmt = $conn->prepare("
    SELECT d.department_name, ROUND(AVG(g.gpa_point), 2) AS avg_cgpa
    FROM student_course_grade scg
    JOIN student s ON scg.student_id = s.student_id
    JOIN department d ON s.department_id = d.department_id
    JOIN grade g ON scg.grade_id = g.grade_id
    GROUP BY d.department_id
");
$cgpa_by_department_stmt->execute();
$cgpa_by_department = $cgpa_by_department_stmt->get_result();

// Query to calculate average GPA by course
$gpa_by_course_stmt = $conn->prepare("
    SELECT c.course_name, ROUND(AVG(g.gpa_point), 2) AS avg_gpa
    FROM student_course_grade scg
    JOIN course c ON scg.course_id = c.course_id
    JOIN grade g ON scg.grade_id = g.grade_id
    GROUP BY c.course_id
");
$gpa_by_course_stmt->execute();
$gpa_by_course = $gpa_by_course_stmt->get_result();

// Query to calculate average GPA by class
$gpa_by_class_stmt = $conn->prepare("
    SELECT cl.class_name, ROUND(AVG(g.gpa_point), 2) AS avg_gpa
    FROM student_course_grade scg
    JOIN class cl ON scg.course_id = cl.course_id
    JOIN grade g ON scg.grade_id = g.grade_id
    GROUP BY cl.class_id
");
$gpa_by_class_stmt->execute();
$gpa_by_class = $gpa_by_class_stmt->get_result();

// Query to calculate average GPA of students taught by each faculty member
$average_performance_by_faculty_stmt = $conn->prepare("
    SELECT f.faculty_name, ROUND(AVG(g.gpa_point), 2) AS avg_gpa
    FROM student_course_grade scg
    JOIN student s ON scg.student_id = s.student_id
    JOIN student_class sc ON s.student_id = sc.student_id
    JOIN class c ON sc.class_id = c.class_id
    JOIN faculty f ON c.faculty_id = f.faculty_id
    JOIN grade g ON scg.grade_id = g.grade_id
    GROUP BY f.faculty_id
");
$average_performance_by_faculty_stmt->execute();
$average_performance_by_faculty = $average_performance_by_faculty_stmt->get_result();


?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <title>Admin Grade Analytics Dashboard</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #4a5568;
        }

        h1 {
            text-align: center;
            /* Center the title */
            color: #34495e;
            /* Color of the title */
            margin-top: 30px;
            /* Space at the top */
            margin-bottom: 20px;
            /* Space below the title */
            text-transform: uppercase;
            /* Capitalize all letters */
        }


        .main-container {
            margin: 20px auto;
            width: 90%;
            background: #c3d9e5;
            /* Light blue background to differentiate the container */
            border-radius: 15px;
            /* Rounded corners for the container */
            box-shadow: 0px 12px 40px rgba(0, 0, 0, 0.2);
            /* Stronger shadow for a floating effect */
            transition: box-shadow 0.3s ease-in-out, transform 0.3s ease-in-out;
            /* Smooth transition for hover effect */
            padding: 30px;

        }

        /* Hover effect for container */
        .main-container:hover {
            box-shadow: 0px 18px 50px rgba(0, 0, 0, 0.25);
            transform: scale(1.01);
            /* Slight zoom effect on hover */
        }


        .analytics {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }

        .card {
            background: #fff;
            border-radius: 8px;
            padding: 20px;
            flex: 1;
            text-align: center;
            margin: 10px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s ease-in-out;
            border: 2px solid #ecdfce;
            /* Add border with desired color */
        }

        /* Hover effect for container */
        .card:hover {
            box-shadow: 0 0 15px 4px rgb(95, 142, 174);
            transform: scale(1.01);
            /* Slight zoom effect on hover */
        }

        .card h2 {
            font-size: 24px;
            color: #112633;
        }

        .table-container {
            position: relative;
            border-radius: 8px;
            transition: transform 0.2s ease-in-out;
            border: 2px solid #ecdfce;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            overflow-y: auto;
            /* Enables vertical scrolling */
            max-height: 200px;
            /* Fixed height to test overflow */
        }


        .table-container:hover {
            box-shadow: 0 0 15px 4px rgb(95, 142, 174);
            transform: scale(1.01);
            /* Slight zoom effect on hover */
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
            border-radius: 8px;
            overflow: hidden;
            background-color: white;

        }

        table th,
        table td {
            text-align: center;
            padding: 15px;
            border: 1px solid #ddd;
            color: #112633;
        }

        table th {
            background-color: #3b667e;
            color: #ecdfce;
        }

        .section-title {
            font-size: 18px;
            margin: 20px 0 10px;
            color: #34495e;
        }

        .return-btn {
            position: absolute;
            top: 10px;
            right: 10px;
            font-size: 30px;
            color: #10171e;
            text-decoration: none;
            background: none;
            border: none;
            cursor: pointer;
            outline: none;
            transition: color 0.3s ease, transform 0.3s ease;
        }

        .return-btn:hover {
            color: #d9534f;
            transform: scale(1.1);
        }
    </style>
</head>

<body>

    <?php include('admin_header.php'); ?>
    <main class="main-content">

        <div class="main-container">
            <!-- Title at the top -->
            <h1 class="page-title">Admin Grade Analytics</h1>

            <!-- Overview Cards -->
            <a href="grades.php" class="return-btn">
                <i class="fas fa-times"></i>
            </a>
            <section class="analytics">
                <div class="card">
                    <h2>Total Students</h2>
                    <p><?= sanitize_output($total_students) ?></p>
                </div>
                <div class="card">
                    <h2>Total Courses</h2>
                    <p><?= sanitize_output($total_courses) ?></p>
                </div>
                <div class="card">
                    <h2>Total Classes</h2>
                    <p><?= sanitize_output($total_classes) ?></p>
                </div>
            </section>

            <!-- CGPA by Department -->
            <h2 class="section-title">Overall CGPA by Department</h2>
            <div class="table-container">
                <table>
                    <tr>
                        <th>Department</th>
                        <th>CGPA</th>
                    </tr>
                    <?php while ($row = $cgpa_by_department->fetch_assoc()): ?>
                        <tr>
                            <td><?= sanitize_output($row['department_name']) ?></td>
                            <td><?= sanitize_output($row['avg_cgpa']) ?></td>
                        </tr>
                    <?php endwhile; ?>
                </table>
            </div>

            <!-- GPA by Course -->
            <h2 class="section-title">Mean GPA by Course</h2>
            <div class="table-container">
                <table>
                    <tr>
                        <th>Course</th>
                        <th>GPA</th>
                    </tr>
                    <?php while ($row = $gpa_by_course->fetch_assoc()): ?>
                        <tr>
                            <td><?= sanitize_output($row['course_name']) ?></td>
                            <td><?= sanitize_output($row['avg_gpa']) ?></td>
                        </tr>
                    <?php endwhile; ?>
                </table>
            </div>

            <!-- GPA by Class -->
            <h2 class="section-title">Mean GPA by Class</h2>
            <div class="table-container">
                <table>
                    <tr>
                        <th>Class</th>
                        <th>GPA</th>
                    </tr>
                    <?php while ($row = $gpa_by_class->fetch_assoc()): ?>
                        <tr>
                            <td><?= sanitize_output($row['class_name']) ?></td>
                            <td><?= sanitize_output($row['avg_gpa']) ?></td>
                        </tr>
                    <?php endwhile; ?>
                </table>
            </div>

            <!-- Faculty Performance Overview, Calucating average gpa of students taught by them. -->
            <h2 class="section-title">Faculty Performance Overview</h2>
            <div class="table-container">
                <table>
                    <tr>
                        <th>Faculty</th>
                        <th>Average GPA</th>
                    </tr>
                    <?php while ($row = $average_performance_by_faculty->fetch_assoc()): ?>
                        <tr>
                            <td><?= sanitize_output($row['faculty_name']) ?></td>
                            <td><?= sanitize_output($row['avg_gpa']) ?></td>
                        </tr>
                    <?php endwhile; ?>
                </table>
            </div>

        </div>
</body>

</html>