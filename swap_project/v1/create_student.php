<?php
    session_start();

    // Check if the user is authenticated
    if (!isset($_SESSION['session_userid']) || !isset($_SESSION['session_role'])) {
        echo "<h2>Unauthorized access. Please log in.</h2>";
        header('Refresh: 3; URL=login.php');
        exit;
    }
    
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Create Student</title>
  <link rel="stylesheet" href="style.css">
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
  <style>
    body {
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: 100vh;
        margin: 0;
        font-family: Arial, sans-serif;
    }

    h2 {
        text-align: left;
        margin-bottom: 20px;
    }

    .form-container {
        display: flex;
        align-items: center;
        gap: 0px;
        width: 800px;
        padding: 20px;
        border: 2px solid #ddd;
        border-radius: 10px;
        background-color: #f9f9f9;
    }


    .form-box {
        width: 70%;
        background-color: #fff;
        padding: 20px;
        border: 1px solid #ddd;
        border-radius: 10px;
        margin-left: 100px;
    }

    .form-box table {
        width: 100%;
        border-collapse: collapse;
    }

    .form-box table td {
        padding: 10px;
    }

    input[type="text"], input[type="email"], input[type="password"], select {
        width: 100%;
        padding: 10px;
        margin: 5px 0;
        border-radius: 5px;
        border: 1px solid #ddd;
    }

    button {
        width: 100%;
        padding: 10px;
        background-color: #007BFF;
        color: white;
        border: none;
        border-radius: 5px;
        cursor: pointer;
    }

    button:hover {
        background-color: #0056b3;
    }

    .action-buttons {
        display: flex;
        justify-content: space-between;
        gap: 10px;
    }
  </style>
</head>

<body>

<?php include('header.php'); ?>

<?php



// Include the database connection
include 'db_connection.php';

// Fetch all departments
$departments = $pdo->query("SELECT department_id, department_name FROM department")->fetchAll(PDO::FETCH_ASSOC);

?>

<main class="main-content">
    <h2>Create Student</h2>
    <div class="form-container">

        <!-- Form Box -->
        <div class="form-box">
            <form action="create_student.php" method="POST">
                <table>
                    <tr>
                        <td><label for="student_name">Student Name:</label></td>
                        <td><input type="text" id="student_name" name="student_name" required></td>
                    </tr>
                    <tr>
                        <td><label for="admission_number">Admission Number:</label></td>
                        <td><input type="text" id="admission_number" name="admission_number" required></td>
                    </tr>
                    <tr>
                        <td><label for="student_email">Email:</label></td>
                        <td><input type="email" id="student_email" name="student_email" required></td>
                    </tr>
                    <tr>
                        <td><label for="student_phone">Phone:</label></td>
                        <td><input type="text" id="student_phone" name="student_phone" required></td>
                    </tr>
                    <tr>
                        <td><label for="department_id">Department:</label></td>
                        <td>
                            <select id="department_id" name="department_id" required>
                                <option value="">Select Department</option>
                                <?php foreach ($departments as $department): ?>
                                    <option value="<?php echo $department['department_id']; ?>">
                                        <?php echo htmlspecialchars($department['department_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td><label for="courses">Courses:</label></td>
                        <td>
                            <select id="courses" name="courses[]" multiple required>
                                <option value="">Select Courses</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td><label for="class_id">Class:</label></td>
                        <td>
                            <select id="class_id" name="class_id" required>
                                <option value="">Select Class</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td><label for="profile_picture">Profile Picture Link:</label></td>
                        <td><input type="text" id="profile_picture" name="profile_picture"></td>
                    </tr>
                    <tr>
                        <td><label for="hashed_password">Password:</label></td>
                        <td><input type="password" id="hashed_password" name="hashed_password" required></td>
                    </tr>
                </table>

                <div class="action-buttons">
                    <button type="submit" name="submit">Create</button>
                    <button type="button" onclick="window.location.href='dashboard.php'">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</main>

<script>
$(document).ready(function () {
    // Load courses and classes on page load
    const departmentId = $('#department_id').val();

    if (departmentId) {
        loadCourses(departmentId, []);
        loadClasses(departmentId, null);
    }

    // Update courses when department changes
    $('#department_id').change(function () {
        const departmentId = $(this).val();
        if (departmentId) {
            loadCourses(departmentId, []);
            loadClasses(departmentId, null); // Reset class selection on department change
        } else {
            $('#courses').html('<option value="">Select Courses</option>');
            $('#class_id').html('<option value="">Select Class</option>');
        }
    });

    // Load classes when courses change
    $('#courses').change(function () {
        const selectedCourses = $(this).val();
        if (selectedCourses && selectedCourses.length > 0) {
            $.ajax({
                url: 'fetch_classes.php',
                method: 'POST',
                data: { course_ids: selectedCourses },
                success: function (response) {
                    $('#class_id').html(response);
                }
            });
        } else {
            $('#class_id').html('<option value="">Select Class</option>');
        }
    });

    function loadCourses(departmentId, selectedCourses) {
        $.ajax({
            url: 'fetch_courses.php',
            method: 'POST',
            data: { department_id: departmentId },
            success: function (response) {
                $('#courses').html(response);
                $('#courses').val(selectedCourses);
            }
        });
    }

    function loadClasses(departmentId, selectedClassId) {
        $.ajax({
            url: 'fetch_classes.php',
            method: 'POST',
            data: { department_id: departmentId },
            success: function (response) {
                $('#class_id').html(response);
                if (selectedClassId) {
                    $('#class_id').val(selectedClassId); // Pre-select the class
                }
            }
        });
    }
});
</script>

<?php
// Handle form submission
if (isset($_POST['submit'])) {
    // Student details
    $student_name = $_POST['student_name'];
    $admission_number = $_POST['admission_number'];
    $student_email = $_POST['student_email'];
    $student_phone = $_POST['student_phone'];
    $department_id = $_POST['department_id'];
    $courses = $_POST['courses'];
    $class_id = $_POST['class_id'];
    $profile_picture = $_POST['profile_picture'];
    $password = $_POST['hashed_password'];

    $role_id = 3;

    // Hash the password before storing it
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Step 1: Insert into the user table (admission number and password)
    $sql = "INSERT INTO user (admission_number, hashed_password, role_id) VALUES (:admission_number, :hashed_password, :role_id)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':admission_number' => $admission_number,
        ':hashed_password' => $hashed_password,
        ':role_id' => $role_id
    ]);

    // Get the new user ID
    $user_id = $pdo->lastInsertId();

    // Step 2: Insert into the student table with the user_id
    $sql = "
        INSERT INTO student (student_name, student_email, student_phone, profile_picture, department_id, user_id)
        VALUES (:student_name, :student_email, :student_phone, :profile_picture, :department_id, :user_id)
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':student_name' => $student_name,
        ':student_email' => $student_email,
        ':student_phone' => $student_phone,
        ':profile_picture' => $profile_picture,
        ':department_id' => $department_id,
        ':user_id' => $user_id // Link the user_id to the student
    ]);

    // Get the new student ID
    $student_id = $pdo->lastInsertId();

    // Step 3: Insert into the student_course table
    foreach ($courses as $course_id) {
        $sql = "INSERT INTO student_course (student_id, course_id) VALUES (:student_id, :course_id)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':student_id' => $student_id,
            ':course_id' => $course_id
        ]);
    }

    // Step 4: Insert into the student_class table
    $sql = "INSERT INTO student_class (student_id, class_id) VALUES (:student_id, :class_id)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':student_id' => $student_id,
        ':class_id' => $class_id
    ]);

    // Success message and redirect
    echo "<script>
            alert('Student created successfully!');
            window.location.href = 'student.php'; // Redirect back to student.php
          </script>";
}
?>
</body>
</html>
