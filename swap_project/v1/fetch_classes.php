<?php
include 'db_connection.php';

$course_ids = $_POST['course_ids'] ?? [];

if (!empty($course_ids)) {
    $placeholders = implode(',', array_fill(0, count($course_ids), '?'));

    $stmt = $pdo->prepare("
        SELECT class_id, class_name 
        FROM class 
        WHERE course_id IN ($placeholders)
    ");
    $stmt->execute($course_ids);
    $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($classes as $class) {
        echo "<option value=\"{$class['class_id']}\">" . htmlspecialchars($class['class_name']) . "</option>";
    }
}
?>
