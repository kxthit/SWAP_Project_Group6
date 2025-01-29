<?php

// Include the database connection
include 'db_connection.php';

session_start();

// Check if the user is logged in
if (!isset($_SESSION['session_roleid'])) {
    // If no session role is set, redirect to login page
    header("Location: login.php");
    exit;
}

// Check if the user has admin or faculty privileges
if ($_SESSION['session_roleid'] != 1 && $_SESSION['session_roleid'] != 2) {
    // If the user is not an admin or faculty, redirect to an unauthorized page or show an error
    if ($_SESSION['session_roleid'] == 3) {
        // Store an error message for students
        $_SESSION['error_message'] = "Students should not be on this page.";
    }
    header("Location: unauthorized.php"); // Redirect to an unauthorized page
    exit;
}

// If the user is an admin or faculty, continue loading the admin dashboard
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
    <?php include('admin_header.php'); ?>
    <!-- Main Content -->
    <main class="main-content">
      
    </main>
  </div>
</body>
</html>
