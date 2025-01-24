<?php
// Include the database connection
include 'db_connection.php';

session_start();

// Check if the user is authenticated
if (!isset($_SESSION['session_userid']) || $_SESSION['session_role'] != 1) {
    echo "<h2>Unauthorized access. Only admins can delete students.</h2>";
    header('Refresh: 3; URL=login.php');
    exit;
}

// Check if the student_id is provided via GET
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['student_id']) && is_numeric($_GET['student_id'])) {
    $student_id = intval($_GET['student_id']);

    // Start a transaction to ensure all operations are performed atomically
    $conn->begin_transaction();

    try {
        // Step 1: Delete from student_course table (remove courses associated with the student)
        $sql = "DELETE FROM student_course WHERE student_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $student_id);
        $stmt->execute();

        // Step 2: Delete from student_class table (remove classes associated with the student)
        $sql = "DELETE FROM student_class WHERE student_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $student_id);
        $stmt->execute();

        // Step 3: Delete from grades table (remove grades associated with the student)
        $sql = "DELETE FROM student_course_grade WHERE student_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $student_id);
        $stmt->execute();

        // Step 4: Delete from student table
        $sql = "DELETE FROM student WHERE student_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $student_id);
        $stmt->execute();

        // Step 4: Delete from user table using the user_id from the student table
        $sql = "DELETE FROM user WHERE user_id = (SELECT user_id FROM student WHERE student_id = ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $student_id);
        $stmt->execute();

        // Commit the transaction if all queries are successful
        $conn->commit();

        // Redirect to the student list page with a success message
        echo "<script>
                alert('Student deleted successfully!');
                window.location.href = 'student.php'; // Redirect to the student list page
              </script>";

    } catch (Exception $e) {
        // If any exception occurs, roll back the transaction
        $conn->rollback();
        echo "<script>
                alert('Error: " . $e->getMessage() . "');
                window.location.href = 'student.php'; // Redirect to the student list page in case of error
              </script>";
    } finally {
        $stmt->close();
        $conn->close();
    }
} else {
    // If student_id is not provided, redirect to the student list page with an error
    echo "<script>
            alert('Error: No student ID provided.');
            window.location.href = 'student.php'; // Redirect to the student list page
          </script>";
}
?>
