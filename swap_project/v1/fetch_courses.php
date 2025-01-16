<?php
include 'db_connection.php';

// Check if the request method is POST and department_id is provided
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['department_id'])) {
    $department_id = $_POST['department_id'];

    // Validate that department_id is numeric
    if (is_numeric($department_id)) {
        $stmt = $pdo->prepare("
            SELECT course_id, course_name 
            FROM course 
            WHERE department_id = :department_id
        ");
        $stmt->execute([':department_id' => $department_id]);
        $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Check if any courses are found
        if ($courses) {
            foreach ($courses as $course) {
                echo "<option value=\"{$course['course_id']}\">" . htmlspecialchars($course['course_name']) . "</option>";
            }
        } else {
            // Fallback for no courses
            echo "<option value=\"\">No courses available</option>";
        }
    } else {
        echo "<option value=\"\">Invalid department ID</option>";
    }
} else {
    echo "<option value=\"\">Invalid request</option>";
}
?>
