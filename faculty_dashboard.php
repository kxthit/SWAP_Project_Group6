
<?php
// Start the session to access session variables
session_start();

// Check if the user is logged in by checking the session variable
if (isset($_SESSION['session_name'])) {
    // Display the user's name
    echo "<h1>Welcome, " . htmlspecialchars($_SESSION['session_name']) . "</h1>";
} else {
    // If not logged in, redirect to the login page
    header("Location: login.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard</title>
  <link rel="stylesheet" href="style.css">
  <style>
  </style>

</head>
<body>

    <!-- index.php -->
    <?php include('faculty_header.php'); ?>
    <!-- Main Content -->
    <main class="main-content">
      
    </main>
  </div>
</body>
</html>
