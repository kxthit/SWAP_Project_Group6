<!DOCTYPE html>
<html lang="en">

<head>
    <title>Update Grade</title>
</head>

<body>
    <h2>Update Grade Record</h2>
    <?php
    $con = mysqli_connect("localhost", "admin", "admin", "xyzpoly");
    $id = $_GET['id'];
    $result = $con->query("SELECT * FROM student_course_grade WHERE student_course_grade_id=$id");
    $row = $result->fetch_assoc();
    ?>

    <form action="update_process.php" method="POST">
        <input type="hidden" name="student_course_grade_id" value="<?php echo $row['student_course_grade_id']; ?>">
        Grade ID: <input type="number" name="grade_id" value="<?php echo $row['grade_id']; ?>" required><br>
        <input type="submit" value="Update Grade">
    </form>
</body>

</html>