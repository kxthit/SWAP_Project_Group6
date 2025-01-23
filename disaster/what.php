<!DOCTYPE html>
<html lang="en">
<?php
session_start();

// Check if the user is authenticated
if (!isset($_SESSION['session_userid']) || !isset($_SESSION['session_role'])) {
    echo "<h2>Unauthorized access. Please log in.</h2>";
    header('Refresh: 3; URL=login.php');
    exit;
}

// Check if the user is either Admin or Faculty (role_id 1 or 2)
if ($_SESSION['session_role'] != 1 && $_SESSION['session_role'] != 2) {
    echo "<h2>You do not have permission to access this page.</h2>";
    exit;
}
?>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Course Management</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>
<header>
    <div class="logo"></div>
    <div class="user-info">
        <p class="user-name">
            <?php echo htmlspecialchars($_SESSION['session_name'] ?? 'Guest'); ?>
        </p>
        <p class="user-role">
            <?php 
                switch ($_SESSION['session_role'] ?? '') {
                    case 1: echo 'Admin'; break;
                    case 2: echo 'Faculty'; break;
                    case 3: echo 'Student'; break;
                    default: echo 'Unknown Role'; break;
                } 
            ?>
        </p>
    </div>
</header>

    <div class="container">
        <aside class="sidebar">
            <p>MAIN MENU</p>
            <ul>
                <li><a href="admin_dashboard.php">Dashboard</a></li>
                <li><a href="student.php">Students</a></li>
                <li>Classes</li>
                <li><a href="courses.php">Courses</a></li>
                <li>Grades</li>
            </ul>
            <p>SETTINGS</p>
            <ul>
                <li>Profile</li>
                <li>Setting</li>
                <li>Logout</li>
            </ul>
        </aside>

        <main class="main-content">
            <h1 id="title">Course Data</h1>
            <table>
                <thead>
                    <tr>
                        <th>Course ID</th>
                        <th>Course Name</th>
                        <th>Course Code</th>
                        <th>Start Date</th>
                        <th>End Date</th>
                        <th>Status</th>
                        <th>Department</th>
                        <th colspan="2">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $con = new mysqli("localhost", "root", "", "xyzpoly");
                    if ($con->connect_error) {
                        die('Could not connect: ' . $con->connect_error);
                    }

                    $stmt = $con->prepare("
                        SELECT course.course_id, course.course_name, course.course_code, course.start_date, course.end_date, status.status_name, department.department_name
                        FROM course
                        JOIN status ON course.status_id = status.status_id
                        JOIN department ON course.department_id = department.department_id
                    ");
                    $stmt->execute();
                    $result = $stmt->get_result();

                    while ($row = $result->fetch_assoc()) {
                        echo "<tr>";
                        echo "<td>" . $row['course_id'] . "</td>";
                        echo "<td>" . $row['course_name'] . "</td>";
                        echo "<td>" . $row['course_code'] . "</td>";
                        echo "<td>" . $row['start_date'] . "</td>";
                        echo "<td>" . $row['end_date'] . "</td>";
                        echo "<td>" . $row['status_name'] . "</td>";
                        echo "<td>" . $row['department_name'] . "</td>";
                        echo "<td><a href='admin_course_updateform.php?course_id=" . $row['course_id'] . "'>
                                <img src='/assignment/images/edit-button.png' alt='Edit' title='Edit'></a></td>";
                        echo "<td><a href='admin_course_delete.php?course_id=" . $row['course_id'] . "'>
                                <img src='/assignment/images/delete-button.png' alt='Delete' title='Delete'></a></td>";
                        echo "</tr>";
                    }

                    $con->close();
                    ?>
                </tbody>
            </table>
            <div class="button-container">
                <button class="add-course" onclick="location.href='admin_course_insertform.php'">Add Course</button>
            </div>
        </main>
    </div>
</body>
</html>
