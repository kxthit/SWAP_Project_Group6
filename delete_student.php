<?php
// Include the database connection
include 'db_connection.php';
include 'session_management.php';

$error_message = ""; // Initialize an empty error message

// Check if the user is authenticated
if (!isset($_SESSION['session_userid']) || $_SESSION['session_role'] != 1) {
    echo "<h2>Unauthorized access. Only admins can delete students.</h2>";
    header('Refresh: 3; URL=login.php');
    exit;
}

// Generate CSRF token if missing
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Check if the student_id is provided via GET
if ($_SERVER['REQUEST_METHOD'] === 'GET' && empty($error_message)) {
    if (!isset($_GET['student_id']) || !is_numeric($_GET['student_id'])) {
        $error_message = "Invalid or missing student ID.";
    } elseif (!isset($_GET['csrf_token']) || $_GET['csrf_token'] !== $_SESSION['csrf_token']) {
        $error_message = "Invalid CSRF token. Please reload the page and try again.";
    } else {
    
        $student_id = intval($_GET['student_id']);

        try {
            // Start a transaction to ensure all operations are performed atomically
            $conn->begin_transaction();

            // Step 1: Check if the student exists
            $stmt = $conn->prepare("SELECT user_id FROM student WHERE student_id = ?");
            $stmt->bind_param("i", $student_id);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 0) {
                throw new Exception("Student not found.");
            }
            $student = $result->fetch_assoc();
            $user_id = $student['user_id'];

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
            // Rollback on error
            $conn->rollback();
            $error_message = "An error occurred while deleting the student. Please try again.";
        } finally {
            // Close statement and connection
            if (isset($stmt)) $stmt->close();
            $conn->close();
        }
    }
}elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && empty($error_message)) {
    $error_message = "Invalid request method.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delete Student</title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* Modal Styles */
        .modal {
            display: flex;
            justify-content: center;
            align-items: center;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
        }

        .modal-content {
            background-color: white;
            padding: 2rem;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.3);
            max-width: 500px;
            width: 90%;
        }

        .modal-content h2 {
            color: #d8000c;
            margin-bottom: 1rem;
        }

        .modal-content p {
            font-size: 1rem;
            margin-bottom: 1.5rem;
        }

        .modal-content button {
            background-color: #6495ed;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            font-size: 1rem;
            cursor: pointer;
        }

        .modal-content button:hover {
            background-color: #5a52d4;
        }
    </style>
</head>
<body>
    <?php if (!empty($error_message)): ?>
        <!-- Error Modal -->
        <div class="modal" id="errorModal">
            <div class="modal-content">
                <h2>Error</h2>
                <p><?php echo htmlspecialchars($error_message); ?></p>
                <button onclick="window.location.href='student.php'">Go Back</button>
            </div>
        </div>
    <?php endif; ?>
</body>
</html>