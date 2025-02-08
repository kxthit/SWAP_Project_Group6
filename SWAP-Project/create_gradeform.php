<?php

// Include necessary files for session management and database connection
include 'csrf_protection.php';
include 'db_connection.php';

// Function to redirect with a message and a custom URL
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

// Validate student ID passed in the URL
if (!isset($_GET['student_id']) || empty($_GET['student_id'])) {
    redirect('No Student Selected.', 'grades.php'); // Redirect if student ID is missing
}

// Sanitize and validate the student ID
$student_id = filter_var($_GET['student_id'], FILTER_VALIDATE_INT);
if ($student_id === false) {
    redirect('Invalid Student ID.', 'grades.php'); // Redirect if the student ID is invalid
}

// Get the role ID and user ID from the session
$role_id = $_SESSION['session_roleid'];
$user_id = $_SESSION['session_userid'];

// If the user is an Admin (role_id == 1), fetch student details without restrictions
if ($role_id == 1) {
    $query = "
        SELECT student_name, department_id 
        FROM student 
        WHERE student_id = ? 
    ";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $student_id);  // Bind the student ID

    // If the user is Faculty (role_id == 2)
} elseif ($role_id == 2) {
    // Use faculty_id directly from the session
    if (!isset($_SESSION['session_facultyid'])) {
        echo "<p style='text-align:center;'>Faculty information not found in the session.</p>";
        exit;
    }
    $faculty_id = $_SESSION['session_facultyid'];

    // Get all courses assigned to the faculty
    $courses_query = "
        SELECT fc.course_id
        FROM faculty_course fc
        WHERE fc.faculty_id = ?
    ";

    $stmt = $conn->prepare($courses_query);
    $stmt->bind_param('i', $faculty_id);
    $stmt->execute();
    $courses_result = $stmt->get_result();
    $faculty_courses = [];

    // Store all assigned courses in an array
    while ($course_row = $courses_result->fetch_assoc()) {
        $faculty_courses[] = $course_row['course_id'];
    }

    // If no courses are assigned, show an error
    if (empty($faculty_courses)) {
        echo "<p style='text-align:center;'>You are not assigned any courses.</p>";
    }

    // Fetch student details only for students enrolled in courses taught by the faculty
    $query = "
        SELECT student_name, department_id 
        FROM student 
        WHERE student_id = ? AND EXISTS (
            SELECT 1
            FROM student_course sc
            WHERE sc.student_id = student.student_id
            AND sc.course_id IN (" . implode(",", $faculty_courses) . ")
        )
    ";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $student_id);  // Bind the student ID
}

$stmt->execute();
$result = $stmt->get_result();

if ($role_id == 1) {
    // Admin can see all students, so no need to check for specific student enrollment
    if ($result->num_rows > 0) {
        // Fetch student details for admin
        $student = $result->fetch_assoc();
        $student_name = $student['student_name'];
        $department_id = $student['department_id'];
    } else {
        // If no students found, redirect with a message
        redirect('No Students Found', 'grades.php');
    }
} elseif ($role_id == 2) {
    // Faculty: Check if student is enrolled in the faculty's courses
    if ($result->num_rows > 0) {
        $student = $result->fetch_assoc();
        $student_name = $student['student_name'];
        $department_id = $student['department_id'];
    } else {
        // If student is not in the faculty's course, redirect with a message
        redirect('Student Is Not Enrolled In Your Courses', 'grades.php');
    }
}

$stmt->close(); // Close the prepared statement

// Fetch courses that the student is taking and have not been graded yet
$query = "
    SELECT c.course_id, c.course_name
    FROM course c
    JOIN student_course sc ON sc.course_id = c.course_id
    WHERE sc.student_id = ? AND c.department_id = ? 
    AND c.course_id NOT IN (
        SELECT scg.course_id 
        FROM student_course_grade scg
        WHERE scg.student_id = ? 
    )
";

// Check if the student has no courses assigned
if ($result->num_rows == 0) {
    redirect('Student has no courses assigned.', 'grades.php');  // Redirect with an error message
}

// If the user is faculty, restrict the query to their assigned courses
if ($role_id == 2) {
    $query .= " AND c.course_id IN (" . implode(",", $faculty_courses) . ")"; // Only courses assigned to faculty
}

$stmt = $conn->prepare($query);
$stmt->bind_param('iii', $student_id, $department_id, $student_id);  // Bind parameters for query
$stmt->execute();
$result = $stmt->get_result();
$stmt->close();

// END of PHP script
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Josefin+Sans:wght@300;400;700&family=Source+Sans+Pro:wght@300;400;700&display=swap" rel="stylesheet">
    <title>Create Grade</title>
    <link rel="stylesheet" href="css/create_gradeform.css"> <!-- Link to the stylesheet -->
</head>

<body>

    <?php include 'admin_header.php'; ?>

    <main class="main-content">
        <div class="grade-create-form">
            <h2>Assign Grade to <?php echo htmlspecialchars($student_name); ?></h2>

            <!-- Grade Assignment Form -->
            <form action="create_grade.php" method="post">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>"> <!-- CSRF Protection Token to prevent Cross-Site Request Forgery -->
                <input type="hidden" name="student_id" value="<?php echo htmlspecialchars($student_id); ?>"> <!-- Hidden field for student ID -->
                <!-- Course Selection Dropdown -->
                <label for="course">Course:</label>
                <select name="course_id" id="course" required>

                    <?php
                    // Display courses available for grade assignment
                    if ($result->num_rows > 0) {
                        while ($course = $result->fetch_assoc()) {
                            echo "<option value='" . htmlspecialchars($course['course_id']) . "'>" . htmlspecialchars($course['course_name']) . "</option>";
                        }
                    } else {
                        echo "<option value=''>No available courses to assign grade</option>";  // Display when no courses are available
                    }
                    ?>
                </select><br><br>

                <!-- Method Selection Dropdown: Grade or Percentage -->
                <label for="method">Choose Assignment Method:</label>
                <select name="method" id="method" required onchange="toggleInput()">
                    <option value="grade">Grade</option>
                    <option value="percentage">Percentage</option>
                </select><br><br>

                <!-- Grade Method Section: Displays Grade Dropdown -->
                <div id="gradeMethod">
                    <label for="grade">Select Grade:</label>
                    <select name="grade_id" id="grade">
                        <?php
                        // Fetch and display grade options from the database
                        $query = "SELECT grade_id, grade_letter FROM grade";
                        $stmt = $conn->prepare($query);
                        $stmt->execute();
                        $result = $stmt->get_result();

                        // Loop through grades and display them in the dropdown
                        while ($grade = $result->fetch_assoc()) {
                            echo "<option value='" . htmlspecialchars($grade['grade_id']) . "'>" . htmlspecialchars($grade['grade_letter']) . "</option>";
                        }
                        ?>
                    </select><br><br>
                </div>

                <!-- Percentage Method Section: Displays Percentage Input -->
                <div id="percentageMethod" style="display: none;">
                    <label for="score">Enter Percentage (0-100):</label>
                    <input type="number" name="score" id="score" min="0" max="100">
                </div><br>

                <!-- Submit Button -->
                <button type="submit">Submit</button>

            </form>

            <!-- Return Button: Navigate to the grades page -->
            <a href="display_grades.php?student_id=<?php echo urlencode($student_id); ?>">
                <button>Return</button>
            </a>
        </div>
    </main>

    <script>
        // Function to toggle between the Grade or Percentage input fields based on selected method
        function toggleInput() {
            var method = document.getElementById('method').value;
            document.getElementById('gradeMethod').style.display = method === 'grade' ? 'block' : 'none'; // Show/hide grade input
            document.getElementById('percentageMethod').style.display = method === 'percentage' ? 'block' : 'none'; // Show/hide percentage input
        }
    </script>

</body>

</html>

<?php
// Close the connection
mysqli_close($conn);
?>