<?php

// Include necessary files for session management and database connection
include 'csrf_protection.php';
include 'db_connection.php';

// Function to redirect with an alert and a custom URL
function redirect($alert, $redirect)
{
    echo "<script>
            alert('$alert'); // Alert Message 
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

// Get the role ID and user ID from the session
$role_id = $_SESSION['session_roleid'];
$user_id = $_SESSION['session_userid'];

// If the user is Faculty, retrieve the faculty ID from the session
if ($role_id == 2) {
    $faculty_id = $_SESSION['session_facultyid'];
}

// Check for student ID passed in the URL
if (!isset($_GET['student_id']) || empty($_GET['student_id'])) {
    // If no student ID is provided, redirect to grades page
    redirect('No Student Selected.', 'grades.php');
}


// Sanitize and validate the student ID (only as an integer)
$student_id = filter_var($_GET['student_id'], FILTER_VALIDATE_INT);

// If the student ID is invalid, redirect to grades page
if ($student_id === false) {
    redirect('Invalid Student ID.', 'grades.php');
}

// If the user is Faculty, restrict the grades view to the courses they teach
if ($role_id == 2) {
    // Ensure that the faculty ID exists
    if (!$faculty_id) {
        echo "<p style='text-align:center;'>Faculty information not found.</p>";
        exit;
    }

    // Query to get all courses assigned to the faculty
    $courses_query = "
        SELECT fc.course_id
        FROM faculty_course fc
        WHERE fc.faculty_id = ?
    ";

    // Prepare and execute the query
    $stmt = $conn->prepare($courses_query);
    $stmt->bind_param('i', $faculty_id);
    $stmt->execute();
    $courses_result = $stmt->get_result();
    // Store the faculty's courses in an array
    $faculty_courses = [];
    while ($course_row = $courses_result->fetch_assoc()) {
        $faculty_courses[] = $course_row['course_id']; // Add course IDs to the array
    }
    $stmt->close();

    // If the faculty is not assigned to any courses, display an error
    if (empty($faculty_courses)) {
        echo "<p style='text-align:center;'>You are not assigned any courses.</p>";
        exit;
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Grades</title>
    <link rel="stylesheet" href="css/display_grades.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>

    <?php include 'admin_header.php'; ?>

    <main class="main-content">
        <h1>Student Grades</h1>
        <div class="container">
            <a href="grades.php" class="return-btn">
                <i class="fas fa-times"></i>
            </a>
            <?php
            // Check if the student ID is passed via URL
            if (isset($_GET['student_id'])) {
                $student_id = (int)$_GET['student_id']; // Cast to integer to prevent SQL injection

                // Initialize GPA calculation variables
                $total_gpa = 0;
                $course_count = 0;

                // SQL Query to fetch the grades for the specific student
                $query = "
                SELECT 
                    scg.student_course_grade_id, 
                    s.student_name, 
                    c.course_name, 
                    g.grade_letter, 
                    g.gpa_point,
                    st.status_name
                FROM student_course_grade scg
                JOIN student s ON scg.student_id = s.student_id
                JOIN course c ON scg.course_id = c.course_id
                JOIN grade g ON scg.grade_id = g.grade_id
                JOIN status st ON c.status_id = st.status_id
                WHERE s.student_id = ?
            ";

                // If faculty, add course filter
                if ($role_id == 2) {
                    $query .= " AND c.course_id IN (" . implode(",", $faculty_courses) . ")";
                }

                // Prepare and execute the query
                $stmt = $conn->prepare($query);
                $stmt->bind_param('i', $student_id);
                $stmt->execute();
                $result = $stmt->get_result();

                // Check if there are grades for the student
                if ($result->num_rows > 0) {
                    // Calculate CGPA
                    while ($row = $result->fetch_assoc()) {
                        $total_gpa += (float) $row['gpa_point'];
                        $course_count++;
                    }
                    // Calculate CGPA by dividing total GPA points by course count
                    $cgpa = $total_gpa / $course_count;

                    // Display CGPA at the top of the container
                    echo "<p class='cgpa'>CGPA: " . number_format($cgpa, 2) . "</p>";

                    // Display grade cards for each course
                    echo "<div class='card-container'>";
                    $result->data_seek(0); // Reset result pointer

                    // Loop through each course and display its grade and GPA
                    while ($row = $result->fetch_assoc()) {
                        echo "<div class='grade-card'>";
                        echo "<h2>" . htmlspecialchars($row['course_name'], ENT_QUOTES, 'UTF-8') . "</h2>"; // Ensure course name is sanitized
                        echo "<p><strong>Student:</strong> " . htmlspecialchars($row['student_name'], ENT_QUOTES, 'UTF-8') . "</p>"; // Sanitize student name
                        echo "<p><strong>Grade:</strong> " . htmlspecialchars($row['grade_letter'], ENT_QUOTES, 'UTF-8') . "</p>"; // Sanitize grade
                        echo "<p><strong>GPA:</strong> " . htmlspecialchars($row['gpa_point'], ENT_QUOTES, 'UTF-8') . "</p>"; // Sanitize GPA

                        // Add the status-icon dynamically based on the course status
                        echo "<div class='badges'>";
                        $class = '';
                        if (htmlspecialchars($row['status_name'], ENT_QUOTES, 'UTF-8') === 'Start') {
                            $class = 'green';
                        } elseif (htmlspecialchars($row['status_name'], ENT_QUOTES, 'UTF-8') === 'In-Progress') {
                            $class = 'yellow';
                        } elseif (htmlspecialchars($row['status_name'], ENT_QUOTES, 'UTF-8') === 'Ended') {
                            $class = 'red';
                        } else {
                            $class = 'blue';
                        }
                        echo "<span class='status-icon $class'>" . htmlspecialchars($row['status_name'], ENT_QUOTES, 'UTF-8') . "</span>";
                        echo "</div>";


                        // Action buttons for updating and deleting grades
                        echo "<div class='actions'>";

                        // Update button
                        echo "<a href='update_gradeform.php?id=" . urlencode($row['student_course_grade_id']) . "' class='icon-btn'><i class='fas fa-pen'></i></a>";

                        // Delete Grade Form
                        // Faculty cannot delete grades via button (disabled action)
                        if ($_SESSION['session_roleid'] == 2) {
                            echo "<a href='#' class='icon-btn disabled' style='pointer-events: none; opacity: 0.5;'><i class='fas fa-trash'></i></a>";
                        } else {
                            // Allow grade deletion via a POST form for other users (Admins)
                            echo "
                            <form action='delete_grade.php' method='POST' onsubmit='return ConfirmDelete()' style='display: inline;'>
                                <input type='hidden' name='student_course_grade_id' value='" . htmlspecialchars($row['student_course_grade_id']) . "'>
                                <input type='hidden' name='csrf_token' value='" . htmlspecialchars($_SESSION['csrf_token']) . "'>
                                <button type='submit' class='icon-btn' style='cursor: pointer;'>
                                    <i class='fas fa-trash'></i>
                                </button>
                            </form>";
                        }

                        echo "</div>";
                        echo "</div>";
                    }
                    echo "</div>";
                } else {
                    // If no grades found for this student, display a message
                    echo "<p style='text-align:center;'>No grades found for this student.</p>";
                }

                $stmt->close();
                mysqli_close($conn);
            } else {
                // If no student selected in the URL, show this message
                echo "<p style='text-align:center;'>No student selected.</p>";
            }
            ?>

            <!-- Button to add a new grade -->
            <div class="button-container">
                <a href="create_gradeform.php?student_id=<?php echo urlencode($student_id); ?>" class="icon-box">
                    <i class="fas fa-plus"></i>
                </a>
            </div>
        </div>
    </main>

    <!-- Delete Confirmation Function-->
    <script>
        function ConfirmDelete() {
            return confirm("Are you sure you want to delete this grade?");
        }
    </script>
</body>

</html>