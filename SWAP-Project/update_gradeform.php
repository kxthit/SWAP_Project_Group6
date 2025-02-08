<?php

// Include necessary files for session management and database connection
include 'csrf_protection.php';
include 'db_connection.php';

// Function to redirect with alert and a custom URL
function redirect($alert, $redirect)
{
    echo "<script>
            alert('$alert'); // Alert message
            window.location.href = '$redirect'; // Redirect to the given URL
        </script>";
    exit;
}

// Check if the user is authenticated , if not authenticated redirect to login page.
if (!isset($_SESSION['session_userid']) || !isset($_SESSION['session_roleid'])) {
    redirect('Unauthorized user. Redirecting To Login.', 'logout.php');
}

// Check if the user has a valid role (Admin or Faculty) , if not authenticated redirect to login page.
if ($_SESSION['session_roleid'] != 1 && $_SESSION['session_roleid'] != 2) {
    redirect('You Do Not Have Permission To Access This.', 'logout.php');
}

// Ensure student course grade ID is passed via URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    redirect('No Grade Selected.', 'grades.php');
}

// Get the role ID and user ID from the session
$role_id = $_SESSION['session_roleid'];
$user_id = $_SESSION['session_userid'];

// Validate and sanitize the student course grade ID from the URL
$student_course_grade_id  = filter_var($_GET['id'], FILTER_VALIDATE_INT);
if ($student_course_grade_id === false) {
    redirect('Invalid SCG ID.', 'grades.php');
}

// Fetch the grade record based on the ID passed in the URL
$query = "
    SELECT scg.student_id, scg.course_id, scg.grade_id, s.student_name, c.course_name, g.grade_letter, g.gpa_point
    FROM student_course_grade scg 
    JOIN student s ON scg.student_id = s.student_id
    JOIN course c ON scg.course_id = c.course_id
    JOIN grade g ON scg.grade_id = g.grade_id
    WHERE scg.student_course_grade_id = ?
";

$stmt = $conn->prepare($query);
$stmt->bind_param('i', $student_course_grade_id); // Bind the student_course_grade_id parameter to the query (expected to be an integer)
$stmt->execute();
$result = $stmt->get_result();

// Check if a record was found
if ($result->num_rows > 0) {
    $grade_record = $result->fetch_assoc();
    // Assign values from the fetched record to variables
    $student_name = $grade_record['student_name'];
    $course_name = $grade_record['course_name'];
    $current_grade_id = $grade_record['grade_id'];
    $current_grade_letter = $grade_record['grade_letter'];
    $current_gpa_point = $grade_record['gpa_point'];
    $course_id = $grade_record['course_id'];
    $student_id = $grade_record['student_id'];
} else {
    // If no records found, redirect with a message
    redirect('Grade Record Not Found.', 'grades.php');
}

$stmt->close();

// If the user is Faculty, ensure they are associated with the course
if ($role_id == 2) {
    $faculty_id = $_SESSION['session_facultyid']; // Get faculty ID from session

    // Check if the logged-in faculty is associated with the course they are trying to edit
    $faculty_course_check = "
        SELECT 1 FROM faculty_course fc 
        WHERE fc.faculty_id = ? AND fc.course_id = ?
    ";

    $stmt = $conn->prepare($faculty_course_check);
    $stmt->bind_param('ii', $faculty_id, $course_id);  // Bind faculty and course ID to the query
    $stmt->execute();
    $stmt->store_result();

    // If no association is found, redirect with an error message
    if ($stmt->num_rows == 0) {
        redirect('You are not authorized to edit this grade.', 'grades.php');
    }

    $stmt->close();
}

// Fetch all available grades for the dropdown
$query = "SELECT grade_id, grade_letter, gpa_point FROM grade";
$stmt = $conn->prepare($query);
$stmt->execute();
$grades_result = $stmt->get_result();
$stmt->close();

// END of PHP script
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Josefin+Sans:wght@300;400;700&family=Source+Sans+Pro:wght@300;400;700&display=swap" rel="stylesheet">
    <title>Update Grade</title>
    <link rel="stylesheet" href="css/update_gradeform.css">
</head>

<body>

    <?php include 'admin_header.php'; ?>

    <main class="main-content">
        <div class="grade-update-form">
            <h2>Update Grade for <?php echo htmlspecialchars($student_name); ?> - <?php echo htmlspecialchars($course_name); ?></h2>

            <!-- Grade Update Form -->
            <form action="update_grade.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>"> <!-- CSRF Protection Token to prevent Cross-Site Request Forgery -->
                <!-- Hidden inputs to carry important data between pages -->
                <input type="hidden" name="student_course_grade_id" value="<?php echo htmlspecialchars($student_course_grade_id); ?>">
                <input type="hidden" name="student_id" value="<?php echo htmlspecialchars($grade_record['student_id']); ?>">
                <input type="hidden" name="course_id" value="<?php echo htmlspecialchars($grade_record['course_id']); ?>">

                <!-- Dropdown for selecting the new grade -->
                <label for="grade">New Grade:</label>
                <select name="grade_id" id="grade" onchange="updateGPA()" required>
                    <?php
                    // Loop through all available grades to populate the dropdown
                    while ($grade = $grades_result->fetch_assoc()) {
                        // Mark the current grade as selected
                        $selected = ($grade['grade_id'] == $current_grade_id) ? 'selected' : '';
                        // Disable the option if it matches the current grade (prevents accidental change)
                        $disabled = ($grade['grade_id'] == $current_grade_id) ? 'disabled' : '';
                        // Display grade options with GPA value stored in data attribute
                        echo "<option value='" . $grade['grade_id'] . "' $selected $disabled data-gpa='" . htmlspecialchars($grade['gpa_point']) . "'>" . htmlspecialchars($grade['grade_letter']) . "</option>";
                    }
                    ?>
                </select><br><br>

                <!-- Display the GPA for the currently selected grade -->
                <p>GPA for selected grade: <span id="current-gpa"><?php echo htmlspecialchars($current_gpa_point); ?></span></p>

                <!-- Submit button to update grade -->
                <button type="submit">Update Grade</button>
            </form>

            <!-- Link to return to the grade display page for the student -->
            <a href="display_grades.php?student_id=<?php echo urlencode($grade_record['student_id']); ?>">
                <button>Return</button>
            </a>
        </div>
    </main>

    <script>
        // JavaScript function to update GPA dynamically based on selected grade
        function updateGPA() {
            var gradeSelect = document.getElementById("grade"); // Get the grade dropdown
            var selectedGradeID = gradeSelect.value; // Get the selected grade ID
            var gpaSpan = document.getElementById("current-gpa"); // Get the GPA display span

            // Get all grade options from the dropdown
            var gradeOptions = gradeSelect.options;
            for (var i = 0; i < gradeOptions.length; i++) {
                var option = gradeOptions[i];
                if (option.value == selectedGradeID) { // Find the option with the selected grade ID
                    var gpaValue = option.getAttribute("data-gpa"); // Get the GPA value from the "data-gpa" attribute
                    gpaSpan.textContent = gpaValue; // Update the GPA display with the selected value
                    break;
                }
            }
        }
    </script>

</body>

</html>

<?php
// Close the connection
mysqli_close($conn);
?>