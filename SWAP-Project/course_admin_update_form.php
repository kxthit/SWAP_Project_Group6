<?php
include('csrf_protection.php');
include('db_connection.php');

if ($_SESSION['session_roleid'] != 1) {
    echo "<h2>Access Denied. You do not have permission to access this page.</h2>";
    header("Location: courses.php"); // Redirect non-admin users
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
    header("Location: course_admin_update_form.php");
    exit(); // Stop further execution
}

// If the course_id is not passed in the URL, check if it's stored in the session
if (!isset($_SESSION['session_courseid'])) {
    echo "<h2>Course not found. Invalid access.</h2>";
    exit;
}

$edit_courseid = $_SESSION['session_courseid'];  // Use the course_id from the session

// Fetch course details from the database
$stmt = $conn->prepare("SELECT * FROM course WHERE course_id = ?");
$stmt->bind_param('i', $edit_courseid);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "<h2>No course found with the provided ID.</h2>";
    exit;
}

$row = $result->fetch_assoc();

// Query to get all status options using prepared statements
$statusQuery = $conn->prepare("SELECT * FROM status");
$statusQuery->execute();
$statusResult = $statusQuery->get_result();

// Fetch the department assigned to the course (with prepared statement)
$assigned_department_query = "SELECT department_id FROM course WHERE course_id = ?";
$assigned_department_stmt = $conn->prepare($assigned_department_query);
$assigned_department_stmt->bind_param('i', $edit_courseid);
$assigned_department_stmt->execute();
$assigned_department_result = $assigned_department_stmt->get_result();

// Fetch the department_id from the result
$assigned_department_row = $assigned_department_result->fetch_assoc();
$assigned_department_id = $assigned_department_row['department_id'];

// Admin: Admins can access all departments using a prepared statement
$department_query = "SELECT * FROM department";
$department_stmt = $conn->prepare($department_query);
$department_stmt->execute();
$department_result = $department_stmt->get_result();

// Fetch all faculty for the dropdown using a prepared statement
$faculty_query = "SELECT faculty.faculty_name, faculty.faculty_id FROM faculty";
$faculty_stmt = $conn->prepare($faculty_query);
$faculty_stmt->execute();
$faculty_result = $faculty_stmt->get_result();

// Fetch the faculty assigned to the course (with prepared statement)
$assigned_faculty_query = "SELECT faculty.faculty_name, faculty.faculty_id 
                           FROM faculty_course 
                           JOIN faculty ON faculty_course.faculty_id = faculty.faculty_id 
                           WHERE faculty_course.course_id = ?";
$assigned_faculty_stmt = $conn->prepare($assigned_faculty_query);
$assigned_faculty_stmt->bind_param('i', $edit_courseid);
$assigned_faculty_stmt->execute();
$assigned_faculty_result = $assigned_faculty_stmt->get_result();
$assigned_faculty_row = $assigned_faculty_result->fetch_assoc();
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
    </script>
</head>
<body>
<?php include('admin_header.php') ;?>
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

    <form action="course_admin_update.php" method="POST" onsubmit="return handleFormSubmit(event);">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">    
    <input type="hidden" name="course_id" value="<?php echo htmlspecialchars($edit_courseid); ?>">

        <label for="upd_coursename">Course Name:</label>
        <input type="text" id="upd_coursename" name="upd_coursename" class="form-input"
            value="<?php echo isset($_SESSION['course_data']['upd_coursename']) ? htmlspecialchars($_SESSION['course_data']['upd_coursename']) : htmlspecialchars($row['course_name']); ?>" >

        <label for="upd_coursecode">Course Code:</label>
        <input type="text" id="upd_coursecode" name="upd_coursecode" class="form-input"
            value="<?php echo isset($_SESSION['course_data']['upd_coursecode']) ? htmlspecialchars($_SESSION['course_data']['upd_coursecode']) : htmlspecialchars($row['course_code']); ?>" >

        <label for="upd_startdate">Start Date:</label>
        <input type="date" id="upd_startdate" name="upd_startdate" class="form-input"
            value="<?php echo isset($_SESSION['course_data']['upd_startdate']) ? htmlspecialchars($_SESSION['course_data']['upd_startdate']) : htmlspecialchars($row['start_date']); ?>" >

        <label for="upd_enddate">End Date:</label>
        <input type="date" id="upd_enddate" name="upd_enddate" class="form-input"
            value="<?php echo isset($_SESSION['course_data']['upd_enddate']) ? htmlspecialchars($_SESSION['course_data']['upd_enddate']) : htmlspecialchars($row['end_date']); ?>" >

        <label for="upd_statusid">Status:</label>
        <select id="upd_statusid" name="upd_statusid" class="form-select">
            <option value="" disabled>--- Select Status ---</option>
            <?php 
            while ($status = $statusResult->fetch_assoc()): 
                $selected = (isset($_SESSION['course_data']['upd_statusid']) && $_SESSION['course_data']['upd_statusid'] == $status['status_id']) || $status['status_id'] == $row['status_id'] ? 'selected' : '';
            ?>
                <option value="<?= htmlspecialchars($status['status_id'], ENT_QUOTES, 'UTF-8'); ?>" <?= $selected; ?>>
                    <?= htmlspecialchars($status['status_name'], ENT_QUOTES, 'UTF-8'); ?>
                </option>
            <?php endwhile; ?>
        </select>

        <label for="upd_departmentid">Department:</label>
        <select id="upd_departmentid" name="upd_departmentid" class="form-select" >
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

        <label for="upd_facultyid">Faculty:</label>
        <select id="upd_facultyid" name="upd_facultyid" class="form-select" >
            <option value="" disabled>--- Select Faculty ---</option>
            <?php 
            while ($faculty = $faculty_result->fetch_assoc()): 
                $selected = (isset($_SESSION['course_data']['upd_facultyid']) && $_SESSION['course_data']['upd_facultyid'] == $faculty['faculty_id']) || $faculty['faculty_id'] == $assigned_faculty_row['faculty_id'] ? 'selected' : '';
            ?>
                <option value="<?php echo $faculty['faculty_id']; ?>" <?php echo $selected; ?>>
                    <?php echo htmlspecialchars($faculty['faculty_name']); ?>
                </option>
            <?php endwhile; ?>
        </select>

        <!-- Button Container for Proper Alignment -->
        <div class="button-container">
            <button type="submit" name="return_button" class="return_button">Return</button>
            <button type="submit" class="form_button">Update Course</button>
        </div>
    </form>
    </div>
</body>
</html>
<?php mysqli_close($conn); ?>