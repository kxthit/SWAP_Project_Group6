<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Header Example</title>
  <link rel="stylesheet" href="style.css">
</head>

<body>

  <!-- Header -->
  <header>
    <div class="logo"></div>
    <div class="header-title">XYZ Polytechnic</div>
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
              case 1:
                echo 'Admin';
                break;
              case 2:
                echo 'Faculty';
                break;
              case 3:
                echo 'Student';
                break;
              default:
                echo 'Unknown Role';
                break;
            }
            ?>
          </p>
        </div>
      </div>
    </div>
  </header>

  <!-- Main Container -->
  <div class="container">
    <aside class="sidebar">
      <p>MAIN MENU</p>
      <ul>
        <li class="button"><a href="admin_dashboard.php">Dashboard</a></li>
        <li class="button"><a href="student.php">Students</a></li>
        <li class="button">Classes</li>
        <li class="button">Courses</li>
        <li class="button"><a href="grades.php">Grades</a></li>
      </ul>
      <p>SETTINGS</p>
      <ul>
        <li class="button">Profile</li>
        <li class="button">Settings</li>
        <li class="button"><a href="login.php">Logout</a></li>
      </ul>
    </aside>

    <!-- Main Content Area -->
    <div class="main-content">
      <!-- Content here -->
    </div>
  </div>

</body>

</html>