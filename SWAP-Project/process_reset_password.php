<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    include 'db_connection.php';

    // Retrieve POST data
    $user_id = intval($_POST['user_id']);
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // Check if passwords match
    if ($new_password !== $confirm_password) {
        echo "<script>
        alert('Passwords do not match! Please reset it properly.'); // Alert message
        window.location.href = 'reset_password.php'; // Redirect to the given URL
        </script>";
        exit;
    }

    // Hash the new password
    $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);

    // Start a transaction
    $conn->begin_transaction();

    try {
        // Update the password in the `user` table
        $stmt = $conn->prepare("
            UPDATE user 
            SET hashed_password = ? 
            WHERE user_id = ?
        ");
        $stmt->bind_param('si', $hashed_password, $user_id);
        $stmt->execute();

        // Invalidate the token by setting it to NULL in the `student` table
        // After successfully resetting the password
        $stmt = $conn->prepare("
        UPDATE student 
        SET is_password_set = TRUE, reset_token = NULL, reset_token_expires = NULL 
        WHERE student_id = ?
        ");
        $stmt->bind_param('i', $user_id);
        $stmt->execute();

        // Commit the transaction
        $conn->commit();

        // Redirect to the login page with a success message
        session_start();
        echo "<script>
            alert('Password Reset Succesfull!'); // Alert message
            window.location.href = 'loginform.php'; // Redirect to the given URL
            </script>";
        exit;
    } catch (Exception $e) {
        // Roll back the transaction in case of error
        $conn->rollback();
        die("Failed to reset password. Please try again.");
    } finally {
        $conn->close();
    }
}
