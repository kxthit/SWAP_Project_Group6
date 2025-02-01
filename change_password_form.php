<?php
include 'db_connection.php';
include 'session_management.php';


$error_message="";

// CSRF Protection: Generate or validate CSRF token
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error_message="Invalid CSRF token. Please try again.";
    }
}

// Check if the user is authenticated
if (!isset($_SESSION['session_userid'])) {
   $error_message="Unauthorized access. Please log in.";
}

$user_id = $_SESSION['session_userid'];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_password = trim(htmlspecialchars($_POST['current_password'] ?? ''));
    $new_password = trim(htmlspecialchars($_POST['new_password'] ?? ''));
    $confirm_password = trim(htmlspecialchars($_POST['confirm_password'] ?? ''));

    // Validate inputs
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $errors[] = "All fields are required.";
    }

    if ($new_password !== $confirm_password) {
        $errors[] = "New password and confirm password do not match.";
    }

    if (!preg_match('/^(?=.*[A-Z])(?=.*[a-z])(?=.*\\d).{8,}$/', $new_password)) {
        $errors[] = "Password must contain at least one uppercase letter, one lowercase letter, one number, and be at least 8 characters long.";
    }

    // Fetch the current password hash from the database
    $stmt = $conn->prepare("SELECT hashed_password FROM user WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if (!$user || !password_verify($current_password, $user['hashed_password'])) {
        $errors[] = "Current password is incorrect.";
    }

    // If no errors, update the password
    if (empty($errors)) {
        $new_hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE user SET hashed_password = ? WHERE user_id = ?");
        $stmt->bind_param("si", $new_hashed_password, $user_id);
        
        if ($stmt->execute()) {
            // Success message
            $_SESSION['password_success'] = "Password updated successfully!";
            header("Location: student_profile.php");
            exit();
        } else {
            $error_message = "Error updating password. Please try again.";
        }
    }

    // Store errors in session and redirect back to the form
    $_SESSION['password_errors'] = $errors;
    header("Location: change_password_form.php");
    exit();
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password</title>
    <link rel="stylesheet" href="style.css">
    <style>
        body {
            font-family: 'Source Sans Pro', sans-serif;
            background-color: #f5f7fc;
            margin: 0;
            padding: 0;
        }

        .form-container {
            background: #c3d9e5;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            padding: 20px;
            max-width: 600px;
            margin: 30px auto;
            text-align: center;
            border: 2px solid #ecdfce;
        }

        h1 {
            color: #1a1a1a;
            margin-bottom: 1rem;
            text-align: center;
        }

        label, p {
            font-size: 18px;
            display: block;
            text-align: left;
            margin-bottom: 5px;
        }

        input {
            width: 80%;
            padding: 10px;
            margin: 10px 0;
            border-radius: 5px;
            border: 1px solid #ccc;
            font-size: 15px;
        }

        button {
            background-color: #3b667e;
            color: white;
            border: none;
            cursor: pointer;
            padding: 10px;
            width: 50%;
            border-radius: 5px;
            font-size: 16px;
            margin-top: 15px;
        }

        button:hover {
            background-color: #ecdfce;
            color: #2b2d42;
            box-shadow: 0 0 15px 4px #3D5671;
        }

        .error-messages {
            background-color: #ffdddd;
            color: #d8000c;
            border: 1px solid #d8000c;
            border-radius: 5px;
            padding: 1rem;
            margin-bottom: 1.5rem;
            text-align: left;
        }
        .error-messages ul {
            list-style-type: disc;
            padding-left: 20px;
            margin: 0;
        }
        .error-messages li {
            font-size: 1rem;
            line-height: 1.5;
        }

        .error-modal {
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

        .error-modal-content {
            background-color: white;
            padding: 2rem;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.3);
            max-width: 500px;
            width: 90%;
        }

        .error-modal-content h2 {
            color: #d8000c;
            margin-bottom: 1rem;
        }

        .error-modal-content p {
            font-size: 1rem;
            margin-bottom: 1.5rem;
        }

        .error-modal-content button {
            background-color: #2c6485;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            font-size: 1rem;
            cursor: pointer;
        }

        .error-modal-content button:hover {
            background-color: #22303f;
        }

    </style>
</head>
<body>
<?php include('student_header.php'); ?>
    <main class="main-content">
        <h1>Change Password</h1>
        <?php if (!empty($error_message)): ?>
            <div class="error-modal" id="errorModal" style="display: flex;">
                <div class="error-modal-content">
                    <h2>Error</h2>
                    <p><?php echo htmlspecialchars($error_message); ?></p>
                    <button onclick="window.location.href='student.php'">Go Back</button>
                </div>
            </div>
        <?php else: ?>
        <div class="form-container">
            <?php if (!empty($errors)): ?>
                <div class="error-messages">
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            <form action="change_password_form.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                <label for="current_password">Current Password</label>
                <input type="password" id="current_password" name="current_password" required>
                
                <label for="new_password">New Password</label>
                <input type="password" id="new_password" name="new_password" required>
                
                <label for="confirm_password">Confirm New Password</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
                
                <button type="submit">Change Password</button>
            </form>
        </div>
        <?php endif; ?>
    </main>
</body>
</html>
