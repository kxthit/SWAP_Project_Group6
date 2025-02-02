<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php'; // Include the PHPMailer autoload file

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    include 'db_connection.php';

    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    // Check if the email exists in the student table
    $stmt = $conn->prepare("SELECT student_id FROM student WHERE student_email = ?");
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        // Generate reset token and expiration time
        $reset_token = bin2hex(random_bytes(32));
        $reset_token_expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

        // Update the student table with the reset token and expiration
        $update_stmt = $conn->prepare("UPDATE student SET reset_token = ?, reset_token_expires = ? WHERE student_email = ?");
        $update_stmt->bind_param('sss', $reset_token, $reset_token_expires, $email);
        if ($update_stmt->execute()) {
            // Generate the reset link
            $reset_link = "http://localhost/disaster/reset_password.php?token=$reset_token";

            // Send the reset link via PHPMailer
            $mail = new PHPMailer(true);

            try {
                // Server settings
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com'; // Set your SMTP server
                $mail->SMTPAuth = true;
                $mail->Username = 'swapprojectsender@gmail.com'; // Your Gmail address
                $mail->Password = 'ylxv vvbf wzrx czqn'; // Your Gmail password or app password
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // Use STARTTLS encryption
                $mail->Port = 587;

                // Recipients
                $mail->setFrom('swapprojectsender@gmail.com', 'SWAP');
                $mail->addAddress($email);

                // Content
                $mail->isHTML(true);
                $mail->Subject = 'Password Reset';
                $mail->Body = "Click the link below to reset your password:<br><a href='$reset_link'>$reset_link</a>";

                $mail->send();
                echo "A reset link has been sent to your email.";
            } catch (Exception $e) {
                echo "Failed to send email. Mailer Error: {$mail->ErrorInfo}";
            }
        } else {
            echo "Failed to update token and expiration time.<br>";
            echo "Error: " . $update_stmt->error . "<br>";

        }
    } else {
        echo "Email not found.";
    }
    mysqli_close($conn);
}
?>
