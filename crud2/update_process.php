<?php
$con = mysqli_connect("localhost", "root", "", "xyzpoly");
if (!$con) {
    die('Could not connect: ' . mysqli_connect_error());
}

// Sanitize inputs
$student_course_grade_id = htmlspecialchars($_POST['student_course_grade_id']);
$new_grade_id = htmlspecialchars($_POST['grade_id']);


// Prepare statement
$stmt = $con->prepare("UPDATE student_course_grade SET grade_id=? WHERE student_course_grade_id=?");
$stmt->bind_param("ii", $new_grade_id, $student_course_grade_id);

if ($stmt->execute()) {
    echo "Grade updated successfully!";
    header("Location: display_grades.php");
} else {
    echo "Error updating grade.";
}

$con->close();
