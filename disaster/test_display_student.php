<?php 
// Include the database connection
include 'db_connection.php';

session_start();

// Check if the user is authenticated
if (!isset($_SESSION['session_userid']) || !isset($_SESSION['session_role'])) {
    echo "<h2>Unauthorized access. Please log in.</h2>";
    header('Refresh: 3; URL=login.php');
    exit;
}

// Validate student_id from URL
if (!isset($_GET['student_id']) || !is_numeric($_GET['student_id'])) {
    echo "Invalid or missing student ID.";
    exit;
}

$student_id = $_GET['student_id'];

$query = "
    SELECT 
        s.student_id,
        s.student_name,
        s.student_email,
        s.student_phone,
        u.admission_number,
        GROUP_CONCAT(c.class_name ORDER BY c.class_name SEPARATOR ', ') AS class_names,
        d.department_name,
        GROUP_CONCAT(co.course_name ORDER BY co.course_name SEPARATOR ', ') AS courses,
        GROUP_CONCAT(co.course_code ORDER BY co.course_name SEPARATOR ', ') AS course_codes,
        s.profile_picture
    FROM student s
    JOIN user u ON s.user_id = u.user_id
    JOIN student_class sc ON s.student_id = sc.student_id
    JOIN class c ON sc.class_id = c.class_id
    JOIN department d ON s.department_id = d.department_id
    JOIN student_course sc2 ON s.student_id = sc2.student_id
    JOIN course co ON sc2.course_id = co.course_id
    WHERE s.student_id = ?
    GROUP BY s.student_id
";



// Prepare and execute the query
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();
$student = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Student Details</title>
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <link rel="stylesheet" href="style.css">
  <style>

    /* Body Styling */
    body {
      font-family: 'Arial', sans-serif;
      background-color: #f8f9fc;
      color: #333;
    }

    /* Back Button */
    .back-button {
      position: absolute;
      left: 2%;
      top: 2%;
      padding: 8px 15px;
      background-color: #e0e0e0;
      color: black;
      text-decoration: none;
      border-radius: 4px;
      transition: background-color 0.2s ease;
      margin-left: 210px;
      margin-top: 80px;
    }

    .back-button:hover {
      background-color: #555;
      color: white;
    }

    /* Student Details Container */
    .student-details-container {
      display: flex;
      flex-direction: column;
      gap: 20px;
      background-color: white;
      border: 1px solid #ddd;
      border-radius: 12px;
      box-shadow: 0px 2px 5px rgba(0, 0, 0, 0.1);
      padding: 20px;
      max-width: 900px;
      margin: 60px 30px auto;
      position: relative;
    }

    /* Header Section */
    .header-container {
      position: relative;
      background: #6495ed; /* Blue */
      height: 150px;
      border-radius: 8px;
      box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
      padding: 10px;
      width: 920px;
      margin-left: -20px;
      margin-top: -20px;
    }


    /* Profile Picture */
    .profile-picture-container {
      position: absolute;
      bottom: -60px;
      left: 20px;
      width: 150px;
      height: 150px;
      border: 4px solid white;
      border-radius: 50%;
      overflow: hidden;
      box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
    }

    .profile-picture-container img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }

    /* Content Below Header */
    .content {
      padding: 0px;
      text-align: left;
      margin-top: 50px;
    }

    .content h1 {
      margin-bottom: -20px; /* Reducing space between the name and admission number */
    }

    .content h4 {
      font-size: 18px; /* Optional: Adjust font size for better alignment */
    }


    /* Contact Section */
    .student-contact {
    display: flex;           /* Use flexbox */
    gap: 15px;               /* Optional: Add space between the items */
    font-size: 15px;
    color: #555;
    }

    .student-contact span {
    display: flex;           /* Allow proper alignment within the flex container */
    align-items: center;     /* Optional: Vertically center the icons with the text */
    }


    /* Action Buttons */
    .action-buttons {
      display: flex;
      justify-content: flex-start;
      gap: 15px;
      margin: 15px 0;
    }

    button {
      background-color: #007bff;
      color: white;
      border: none;
      padding: 8px 15px;
      border-radius: 5px;
      cursor: pointer;
      transition: background-color 0.2s ease;
    }

    button:hover {
      background-color: #0056b3;
    }

    /* Responsive Adjustments */
    @media (max-width: 768px) {
      .profile-picture-container {
        width: 60px;
        height: 60px;
      }

      .header-container {
        height: 120px;
      }
    }

    .student-department-container {
      display: flex;
      flex-direction: column;
      gap: 10px;
      background-color: white;
      border: 1px solid #ddd;
      border-radius: 12px;
      box-shadow: 0px 2px 5px rgba(0, 0, 0, 0.1);
      padding: 20px;
      max-width: 900px;
      margin: 30px auto;
      position: relative;
      font-weight: bold;
    }

    .student-department-container h2 {
      border-bottom: 1px solid #ddd; /* Add a border below the department title */
      padding-bottom: 20px; /* Optional: Adds some spacing between the title and the border */
      margin-bottom: 10px; /* Reduce the bottom margin of the heading */
    }

    .student-department-container p {
      margin-top: 0; /* Remove the top margin from the paragraph */
    }


    .student-course-container {
      display: flex;
      flex-direction: column;
      gap: 10px;
      background-color: white;
      border: 1px solid #ddd;
      border-radius: 12px;
      box-shadow: 0px 2px 5px rgba(0, 0, 0, 0.1);
      padding: 20px;
      max-width: 900px;
      margin: 30px auto;
      position: relative;
    }

    .student-course-container h2 {
      margin-bottom: 10px; /* Reduce the bottom margin of the heading */
    }

    /* Course Item Styling */
    .course-item {
        border-top: 1px solid #ddd;
        margin-bottom: 15px;
        padding-top: 10px;
    }

    /* Course Title Styling */
    .course-title {
        display: flex;
        justify-content: space-between;
        align-items: center;
        cursor: pointer;
        padding: 5px 0;
        font-weight: bold;
    }

    /* Toggle Arrow */
    .toggle-arrow {
        cursor: pointer;
        font-size: 14px;
    }

    /* Course Content (Initially Hidden) */
    .course-content {
        display: none;
        padding-left: 20px;
        border-top: 1px solid #ddd;
        padding-top: 20px;
    }

    /* Active Course Content */
    .course-title.active + .course-content {
        display: block;
    }

  </style>
</head>

<body>

  <?php include('admin_header.php'); ?>

    <!-- Main Content -->
  <main class="main-content">
    <!-- Back Button -->
    <a href="student.php" class="back-button">← Back</a>

    <!-- Student Details Section -->
    <div class="student-details-container">
      <!-- Header Section with Background & Profile -->
      <div class="header-container">
        <!-- Profile Picture -->
        <div class="profile-picture-container">
          <img src="<?php echo htmlspecialchars($student['profile_picture']); ?>" alt="Profile Picture">
        </div>
      </div>

      <!-- Main Content -->
      <div class="content">
        <h1><?php echo htmlspecialchars($student['student_name']); ?></h1>
        <h4><?php echo htmlspecialchars($student['admission_number']); ?></h4>
        

        <!-- Main Details Section -->
        <div class="student-contact">
          <span><i class="fa fa-phone"></i> <?php echo htmlspecialchars($student['student_phone']); ?></span>
          <span><i class="fa fa-envelope"></i> <?php echo htmlspecialchars($student['student_email']); ?></span>
        </div>
        <br>


        <!-- Action Buttons Section -->
        <div class="action-buttons">
          <button class="edit-btn" onclick="window.location.href='edit_studentform.php?student_id=<?php echo $student['student_id']; ?>'">Edit</button>
          <button class="delete-btn">Delete</button>
        </div>
      </div>
    </div>

    <div class="student-department-container">
      <h2>Department</h2>
      <p><?php echo htmlspecialchars($student['department_name']); ?></p>
    </div>

    <div class="student-course-container">
      <h2>Courses</h2>
      <?php 
        $courses = is_array($student['courses']) ? $student['courses'] : explode(',', $student['courses']);
        foreach ($courses as $index => $course) {
            $course_name = htmlspecialchars($course); // Sanitize the course name
            $course_code = isset($student['course_codes'][$index]) ? htmlspecialchars($student['course_codes'][$index]) : 'No Code Available';
            $class_names = isset($student['class_names'][$index]) ? htmlspecialchars($student['class_names'][$index]) : 'No Classes Available';

            echo '<div class="course-item">';
            echo '<div class="course-title">';
            echo '<span>' . $course_name . '</span>'; // Course name
            echo '<span style="font-size: small;">' . $course_code . '</span>'; // Course code
            echo '<span class="toggle-arrow">▼</span>';
            echo '</div>';
            echo '<div class="course-content" style="display: none;">';
            echo '<ul>';

            if ($class_names !== 'No Classes Available') {
                echo '<li>' . $class_names . '</li>'; // Display class names as is
            } else {
                echo '<li>' . $class_names . '</li>'; // Fallback if no class details
            }

            echo '</ul>';
            echo '</div>';
            echo '</div>';
        }
        ?>




    </div>
  </main>

  <script>
    $(document).ready(function() {
        $('.toggle-arrow').on('click', function() {
            var courseItem = $(this).closest('.course-item');
            courseItem.find('.course-content').slideToggle(); // Toggle content visibility
            $(this).text(courseItem.find('.course-content').is(':visible') ? '▲' : '▼'); // Toggle arrow direction
            courseItem.find('.course-title').toggleClass('active');
        });
    });

  </script>

  <!-- Load icons -->
  <<script src="https://kit.fontawesome.com/10051b25e7.js" crossorigin="anonymous"></script>
</body>

</html>
