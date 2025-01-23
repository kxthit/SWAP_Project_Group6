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
 
// Check if the user is either Admin or Faculty (role_id 1 or 2)
if ($_SESSION['session_role'] != 1 && $_SESSION['session_role'] != 2) {
    echo "<h2>You do not have permission to access this page.</h2>";
    exit;
}

$con = mysqli_connect("localhost","root","","xyzpoly"); //connect to database
if (!$con){
	die('Could not connect: ' . mysqli_connect_errno()); //return error is connect fail
}

// Prepare the statement
$stmt= $con->prepare("INSERT INTO `course` (`course_name`,`course_code`, `start_date`, `end_date`, `status_id`,`department_id`) VALUES (?,?,?,?,?,?)");

// Input incoming from form are sanitized
$course_name = htmlspecialchars($_POST["course_name"]);
$course_code = htmlspecialchars($_POST["course_code"]);
$start_date = htmlspecialchars($_POST["start_date"]);
$end_date = htmlspecialchars($_POST["end_date"]);
$status_id = htmlspecialchars($_POST["status_id"]);
$department_id = htmlspecialchars($_POST["department_id"]);

//bind the parameters
$stmt->bind_param('ssssii', $course_name,$course_code, $start_date, $end_date, $status_id,$department_id); 

//execute query
if ($stmt->execute()){  
    print "Insert Query executed.";
    header("Location: what.php");
}else{
  echo "Error executing INSERT query.";
}

// Close SQL Connection
$con->close();
?>
</body>
</html>
