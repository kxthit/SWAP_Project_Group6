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
            background-color: #f4f4f4;
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
            background-color: #6495ed;
            color: white;
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        /* Styling for the image */
        .welcome-img {
            max-width: 100%;  /* Ensure the image does not overflow */
            height: auto;     /* Maintain the aspect ratio */
            border-radius: 10px;  /* Optional: add rounded corners to the image */
        }

        /* Right white section */
        .login-container .right {
            background-color: white;
            flex: 1;
            padding: 20px;
            display: flex;
            flex-direction: column;
            justify-content: center; /* Center content vertically */
            align-items: center; /* Center content horizontally */
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
            border: 1px solid #ddd; /* Faint grey border */
            border-radius: 5px;
            font-size: 14px;
            background-color: #f9f9f9; /* Lighter background color for the fields */
        }

        /* Placeholder styling */
        input::placeholder {
            color: #aaa; /* Faint grey color for placeholder text */
            font-style: italic; /* Optional: make the placeholder text italic */
        }

        /* Focused input field styling */
        input:focus {
            outline: none; /* Remove the default blue outline on focus */
            border-color: #007BFF; /* Change border color when input is focused */
            background-color: white; /* Change background color when focused */
        }

        /* Button styling */
        button {
            width: 50%;
            padding: 10px;
            background-color: #6495ed;
            color: white;
            border: none;
            border-radius: 50px;
            font-size: 16px;
            cursor: pointer;
            margin-left: 85px;
            margin-top: 10px;
        }

        /* Button hover effect */
        button:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>

    <div class="login-container">
        <!-- Left section -->
        <div class="left">
            <img src="image\x_y_z.-removebg-preview.png" alt="Welcome Image" class="welcome-img">
        </div>
        
        <!-- Right section -->
        <div class="right">
            <h2>Access Key Verification</h2>
            <form action="access_key.php" method="POST">
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
