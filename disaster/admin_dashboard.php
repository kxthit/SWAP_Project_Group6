<?php

// Include the database connection
include 'db_connection.php';

session_start();

// Check if the user is logged in
if (!isset($_SESSION['session_role'])) {
  // If no session role is set, redirect to login page
  header("Location: login.php");
  exit;
}

// Check if the user has admin privileges
if ($_SESSION['session_role'] != 1) { // 1 = Admin
  // If the user is not an admin, show an unauthorized error or redirect
  header("Location: unauthorized.php"); // Redirect to an unauthorized page
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
    <?php include('admin_header.php'); ?>
    <!-- Main Content -->
    <main class="main-content">
      
    </main>
  </div>
</body>
</html>
