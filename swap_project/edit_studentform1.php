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
    <link rel="stylesheet" href="style.css">
    <style>
        /* General Reset */
        body {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f5f7fc;
        }

        /* Container Styles */
        .form-container {
            width: 100%;
            max-width: 1000px;
            margin: 1rem auto;
            background-color: #fff;
            border-radius: 10px;
            padding: 2rem;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        h1 {
            color: #1a1a1a;
            margin-bottom: 1.5rem;
            text-align: center;
            margin-top: -20px;
        }

        h2 {
            background-color: #6495ed;
            color: white;
            padding: 1rem;
            border-radius: 10px 10px 0 0;
            margin-left: -32px;
            text-align: left;
            width: 103.2%;
            margin-top: -30px;
        }

        /* Form Styles */
        form {
            padding: 1.5rem;
        }

        .form-row {
            display: flex;
            gap: 4rem; /* Space between photo and table */
            flex-wrap: wrap;
            margin-bottom: 1.5rem;
        }

        .photo-upload {
            flex: 0 0 10%; /* Photo box column takes 30% width */
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-start;
            height: 100px;
            margin-left: 15px;
            margin-top: 12px;
        }
        .photo-upload label {
            align-self: flex-start; /* Ensure the label aligns to the left */ 
            margin-left: -30px;
        }

        .photo-box {
            border: 2px dashed #ccc;
            border-radius: 5px;
            padding: 2rem;
            text-align: center;
            color: #888;
            cursor: pointer;
            height: 200px; /* Adjust height as needed */
            width: 100%;
        }

        #image-preview {
            margin-top: 20px;
            border: 1px solid #ccc;
            display: block;
        }

        .details-table {
            flex: 1; /* Table takes up the remaining space */
            width: 100%;
            border-collapse: collapse;
        }

        .details-table th, .details-table td {
            text-align: left;
            padding: 0.8rem;
        }

        .details-table td {
            border-bottom: none;
            padding: 0.8rem 1.5rem; /* Increase horizontal padding */
        }

        label {
            font-weight: bold;
        }

        input, textarea {
            border: 1px solid #ccc;
            border-radius: 5px;
            padding: 0.8rem;
            font-size: 1rem;
            outline: none;
            width: 100%;
            box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        input:focus, textarea:focus {
            border-color: #6c63ff;
        }

        textarea {
            resize: none;
        }

        select {
            width: 300px;
            padding: 10px;
            margin: 10px 0;
            border-radius: 10px;
            border: 1px solid #ddd;
        }

        button {
            display: block;
            width: 20%;
            padding: 0.5rem;
            background-color: #6495ed;
            color: #fff;
            border: none;
            border-radius: 5px;
            font-size: 1rem;
            cursor: pointer;
            margin-top: 3rem;
            margin-left: 370px;
        }

        button:hover {
            background-color: #5a52d4;
        }

    </style>
</head>
<body>
<?php include('admin_header.php'); ?>
    <main class="main-content">
        <h1>Edit Student Details</h1>
        <div class="form-container">
            <div class="form-card">
                <h2>Student Details</h2>
                <form action="edit_studentform1.php" method="POST">
                    <!-- Row with Photo and Table -->
                    <div class="form-row">
                        <!-- Right Column: Table -->
                        <table class="details-table">
                            <tr>
                                <td>
                                    <label for="student_name">Full Name *</label>
                                    <input type="text" id="student_name" name="student_name" value="<?= isset($student['student_name']) ? htmlspecialchars($student['student_name']) : '' ?>" required>
                                </td>
                                <td>
                                    <label for="admission_number">Admission No. *</label>
                                    <input type="text" id="admission_number" name="admission_number" value="<?= isset($student['admission_number']) ? htmlspecialchars($student['admission_number']) : '' ?>" required>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <label for="student_phone">Phone *</label>
                                    <input type="text" id="student_phone" name="student_phone" value="<?= isset($student['student_phone']) ? htmlspecialchars($student['student_phone']) : '' ?>" required>
                                </td>
                                <td>
                                    <label for="student_email">Email *</label>
                                    <input type="email" id="student_email" name="student_email" value="<?= isset($student['student_email']) ? htmlspecialchars($student['student_email']) : '' ?>" required>
                                </td>
                            </tr>
                            <tr>
                                <td colspan="2">
                                    <label for="department_id">Department *</label>
                                    <select id="department_id" name="department_id" required>
                                        <option value="">Select Department</option>
                                        <?php foreach ($departments as $department): ?>
                                            <option value="<?= $department['department_id'] ?>" <?= isset($student['department_id']) && $student['department_id'] == $department['department_id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($department['department_name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                            </tr>
                        </table>
                    </div>
                    <button type="submit">Next</button>
                </form>
            </div>
        </div>
    </main>
</body>
</html>
