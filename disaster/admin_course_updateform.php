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
$con = mysqli_connect("localhost", "root", "", "xyzpoly"); // connect to database
if (!$con) {
    die('Could not connect: ' . mysqli_connect_errno()); // return error if connection fails
}

// Catch the submitted value 
$edit_courseid = htmlspecialchars($_GET["course_id"]);

// Prepare the statement
$stmt = $con->prepare("SELECT * FROM course WHERE course_id=?");
$stmt->bind_param('i', $edit_courseid); // bind parameter
$stmt->execute(); // execute the query
$result = $stmt->get_result(); // get the result set
$row = $result->fetch_assoc(); // fetch the single row

// Query to get all status options
$status_query = "SELECT * FROM status";
$status_result = mysqli_query($con, $status_query);

// Query to get all department options
$department_query = "SELECT * FROM department";
$department_result = mysqli_query($con, $department_query);

?>

<html>
<head>
    <title>Update Course</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f0f8ff; /* Alice Blue background */
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh; /* Ensure full height of the viewport */
        }

        h3 {
            text-align: center;
            font-size: 2rem;
            font-weight: bold;
            color: #004080;
            margin-bottom: 20px;
        }

        .container {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            background-color: #ffffff;
            padding: 30px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            width: 100%;
            max-width: 900px;
        }

        .form table {
            width: 100%;
            margin-top: 20px;
            text-align: center;
            border-spacing: 15px 10px;
        }

        .form th, .form td {
            padding: 10px;
        }

        .form input, .form select {
            width: 100%; /* Full width for input fields */
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #dce7f1;
            border-radius: 4px;
        }

        .form input[type="submit"] {
            background-color: #0078D7;
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 5px;
            cursor: pointer;
            transition: 0.2s ease;
        }

        .form input[type="submit"]:hover {
            background-color: #005BB5;
            transform: scale(1.05);
        }

        .data table {
            width: 100%;
            border-collapse: collapse;
        }

        .data th, .data td {
            padding: 10px;
            border: 1px solid #dce7f1;
            text-align: left;
        }

        .data th {
            background-color: #0078D7;
            color: white;
        }
    </style>
</head>
<body>
<div class="container">
    <!-- Left side: Display retrieved data -->
    <div class="data">
        <h3>Existing Data</h3>
        <table>
            <tr><th>COURSE ID</th><td><?php echo $row['course_id']; ?></td></tr>
            <tr><th>COURSE NAME</th><td><?php echo $row['course_name']; ?></td></tr>
            <tr><th>COURSE CODE</th><td><?php echo $row['course_code']; ?></td></tr>
            <tr><th>START DATE</th><td><?php echo $row['start_date']; ?></td></tr>
            <tr><th>END DATE</th><td><?php echo $row['end_date']; ?></td></tr>
            <tr><th>STATUS</th><td><?php echo $row['status_id']; ?></td></tr>
            <tr><th>DEPARTMENT</th><td><?php echo $row['department_id']; ?></td></tr>
        </table>
    </div>

    <!-- Form to Update Course -->
    <div class="form">
        <h3>Update Data</h3>
        <form action="admin_course_update.php?gcourse_id=<?php echo $edit_courseid ?>" method="POST">
            <table>
                <tr>
                    <th>COURSE_NAME</th>
                    <td><input type="text" name="upd_coursename" value="<?php echo $row['course_name']; ?>"></td>
                </tr>
                <tr>
                    <th>COURSE_CODE</th>
                    <td><input type="text" name="upd_coursecode" value="<?php echo $row['course_code']; ?>"></td>
                </tr>
                <tr>
                    <th>START_DATE</th>
                    <td><input type="date" name="upd_startdate" value="<?php echo $row['start_date']; ?>"></td>
                </tr>
                <tr>
                    <th>END_DATE</th>
                    <td><input type="date" name="upd_enddate" value="<?php echo $row['end_date']; ?>"></td>
                </tr>
                <tr>
                    <th>STATUS</th>
                    <td>
                        <select name="upd_statusid">
                            <?php while ($status = mysqli_fetch_assoc($status_result)) { ?>
                                <option value="<?php echo $status['status_id']; ?>" <?php echo ($status['status_id'] == $row['status_id']) ? 'selected' : ''; ?>>
                                    <?php echo $status['status_name']; ?>
                                </option>
                            <?php } ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th>DEPARTMENT</th>
                    <td>
                        <select name="upd_departmentid">
                            <?php while ($department = mysqli_fetch_assoc($department_result)) { ?>
                                <option value="<?php echo $department['department_id']; ?>" <?php echo ($department['department_id'] == $row['department_id']) ? 'selected' : ''; ?>>
                                    <?php echo $department['department_name']; ?>
                                </option>
                            <?php } ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td colspan="2" align="right">
                        <input type="submit" value="Update Record">
                    </td>
                </tr>
            </table>
        </form>
    </div>
</div>
</body>
</html>

<?php
$con->close();
?>