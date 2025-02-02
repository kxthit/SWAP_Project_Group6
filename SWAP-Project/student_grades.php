<?php
include 'db_connection.php';
include 'session_management.php';

// Check if the user is authenticated
if (!isset($_SESSION['session_userid']) || !isset($_SESSION['session_roleid'])) {
    $error_message = "Unauthorized access. Please log in.";
    header('Refresh: 3; URL=login.php');
    exit;
}

// Check if the user has a valid role (Student) , if not redirect to login page.
if ($_SESSION['session_roleid'] != 3) {
    $error_message = "Unauthorized access. Please log in.";
    header('Refresh: 3; URL=login.php');
    exit;
}

// Fetch student_id from the database based on the logged-in user's user_id if it's not already set in session
if (!isset($_SESSION['session_studentid'])) {
    $stmt = $conn->prepare("SELECT student_id FROM student WHERE user_id = ?");
    $stmt->bind_param("i", $_SESSION['session_userid']);
    $stmt->execute();
    $result = $stmt->get_result();
    $student_data = $result->fetch_assoc();

    if ($student_data) {
        $_SESSION['session_studentid'] = $student_data['student_id'];
    } else {
        $error_message = "Student record not found for this user.";
        echo $error_message;
        exit;
    }
}

$student_id = $_SESSION['session_studentid']; // Use the student_id from session

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Grades</title>
    <style>
        /* Your CSS styles here */
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
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
            /* Make the container scrollable */
            max-height: 550px;
            width: 100%;
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
            overflow: hidden;
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

        .cgpa {
            font-size: 22px;
            font-weight: bold;
            color: #0d2444;
            text-align: center;
        }
    </style>
</head>

<body>
    <?php include('student_header.php'); ?>
    <main class="main-content">
        <h1>View Your Grades</h1>
        <div class="container">
            <?php
            // Initialize GPA calculation variables
            $total_gpa = 0;
            $course_count = 0;

            // SQL Query to fetch the grades for the logged-in student (based on student_id from session)
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

            // Prepare and execute the query
            $stmt = $conn->prepare($query);
            $stmt->bind_param('i', $student_id); // Use the session's student_id
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
                    echo "</div>";
                }
                echo "</div>"; // End card-container
            } else {
                // Display an error message if no grades are found
                echo "<p>No grades found for this student.</p>";
            }
            ?>
        </div>
    </main>
</body>

</html>