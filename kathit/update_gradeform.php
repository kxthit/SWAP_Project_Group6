<?php
session_start();

// Check if the user is authenticated
if (!isset($_SESSION['session_userid']) || !isset($_SESSION['session_role'])) {
    echo "<h2>Unauthorized access. Please log in.</h2>";
    header('Refresh: 3; URL=login.php');
    exit;
}

// Check if the user is either Admin or Faculty (role_id 1 or 2)
if ($_SESSION['session_role'] != 1 && $_SESSION['session_role'] != 2) {
    echo "<h2>You do not have permission to access this page.</h2>";
    exit;
}

// Check if the grade ID is passed via URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo "<p>No grade selected.</p>";
    exit;
}

$student_course_grade_id = (int)$_GET['id']; // Safely cast to integer

// Include database connection
include 'db_connection.php';

// Fetch the grade record based on the ID passed
$query = "
    SELECT scg.student_id, scg.course_id, scg.grade_id, s.student_name, c.course_name, g.grade_letter, g.gpa_point
    FROM student_course_grade scg
    JOIN student s ON scg.student_id = s.student_id
    JOIN course c ON scg.course_id = c.course_id
    JOIN grade g ON scg.grade_id = g.grade_id
    WHERE scg.student_course_grade_id = ?
";

$stmt = $conn->prepare($query);
$stmt->bind_param('i', $student_course_grade_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $grade_record = $result->fetch_assoc();
    $student_name = $grade_record['student_name'];
    $course_name = $grade_record['course_name'];
    $current_grade_id = $grade_record['grade_id'];
    $current_grade_letter = $grade_record['grade_letter'];
    $current_gpa_point = $grade_record['gpa_point'];
} else {
    echo "<p>Grade record not found.</p>";
    exit;
}

$stmt->close();

// Fetch all available grades
$query = "SELECT grade_id, grade_letter, gpa_point FROM grade";
$stmt = $conn->prepare($query);
$stmt->execute();
$grades_result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Grade</title>
    <link rel="stylesheet" href="style.css">
    <script>
        // JavaScript function to update GPA dynamically based on selected grade
        function updateGPA() {
            var gradeSelect = document.getElementById("grade");
            var selectedGradeID = gradeSelect.value;
            var gpaSpan = document.getElementById("current-gpa");

            // Get all grade options from the dropdown
            var gradeOptions = gradeSelect.options;
            for (var i = 0; i < gradeOptions.length; i++) {
                var option = gradeOptions[i];
                if (option.value == selectedGradeID) {
                    var gpaValue = option.getAttribute("data-gpa");
                    gpaSpan.textContent = gpaValue;
                    break;
                }
            }
        }
    </script>
</head>

<body>

    <h1>Update Grade for <?php echo htmlspecialchars($student_name); ?> - <?php echo htmlspecialchars($course_name); ?></h1>

    <!-- Grade Update Form -->
    <form action="update_grade.php" method="POST">
        <input type="hidden" name="student_course_grade_id" value="<?php echo $student_course_grade_id; ?>">
        <input type="hidden" name="student_id" value="<?php echo $grade_record['student_id']; ?>">
        <input type="hidden" name="course_id" value="<?php echo $grade_record['course_id']; ?>">

        <label for="grade">New Grade:</label>
        <select name="grade_id" id="grade" onchange="updateGPA()" required>
            <?php
            // Display all grades
            while ($grade = $grades_result->fetch_assoc()) {
                // Mark the current grade as selected
                $selected = ($grade['grade_id'] == $current_grade_id) ? 'selected' : '';
                // Disable the option if it matches the current grade
                $disabled = ($grade['grade_id'] == $current_grade_id) ? 'disabled' : '';
                echo "<option value='" . $grade['grade_id'] . "' $selected $disabled data-gpa='" . htmlspecialchars($grade['gpa_point']) . "'>" . htmlspecialchars($grade['grade_letter']) . "</option>";
            }
            ?>
        </select><br><br>

        <!-- Display current GPA next to the grade -->
        <p>GPA for selected grade: <span id="current-gpa"><?php echo htmlspecialchars($current_gpa_point); ?></span></p>

        <button type="submit">Update Grade</button>
    </form>

    <button><a href="display_grades.php?student_id=<?php echo $grade_record['student_id']; ?>">Return</a></button>

</body>

</html>

<?php
// Close database connection
mysqli_close($conn);
?>