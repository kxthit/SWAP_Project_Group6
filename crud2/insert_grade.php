<?php
$con = mysqli_connect("localhost", "root", "", "xyzpoly"); // Connect to the database
if (!$con) {
    die('Could not connect: ' . mysqli_connect_error());
}

// Prepare the statement
$stmt = $con->prepare("INSERT INTO student_course_grade (student_id, course_id, grade_id) VALUES (?, ?, ?)");

// Sanitize inputs
$student_id = htmlspecialchars($_POST["student_id"]);
$course_id = htmlspecialchars($_POST["course_id"]);
$grade_id = htmlspecialchars($_POST["grade_id"]);

// Bind and execute
$stmt->bind_param("iii", $student_id, $course_id, $grade_id);

if ($stmt->execute()) {
    echo "Grade added successfully!";
    header("Location: display_grades.php");
} else {
    echo "Error adding grade.";
}

$con->close();
