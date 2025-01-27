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


$role_id = $_SESSION['session_role']; // Get role ID from the session
$user_id = $_SESSION['session_userid']; // Get user ID from the session


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
    </style>
</head>

<body>

    <?php include 'admin_header.php'; ?> <!-- Include the admin header here -->
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

                // Connect to the database
                include 'db_connection.php';

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
                        g.gpa_point
                    FROM student_course_grade scg
                    JOIN student s ON scg.student_id = s.student_id
                    JOIN course c ON scg.course_id = c.course_id
                    JOIN grade g ON scg.grade_id = g.grade_id
                    WHERE s.student_id = ?
                ";

                $stmt = $conn->prepare($query);
                $stmt->bind_param('i', $student_id);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows > 0) {
                    // Calculate CGPA
                    while ($row = $result->fetch_assoc()) {
                        $total_gpa += (float) $row['gpa_point'];
                        $course_count++;
                    }
                    $cgpa = $total_gpa / $course_count;

                    // Display CGPA at the top of the container
                    echo "<p class='cgpa'>CGPA: " . number_format($cgpa, 2) . "</p>";

                    // Display grade cards for each course
                    echo "<div class='card-container'>";
                    $result->data_seek(0); // Reset result pointer
                    while ($row = $result->fetch_assoc()) {
                        echo "<div class='grade-card'>";
                        echo "<h2>" . htmlspecialchars($row['course_name']) . "</h2>";
                        echo "<p><strong>Student:</strong> " . htmlspecialchars($row['student_name']) . "</p>";
                        echo "<p><strong>Grade:</strong> " . htmlspecialchars($row['grade_letter']) . "</p>";
                        echo "<p><strong>GPA:</strong> " . htmlspecialchars($row['gpa_point']) . "</p>";
                        echo "<div class='actions'>";
                        echo "<a href='update_gradeform.php?id=" . urlencode($row['student_course_grade_id']) . "' class='icon-btn'><i class='fas fa-pen'></i></a>";
                        echo "<a href='delete_grade.php?id=" . urlencode($row['student_course_grade_id']) . "' class='icon-btn'><i class='fas fa-trash'></i></a>";
                        echo "</div>";
                        echo "</div>";
                    }
                    echo "</div>";
                } else {
                    echo "<p style='text-align:center;'>No grades found for this student.</p>";
                }

                $stmt->close();
                mysqli_close($conn);
            } else {
                echo "<p style='text-align:center;'>No student selected.</p>";
            }
            ?>

            <div class="button-container">
                <a href="create_gradeform.php?student_id=<?php echo urlencode($student_id); ?>" class="icon-box">
                    <i class="fas fa-plus"></i>
                </a>
            </div>
        </div>
    </main>
</body>

</html>