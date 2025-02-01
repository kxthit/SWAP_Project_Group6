<?php
include('session_management.php');
include('db_connection.php');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Course Deletion Notice</title>
    <link rel="stylesheet" href="styles.css"> <!-- Include your CSS if needed -->
</head>
<body>
    <div class="message-container">
        <?php
        // Display the message from the session
        if (isset($_SESSION['delete_message'])) {
            echo "<h2>" . $_SESSION['delete_message'] . "</h2>";
            // Clear the session message after displaying it
            unset($_SESSION['delete_message']);
        } else {
            echo "<h2>No message available.</h2>";
        }
        ?>
        
        <br>
        <a href="courses.php">Return to Courses List</a>
    </div>
</body>
</html>
<?php mysqli_close($conn); ?>
