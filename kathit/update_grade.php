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

// Include database connection
include 'db_connection.php';

// Check if form data is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get POST values
    $student_course_grade_id = (int)$_POST['student_course_grade_id']; // Grade ID to update
    $student_id = (int)$_POST['student_id'];
    $course_id = (int)$_POST['course_id'];
    $grade_id = (int)$_POST['grade_id'];

    // Validate data (check if values are valid integers)
    if (empty($student_course_grade_id) || empty($student_id) || empty($course_id) || empty($grade_id)) {
        echo "<p>Invalid data. Please try again.</p>";
        exit;
    }

    // Fetch the GPA points associated with the selected grade
    $gpa_query = "SELECT gpa_point FROM grade WHERE grade_id = ?";
    $stmt = $conn->prepare($gpa_query);
    $stmt->bind_param('i', $grade_id);
    $stmt->execute();
    $gpa_result = $stmt->get_result();

    if ($gpa_result->num_rows > 0) {
        $gpa_data = $gpa_result->fetch_assoc();
        $gpa_point = $gpa_data['gpa_point'];
    } else {
        echo "<p>Error fetching GPA point. Please try again.</p>";
        exit;
    }

    // Update the student's grade in the database
    $update_query = "
        UPDATE student_course_grade 
        SET grade_id = ? 
        WHERE student_course_grade_id = ?
    ";

    $stmt = $conn->prepare($update_query);
    $stmt->bind_param('ii', $grade_id, $student_course_grade_id);

    if ($stmt->execute()) {
        echo "<p>Grade updated successfully! GPA: " . $gpa_point . "</p>";
        echo "<a href='display_grades.php?student_id=" . $student_id . "'>Back to Grades</a>";
    } else {
        echo "<p>Error updating grade. Please try again.</p>";
    }

    // Close statement
    $stmt->close();
} else {
    echo "<p>Invalid request.</p>";
}

// Close database connection
mysqli_close($conn);
