<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password</title>
    <style>
        /* General styling */
        body {
            font-family: Arial, sans-serif;
            background-color: #f9f9f9;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        /* Form container */
        .forgot-password-container {
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
            color: #22303F; /* Dark blue */
        }

        label {
            display: block;
            text-align: left;
            margin-bottom: 10px;
            font-size: 14px;
            color: #394A56; /* Charcoal */
        }

        input[type="email"] {
            width: 94%;
            padding: 12px;
            margin-bottom: 20px;
            border: 1px solid #ccc;
            border-radius: 8px;
            font-size: 16px;
            background-color: #f7f8f9; /* Light grey background */
            outline: none;
            transition: border-color 0.3s;
        }

        input[type="email"]:focus {
            border-color: #8FBFDA; /* Sky blue */
        }

        button {
            width: 100%;
            padding: 12px;
            background-color: #6495ED; /* Blue */
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s ease, box-shadow 0.3s ease;
        }

        button:hover {
            background-color: #0056b3; /* Darker blue */
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.15);
        }

        /* Add some spacing around the page */
        .container {
            padding: 20px;
        }

        /* Media query for responsive design */
        @media (max-width: 768px) {
            .forgot-password-container {
                width: 90%;
                padding: 20px;
            }

            h2 {
                font-size: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="forgot-password-container">
        <h2>Forgot Password</h2>
        <form action="send_reset_link.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            <div>
                <label for="email">Enter your registered email address:</label>
                <input type="email" id="email" name="email" placeholder="example@email.com" required>
            </div>
            <button type="submit">Send Reset Link</button>
        </form>
    </div>
</body>
</html>
