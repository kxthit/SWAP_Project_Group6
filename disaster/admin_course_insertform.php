<!DOCTYPE html>
<html>

<head>
    <style>
        body {
    font-family: Arial, sans-serif;
    background-color: #f0f8ff; /* Alice Blue background */
    margin: 0;
    padding: 0;
}

h1#title {
    text-align: center;
    font-size: 2.5rem; /* Bigger and bold */
    font-weight: bold;
    color: #004080; /* Nice dark blue */
    margin-bottom: 20px;
}

/* Form styling */
form {
    width: 60%;
    margin: 0 auto;
    background-color: #ffffff;
    padding: 20px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    border-radius: 8px;
}

form label {
    font-size: 1.2rem;
    color: #333;
}

form input, form select {
    width: 100%;
    padding: 10px;
    margin: 10px 0;
    border: 1px solid #dce7f1;
    border-radius: 4px;
}

form button {
    background-color: #0078D7; /* Bright blue for the button */
    color: white;
    border: none;
    padding: 12px 20px;
    border-radius: 5px;
    cursor: pointer;
    transition: 0.2s ease;
}

form button:hover {
    background-color: #005BB5; /* Darker blue on hover */
    transform: scale(1.05);
}

/* Pop-up form and overlay styles (for the pop-up option) */
.popup {
    display: none;
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background-color: white;
    padding: 20px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
    border-radius: 8px;
    z-index: 1000;
}

.overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    z-index: 999;
}

    </style>
</head>

<body>

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

// Connect to the database
$con = mysqli_connect("localhost", "root", "", "xyzpoly");
if (!$con) {
    die('Could not connect: ' . mysqli_connect_errno());
}

// Query to get all status options
$status_query = "SELECT * FROM status";
$status_result = mysqli_query($con, $status_query);

// Query to get all department options
$department_query = "SELECT * FROM department";
$department_result = mysqli_query($con, $department_query);
?>

<h1 id="title">Add New Course</h1>

<form action="admin_course_insert.php" method="POST">
    <label for="course_name">Course Name:</label>
    <input type="text" id="course_name" name="course_name" required><br><br>

    <label for="course_code">Course Code:</label>
    <input type="text" id="course_code" name="course_code" required><br><br>

    <label for="start_date">Start Date:</label>
    <input type="date" id="start_date" name="start_date" required><br><br>

    <label for="end_date">End Date:</label>
    <input type="date" id="end_date" name="end_date" required><br><br>

    <label for="status_id">Status:</label>
    <select id="status_id" name="status_id" required>
        <!-- Populate status options dynamically -->
        <?php
        while ($status = mysqli_fetch_assoc($status_result)) {
            echo "<option value='" . $status['status_id'] . "'>" . $status['status_name'] . "</option>";
        }
        ?>
        
    </select><br><br>

    <label for="department_id">Department:</label>
    <select id="department_id" name="department_id" required>
        <!-- Populate department options dynamically -->
        <?php
        while ($department = mysqli_fetch_assoc($department_result)) {
            echo "<option value='" . $department['department_id'] . "'>" . $department['department_name'] . "</option>";
        }
        ?>
    </select><br><br>

    <button type="submit">Add Course</button>
</form>

</body>

</html>

<?php
// Close the database connection
mysqli_close($con);
?>