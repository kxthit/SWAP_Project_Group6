<?php

// Include necessary files for session management and database connection
include 'csrf_protection.php';
include 'db_connection.php';

// Retrieve error message from session
$error_message = $_SESSION['error_message'] ?? '';
unset($_SESSION['error_message']);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="css/loginform.css">
</head>

<body>
    <div class="login-container">
        <div class="left">
            <img src="image/logo-main.png" alt="Welcome Image" class="welcome-img">
        </div>
        <div class="right">
            <h2>Login Page</h2>
            <form action="login.php" method="POST">
                <!-- CSRF Token -->
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>"> <?php if (!empty($error_message)): ?>
                    <div class="error-message">
                        <?php echo htmlspecialchars($error_message, ENT_QUOTES, 'UTF-8'); ?>
                    </div>
                <?php endif; ?>
                <div class="form-group">
                    <label for="admission_number"></label>
                    <input type="text" id="admission_number" name="admission_number" placeholder="Enter Admission no." required>
                    <label for="hashed_password"></label>
                    <input type="password" id="hashed_password" name="hashed_password" placeholder="Enter Password" required>
                    <a href="forgot_password.php" class="forgot-password-btn">Forgot Password?</a>
                </div>
                <div>
                    <button type="submit">Login</button>
                </div>
            </form>
        </div>
    </div>
</body>

</html>