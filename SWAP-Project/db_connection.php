<?php
// db_connection.php
$host = 'localhost'; // Database host
$dbname = 'xyzpoly'; // Replace with your database name
$username = 'root'; // Replace with your database username
$password = ''; // Replace with your database password

// Create a connection using mysqli
$conn = mysqli_connect($host, $username, $password, $dbname);

// Check the connection
if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}
