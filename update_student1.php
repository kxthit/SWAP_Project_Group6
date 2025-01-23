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

// Debug: Dump session data
echo "<h3>Session Data (before update):</h3>";
echo "<pre>";
var_dump($_SESSION);
echo "</pre>";

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
                $stmt = $conn->prepare("INSERT INTO student_class (student_id, course_id, class_id) VALUES (?, ?, ?)");
                $stmt->bind_param("iii",  $student_data['student_id'], $course_id, $class_id);
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Student Details</title>
</head>
<body>
    <h2>Update Student Details</h2>

    <form action="update_student1.php" method="POST">
    <?php if (isset($_SESSION['student_data'], $_SESSION['selected_courses'], $_SESSION['selected_classes'])): ?>
        <?php
        $student_data = $_SESSION['student_data'];
        $selected_courses = $_SESSION['selected_courses'];
        $selected_classes = $_SESSION['selected_classes'];

        // Fetch department name
        $stmt = $conn->prepare("SELECT department_name FROM department WHERE department_id = ?");
        $stmt->bind_param("i", $student_data['department_id']);
        $stmt->execute();
        $stmt->bind_result($department_name);
        $stmt->fetch();
        $stmt->close();
        ?>

        <h3>Student Information:</h3>
        <input type="hidden" name="student_id" value="<?= htmlspecialchars($student_data['student_id']); ?>">
        <input type="hidden" name="user_id" value="<?= htmlspecialchars($student_data['user_id']); ?>">
        <p><strong>Student Name:</strong> <?php echo htmlspecialchars($student_data['student_name']); ?></p>
        <p><strong>Admission Number:</strong> <?php echo htmlspecialchars($student_data['admission_number']); ?></p>
        <p><strong>Student Email:</strong> <?php echo htmlspecialchars($student_data['student_email']); ?></p>
        <p><strong>Student Phone:</strong> <?php echo htmlspecialchars($student_data['student_phone']); ?></p>
        <p><strong>Department Name:</strong> <?php echo htmlspecialchars($department_name); ?></p>

        <h3>Selected Courses:</h3>
        <ul>
            <?php foreach ($selected_courses as $course_id): ?>
                <?php
                $stmt = $conn->prepare("SELECT course_name FROM course WHERE course_id = ?");
                $stmt->bind_param("i", $course_id);
                $stmt->execute();
                $stmt->bind_result($course_name);
                $stmt->fetch();
                $stmt->close();
                ?>
                <li><?php echo htmlspecialchars($course_name); ?></li>
            <?php endforeach; ?>
        </ul>

        <h3>Selected Classes:</h3>
        <ul>
            <?php foreach ($selected_classes as $course_id => $class_ids): ?>
                <?php
                $stmt = $conn->prepare("SELECT course_name FROM course WHERE course_id = ?");
                $stmt->bind_param("i", $course_id);
                $stmt->execute();
                $stmt->bind_result($course_name);
                $stmt->fetch();
                $stmt->close();
                ?>
                <strong>Course Name: <?php echo htmlspecialchars($course_name); ?></strong>
                <ul>
                    <?php foreach ($class_ids as $class_id): ?>
                        <?php
                        $stmt = $conn->prepare("SELECT class_name FROM class WHERE class_id = ?");
                        $stmt->bind_param("i", $class_id);
                        $stmt->execute();
                        $stmt->bind_result($class_name);
                        $stmt->fetch();
                        $stmt->close();
                        ?>
                        <li><?php echo htmlspecialchars($class_name); ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <input type="submit" value="Submit">
    </form>

    <a href="edit_studentform1.php?student_id=<?php echo $_SESSION['session_userid']; ?>">Back</a>
</body>
</html>
