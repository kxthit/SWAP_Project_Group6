<?php
session_start();

if (!isset($_SESSION['session_userid']) || !isset($_SESSION['session_role'])) {
    echo "<h2>Unauthorized access. Please log in.</h2>";
    header('Refresh: 3; URL=login.php');
    exit;
}

if ($_SESSION['session_role'] != 1 && $_SESSION['session_role'] != 2) {
    echo "<h2>You do not have permission to access this page.</h2>";
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
        echo "<p>Grade successfully assigned!</p>";
    } else {
        echo "<p>Error assigning grade. Please try again.</p>";
    }

    $stmt->close();
    echo "<a href='display_grades.php?student_id=" . $student_id . "'>Back to Grades</a>";
} else {
    echo "<p>Invalid request.</p>";
}

mysqli_close($conn);
