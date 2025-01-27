<?php
session_start();

if (!isset($_SESSION['session_userid']) || !isset($_SESSION['session_role'])) {
    echo "<script>
            alert('Unauthorized user. Redirecting To Login.');
            window.location.href = 'login.php'; // Redirect to login
        </script>";
    exit;
}

if ($_SESSION['session_role'] != 1 && $_SESSION['session_role'] != 2) {
    echo "<script>
            alert('You Do Not Have Permission To Access This.');
            window.location.href = 'login.php'; // Redirect to login
        </script>";
    exit;
}

include 'db_connection.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $student_id = (int)$_POST['student_id'];
    $course_id = (int)$_POST['course_id'];
    $method = $_POST['method'];

    if ($method == 'grade') {
        // Grade method selected
        $grade_id = (int)$_POST['grade_id'];
        $query = "INSERT INTO student_course_grade (student_id, course_id, grade_id) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('iii', $student_id, $course_id, $grade_id);
    } elseif ($method == 'percentage') {
        // Percentage method selected
        $score = (int)$_POST['score'];

        // Fetch grade based on score range
        $query = "SELECT grade_id FROM grade WHERE ? BETWEEN min_score AND max_score LIMIT 1";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('i', $score);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $grade = $result->fetch_assoc();
            $grade_id = $grade['grade_id'];

            // Insert the calculated grade into student_course_grade
            $query = "INSERT INTO student_course_grade (student_id, course_id, grade_id) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($query);
            $stmt->bind_param('iii', $student_id, $course_id, $grade_id);
        } else {
            echo "<p>No matching grade found for the given score.</p>";
            exit;
        }
    } else {
        echo "<p>Invalid method selected.</p>";
        exit;
    }

    if ($stmt->execute()) {
        echo "<script>
                alert('Grade Inserted Successfully.');
                window.location.href = 'display_grades.php?student_id=" . $student_id . "'; // Redirect to grades
            </script>";
        exit;
    } else {
        echo "<script>
            alert('Error Updating Grade. Please Try Again.');
            window.location.href = 'display_grades.php?student_id=" . $student_id . "'; // Redirect to grades
         </script>";
    }

    $stmt->close();
} else {
    echo "<script>
            alert('Invalid Request.');
            window.location.href = 'grades.php'; 
        </script>";
}

mysqli_close($conn);
