<?php
// Include the database connection
include_once 'db_connection.php';

// Start session
session_start();

$error_message = ''; // To store error messages

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize inputs
    $form_admission_no = htmlspecialchars($_POST['admission_number']);
    $form_password = htmlspecialchars($_POST['hashed_password']);

    // Check for empty fields
    if (empty($form_admission_no) || empty($form_password)) {
        $error_message = "Admission number and password cannot be empty.";
    } else {
        // Prepare SQL query to fetch user details
        $stmt = $con->prepare("SELECT id, admission_no, hashed_password, role_id FROM `user` WHERE admission_no = ?");
        $stmt->bind_param('s', $form_admission_no);

        if ($stmt->execute()) {
            $stmt->store_result(); // Store the result

            // Check if a user exists
            if ($stmt->num_rows > 0) {
                // Bind the result to variables
                $stmt->bind_result($id, $db_admission_no, $db_password, $role_id);
                $stmt->fetch();

                // Verify the password
                if (password_verify($form_password, $db_password)) {
                    // Store session variables
                    $_SESSION['user_id'] = $id;
                    $_SESSION['admission_no'] = $db_admission_no;
                    $_SESSION['role_id'] = $role_id;

                    // Role-specific redirects
                    switch ($role_id) {
                        case 1: // Admin
                            $_SESSION['role_name'] = 'Admin';
                            header("Location: access_key_form.php");
                            break;
                        case 2: // Faculty
                            $_SESSION['role_name'] = 'Faculty';
                            header("Location: access_key_form.php");
                            break;
                        case 3: // Student
                            $_SESSION['role_name'] = 'Student';
                            header("Location: student_dashboard.php");
                            break;
                        default:
                            $error_message = "Invalid role detected. Please contact the administrator.";
                    }
                    exit; // Stop further execution after redirect
                } else {
                    $error_message = "Incorrect password. Please try again.";
                }
            } else {
                $error_message = "No user found with the provided admission number.";
            }
        } else {
            $error_message = "Error executing the query. Please try again.";
        }

        $stmt->close(); // Close the statement
    }
}
?>
<!-- Include the login form -->
<?php include('loginform.php'); ?>
