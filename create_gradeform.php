<?php
session_start();

// Authentication checks: Ensures the user is logged in and has appropriate permissions
if (!isset($_SESSION['session_userid']) || !isset($_SESSION['session_role'])) {
    echo "<script>
            alert('Unauthorized user. Redirecting To Login.');
            window.location.href = 'login.php'; // Redirect to login
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

// Validate student ID passed in the URL
if (!isset($_GET['student_id']) || empty($_GET['student_id'])) {
    echo "<script>
        alert('No Student Selected.');
        window.location.href = 'grades.php'; 
        </script>";
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
    <link href="https://fonts.googleapis.com/css2?family=Josefin+Sans:wght@300;400;700&family=Source+Sans+Pro:wght@300;400;700&display=swap" rel="stylesheet">
    <title>Create Grade</title>
    <link rel="stylesheet" href="style.css"> <!-- Link to the stylesheet -->
    <style>
        .main-content {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 20px;
        }

        .grade-create-form {
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

        .grade-create-form:hover {
            box-shadow: 0 0 15px 4px rgb(95, 142, 174);
            transform: scale(1.01);
        }

        .grade-create-form h2 {
            font-size: 22px;
            color: #112633;
        }

        .grade-create-form label,
        .grade-create-form p {
            font-size: 18px;
            color: #443E3A;
        }

        .grade-create-form select,
        .grade-create-form input,
        .grade-create-form button {
            padding: 10px;
            border-radius: 5px;
            margin: 10px 0;
            width: 100%;
            font-size: 15px;
            font-family: 'Source Sans Pro', sans-serif;
            font-weight: 700;
            text-transform: uppercase;
        }

        .grade-create-form button {
            background-color: #3b667e;
            color: white;
            border: none;
            cursor: pointer;
        }

        .grade-create-form button a {
            text-decoration: none;
            color: white;
        }


        /* Hover Effect */
        .grade-create-form button:hover {
            color: #2b2d42;
            /* Text turns dark */
            background-color: #ecdfce;
            /* Background turns white */
            box-shadow: 0 0 15px 4px #3D5671;
            /* Glowing effect */
            outline: none;
        }
    </style>
</head>

<body>

    <?php include 'admin_header.php'; ?> <!-- Include the admin header here -->

    <main class="main-content">
        <div class="grade-create-form">
            <h2>Assign Grade to <?php echo htmlspecialchars($student_name); ?></h2>

            <!-- Grade Assignment Form -->
            <form action="create_grade.php" method="POST">
                <input type="hidden" name="student_id" value="<?php echo $student_id; ?>"> <!-- Hidden field for student ID -->

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

                <label for="method">Choose Assignment Method:</label>
                <select name="method" id="method" required onchange="toggleInput()">
                    <option value="grade">Grade</option>
                    <option value="percentage">Percentage</option>
                </select><br><br>

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

                <div id="percentageMethod" style="display: none;">
                    <label for="score">Enter Percentage (0-100):</label>
                    <input type="number" name="score" id="score" min="0" max="100">
                </div><br>

                <button type="submit">Submit</button>
            </form>

            <a href="display_grades.php?student_id=<?php echo $student_id; ?>">
                <button>Return</button>
            </a>
        </div>
    </main>

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