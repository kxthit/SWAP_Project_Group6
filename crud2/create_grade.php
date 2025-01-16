<!DOCTYPE html>
<html lang="en">

<head>
    <title>Create Grade</title>
</head>

<body>
    <h2>Create Grade Record</h2>
    <form action="insert_grade.php" method="POST">
        Student ID: <input type="number" name="student_id" required><br>
        Course ID: <input type="number" name="course_id" required><br>
        Grade ID: <input type="number" name="grade_id" required><br>
        <input type="submit" value="Add Grade">
    </form>
</body>

</html>