<?php
// Include the database connection
include 'db_connection.php';

session_start();

// Check if the user is authenticated
if (!isset($_SESSION['session_userid']) || !isset($_SESSION['session_role'])) {
    echo "<h2>Unauthorized access. Please log in.</h2>";
    header('Refresh: 3; URL=login.php');
    exit;
}

// Validate student_id from URL
if (!isset($_GET['student_id']) || !is_numeric($_GET['student_id'])) {
    echo "Invalid or missing student ID.";
    exit;
}

$student_id = $_GET['student_id'];

// Query to fetch student details
$query = "
    SELECT 
        s.student_id,
        s.student_name,
        s.student_email,
        s.student_phone,
        u.admission_number,
        c.class_name,
        d.department_name,
        GROUP_CONCAT(co.course_name ORDER BY co.course_name SEPARATOR ', ') AS courses
    FROM student s
    JOIN user u ON s.user_id = u.user_id
    JOIN student_class sc ON s.student_id = sc.student_id
    JOIN class c ON sc.class_id = c.class_id
    JOIN department d ON s.department_id = d.department_id
    JOIN student_course sc2 ON s.student_id = sc2.student_id
    JOIN course co ON sc2.course_id = co.course_id
    WHERE s.student_id = ?
    GROUP BY s.student_id
";

// Prepare and execute the query
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();
$student = $result->fetch_assoc();

// Query to fetch grades for the student
$grades_query = "
    SELECT 
        co.course_name,
        g.grade_letter,
        g.gpa_point
    FROM student_course_grade scg
    JOIN course co ON scg.course_id = co.course_id
    JOIN grade g ON scg.grade_id = g.grade_id
    WHERE scg.student_id = ?
";

$grades_stmt = $conn->prepare($grades_query);
$grades_stmt->bind_param("i", $student_id);
$grades_stmt->execute();
$grades_result = $grades_stmt->get_result();

// Calculate the average GPA point
$total_gpa = 0;
$total_courses = 0;
$grades = [];
while ($row = $grades_result->fetch_assoc()) {
    $grades[] = $row;
    $total_gpa += $row['gpa_point'];
    $total_courses++;
}
$average_gpa = $total_courses > 0 ? $total_gpa / $total_courses : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Details</title>
    <style>
        /* General Reset */
        body {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f5f7fc;
            
        }

        /* Container Styles */
        .form-container {
            width: 100%;
            max-width: 1000px;
            margin: 1rem auto;
            background-color: #fff;
            border-radius: 10px;
            padding: 1rem;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            margin-top: 80px;
            justify-content: center;
            
        }

        h1 {
            color: #1a1a1a;
            margin-bottom: 1rem;
            text-align: center;
            margin-top: -30px;
        }

        h2 {
            background-color: #6495ed;
            color: white;
            padding: 1rem;
            border-radius: 10px 10px 0 0;
            margin-left: -17px;
            text-align: left;
            width: 100.2%;
            margin-top: -69px;
            
        }

        .form-row {
            display: flex;
            gap: 2rem; /* Space between details and photo */
            flex-wrap: wrap;
            margin-bottom: 1.5rem;
        }

        /* Details Section */
        .details-table {
            flex: 1; /* Table takes up the remaining space */
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            margin-left: 150px;
        }

        .details-table th {
            text-align: right;
            padding: 0.8rem;
        }

        .details-table td{
            text-align: left;
            padding: 0.8rem;
        }

        .details-table td {
            border-bottom: none;
            padding: 0.8rem 1.5rem; /* Increase horizontal padding */
        }

        label {
            font-weight: bold;
        }

        ul {
            list-style-type: disc;
            padding-left: 2rem;
        }

        /* Responsive Design */
        @media (max-width: 600px) {
            .form-row {
                flex-direction: column;
            }

            .details-table {
                flex: 1;
            }
        }

        .action-buttons {
            margin-top: 20px;
            display: flex;
            justify-content: center;
            gap: 10px;
        }

        .action-buttons a {
            padding: 10px 20px;
            text-decoration: none;
            color: white;
            background-color: #007bff;
            border-radius: 5px;
            font-size: 1rem;
        }

        .action-buttons a:hover {
            background-color: #0056b3;
        }

        .modal {
            display: none; /* Hidden by default */
            position: fixed; 
            z-index: 1; 
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.4); /* Black with transparency */
            align-content: center;
        }

        .modal-content {
            background-color: #fefefe;
            margin: 15% auto; /* 15% from the top and centered */
            padding: 20px;
            border: 1px solid #888;
            width: 30%; /* Could be more or less, depending on screen size */
            text-align: center;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        .modal-buttons {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 20px;
        }

        .modal-buttons a {
            padding: 8px 16px;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 4px;
        }

        .modal-buttons a:hover {
            background-color: #0056b3;
        }

        .modal-buttons .btn-cancel {
            background-color: #6c757d;
        }

        .modal-buttons .btn-cancel:hover {
            background-color: #545b62;
        }
    </style>
</head>
<body>
<?php include('admin_header.php'); ?>
    <main class="main-content">
        <h1>Student Details</h1>
        <div class="form-container">
            <div class="form-card">
                <h2>Details</h2>
                <div class="form-row">
                    <!-- Right Column: Table -->
                    <table class="details-table">
                        <tr>
                            <th>Name:</th>
                            <td><?php echo htmlspecialchars($student['student_name']); ?></td>
                        </tr>
                        <tr>
                            <th>Admission Number:</th>
                            <td><?php echo htmlspecialchars($student['admission_number']); ?></td>
                        </tr>
                        <tr>
                            <th>Email:</th>
                            <td><?php echo htmlspecialchars($student['student_email']); ?></td>
                        </tr>
                        <tr>
                            <th>Phone:</th>
                            <td><?php echo htmlspecialchars($student['student_phone']); ?></td>
                        </tr>
                        <tr>
                            <th>Department:</th>
                            <td><?php echo htmlspecialchars($student['department_name']); ?></td>
                        </tr>
                        <tr>
                            <th>Courses:</th>
                            <td>
                                <ul>
                                    <?php 
                                    $courses = explode(',', $student['courses']);
                                    foreach ($courses as $course): ?>
                                        <li><?php echo htmlspecialchars(trim($course)); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </td>
                        </tr>
                        <tr>
                            <th>Classes:</th>
                            <td>
                                <ul>
                                    <?php 
                                    $classes = explode(',', $student['class_name']);
                                    foreach ($classes as $class): ?>
                                        <li><?php echo htmlspecialchars(trim($class)); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Buttons -->
            <div class="action-buttons">
                <?php if ($_SESSION['session_role'] == 1): // Check if the user is an admin ?>
                    <a href="edit_studentform1.php?student_id=<?php echo $student['student_id']; ?>">Edit</a>
                    <a href="#" onclick="openModal(<?php echo $student['student_id']; ?>)" class="delete-button">Delete</a>
                <?php elseif ($_SESSION['session_role'] == 2): // Check if the user is a faculty ?>
                    <a href="faculty_edit_studentform.php?student_id=<?php echo $student['student_id']; ?>">Edit</a>
                <?php endif; ?>
            </div>

        </div>
    </main>

    <!-- Delete Modal -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <p>Are you sure you want to delete this student?</p>
            <div class="modal-buttons">
                <a href="#" id="confirmDelete" class="btn-confirm">Yes, Delete</a>
                <a href="#" onclick="closeModal()" class="btn-cancel">Cancel</a>
            </div>
        </div>
    </div>

    <script>
        let studentIdToDelete = null;

        function openModal(studentId) {
            studentIdToDelete = studentId;
            document.getElementById('deleteModal').style.display = 'block';
            const confirmButton = document.getElementById('confirmDelete');
            confirmButton.href = `delete_student.php?student_id=${encodeURIComponent(studentId)}`;
        }

        function closeModal() {
            document.getElementById('deleteModal').style.display = 'none';
            studentIdToDelete = null;
        }
    </script>
</body>
</html>