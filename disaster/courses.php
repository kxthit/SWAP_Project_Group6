<!DOCTYPE html>
<html>
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
 
include('admin_header.php');

?>

<head>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f0f8ff; /* Alice Blue background */
            margin: 0;
            padding: 0;
        }

        #title {
            text-align: center;
            font-size: 2.5rem; /* Bigger and bold */
            font-weight: bold;
            color: #004080; /* Nice dark blue */
            margin-bottom: 20px;
        }

        table {
            border-collapse: collapse;
            width: 90%;
            margin: 20px auto;
            background-color: #ffffff; /* White table */
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            overflow: hidden;
            cursor: default;
        }

        th, td {
            border: 1px solid #dce7f1; /* Light blue borders */
            padding: 12px;
            text-align: center;
            cursor: default;
        }

        th {
            background-color: #007ACC; /* Sky blue header */
            color: white;
            font-weight: bold;
        }

        tr:nth-child(even) {
            background-color: #f2f9ff; /* Light sky blue for even rows */
        }

        tr:hover {
            background-color: #e6f7ff; /* Slight hover effect */
            cursor: pointer;
        }

        td img {
            width: 40px; /* Increased from 20px */
            height: 40px; /* Increased from 20px */
            cursor: pointer;
            margin: 0 5px;
}
        
        .button-container {
            text-align: center; /* Centering the button */
            margin-top: 20px; /* Optional, just to add some breathing room */
        }

        /* Add Course button style */
        button.add-course {
            align-items: center;
            font-size: 1.5rem; /* Larger font */
            padding: 10px 20px;
            background-color: #0078D7; /* Bright blue for the button */
            color: white;
            border: none;
            border-radius: 5px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); /* Subtle shadow */
            cursor: pointer;
            transition: transform 0.2s, background-color 0.2s;
        }

        /* Add hover effect for the button */
        button.add-course:hover {
            background-color: #005BB5; /* Darker blue on hover */
            transform: scale(1.05); /* Slight zoom effect */
        }
    </style>
</head>

<body>

    <h1 id="title">Course Data</h1>

    <!-- Table for Displaying Data -->
    <table>
        <thead>
            <tr>
                <th>Course ID</th>
                <th>Course Name</th>
                <th>Course Code</th>
                <th>Start Date</th>
                <th>End Date</th>
                <th>Status ID</th>
                <th>Department ID</th>
                <th colspan="2">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php
            // Connect to database
            $con = mysqli_connect("localhost", "root", "", "xyzpoly");
            if (!$con) {
                die('Could not connect: ' . mysqli_connect_errno());
            }

            // Fetch data
            $stmt = $con->prepare("
    SELECT course.course_id, course.course_name, course.course_code, course.start_date, course.end_date, status.status_name, department.department_name
    FROM course
    JOIN status ON course.status_id = status.status_id
    JOIN department ON course.department_id = department.department_id
");
            $stmt->execute();
            $result = $stmt->get_result();

            // Dynamically fill table rows
            while ($row = $result->fetch_assoc()) {
                echo "<tr>";
                echo "<td>" . $row['course_id'] . "</td>";
                echo "<td>" . $row['course_name'] . "</td>";
                echo "<td>" . $row['course_code'] . "</td>";
                echo "<td>" . $row['start_date'] . "</td>";
                echo "<td>" . $row['end_date'] . "</td>";
                echo "<td>" . $row['status_name'] . "</td>";
                echo "<td>" . $row['department_name'] . "</td>";

                // Add icons for Edit and Delete
                echo "<td><a href='admin_course_updateform.php?course_id=" . $row['course_id'] . "'>
                        <img src='/assignment/images/edit-button.png' alt='Edit' title='Edit'></a></td>";
                echo "<td><a href='admin_course_delete.php?course_id=" . $row['course_id'] . "'>
                        <img src='/assignment/images/delete-button.png' alt='Delete' title='Delete'></a></td>";
                echo "</tr>";
            }

            // Close connection
            $con->close();
            ?>
        </tbody>
    </table>

    <!-- Button Below the Table -->
    <div class="button-container">
        <button class="add-course" onclick="location.href='admin_course_insertform.php'">Add Course</button>
    </div>

</body>

</html>