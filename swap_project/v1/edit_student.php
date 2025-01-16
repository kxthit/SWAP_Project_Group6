<?php

// Include database connection
include 'db_connection.php';

session_start();

// Check if the user is authenticated
if (!isset($_SESSION['session_userid']) || !isset($_SESSION['session_role'])) {
    echo "<h2>Unauthorized access. Please log in.</h2>";
    header('Refresh: 3; URL=login.php');
    exit;
}


// Check if the form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get the student ID from the form
    $student_id = $_POST['student_id'] ?? null;
    // Get other form data
    $student_name = $_POST['student_name'] ?? '';
    $admission_number = $_POST['admission_number'] ?? '';
    $student_email = $_POST['student_email'] ?? '';
    $student_phone = $_POST['student_phone'] ?? '';
    $department_id = $_POST['department_id'] ?? '';
    $courses = $_POST['courses'] ?? [];
    $class_id = $_POST['class_id'] ?? '';  // This is linked to the student_class table

    if ($student_id) {
        // Start a transaction to ensure data integrity
        $pdo->beginTransaction();

        try {
            
            $stmt = $pdo->prepare("UPDATE student SET student_name = :student_name, student_email = :student_email, student_phone = :student_phone, department_id = :department_id WHERE student_id = :student_id");
            $stmt->execute([
                ':student_name' => $student_name,
                ':student_email' => $student_email,
                ':student_phone' => $student_phone,
                ':department_id' => $department_id,
                ':student_id' => $student_id
            ]);

            // Update class association in the student_class table (if class_id is provided)
            if (!empty($class_id)) {
                // Delete any existing class associations
                $stmt = $pdo->prepare("DELETE FROM student_class WHERE student_id = :student_id");
                $stmt->execute([':student_id' => $student_id]);

                // Insert the new class association
                $stmt = $pdo->prepare("INSERT INTO student_class (student_id, class_id) VALUES (:student_id, :class_id)");
                $stmt->execute([
                    ':student_id' => $student_id,
                    ':class_id' => $class_id
                ]);
            }

            // Update course associations in the student_course table
            if (!empty($courses)) {
                // Delete existing courses
                $stmt = $pdo->prepare("DELETE FROM student_course WHERE student_id = :student_id");
                $stmt->execute([':student_id' => $student_id]);

                // Insert new courses
                $stmt = $pdo->prepare("INSERT INTO student_course (student_id, course_id) VALUES (:student_id, :course_id)");
                foreach ($courses as $course_id) {
                    $stmt->execute([
                        ':student_id' => $student_id,
                        ':course_id' => $course_id
                    ]);
                }
            }

            // Commit the transaction
            $pdo->commit();

            // Redirect back to display_student.php with updated student ID
            header("Location: display_student.php?student_id=" . $student_id);
            exit;

        } catch (Exception $e) {
            // Rollback the transaction if an error occurs
            $pdo->rollBack();
            echo "Error updating student: " . $e->getMessage();
        }
    } else {
        echo "Invalid student ID.";
    }
}
?>
