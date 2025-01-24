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

var_dump($_GET); // Debugging line

// Fetch `student_id` from URL
$student_id = $_GET['student_id'] ?? null;


// Debug: Check if student_id is set
if (!$student_id) {
    echo "<h2>Error: Student ID is missing in the URL.</h2>";
    exit;
}

// Fetch student details
$stmt = $conn->prepare("
    SELECT 
        student.student_name, 
        student.student_email, 
        student.student_phone, 
        student.department_id, 
        student.student_id,
        user.user_id, 
        user.admission_number, 
        student_class.class_id
    FROM student 
    JOIN user ON student.user_id = user.user_id
    LEFT JOIN student_class ON student.student_id = student_class.student_id
    WHERE student.student_id = ?
");
$stmt->bind_param('i', $student_id);

if ($stmt->execute()) {
    $student_result = $stmt->get_result();
    if ($student_result->num_rows > 0) {
        $student = $student_result->fetch_assoc();
    } else {
        echo "<h2>Error: Student not found!</h2>";
        exit;
    }
} else {
    echo "Error executing query: " . $stmt->error;
    exit;
}
$stmt->close();

// Fetch classes related to the student's department
$classes = [];
if (isset($student['department_id'])) {
    $stmt_classes = $conn->prepare("
        SELECT 
            class.class_id, 
            class.class_name 
        FROM class
        JOIN course ON class.course_id = course.course_id
        WHERE course.department_id = ?
    ");
    $stmt_classes->bind_param('i', $student['department_id']);
    $stmt_classes->execute();
    $class_result = $stmt_classes->get_result();
    while ($row = $class_result->fetch_assoc()) {
        $classes[] = $row;
    }
    $stmt_classes->close();
}

// Handle form submission for student update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Debug: Check POST data
    var_dump($_POST); // Debugging line

    $student_id = $_POST['student_id'] ?? null; // Retrieve student_id from POST
    $student_name = $_POST['student_name'];
    $admission_number = $_POST['admission_number'];
    $student_email = $_POST['student_email'];
    $student_phone = $_POST['student_phone'];
    $class_id = $_POST['class_id'];

    if (empty($student_id)) {
        echo "<p>Error: Student ID is missing in the POST request.</p>";
        exit;
    }

    if (empty($student_name) || empty($admission_number) || empty($student_email) || empty($student_phone) || empty($class_id)) {
        echo "<p>Please fill in all fields.</p>";
    } else {
        // Update student details
        $stmt = $conn->prepare("UPDATE student SET student_name = ?, student_email = ?, student_phone = ? WHERE student_id = ?");
        $stmt->bind_param("sssi", $student_name, $student_email, $student_phone, $student_id);
        $stmt->execute();
        $stmt->close();

        // Update user admission number
        $stmt = $conn->prepare("UPDATE user SET admission_number = ? WHERE user_id = ?");
        $stmt->bind_param("si", $admission_number, $_POST['user_id']);
        $stmt->execute();
        $stmt->close();

        // Update class mappings
        $stmt = $conn->prepare("DELETE FROM student_class WHERE student_id = ?");
        $stmt->bind_param("i", $student_id);
        $stmt->execute();
        $stmt->close();

        $stmt = $conn->prepare("INSERT INTO student_class (student_id, class_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $student_id, $class_id);
        $stmt->execute();
        $stmt->close();

        echo "<p>Student details updated successfully!</p>";
    }
    // Redirect to faculty_display_student.php with the student_id
    header("Location: display_student.php?student_id=" . urlencode($student_id));
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Student</title>
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

        input, select {
            border: 1px solid #ccc;
            border-radius: 5px;
            padding: 0.8rem;
            font-size: 1rem;
            outline: none;
            width: 100%;
            box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        input:focus, select:focus {
            border-color: #6c63ff;
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

        /* Modal styles */
        .modal {
            display: none; /* Hidden by default */
            position: fixed; 
            z-index: 1; 
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.4); /* Black with transparency */
        }

        .modal-content {
            background-color: #fefefe;
            margin: 15% auto; /* 15% from the top and centered */
            padding: 20px;
            border: 1px solid #888;
            width: 30%; /* Could be more or less, depending on screen size */
            text-align: center;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        .modal-buttons {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 20px;
        }

        .modal-buttons button {
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        .btn-confirm {
            background-color: #007bff;
            color: white;
        }

        .btn-confirm:hover {
            background-color: #0056b3;
        }

        .btn-cancel {
            background-color: #6c757d;
            color: white;
        }

        .btn-cancel:hover {
            background-color: #545b62;
        }
    </style>
</head>
<body>
<?php include('faculty_header.php'); ?>
    <main class="main-content">
        <h1>Edit Student Details</h1>
        <div class="form-container">
            <div class="form-card">
                <h2>Student Details</h2>
                <form action="faculty_edit_studentform.php?student_id=<?php echo $student['student_id']; ?>" method="POST">
                    <input type="hidden" name="student_id" value="<?= htmlspecialchars($student['student_id'] ?? '') ?>">
                    <input type="hidden" name="user_id" value="<?= htmlspecialchars($student['user_id'] ?? '') ?>">
                    
                    <div class="form-row">
                        <table class="details-table">
                            <tr>
                                <td>
                                    <label for="student_name">Full Name *</label>
                                    <input type="text" id="student_name" name="student_name" value="<?= htmlspecialchars($student['student_name'] ?? '') ?>" required>
                                </td>
                                <td>
                                    <label for="admission_number">Admission No. *</label>
                                    <input type="text" id="admission_number" name="admission_number" value="<?= htmlspecialchars($student['admission_number'] ?? '') ?>" required>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <label for="student_phone">Phone *</label>
                                    <input type="text" id="student_phone" name="student_phone" value="<?= htmlspecialchars($student['student_phone'] ?? '') ?>" required>
                                </td>
                                <td>
                                    <label for="student_email">Email *</label>
                                    <input type="email" id="student_email" name="student_email" value="<?= htmlspecialchars($student['student_email'] ?? '') ?>" required>
                                </td>
                            </tr>
                            <tr>
                                <td colspan="2">
                                    <label for="class_id">Class *</label>
                                    <select id="class_id" name="class_id" required>
                                        <option value="">Select Class</option>
                                        <?php foreach ($classes as $class): ?>
                                            <option value="<?= htmlspecialchars($class['class_id']) ?>" <?= isset($student['class_id']) && $student['class_id'] == $class['class_id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($class['class_name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                            </tr>
                        </table>
                    </div>
                    <button type="submit">Save</button>
                </form>
            </div>
        </div>
    </main>
    <script>
        // Get modal elements
        const confirmationModal = document.getElementById('confirmationModal');
        const updateButton = document.getElementById('updateButton');
        const confirmButton = document.getElementById('confirmButton');
        const cancelButton = document.getElementById('cancelButton');
        const updateForm = document.getElementById('updateForm');

        // Show confirmation modal
        updateButton.addEventListener('click', () => {
            confirmationModal.style.display = 'block';
        });

        // Handle cancel button
        cancelButton.addEventListener('click', () => {
            confirmationModal.style.display = 'none';
        });

        // Handle confirm button
        confirmButton.addEventListener('click', () => {
            confirmationModal.style.display = 'none'; // Close the modal

            // Show alert
            alert('Student updated successfully!');

            // Submit the form
            updateForm.submit();
        });
    </script>
</body>
</html>




