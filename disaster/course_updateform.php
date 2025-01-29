<?php 
include('db_connection.php');
session_start();

// Check if the user is authenticated
if (!isset($_SESSION['session_userid']) || !isset($_SESSION['session_roleid'])) {
    echo "<h2>Unauthorized access. Please log in.</h2>";
    header('Refresh: 3; URL=login.php');
    exit;
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
    header("Location: course_updateform.php"); 
    exit(); // Stop further execution
}

// If the course_id is not passed in the URL, check if it's stored in the session
if (!isset($_SESSION['session_courseid'])) {
    echo "<h2>Course not found. Invalid access.</h2>";
    exit;
}

// If the user is Faculty (role_id 2), check if they are authorized to edit the course
if ($_SESSION['session_roleid'] == 2) {
    if (!isset($_SESSION['session_facultyid'])) {
        $user_id = $_SESSION['session_userid'];

        // Query to get faculty_id and department_id
        $facultyQuery = $conn->prepare("SELECT faculty_id, department_id FROM faculty WHERE user_id = ?");
        $facultyQuery->bind_param("i", $user_id);
        $facultyQuery->execute();
        $facultyResult = $facultyQuery->get_result();

        if ($facultyResult->num_rows > 0) {
            $facultyData = $facultyResult->fetch_assoc();
            $_SESSION['session_facultyid'] = $facultyData['faculty_id'];
            $_SESSION['session_facultydepartmentid'] = $facultyData['department_id'];  // Store department ID
        } else {
            echo "<h2>Faculty not found. Invalid access.</h2>";
            exit;
        }
    }

    // Get the faculty ID
    $faculty_id = $_SESSION['session_facultyid'];
    $course_id = $_SESSION['session_courseid']; // Use the session value
    $department_id = $_SESSION['session_facultydepartmentid'];

    // Check if the course belongs to this faculty (and their department)
    $authQuery = $conn->prepare("SELECT COUNT(*) AS count 
        FROM faculty_course 
        JOIN course ON faculty_course.course_id = course.course_id
        WHERE faculty_course.faculty_id = ? AND faculty_course.course_id = ? AND course.department_id = ?");
    $authQuery->bind_param("iii", $faculty_id, $course_id, $department_id);
    $authQuery->execute();
    $authResult = $authQuery->get_result();
    $authRow = $authResult->fetch_assoc();

    // If the faculty is not authorized to view the course, deny access
    if ($authRow['count'] == 0) {
        echo "<h2>You do not have permission to view this course.</h2>";
        exit;
    }
} else {
    // Admins have unrestricted access
    if ($_SESSION['session_roleid'] == 1) {
        // Admin: No need to check faculty_course table, they have access to all courses
        if (isset($_GET['course_id']) && is_numeric($_GET['course_id'])) {
            $_SESSION['session_courseid'] = intval($_GET['course_id']);
        }
    }
}

$edit_courseid = $_SESSION['session_courseid'];  // Use the course_id from the session

// Fetch course details from the database
$stmt = $conn->prepare("SELECT * FROM course WHERE course_id=?");
$stmt->bind_param('i', $edit_courseid);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "<h2>No course found with the provided ID.</h2>";
    exit;
}

$row = $result->fetch_assoc();

// Fetch status and department options
$status_query = "SELECT * FROM status";
$status_result = mysqli_query($conn, $status_query);

$department_query = "SELECT * FROM department";
$department_result = mysqli_query($conn, $department_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Update Course Details</title>
    <link rel="stylesheet" href="css/course_updateform.css">
    <script>
        // Function to handle form submission
        function handleFormSubmit(event) {
            // Check if any input field is modified
            var hasChanges = false;

            // Compare current field values with original ones (from PHP)
            var originalValues = {
                coursename: "<?php echo htmlspecialchars($row['course_name']); ?>",
                coursecode: "<?php echo htmlspecialchars($row['course_code']); ?>",
                startdate: "<?php echo htmlspecialchars($row['start_date']); ?>",
                enddate: "<?php echo htmlspecialchars($row['end_date']); ?>",
                statusid: "<?php echo htmlspecialchars($row['status_id']); ?>",
                departmentid: "<?php echo htmlspecialchars($row['department_id']); ?>"
            };

            // Check if the values have been changed
            if (document.getElementById('upd_coursename').value !== originalValues.coursename ||
                document.getElementById('upd_coursecode').value !== originalValues.coursecode ||
                document.getElementById('upd_startdate').value !== originalValues.startdate ||
                document.getElementById('upd_enddate').value !== originalValues.enddate ||
                document.getElementById('upd_statusid').value !== originalValues.statusid ||
                document.getElementById('upd_departmentid').value !== originalValues.departmentid) {
                    hasChanges = true;
            }

            // If no changes, show the confirmation alert
            if (!hasChanges) {
                var confirmUpdate = confirm('No details have been updated. Would you like to make changes or continue anyway?');
                if (!confirmUpdate) {
                    // Prevent form submission if user doesn't want to proceed
                    event.preventDefault();
                    return false; // Stops form submission
                }
            }
            
            // Proceed with form submission if there are changes or user confirms
            return true;
        }

            function unsetSessionAndReturn() {
            // Unset the session data for course_data
            <?php 
                unset($_SESSION['course_data']); // Unset the session data in PHP
            ?>
            
            // Redirect to the view_course.php page
            window.location.href = 'view_course.php';
        }
    </script>
</head>
<body>
<?php include("admin_header.php");?>
    <h1 class="title">Update Course Details</h1>
    <div class="form-container">
    <!-- Display Error Message -->
    <?php if (isset($_SESSION['error_message'])): ?>
        <p style="color: red;">
            
    <!-- If the error message is an array, join the elements into a single string -->
    <?php 
    echo is_array($_SESSION['error_message']) ? htmlspecialchars(implode(", ", $_SESSION['error_message'])) : htmlspecialchars($_SESSION['error_message']);
    ?>
        </p>
        <?php unset($_SESSION['error_message']); ?> <!-- Clear after displaying -->
    <?php endif; ?>

        <form action="course_update.php" method="POST" onsubmit="return handleFormSubmit(event);">
            <label for="upd_coursename">Course Name:</label>
            <input type="text" id="upd_coursename" name="upd_coursename" class="form-input"
                value="<?php echo isset($_SESSION['course_data']['upd_coursename']) ? htmlspecialchars($_SESSION['course_data']['upd_coursename']) : htmlspecialchars($row['course_name']); ?>" required>

            <label for="upd_coursecode">Course Code:</label>
            <input type="text" id="upd_coursecode" name="upd_coursecode" class="form-input"
                value="<?php echo isset($_SESSION['course_data']['upd_coursecode']) ? htmlspecialchars($_SESSION['course_data']['upd_coursecode']) : htmlspecialchars($row['course_code']); ?>" required>

            <label for="upd_startdate">Start Date:</label>
            <input type="date" id="upd_startdate" name="upd_startdate" class="form-input"
                value="<?php echo isset($_SESSION['course_data']['upd_startdate']) ? htmlspecialchars($_SESSION['course_data']['upd_startdate']) : htmlspecialchars($row['start_date']); ?>" required>

            <label for="upd_enddate">End Date:</label>
            <input type="date" id="upd_enddate" name="upd_enddate" class="form-input"
                value="<?php echo isset($_SESSION['course_data']['upd_enddate']) ? htmlspecialchars($_SESSION['course_data']['upd_enddate']) : htmlspecialchars($row['end_date']); ?>" required>

            <label for="upd_statusid">Status:</label>
            <select id="upd_statusid" name="upd_statusid" class="form-select" required>
                <option value="" disabled>--- Select Status ---</option>
                <?php 
                    while ($status = mysqli_fetch_assoc($status_result)): 
                        $selected = (isset($_SESSION['course_data']['upd_statusid']) && $_SESSION['course_data']['upd_statusid'] == $status['status_id']) || $status['status_id'] == $row['status_id'] ? 'selected' : '';
                ?>
                    <option value="<?php echo $status['status_id']; ?>" <?php echo $selected; ?>>
                        <?php echo htmlspecialchars($status['status_name']); ?>
                    </option>
                <?php endwhile; ?>
            </select>

            <label for="upd_departmentid">Department:</label>
            <select id="upd_departmentid" name="upd_departmentid" class="form-select" required>
                <option value="" disabled>--- Select Department ---</option>
                <?php 
                    while ($department = mysqli_fetch_assoc($department_result)): 
                        $selected = (isset($_SESSION['course_data']['upd_departmentid']) && $_SESSION['course_data']['upd_departmentid'] == $department['department_id']) || $department['department_id'] == $row['department_id'] ? 'selected' : '';
                ?>
                    <option value="<?php echo $department['department_id']; ?>" <?php echo $selected; ?>>
                        <?php echo htmlspecialchars($department['department_name']); ?>
                    </option>
                <?php endwhile; ?>
            </select>

            <!-- Button Container for Proper Alignment -->
            <div class="button-container">
            <button type="button" class="return-button" onclick="unsetSessionAndReturn();">Return</button>
            <button type="submit" class="form-button">Update Course</button>
            </div>
        </form>
    </div>
</body>
</html>
