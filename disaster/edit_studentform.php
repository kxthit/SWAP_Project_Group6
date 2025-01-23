<?php
;

// Check if the user is authenticated
if (!isset($_SESSION['session_userid']) || !isset($_SESSION['session_role'])) {
    echo "<h2>Unauthorized access. Please log in.</h2>";
    header('Refresh: 3; URL=login.php');
    exit;
}

// Database connection
include 'db_connection.php';

// Get the student ID from URL
$student_id = $_GET['student_id'] ?? null;
if (!$student_id) {
    echo "No student ID provided!";
    exit;
}

// Fetch student details, courses, and classes
$query = "SELECT s.student_id, s.student_name, s.student_email, s.student_phone, 
                 u.admission_number, s.department_id, 
                 GROUP_CONCAT(c.course_name) AS courses, 
                 GROUP_CONCAT(cl.class_name) AS class_name
          FROM student s
          JOIN user u ON s.user_id = u.user_id
          LEFT JOIN student_course sc ON s.student_id = sc.student_id
          LEFT JOIN course c ON sc.course_id = c.course_id
          LEFT JOIN student_class scs ON s.student_id = scs.student_id
          LEFT JOIN class cl ON scs.class_id = cl.class_id
          WHERE s.student_id = ?
          GROUP BY s.student_id";
$stmt = $conn->prepare($query);
$stmt->bind_param('i', $student_id);
$stmt->execute();
$result = $stmt->get_result();
$student = $result->fetch_assoc();

if (!$student) {
    echo "No student found with the given ID.";
    exit;
}

// Fetch all departments
$departments_query = "SELECT department_id, department_name FROM department";
$departments = $conn->query($departments_query);

$all_departments=array();
while ($row = $departments->fetch_assoc()){
    $department=array(
        'department_id' => $row['department_id'],
        'department_name' => $row['department_name']
    );
    $all_departments[]=$department;
}

// Fetch all courses
$selected_department = $student['department_id'];
$all_courses_query = "SELECT department_id, course_id, course_name FROM course";
$all_courses_result = $conn->query($all_courses_query);

// Group courses by department ID
$all_courses = [];
while ($row = $all_courses_result->fetch_assoc()) {
    $all_courses[$row['department_id']][] = [
        'course_id' => $row['course_id'],
        'course_name' => $row['course_name'],
        'department_id' => $row['department_id']
    ];
}

// Fetch all classes 
$all_classes_query = "SELECT class_id, class_name FROM class";
$all_classes_result = $conn->query($all_classes_query);

// Group classes (you might not need grouping here, depending on your data structure)
$all_classes = [];
while ($row = $all_classes_result->fetch_assoc()) {
    $all_classes[] = [
        'class_id' => $row['class_id'],
        'class_name' => $row['class_name'],
    ];
}

// Fetch current courses for the student
$current_courses_query = "SELECT course_id FROM student_course WHERE student_id = ?";
$stmt = $conn->prepare($current_courses_query);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$current_courses = [];
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $current_courses[] = $row['course_id'];
}

// Fetch current classes for the student
$current_classes_query = "SELECT class_id FROM student_class WHERE student_id = ?";
$stmt = $conn->prepare($current_classes_query);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$current_classes = [];
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $current_classes[] = $row['class_id'];
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_name = $_POST['student_name'];
    $admission_number = $_POST['admission_number'];
    $student_email = $_POST['student_email'];
    $student_phone = $_POST['student_phone'];
    $department_id = $_POST['department_id'];
    $new_courses = $_POST['new_courses'] ?? [];

    $update_query = "UPDATE student 
                     SET student_name = ?, department_id = ?, 
                         student_email = ?, student_phone = ? 
                     WHERE student_id = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param('sisii', $student_name, $department_id, $student_email, $student_phone, $student_id);

    if ($stmt->execute()) {
        // Update courses
        $delete_query = "DELETE FROM student_course WHERE student_id = ?";
        $stmt = $conn->prepare($delete_query);
        $stmt->bind_param("i", $student_id);
        $stmt->execute();

        $insert_query = "INSERT INTO student_course (student_id, course_id) VALUES (?, ?)";
        $stmt = $conn->prepare($insert_query);

        foreach ($new_courses as $course_id) {
            $stmt->bind_param("ii", $student_id, $course_id);
            $stmt->execute();
        }

        echo "<script>alert('Student details and courses updated successfully!');</script>";
        header("Location: display_student.php?student_id=$student_id");
        exit;
    } else {
        echo "Error updating student details: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Student</title>
    <style>
        .course-classes {
            display: none;
        }
    </style>
</head>
<body>
    <h2>Edit Student</h2>
    <form action="" method="POST">
        <input type="hidden" name="student_id" value="<?php echo $student['student_id']; ?>">

        <table border="1" cellpadding="5">
            <tr>
                <th colspan="2">Student Details</th>
            </tr>
            <tr>
                <td><label for="student_name">Student Name:</label></td>
                <td><input type="text" id="student_name" name="student_name" value="<?php echo htmlspecialchars($student['student_name']); ?>" required></td>
            </tr>
            <tr>
                <td><label for="admission_number">Admission Number:</label></td>
                <td><input type="text" id="admission_number" name="admission_number" value="<?php echo htmlspecialchars($student['admission_number']); ?>" required></td>
            </tr>
            <tr>
                <td><label for="student_email">Email:</label></td>
                <td><input type="email" id="student_email" name="student_email" value="<?php echo htmlspecialchars($student['student_email']); ?>" required></td>
            </tr>
            <tr>
                <td><label for="student_phone">Phone:</label></td>
                <td><input type="text" id="student_phone" name="student_phone" value="<?php echo htmlspecialchars($student['student_phone']); ?>" required></td>
            </tr>
        </table>

        <br>

        <table border="1" cellpadding="5">
            <tr>
                <th>Department</th>
            </tr>
            <tr>
                <td>
                <select id="department_id" name="department_id" required>
                    <option value="">Select Department</option>
                    <?php foreach ($all_departments as $department): ?>
                        <option value="<?php echo $department['department_id']; ?>" 
                                <?php if ($department['department_id'] == $student['department_id']): ?>
                                    selected 
                                <?php endif; ?>>
                            <?php echo htmlspecialchars($department['department_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                </td>
            </tr>
        </table>

        <br>

        <table border="1" cellpadding="5">
            <tr>
                <th>Courses</th>
            </tr>
            <tr>
                <td>
                    Current: 
                    <div id="current_courses">
                        <?php
                        foreach ($current_courses as $course_id) {
                            $course_name = array_values(array_filter($all_courses[$selected_department], function($course) use ($course_id) {
                                return $course['course_id'] == $course_id;
                            }));
                            echo htmlspecialchars($course_name[0]['course_name']) . '<br>';
                        }
                        ?>
                    </div>
                    <br>
                    New:<br>
                    <?php foreach ($all_courses as $department_id => $department_courses){
                        foreach ($department_courses as $course){
                            ?>
                            <input type="checkbox" name="new_courses[]" value="<?php echo $course['course_id']; ?>" data-department-id="<?php echo $department_id; ?>">
                            <?php echo htmlspecialchars($course['course_name']); ?><br>
                            <?php
                        }
                    }
                    ?>
                </td>
            </tr>
        </table>

        <br>

        <table border="1" cellpadding="5">
            <tr>
                <th>Classes</th>
            </tr>
            <tr>
                <td>
                    Current:
                    <div id="current_classes">
                        <?php
                        foreach ($current_classes as $class_id) {
                            $class_name = array_values(array_filter($all_classes, function($class) use ($class_id) {
                                return $class['class_id'] == $class_id;
                            }));
                            echo htmlspecialchars($class_name[0]['class_name']) . '<br>';
                        }
                        ?>
                    </div>
                    <br>
                    New:<br>
                    <?php foreach ($all_classes as $class) { ?>
                        <input type="radio" name="new_classes[]" value="<?php echo $class['class_id']; ?>"> 
                        <?php echo htmlspecialchars($class['class_name']); ?><br>
                    <?php } ?>
                </td>
            </tr>
        </table>

        <br>

        <div id="new_course_classes"></div>

        <button type="submit">Update</button>
        <button type="button" onclick="window.location.href='display_student.php?student_id=<?php echo $student_id; ?>'">Cancel</button>
    </form>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Event listener for department change
            document.getElementById('department_id').addEventListener('change', function() {
                const selectedDepartment = this.value;
                const allCourses = document.querySelectorAll('input[name="new_courses[]"]');

                allCourses.forEach(function(courseCheckbox) {
                    const courseDepartment = courseCheckbox.dataset.departmentId;
                    if (courseDepartment !== selectedDepartment) {
                        courseCheckbox.disabled = true;
                    } else {
                        courseCheckbox.disabled = false;
                    }
                });

                populateClassSections(selectedDepartment);
            });

            // Event listener for course checkbox change
            const courseCheckboxes = document.querySelectorAll('input[name="new_courses[]"]');
            courseCheckboxes.forEach(function(courseCheckbox) {
                courseCheckbox.addEventListener('change', function() {
                const selectedCourses = Array.from(courseCheckboxes).filter(cb => cb.checked).map(cb => cb.value);

                // Update class radio button visibility based on selected courses
                document.querySelectorAll('.course-classes').forEach(function(classGroup) {
                    const courseId = classGroup.dataset.courseId;
                    const courseSelected = selectedCourses.includes(courseId);

                    classGroup.style.display = courseSelected ? 'block' : 'none'; // Show/hide class section

                    if (courseSelected) {
                    // Enable radio buttons only for the selected course's classes
                    classGroup.querySelectorAll('input[type="radio"]').forEach(radio => {
                        radio.disabled = false;
                    });
                    } else {
                    // Disable all radio buttons for the hidden course section
                    classGroup.querySelectorAll('input[type="radio"]').forEach(radio => {
                        radio.disabled = true;
                        radio.checked = false; // Uncheck previously selected radio
                    });
                    }
                });
                });
            });

            function populateClassSections(departmentId) {
                const courses = <?php echo json_encode($all_courses); ?>;
                const classSectionsDiv = document.getElementById('new_course_classes');

                classSectionsDiv.innerHTML = ''; // Clear existing sections

                courses[departmentId].forEach(function(course) {
                const courseSection = document.createElement('div');
                courseSection.innerHTML = `<strong>${course.course_name}</strong><br>`;
                courseSection.className = 'course-classes';
                courseSection.dataset.courseId = course.course_id;

                // Fetch classes and display radio buttons
                fetch(`get_classes.php?course_id=${course.course_id}`)
                    .then(response => response.json())
                    .then(classes => {
                    classes.forEach(function(classItem) {
                        const radioName = `class_${course.course_id}`; // Create unique name based on courseId
                        const label = document.createElement('label');
                        label.innerHTML = `<input type="radio" name="${radioName}" value="${classItem.class_id}"> ${classItem.class_name}<br>`;
                        courseSection.appendChild(label);
                    });
                    classSectionsDiv.appendChild(courseSection);
                    });
                });
            }
            });
    </script>
</body>
</html>
