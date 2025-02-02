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
    redirect('Unauthorized user. Redirecting To Login.', 'login.php');
}

// Check if the user has a valid role (Admin or Faculty) , if not authenticated redirect to login page.
if ($_SESSION['session_roleid'] != 1 && $_SESSION['session_roleid'] != 2) {
    redirect('You Do Not Have Permission To Access This.', 'login.php');
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
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Page layout and design */
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }

        .container {
            display: flex;
            justify-content: center;
            align-items: center;
            flex-direction: column;
            max-width: 1100px;
            margin: 20px auto;
            padding: 20px;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0px 12px 40px rgba(0, 0, 0, 0.2);
            transition: box-shadow 0.3s ease-in-out, transform 0.3s ease-in-out;
            overflow-y: auto;
            /* Enable vertical scrolling */
            max-height: 550px;
            /* Set a maximum height for the container */
        }

        .container:hover {
            box-shadow: 0px 18px 50px rgba(0, 0, 0, 0.25);
            transform: scale(1.01);
        }

        h1 {
            text-align: center;
            color: #0d2444;
        }

        .card-container {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            justify-content: center;
        }

        .grade-card {
            background: #2c6485;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            width: 300px;
            transition: 0.3s ease;
            border: 2px solid #ecdfce;
            display: flex;
            flex-direction: column;
            position: relative;
            /* Make the card a positioned element */
            overflow: hidden;
            /* Ensure content doesn't spill out */
        }

        .grade-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 0 15px 4px rgb(95, 142, 174);
        }

        .grade-card h2 {
            color: #f1eaeb;
            margin: 0 0 10px;
        }

        .grade-card p {
            margin: 5px 0;
            font-size: 16px;
            color: #ecdfce;
        }

        .actions {
            margin-top: 15px;
        }

        .icon-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 35px;
            height: 35px;
            border-radius: 50%;
            background-color: #fff;
            color: #10171e;
            font-size: 18px;
            text-decoration: none;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            border: 1px solid #10171e;
            margin-right: 10px;
        }

        .icon-btn:hover {
            background-color: #10171e;
            color: #fff;
        }

        .icon-box {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            background-color: #fff;
            color: #10171e;
            font-size: 24px;
            border-radius: 10px;
            text-decoration: none;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            border: 1px solid #10171e;
        }

        .icon-box:hover {
            background-color: #10171e;
            color: #fff;
            transform: scale(1.1);
        }

        .button-container {
            display: flex;
            justify-content: flex-end;
            width: 100%;
            margin-top: 20px;
        }

        .return-btn {
            position: absolute;
            top: 10px;
            right: 10px;
            font-size: 30px;
            color: #10171e;
            text-decoration: none;
            background: none;
            border: none;
            cursor: pointer;
            outline: none;
            transition: color 0.3s ease, transform 0.3s ease;
        }

        .return-btn:hover {
            color: #d9534f;
            transform: scale(1.1);
        }

        .cgpa {
            font-size: 22px;
            font-weight: bold;
            color: #0d2444;
            text-align: center;
        }

        .badges {
            position: absolute;
            /* Position badges at the bottom-right */
            bottom: 10px;
            right: 10px;
            display: flex;
            gap: 0.5rem;
            align-items: center;
        }

        .status-icon {
            font-size: 0.875rem;
            line-height: 1.25rem;
            padding: 4px 0.75rem;
            border-radius: 0.375rem;
            border: 1px solid transparent;
            text-align: center;
        }

        .status-icon.green {
            background-color: rgba(34, 197, 94, 0.10);
            color: rgb(34, 197, 94);
            border-color: rgb(34, 197, 94);
        }

        .status-icon.yellow {
            background-color: rgba(255, 200, 35, 0.1);
            color: rgb(234, 179, 8);
            border-color: rgb(234, 179, 8);
        }

        .status-icon.blue {
            background-color: rgba(59, 130, 246, 0.10);
            color: rgb(59, 130, 246);
            border-color: rgb(59, 130, 246);
        }

        .status-icon.red {
            background-color: rgba(239, 68, 68, 0.10);
            color: rgb(239, 68, 68);
            border-color: rgb(239, 68, 68);
        }
    </style>
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