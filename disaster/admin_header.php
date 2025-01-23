<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Header Example</title>
    <link rel="stylesheet" href="style.css"> <!-- Link to your CSS -->
</head>
<body>

    <!-- header.php -->
    <header>
      <div class="logo"></div>
      <div class="user-info">
        <!-- Profile Picture -->
        <div class="profile">

        <!-- User Details -->
          <div class="user-details">
            <p class="user-name"><?php echo htmlspecialchars($_SESSION['session_name'] ?? 'Guest'); ?></p>
            <p class="user-role">
              <?php 
                // Determine role based on session_role
                switch ($_SESSION['session_role'] ?? '') {
                  case 1: echo 'Admin'; break;
                  case 2: echo 'Faculty'; break;
                  case 3: echo 'Student'; break;
                  default: echo 'Unknown Role'; break;
                } 
              ?>
            </p>
          </div>
        </div> 
      </div>
    </header>



    <div class="container">
      <aside class="sidebar">
        <p>MAIN MENU</p>
        <ul>
          <li><a href="admin_dashboard.php">Dashboard</a></li>
          <li><a href="student.php">Students</a></li>
          <li>Classes</li>
          <li><a href="courses.php">Courses</a></li>
          <li>Grades</li>
        </ul>
        <p>SETTINGS</p>
        <ul>
          <li>Profile</li>
          <li>Setting</li>
          <li>Logout</li>
        </ul>
      </aside>
    
</body> 
</html>
