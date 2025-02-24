<?php
include 'session_management.php';

// Retrieve the error message from the session
$error_message = $_SESSION['error_message'] ?? '';

// Clear the error message after displaying it
unset($_SESSION['error_message']);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Access Key Verification</title>
    <style>
        /* Body styles */
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

        /* Login container styles */
        .login-container {
            display: flex;
            width: 1000px;
            height: 500px;
            border-radius: 15px;
            overflow: hidden;
        }

        /* Left blue section */
        .login-container .left {
            background-color: #22303f;
            color: white;
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        /* Styling for the image */
        .welcome-img {
            max-width: 50%;
            /* Ensure the image does not overflow */
            height: auto;
            /* Maintain the aspect ratio */
            border-radius: 10px;
            /* Optional: add rounded corners to the image */
        }

        /* Right white section */
        .login-container .right {
            background-color: white;
            flex: 1;
            padding: 20px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            /* Center content vertically */
            align-items: center;
            /* Center content horizontally */
        }

        .right h2 {
            margin-bottom: 20px;
            text-align: center;
        }

        /* Form styling */
        .form-group {
            margin-bottom: 15px;
        }

        label {
            font-size: 16px;
            color: #333;
            display: block;
            margin-bottom: 5px;
        }

        /* Input field styling */
        input {
            width: 300px;
            padding: 10px;
            border: 1px solid #ddd;
            /* Faint grey border */
            border-radius: 5px;
            font-size: 14px;
            background-color: #f9f9f9;
            /* Lighter background color for the fields */
        }

        /* Placeholder styling */
        input::placeholder {
            color: #aaa;
            /* Faint grey color for placeholder text */
            font-style: italic;
            /* Optional: make the placeholder text italic */
        }

        /* Focused input field styling */
        input:focus {
            outline: none;
            /* Remove the default blue outline on focus */
            border-color: #007BFF;
            /* Change border color when input is focused */
            background-color: white;
            /* Change background color when focused */
        }

        /* Button styling */
        button {
            width: 50%;
            padding: 10px;
            background-color: #22303f;
            color: white;
            border: none;
            border-radius: 50px;
            font-size: 16px;
            cursor: pointer;
            margin-left: 85px;
            margin-top: 10px;
        }


        button:hover {
            letter-spacing: 3px;
            background-color: #FCD34D;
            /* Warm gold */
            color: black;
            box-shadow: rgb(252 211 77) 0px 7px 29px 0px;
            /* Soft gold glow */
        }

        button:active {
            letter-spacing: 3px;
            background-color: #FCD34D;
            /* Warm gold */
            color: black;
            box-shadow: rgb(252 211 77) 0px 0px 0px 0px;
            transform: translateY(5px);
            transition: 100ms;
        }

        /* Error message styling */
        .error-message {
            color: red;
            font-weight: lighter;
            position: absolute;
            /* Absolute positioning */
            top: 120px;
            /* Adjust based on where you want it to appear */
            left: 50%;
            transform: translateX(-50%);
            /* Center horizontally */
            text-align: center;
            z-index: 1;
            /* Ensure it appears above other elements */
            width: 60%;
            /* Optional: Limit width for long messages */
            background-color: #f8d7da;
            /* Light red background for better visibility */
            padding: 10px;
            /* Padding inside the error box */
            border: 1px solid #f5c6cb;
            /* Border for better distinction */
            border-radius: 5px;
            /* Rounded corners */
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            /* Subtle shadow for styling */
        }
    </style>
</head>

<body>

    <div class="login-container">
        <!-- Left section -->
        <div class="left">
            <img src="image/logo-main.png" alt="Welcome Image" class="welcome-img">
        </div>

        <!-- Right section -->
        <div class="right">
            <h2>Access Key Verification</h2>
            <!-- Display Error Message -->
            <?php if (!empty($error_message)): ?>
                <div class="error-message">
                    <?php echo htmlspecialchars($error_message, ENT_QUOTES, 'UTF-8'); ?>
                </div>
            <?php endif; ?>

            <form action="access_key.php" method="POST">
                <!-- CSRF Token -->
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                <div class="form-group">
                    <label for="access_key"></label>
                    <input type="password" id="access_key" name="access_key" placeholder="Enter Access Key" required>
                </div>
                <div>
                    <button type="submit">Submit</button>
                </div>
            </form>
        </div>
    </div>

</body>

</html>