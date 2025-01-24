<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel</title>
    <link rel="stylesheet" href="style.css"> <!-- Link to your CSS -->
</head>
<body>
    <!-- Header Section -->
    <header>
        <div class="logo">
            <img src="path/to/logo.png" alt="Logo" style="height: 40px;"> <!-- Optional -->
        </div>
        <div class="user-info">
            <div class="profile">
                <div class="user-details">
                    <p class="user-name"><?php echo htmlspecialchars($_SESSION['session_name'] ?? 'Guest'); ?></p>
                    <p class="user-role">
                        <?php 
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

    <!-- Navigation Bar -->
    <nav class="navbar">
        <ul>
            <li><a href="faculty_dashboard.php">Dashboard</a></li>
            <li><a href="student.php">Students</a></li>
            <li><a href="faculty_classes.php">Classes</a></li>
            <li><a href="faculty_courses.php">Courses</a></li>
            <li><a href="faculty_grades.php">Grades</a></li>
        </ul>
    </nav>

    <div class="main-content">
        <!-- Main content goes here -->
    </div>
</body>
</html>
