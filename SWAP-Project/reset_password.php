<?php
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    include 'db_connection.php';

    $token = $_GET['token'] ?? '';

    // Validate token and check expiration
    $stmt = $conn->prepare("
        SELECT student.user_id, student.reset_token_expires 
        FROM student 
        WHERE student.reset_token = ?
    ");
    $stmt->bind_param('s', $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $row = $result->fetch_assoc();
        $reset_token_expires = strtotime($row['reset_token_expires']);
        $current_time = time();

        if ($current_time > $reset_token_expires) {
            die("Invalid or expired token.");
        } else {
            $user_id = $row['user_id'];
        }
    } else {
        die("Invalid or expired token.");
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #4a5568;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        .reset-password-container {
            background-color: #fff;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            width: 400px;
            text-align: center;
        }

        h2 {
            margin-bottom: 20px;
            font-size: 24px;
            color: #22303F;
        }

        label {
            display: block;
            text-align: left;
            margin-bottom: 8px;
            font-size: 14px;
            color: #394A56;
        }

        input[type="password"] {
            width: 94%;
            padding: 12px;
            margin-bottom: 20px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            background-color: #f7f8f9;
            outline: none;
            transition: border-color 0.3s;
        }

        input[type="password"]:focus {
            border-color: #8FBFDA;
        }

        button {
            width: 100%;
            padding: 12px;
            background-color: #22303f;
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s ease, box-shadow 0.3s ease;
        }

        button:hover {
            background-color: #0056b3;
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.15);
        }

        button:hover {
            background-color: #FCD34D;
            /* Darker blue */
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.15);
            color: black;

        }

        .reset-password-container div {
            text-align: left;
        }
    </style>
</head>

<body>
    <div class="reset-password-container">
        <h2>Reset Your Password</h2>
        <form action="process_reset_password.php" method="POST">
            <input type="hidden" name="user_id" value="<?php echo $user_id; ?>">
            <div>
                <label for="new_password">New Password:</label>
                <input type="password" id="new_password" name="new_password" required>
            </div>
            <div>
                <label for="confirm_password">Confirm Password:</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
            </div>
            <button type="submit">Reset Password</button>
        </form>
    </div>
</body>

</html>