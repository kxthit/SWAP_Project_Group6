<?php
// Include database connection
include_once 'db_connection.php';

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form_admission_no = htmlspecialchars($_POST['admission_number']);
    $form_password = htmlspecialchars($_POST['hashed_password']);

    // Check for empty fields
    if (empty($form_admission_no) || empty($form_password)) {
        $error_message = "Both admission number or password cannot be empty. Please try again.";
    } else {
        
        // Prepare the statement
        $stmt = mysqli_prepare($conn, "SELECT * FROM user WHERE admission_number = ?");
        mysqli_stmt_bind_param($stmt, 's', $form_admission_no);
        mysqli_stmt_execute($stmt);

        // Fetch the result
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);

        // Check if user exists
        if (!$row) {
            $error_message = "No such user. Please try again.";
        } else {
            // Verify password
            if (!password_verify($form_password, $row['hashed_password'])) {
                echo "<script>alert('Incorrect password. Please try again.');</script>";
            } else {
                // Start session and set session variables
                session_start();
                $_SESSION['session_userid'] = $row['user_id'];
                $_SESSION['session_role'] = $row['role_id'];

                // Fetch role-specific data and redirect page
                $role_table = '';
                $role_column = '';
                $redirect = '';

                switch ($row['role_id']) {
                    case 3: // Student
                        $role_table = 'student';
                        $role_column = 'student_name';
                        $redirect = 'student_dashboard.php';
                        break;
                    case 1: // Admin
                        $role_table = 'admin';
                        $role_column = 'admin_name';
                        $redirect = 'access_key_form.php';
                        break;
                    case 2: // Faculty
                        $role_table = 'faculty';
                        $role_column = 'faculty_name';
                        $redirect = 'access_key_form.php';
                        break;
                    default:
                        $error_message = "Invalid role. Please contact the administrator.";
                        break;
                }

                if (empty($error_message)) {
                    // Prepare the query to fetch the name from the respective table
                    $stmt = mysqli_prepare($conn, "SELECT $role_column FROM $role_table WHERE user_id = ?");
                    mysqli_stmt_bind_param($stmt, 'i', $row['user_id']);
                    mysqli_stmt_execute($stmt);
                    $result = mysqli_stmt_get_result($stmt);
                    $role_data = mysqli_fetch_assoc($result);

                    // Store the name in session
                    $_SESSION['session_name'] = $role_data[$role_column];

                    // Redirect user
                    header("Location: $redirect");
                    exit;
                }
            }
        }
    }
}
?>
<?php include('loginform.php'); // Include the form here ?>
