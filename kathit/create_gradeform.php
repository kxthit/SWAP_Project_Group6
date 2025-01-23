<?php
session_start();

// Authentication checks: Ensures the user is logged in and has appropriate permissions
if (!isset($_SESSION['session_userid']) || !isset($_SESSION['session_role'])) {
    echo "<h2>Unauthorized access. Please log in.</h2>";
    header('Refresh: 3; URL=login.php');
    exit;
}

if ($_SESSION['session_role'] != 1 && $_SESSION['session_role'] != 2) {
    echo "<h2>You do not have permission to access this page.</h2>";
    exit;
}

// Validate student ID passed in the URL
if (!isset($_GET['student_id']) || empty($_GET['student_id'])) {
    echo "<p>No student selected.</p>";
    exit;
}

$student_id = (int)$_GET['student_id'];  // Convert student ID to integer

// Include the database connection
include 'db_connection.php';

// Fetch student details from the database
$query = "SELECT student_name, department_id FROM student WHERE student_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param('i', $student_id);  // Bind the student ID parameter
$stmt->execute();
$result = $stmt->get_result();

// If student exists, fetch their details
if ($result->num_rows > 0) {
    $student = $result->fetch_assoc();
    $student_name = $student['student_name'];
    $department_id = $student['department_id'];
} else {
    echo "<p>Student not found.</p>";
    exit;
}
$stmt->close();  // Close the prepared statement

// Fetch courses under the same department that the student is taking and have not been graded yet
$query = "
    SELECT c.course_id, c.course_name
    FROM course c
    JOIN student_course sc ON sc.course_id = c.course_id
    WHERE sc.student_id = ? AND c.department_id = ? 
    AND c.course_id NOT IN (
        SELECT scg.course_id 
        FROM student_course_grade scg
        WHERE scg.student_id = ? 
    )";
$stmt = $conn->prepare($query);
$stmt->bind_param('iii', $student_id, $department_id, $student_id);  // Bind parameters for query
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Grade</title>
    <link rel="stylesheet" href="style.css"> <!-- Link to the stylesheet -->
</head>

<body>

    <h1>Assign Grade to <?php echo htmlspecialchars($student_name); ?></h1> <!-- Display student's name -->

    <!-- Form for assigning grades to the student -->
    <form action="create_grade.php" method="POST">
        <input type="hidden" name="student_id" value="<?php echo $student_id; ?>"> <!-- Hidden field for student ID -->

        <!-- Course selection dropdown -->
        <label for="course">Course:</label>
        <select name="course_id" id="course" required>
            <?php
            // If there are available courses, display them in the dropdown
            if ($result->num_rows > 0) {
                while ($course = $result->fetch_assoc()) {
                    echo "<option value='" . $course['course_id'] . "'>" . htmlspecialchars($course['course_name']) . "</option>";
                }
            } else {
                echo "<option value=''>No available courses to assign grade</option>";  // No courses available message
            }

            $stmt->close();  // Close the prepared statement
            ?>
        </select><br><br>

        <!-- Method selection dropdown: Grade or Percentage -->
        <label for="method">Choose Assignment Method:</label>
        <select name="method" id="method" required onchange="toggleInput()">
            <option value="grade">Grade</option>
            <option value="percentage">Percentage</option>
        </select><br><br>

        <!-- Grade selection method (visible only if Grade is selected) -->
        <div id="gradeMethod">
            <label for="grade">Select Grade:</label>
            <select name="grade_id" id="grade">
                <?php
                // Fetch grade options from the database
                $query = "SELECT grade_id, grade_letter FROM grade";
                $stmt = $conn->prepare($query);
                $stmt->execute();
                $result = $stmt->get_result();

                // Display each grade option in the dropdown
                while ($grade = $result->fetch_assoc()) {
                    echo "<option value='" . $grade['grade_id'] . "'>" . htmlspecialchars($grade['grade_letter']) . "</option>";
                }
                ?>
            </select><br><br>
        </div>

        <!-- Percentage input method (visible only if Percentage is selected) -->
        <div id="percentageMethod" style="display: none;">
            <label for="score">Enter Percentage (0-100):</label>
            <input type="number" name="score" id="score" min="0" max="100">
        </div><br>

        <!-- Submit button to submit the form -->
        <button type="submit">Submit</button>
    </form>

    <!-- Button to return to the grades display page -->
    <button><a href="display_grades.php?student_id=<?php echo $student_id; ?>">Return</a></button>

    <script>
        // Function to toggle between the Grade or Percentage input fields based on selected method
        function toggleInput() {
            var method = document.getElementById('method').value;
            document.getElementById('gradeMethod').style.display = method === 'grade' ? 'block' : 'none';
            document.getElementById('percentageMethod').style.display = method === 'percentage' ? 'block' : 'none';
        }
    </script>

</body>

</html>

<?php
mysqli_close($conn);  // Close the database connection
?>