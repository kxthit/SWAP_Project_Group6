<html>
<body>  
<?php

session_start();

// Check if the user is authenticated
if (!isset($_SESSION['session_userid']) || !isset($_SESSION['session_role'])) {
  echo "<h2>Unauthorized access. Please log in.</h2>";
  header('Refresh: 3; URL=login.php');
  exit;
}

// Check if the user has admin privileges
if ($_SESSION['session_role'] != 1) { // 1 = Admin
  // If the user is not an admin, show an unauthorized error or redirect
  header("Location: unauthorized.php"); // Redirect to an unauthorized page
  exit;
}


$con = mysqli_connect("localhost","root","","xyzpoly"); //connect to database
if (!$con){
	die('Could not connect: ' . mysqli_connect_errno()); //return error is connect fail
}

// Prepare the statement 
$stmt= $con->prepare("DELETE FROM course WHERE course_id=?");

// Sanitize the GET entry
$del_courseid = htmlspecialchars($_GET["course_id"]);


// Bind the parameters 
$stmt->bind_param('i', $del_courseid); 
if ($stmt->execute()){
 echo "Delete Query executed.";
 header("location:admin_course_display.php");

}else{
  echo "Error executing DELETE query.";
}
?>
</body>
</html>
