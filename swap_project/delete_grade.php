<?php
$con = mysqli_connect("localhost", "admin", "admin", "xyzpoly");
if (!$con) {
    die('Could not connect: ' . mysqli_connect_error());
}

$id = $_GET['id'];

// Prepare and execute delete
$stmt = $con->prepare("DELETE FROM student_course_grade WHERE student_course_grade_id=?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    echo "Grade deleted successfully!";
    header("Location: display_grades.php");
} else {
    echo "Error deleting grade.";
}

$con->close();
