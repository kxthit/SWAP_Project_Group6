<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    include 'db_connection.php';

    // Retrieve POST data
    $user_id = intval($_POST['user_id']);
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // Check if passwords match
    if ($new_password !== $confirm_password) {
        die("Passwords do not match.");
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
        $_SESSION['success_message'] = "Password has been successfully reset. You can now log in.";
        header("Location: login.php");
        exit;
    } catch (Exception $e) {
        // Roll back the transaction in case of error
        $conn->rollback();
        die("Failed to reset password. Please try again.");
    } finally {
        $conn->close();
    }
}
?>
