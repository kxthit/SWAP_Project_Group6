<?php
session_start();

// Check if the user is authenticated
if (!isset($_SESSION['session_userid']) || !isset($_SESSION['session_role'])) {
    echo "<h2>Unauthorized access. Please log in.</h2>";
    header('Refresh: 3; URL=login.php');
    exit;
}

// Include database connection
include 'db_connection.php';


// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ensure session contains the required data
    if (!isset($_SESSION['student_data'], $_SESSION['selected_courses'], $_SESSION['selected_classes'])) {
        echo '<h2>Session data missing. Please restart the process.</h2>';
        exit;
    }


    $student_data = $_SESSION['student_data'];
    $selected_courses = $_SESSION['selected_courses'];
    $selected_classes = $_SESSION['selected_classes'];

    // Begin transaction
    $conn->begin_transaction();

    try {
        // Update student details
        $stmt = $conn->prepare("UPDATE student SET student_name = ?, student_email = ?, student_phone = ?, department_id = ? WHERE student_id = ?");
        $stmt->bind_param("sssii", $student_data['student_name'], $student_data['student_email'], $student_data['student_phone'], $student_data['department_id'], $student_data['student_id']);
        $stmt->execute();
        $stmt->close();

        // Update user admission number
        $stmt = $conn->prepare("UPDATE user SET admission_number = ? WHERE user_id = ?");
        $stmt->bind_param("si", $student_data['admission_number'], $student_data['user_id']);
        $stmt->execute();
        $stmt->close();

        // Delete old course mappings
        $stmt = $conn->prepare("DELETE FROM student_course WHERE student_id = ?");
        $stmt->bind_param("i", $student_data['student_id']);
        $stmt->execute();
        $stmt->close();

        // Insert new course mappings
        foreach ($selected_courses as $course_id) {
            $stmt = $conn->prepare("INSERT INTO student_course (student_id, course_id) VALUES (?, ?)");
            $stmt->bind_param("ii", $student_data['student_id'], $course_id);
            $stmt->execute();
            $stmt->close();
        }

        // Update class mappings
        $stmt = $conn->prepare("DELETE FROM student_class WHERE student_id = ?");
        $stmt->bind_param("i",  $student_data['student_id']);
        $stmt->execute();
        $stmt->close();

        foreach ($selected_classes as $course_id => $class_ids) {
            foreach ($class_ids as $class_id) {
                $stmt = $conn->prepare("INSERT INTO student_class (student_id, class_id) VALUES (?, ?)");
                $stmt->bind_param("ii",  $student_data['student_id'], $class_id);
                $stmt->execute();
                $stmt->close();
            }
        }

        // Commit transaction if all queries succeeded
        $conn->commit();

        echo "<h2>Student details updated successfully!</h2>";
        header('Location: student.php');
        exit;

    } catch (mysqli_sql_exception $e) {
        // Rollback transaction in case of error
        $conn->rollback();
        echo '<h2>Error updating student details: ' . htmlspecialchars($e->getMessage()) . '</h2>';
    }
}

// Fetch department name
$student_data = $_SESSION['student_data'];
$stmt = $conn->prepare("SELECT department_name FROM department WHERE department_id = ?");
$stmt->bind_param("i", $student_data['department_id']);
$stmt->execute();
$stmt->bind_result($department_name);
$stmt->fetch();
$stmt->close();

// Fetch course names
$courses = [];
foreach ($_SESSION['selected_courses'] as $course_id) {
    $stmt = $conn->prepare("SELECT course_name FROM course WHERE course_id = ?");
    $stmt->bind_param("i", $course_id);
    $stmt->execute();
    $stmt->bind_result($course_name);
    $stmt->fetch();
    $stmt->close();
    $courses[] = htmlspecialchars($course_name);
}

// Fetch class names grouped by course
$classes = [];
foreach ($_SESSION['selected_classes'] as $course_id => $class_ids) {
    $class_list = [];
    foreach ($class_ids as $class_id) {
        $stmt = $conn->prepare("SELECT class_name FROM class WHERE class_id = ?");
        $stmt->bind_param("i", $class_id);
        $stmt->execute();
        $stmt->bind_result($class_name);
        $stmt->fetch();
        $stmt->close();
        $class_list[] = htmlspecialchars($class_name);
    }
    $classes[$course_id] = $class_list;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Student Details</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f5f7fc;
        }
        .form-container {
            width: 100%;
            max-width: 1000px;
            margin: 1rem auto;
            background-color: #fff;
            border-radius: 10px;
            padding: 1rem;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            margin-top: 30px;
        }
        h2 {
            background-color: #6495ed;
            color: white;
            padding: 1rem;
            border-radius: 10px 10px 0 0;
            margin-left: -17px;
            width: 100.2%;
            margin-top: -30px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th {
            text-align: right;
            padding: 10px;
            vertical-align: top;
        }
        td {
            padding: 10px;
            text-align: left;
        }
        ul {
            list-style: none;
            padding: 0;
        }
        ul li {
            margin-left: 20px;
        }
        .action-buttons {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 20px;
        }
        button {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            font-size: 1rem;
            cursor: pointer;
        }
        .submit-btn {
            background-color: #28a745;
            color: white;
        }
        .cancel-btn {
            background-color: #dc3545;
            color: white;
        }
        button:hover {
            opacity: 0.9;
        }
    </style>
</head>
<body>
<?php include('admin_header.php'); ?>
    <main class="main-content">
        <form action="update_student1.php" method="POST">
            <div class="form-container">
                <h2>Final Student Details</h2>
                <table>
                    <tr>
                        <th>Name:</th>
                        <td><?= htmlspecialchars($student_data['student_name']) ?></td>
                    </tr>
                    <tr>
                        <th>Admission Number:</th>
                        <td><?= htmlspecialchars($student_data['admission_number']) ?></td>
                    </tr>
                    <tr>
                        <th>Email:</th>
                        <td><?= htmlspecialchars($student_data['student_email']) ?></td>
                    </tr>
                    <tr>
                        <th>Phone:</th>
                        <td><?= htmlspecialchars($student_data['student_phone']) ?></td>
                    </tr>
                    <tr>
                        <th>Department:</th>
                        <td><?= htmlspecialchars($department_name) ?></td>
                    </tr>
                    <tr>
                        <th>Courses:</th>
                        <td>
                            <ul>
                                <?php foreach ($courses as $course): ?>
                                    <li><?= $course ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </td>
                    </tr>
                    <tr>
                        <th>Classes:</th>
                        <td>
                            <ul>
                                <?php foreach ($classes as $course_id => $class_list): ?>
                                    <?php foreach ($class_list as $class): ?>
                                        <li><?= $class ?></li>
                                    <?php endforeach; ?>
                                <?php endforeach; ?>
                            </ul>
                        </td>
                    </tr>
                </table>
            </div>
            <div class="action-buttons">
                <button type="submit" class="submit-btn">Submit</button>
                <button type="button" class="cancel-btn" onclick="window.location.href='display_student.php?student_id=<?= $student_data['student_id'] ?>'">Cancel</button>
            </div>
        </form>
    </main>
</body>
</html>