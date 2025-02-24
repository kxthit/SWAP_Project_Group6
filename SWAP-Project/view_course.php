<?php
include('session_management.php');
include("db_connection.php");

// Check if the user is authenticated
if (!isset($_SESSION['session_userid']) || !isset($_SESSION['session_roleid'])) {
    echo "<h2>Unauthorized access. Please log in.</h2>";
    header('Refresh: 3; URL=login.php');
    exit;
}

// Check if the user is either Admin or Faculty (role id 1 or 2)
if ($_SESSION['session_roleid'] != 1 && $_SESSION['session_roleid'] != 2) {
    echo "<h2>You do not have permission to access this page.</h2>";
    exit;
}

// Skip fetching faculty_id for Admins (role id 1)
if ($_SESSION['session_roleid'] == 2 && !isset($_SESSION['session_facultyid'])) {
    // Only fetch faculty_id if the user is Faculty (role id 2)
    $user_id = $_SESSION['session_userid'];

    // Query to get faculty_id based on user_id
    $facultyQuery = $conn->prepare("SELECT faculty_id FROM faculty WHERE user_id = ?");
    $facultyQuery->bind_param("i", $user_id);
    $facultyQuery->execute();
    $facultyResult = $facultyQuery->get_result();

    if ($facultyResult->num_rows > 0) {
        $facultyData = $facultyResult->fetch_assoc();
        $_SESSION['session_facultyid'] = $facultyData['faculty_id']; // Store faculty_id in session
    } else {
        echo "<h2>Faculty not found. Invalid access.</h2>";
        exit;
    }
}

if ($_SESSION['session_roleid'] == 1) {
    // Admin doesn't need a faculty ID
    $faculty_id = null;
} else {
    // Non-admin users will have their faculty ID
    $faculty_id = $_SESSION['session_facultyid'];
}

// Check if the course_id is passed in the URL
if (isset($_GET['course_id'])) {
    // Validate that course_id is numeric
    if (!is_numeric($_GET['course_id'])) {
        echo "<h2>Invalid course ID.</h2>";
        exit;
    }

    // Store the course_id in the session
    $_SESSION['session_courseid'] = intval($_GET['course_id']); // Cast to int for safety

    // Redirect to the same page to prevent the course_id from showing up in the URL
    header("Location: view_course.php");
    exit(); // Stop further execution
}

// If course_id is not set in the session, show an error or redirect
if (!isset($_SESSION['session_courseid'])) {
    echo "<h2>Course not found. Invalid access.</h2>";
    exit;
}

$course_id = $_SESSION['session_courseid'];

// If the user is Admin (role_id = 1), they can view all courses
if ($_SESSION['session_roleid'] == 1) {
    // Admin: No need to check the faculty_course table
    $authRow['count'] = 1; // Allow access to any course (Admin has full access)
} else {
    // Verify if the faculty has access to the course (faculty role only)
    // Check if the course belongs to the faculty's department and if the faculty is assigned to that course
    $authQuery = $conn->prepare("
        SELECT COUNT(*) AS count 
        FROM faculty_course 
        JOIN course ON faculty_course.course_id = course.course_id
        WHERE faculty_course.faculty_id = ? AND faculty_course.course_id = ? AND course.department_id = ?
    ");
    $department_id = $_SESSION['session_facultydepartmentid']; // Faculty's department from session
    $authQuery->bind_param("iii", $faculty_id, $course_id, $department_id);
    $authQuery->execute();
    $authResult = $authQuery->get_result();
    $authRow = $authResult->fetch_assoc();
}

// Admins have unrestricted access
if ($_SESSION['session_roleid'] == 1) {
    $authRow['count'] = 1; // Allow access to any course for admins
} else {
    // Faculty-specific access check
    if ($authRow['count'] == 0) {
        // If no match, deny access
        echo "<h2>You do not have permission to view this course.</h2>";
        exit;
    }
}

// Prepare the query to fetch course details
$courseResult = $conn->prepare("
    SELECT course.course_id, course.course_name, course.course_code, course.start_date, course.end_date, course.status_id, course.department_id, status.status_name, department.department_name
    FROM course 
    JOIN `status` ON course.status_id = status.status_id
    JOIN `department` ON course.department_id = department.department_id 
    WHERE course.course_id = ?
");
$courseResult->bind_param("i", $course_id);
$courseResult->execute();
$courseData = $courseResult->get_result();
$courseDetails = $courseData->fetch_assoc();

// If no course details found, exit and display message
if (!$courseDetails) {
    echo "<h2>Course details not found.</h2>";
    exit;
}

// Now that we have valid course details, store status_id in session
$_SESSION['session_coursestatusid'] = $courseDetails['status_id'];  // Store status_id in session


// Now fetch the faculty name for the relevant course for both Admin and Faculty
$facultyQuery = $conn->prepare("
    SELECT faculty_name 
    FROM faculty 
    WHERE faculty_id = (SELECT faculty_id FROM faculty_course WHERE course_id = ? LIMIT 1)
");
$facultyQuery->bind_param("i", $course_id);
$facultyQuery->execute();
$facultyResult = $facultyQuery->get_result();
$facultyData = $facultyResult->fetch_assoc();

// Check if faculty data exists, but allow admins to proceed without an error
if (!$facultyData) {
    if ($_SESSION['session_roleid'] == 1) { // If the user is an Admin
        $facultyData['faculty_name'] = "Unassigned"; // Set faculty name to "Unassigned" for Admins
    } else {
        // For non-admin users, prevent access and show the error
        echo "<h2>Faculty not found for this course.</h2>";
        exit;
    }
}

// If the return button was clicked, clear session data and redirect
if (isset($_POST['return_button'])) {
    unset($_SESSION['session_coursestatusid']);  // Unset course status
    unset($_SESSION['session_courseid']);
    header("Location: courses.php");
    exit;
}
?>

<!DOCTYPE html>
<html>

<head>
    <link rel="stylesheet" href="css/view_course.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script>
        // Confirmation dialog for delete
        function confirmDelete() {
            return confirm("Are you sure you want to delete this course?");
        }
    </script>
</head>

<body>
    <?php include('admin_header.php'); ?>
    <main class="main-content">

        <h1 id="title"><?php echo htmlspecialchars($courseDetails['course_name']); ?></h1>

        <div class="main-container">
            <div class="course-details">
                <p><strong>Course Code:</strong> <?php echo htmlspecialchars($courseDetails['course_code']); ?></p>
                <p><strong>Department:</strong> <?php echo htmlspecialchars($courseDetails['department_name']); ?></p>
                <p><strong>Faculty Name:</strong> <?php echo htmlspecialchars($facultyData['faculty_name']); ?></p>
                <p><strong>Start Date:</strong> <?php echo htmlspecialchars($courseDetails['start_date']); ?></p>
                <p><strong>End Date:</strong> <?php echo htmlspecialchars($courseDetails['end_date']); ?></p>

                <!-- Status Box -->
                <div class="status-box 
            <?php
            // Map the status_id to specific classes for color changes
            switch ($_SESSION['session_coursestatusid']) {
                case 1:
                    echo 'Started'; // Green
                    break;
                case 2:
                    echo 'In-Progress'; // Darker Yellow
                    break;
                case 3:
                    echo 'Ended'; // Red
                    break;
                case 4:
                    echo 'Unassigned'; // Grey
                    break;
                default:
                    echo 'Unknown'; // Default class for unknown statuses
                    break;
            }
            ?>
        ">
                    <?php echo htmlspecialchars($courseDetails['status_name']); ?>
                </div>

                <!-- Button Container -->
                <div class="btn-container">
                    <!-- Edit Course button -->
                    <form method="POST">
                        <button type="submit" name="edit_course" class="add-course-btn">
                            <i class="fas fa-pencil-alt"></i> <!-- Edit icon -->
                        </button>
                    </form>

                    <!-- Back to Course List Form -->
                    <form method="post">
                        <button type="submit" name="return_button" class="add-course-btn">
                            <i class="fas fa-arrow-left"></i> <!-- Back icon -->
                        </button>
                    </form>

                    <!-- Delete Course Form -->
                    <form action="course_delete.php" method="post" onsubmit="return confirmDelete()">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                        <input type="hidden" name="course_id" value="<?php echo htmlspecialchars($_SESSION['session_courseid']); ?>">
                        <button type="submit" class="add-course-btn">
                            <i class="fas fa-trash-alt"></i> <!-- Delete icon -->
                        </button>
                    </form>
                </div>
                <?php
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    // Check if the "edit_course" button was pressed
                    if (isset($_POST['edit_course'])) {
                        // Ensure session_roleid is set and valid
                        if (isset($_SESSION['session_roleid'])) {
                            switch ($_SESSION['session_roleid']) {
                                case 1: // Admin
                                    // Admin redirects to the course admin update form
                                    header('Location: course_admin_update_form.php?course_id=' . urlencode($_SESSION['session_courseid']));
                                    exit;
                                case 2: // Faculty
                                    // Faculty redirects to the course update form
                                    header('Location: course_update_form.php?course_id=' . urlencode($_SESSION['session_courseid']));
                                    exit;
                                default:
                                    // Handle unexpected role case
                                    echo "Invalid role. Cannot redirect.";
                                    exit;
                            }
                        } else {
                            echo "Session role ID not set.";
                            exit;
                        }
                    }

                    // Handle the return button
                    if (isset($_POST['return_button'])) {
                        // Redirect to courses page
                        header('Location: courses.php');
                        exit;
                    }
                }
                ?>
            </div>
        </div>
    </main>
</body>

</html>
<?php mysqli_close($conn); ?>