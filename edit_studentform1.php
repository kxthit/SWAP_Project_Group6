<?php
session_start();

// Check if the user is authenticated
if (!isset($_SESSION['session_userid']) || !isset($_SESSION['session_role'])) {
    echo "<h2>Unauthorized access. Please log in.</h2>";
    header('Refresh: 3; URL=login.php');
    exit;
}

// Include the database connection
include 'db_connection.php';



// Fetch departments for selection
$departments = [];
$stmt = $conn->prepare("SELECT department_id, department_name FROM department");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $departments[] = $row;
}

// Fetch existing student details if student_id is provided
if (isset($_GET['student_id'])) {
    $student_id = $_GET['student_id'];
    $stmt = $conn->prepare("SELECT student.student_name, student.student_email, student.student_phone, student.department_id, user.user_id, user.admission_number 
                            FROM student 
                            JOIN user ON student.user_id = user.user_id 
                            WHERE student.student_id = ?");
    $stmt->bind_param('i', $student_id);
    $stmt->execute();
    $student_result = $stmt->get_result();
    if ($student_result->num_rows > 0) {
        $student = $student_result->fetch_assoc();

        // Store all student data including student_id in the session
        $_SESSION['student_data'] = [
            'student_id' => $student_id,
            'user_id' => $student['user_id'],
            'student_name' => $student['student_name'],
            'admission_number' => $student['admission_number'],
            'student_email' => $student['student_email'],
            'student_phone' => $student['student_phone'],
            'department_id' => $student['department_id'],
        ];
    } else {
        echo "<h2>Student not found!</h2>";
        exit;
    }
}

// Handle form submission for student update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $student_name = $_POST['student_name'];
    $admission_number = $_POST['admission_number'];
    $student_email = $_POST['student_email'];
    $student_phone = $_POST['student_phone'];
    $department_id = $_POST['department_id'];

    // Validate the form data
    if (empty($student_name)|| empty($admission_number)  || empty($student_email) || empty($student_phone) || empty($department_id)) {
        echo "<p>Please fill in all fields.</p>";
    } else {
        // Save the student data in session to be used on the next page
        $_SESSION['student_data'] = [
            'student_id' => $_SESSION['student_data']['student_id'],  // Retaining the student_id from session
            'user_id' => $_SESSION['student_data']['user_id'],  // Retaining the user_id from session
            'student_name' => $student_name,
            'admission_number'=>$admission_number,
            'student_email' => $student_email,
            'student_phone' => $student_phone,
            'department_id' => $department_id,
        ];


        // Redirect to the next step (edit_student_courseform.php)
        header('Location: edit_student_courseform.php');
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Student</title>
</head>
<body>
    <h2>Edit Student Details</h2>
    <form action="edit_studentform1.php" method="POST">
        <input type="hidden" name="student_id" value="<?= isset($_SESSION['student_data']['student_id']) ? htmlspecialchars($_SESSION['student_data']['student_id']) : '' ?>">
        <input type="hidden" name="user_id" value="<?= isset($_SESSION['student_data']['user_id']) ? htmlspecialchars($_SESSION['student_data']['user_id']) : '' ?>">

        
        <label for="student_name">Student Name:</label>
        <input type="text" id="student_name" name="student_name" value="<?= isset($student['student_name']) ? htmlspecialchars($student['student_name']) : '' ?>" required><br>

        <label for="admission_number">Admission Number:</label>
        <input type="text" id="admission_number" name="admission_number" value="<?= isset($student['admission_number']) ? htmlspecialchars($student['admission_number']) : '' ?>" required><br>

        <label for="student_email">Email:</label>
        <input type="email" id="student_email" name="student_email" value="<?= isset($student['student_email']) ? htmlspecialchars($student['student_email']) : '' ?>" required><br>

        <label for="student_phone">Phone:</label>
        <input type="text" id="student_phone" name="student_phone" value="<?= isset($student['student_phone']) ? htmlspecialchars($student['student_phone']) : '' ?>" required><br>

        <label for="department_id">Select Department:</label>
        <select id="department_id" name="department_id" required>
            <option value="">Select Department</option>
            <?php foreach ($departments as $department): ?>
                <option value="<?= $department['department_id'] ?>" <?= isset($student['department_id']) && $student['department_id'] == $department['department_id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($department['department_name']) ?>
                </option>
            <?php endforeach; ?>
        </select><br>

        <input type="submit" value="Next">
    </form>
</body>
</html>
