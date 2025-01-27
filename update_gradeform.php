<?php
session_start();

// Check if the user is authenticated
if (!isset($_SESSION['session_userid']) || !isset($_SESSION['session_role'])) {
    echo "<script>
            alert('Unauthorized user. Redirecting To Login.');
            window.location.href = 'login.php'; // Redirect to homepage or any page you want
        </script>";
    exit;
}

// Check if the user is either Admin or Faculty (role_id 1 or 2)
if ($_SESSION['session_role'] != 1 && $_SESSION['session_role'] != 2) {
    echo "<script>
            alert('You Do Not Have Permission To Access This.');
            window.location.href = 'login.php'; // Redirect to login
        </script>";
    exit;
}

// Check if the grade ID is passed via URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo "<script>
            alert('No Grade Selected.');
            window.location.href = 'grades.php'; // Redirect to login
        </script>";
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
    <link href="https://fonts.googleapis.com/css2?family=Josefin+Sans:wght@300;400;700&family=Source+Sans+Pro:wght@300;400;700&display=swap" rel="stylesheet">
    <title>Update Grade</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .main-content {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 20px;
        }

        .grade-update-form {
            background: #c3d9e5;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            padding: 20px;
            width: 100%;
            max-width: 500px;
            text-align: center;
            transition: 0.3s ease;
            border: 2px solid #ecdfce;
        }

        .grade-update-form:hover {
            box-shadow: 0 0 15px 4px rgb(95, 142, 174);
            transform: scale(1.01);
        }

        .grade-update-form h2 {
            font-size: 22px;
            color: #112633;
        }

        .grade-update-form label,
        .grade-update-form p {
            font-size: 18px;
            color: #443E3A;
        }

        .grade-update-form select,
        .grade-update-form button {
            padding: 10px;
            border-radius: 5px;
            margin: 10px 0;
            width: 100%;
            font-size: 15px;
            font-family: 'Source Sans Pro', sans-serif;
            font-weight: 700;
            text-transform: uppercase;
        }

        .grade-update-form button {
            background-color: #3b667e;
            color: white;
            border: none;
            cursor: pointer;
        }

        .grade-update-form button a {
            text-decoration: none;
            color: white;
        }

        /* Hover Effect */
        .grade-update-form button:hover {
            color: #2b2d42;
            /* Text turns dark */
            background-color: #ecdfce;
            /* Background turns white */
            box-shadow: 0 0 15px 4px #3D5671;
            /* Glowing effect */
            outline: none;
        }
    </style>
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

    <?php include 'admin_header.php'; ?>

    <main class="main-content">
        <div class="grade-update-form">
            <h2>Update Grade for <?php echo htmlspecialchars($student_name); ?> - <?php echo htmlspecialchars($course_name); ?></h2>

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

                <p>GPA for selected grade: <span id="current-gpa"><?php echo htmlspecialchars($current_gpa_point); ?></span></p>

                <button type="submit">Update Grade</button>
            </form>

            <a href="display_grades.php?student_id=<?php echo $grade_record['student_id']; ?>">
                <button>Return</button>
            </a>
        </div>
    </main>

</body>

</html>

<?php
// Close database connection
mysqli_close($conn);
?>