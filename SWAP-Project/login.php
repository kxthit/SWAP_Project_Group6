<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Force logout when visiting login.php directly
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    session_unset();  // Unset all session variables
    session_destroy(); // Destroy session

    // Redirect to login form
    header("Location: loginform.php");
    exit;
}

// Include database connection
include_once 'db_connection.php';
include 'csrf_protection.php';


$error_message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !validate_csrf_token($_POST['csrf_token'])) {
        regenerate_csrf_token();
        die("Invalid CSRF token. <a href='logout.php'>Try again</a>");
    }

    // Retrieve and sanitize inputs
    $form_admission_no = filter_var($_POST['admission_number'], FILTER_SANITIZE_STRING);
    $form_password = $_POST['hashed_password'];

    // Check for empty fields
    if (empty($form_admission_no) || empty($form_password)) {
        $error_message = "Admission number or password cannot be empty.";
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
            // Error message for invalid admission number
            $error_message = "Invalid credentials. Please try again.";
        } else {
            // Verify password
            if (!password_verify($form_password, $row['hashed_password'])) {
                // Error message for incorrect password
                $error_message = "Invalid credentials. Please try again.";
            } else {
                // Regenerate session ID for security
                session_regenerate_id(true);

                // Store user data in session
                $_SESSION['session_userid'] = $row['user_id'];
                $_SESSION['session_roleid'] = $row['role_id'];

                // Role-based redirection
                $roles = [
                    3 => ['table' => 'student', 'column' => 'student_name', 'redirect' => 'student_profile.php'],
                    1 => ['table' => 'admin', 'column' => 'admin_name', 'redirect' => 'access_key_form.php'],
                    2 => ['table' => 'faculty', 'column' => 'faculty_name', 'redirect' => 'access_key_form.php'],
                ];

                if (array_key_exists($row['role_id'], $roles)) {
                    $role = $roles[$row['role_id']];

                    // Fetch name from respective table
                    $stmt = mysqli_prepare($conn, "SELECT {$role['column']} FROM {$role['table']} WHERE user_id = ?");
                    mysqli_stmt_bind_param($stmt, 'i', $row['user_id']);
                    mysqli_stmt_execute($stmt);
                    $result = mysqli_stmt_get_result($stmt);
                    $role_data = mysqli_fetch_assoc($result);

                    // Store the name in session
                    $_SESSION['session_name'] = htmlspecialchars($role_data[$role['column']], ENT_QUOTES, 'UTF-8');

                    // Redirect user
                    header("Location: " . $role['redirect']);
                    exit;
                } else {
                    $error_message = "Invalid role. Please contact the administrator.";
                }
            }
        }
    }

    // Store error message in session and redirect to login form
    if (!empty($error_message)) {
        $_SESSION['error_message'] = $error_message;
        header("Location: loginform.php");
        exit;
    }
}
?>

<?php include('loginform.php'); // Include the form here 
?>
