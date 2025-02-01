<?php
include 'db_connection.php';
include 'session_management.php';

$error_message="";

// Check if the user is authenticated
if (!isset($_SESSION['session_userid']) || !isset($_SESSION['session_role'])) {
    $error_message= "Unauthorized access. Please log in.";
    header('Refresh: 3; URL=login.php');
    exit;
}

// Fetch student_id from the database based on the logged-in user's user_id
if (!isset($_SESSION['session_studentid'])) {
    $stmt = $conn->prepare("SELECT student_id FROM student WHERE user_id = ?");
    $stmt->bind_param("i", $_SESSION['session_userid']);
    $stmt->execute();
    $result = $stmt->get_result();
    $student_data = $result->fetch_assoc();

    if ($student_data) {
        $_SESSION['session_studentid'] = $student_data['student_id'];
    } else {
        $error_message="Student record not found for this user.";
    }
}
$student_id = $_SESSION['session_studentid'];

// Fetch student details, courses, and classes
$query = "
    SELECT 
        s.student_id,
        s.student_name,
        s.student_email,
        s.student_phone,
        u.admission_number,
        d.department_name
    FROM student s
    JOIN user u ON s.user_id = u.user_id
    JOIN department d ON s.department_id = d.department_id
    WHERE s.student_id = ?
";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();
$student = $result->fetch_assoc();

// Fetch courses with status
$course_query = "
    SELECT co.course_name, st.status_name 
    FROM student_course sc
    JOIN course co ON sc.course_id = co.course_id
    JOIN status st ON co.status_id = st.status_id
    WHERE sc.student_id = ?
";
$stmt = $conn->prepare($course_query);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$course_result = $stmt->get_result();

// Fetch classes with their respective courses
$class_query = "
    SELECT c.class_name, co.course_name
    FROM student_class sc
    JOIN class c ON sc.class_id = c.class_id
    JOIN course co ON c.course_id = co.course_id
    WHERE sc.student_id = ?
";
$stmt = $conn->prepare($class_query);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$class_result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Details</title>
    <link rel="stylesheet" href="student_style.css">
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
            display: flex; /* Enables Flexbox */
            justify-content: center; /* Centers horizontally */
            align-items: center; /* Centers vertically */
            height: 100vh; /* Makes body take full viewport height */
        }

        .main-container {
            width: 100%;
            max-width: 1000px;
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            overflow: hidden; /* Prevents content from overflowing */
            margin-top: 100px;
        }

        h1 {
            text-align: center;
            color: #0d2444;
        }


        .student-container {
            position: relative; /* Makes child elements position relative to this */
            background: #2c6485;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
            color: white;
            border: 2px solid #ecdfce;
        }

        .change-password-btn {
            position: absolute;
            top: 15px;
            right: 20px;
            background-color: #ffffff;
            color: #2c6485;
            padding: 8px 12px;
            border-radius: 5px;
            font-size: 14px;
            font-weight: bold;
            text-decoration: none;
            border: 2px solid #2c6485;
            transition: 0.3s;
        }

        .change-password-btn:hover {
            background-color: #ecdfce;
            color: #2c6485;
            border-color: #ecdfce;
        }


        .student-container h2, .student-container p {
            color: #f1eaeb;
        }

        .courses-container, .classes-container {
            background: #c3d9e5;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
            overflow-y: auto;
            max-height: 150px;
            border: 1px solid #ecdfce;
        }

        /* NEW: Add a scrollable wrapper for the table */
        .table-wrapper {
            max-height: 250px; /* Set the max height */
            overflow-y: auto; /* Enables vertical scrolling */
            overflow-x: hidden; /* Prevents horizontal scroll */
        }

        /* Make sure table headers stick */
        table {
            width: 100%;
            border-collapse: collapse;
        }

        /* Keep table header fixed while scrolling */
        thead {
            position: sticky;
            top: 0;
            background-color: #2c6485; /* Keep the header blue */
            color: white;
            z-index: 2;
        }

        /* Style the table rows */
        th, td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        th {
            background-color: #2c6485;
            color: white;
        }

        /* Ensure rows alternate colors */
        tr:nth-child(even) {
            background-color: white;
        }

        tr:nth-child(odd) {
            background-color: white;
        }

        /* Optional: Hover effect */
        tr:hover {
            background-color: #f5f5f5;
        }

        .status-icon {
            font-size: 0.875rem;
            padding: 4px 8px;
            border-radius: 5px;
            display: inline-block;
            font-weight: bold;
        }

        .status-green {
            background-color: rgba(34, 197, 94, 0.10);
            color: rgb(34, 197, 94);
        }

        .status-yellow {
            background-color: rgba(255, 200, 35, 0.1);
            color: rgb(234, 179, 8);
        }

        .status-red {
            background-color: rgba(239, 68, 68, 0.10);
            color: rgb(239, 68, 68);
        }

        .status-blue {
            background-color: rgba(59, 130, 246, 0.10);
            color: rgb(59, 130, 246);
        }

        .error-modal {
            display: flex;
            justify-content: center;
            align-items: center;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
        }

        .error-modal-content {
            background-color: white;
            padding: 2rem;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.3);
            max-width: 500px;
            width: 90%;
        }

        .error-modal-content h2 {
            color: #d8000c;
            margin-bottom: 1rem;
        }

        .error-modal-content p {
            font-size: 1rem;
            margin-bottom: 1.5rem;
        }

        .error-modal-content button {
            background-color: #2c6485;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            font-size: 1rem;
            cursor: pointer;
        }

        .error-modal-content button:hover {
            background-color: #22303f;
        }


    </style>
</head>
<body>
<?php include('student_header.php'); ?>
    <main class="main-container">
        <h1>Student Details</h1>
        <?php if (!empty($error_message)): ?>
            <div class="error-modal" id="errorModal" style="display: flex;">
                <div class="error-modal-content">
                    <h2>Error</h2>
                    <p><?php echo htmlspecialchars($error_message); ?></p>
                    <button onclick="window.location.href='student.php'">Go Back</button>
                </div>
            </div>
        <?php else: ?>
        <div class="student-container">
            <h2><?php echo htmlspecialchars($student['student_name']); ?></h2>
            <a href="change_password_form.php" class="change-password-btn">Change Password</a>
            <p><strong>Admission No:</strong> <?php echo htmlspecialchars($student['admission_number']); ?></p>
            <p><strong>Email:</strong> <?php echo htmlspecialchars($student['student_email']); ?></p>
            <p><strong>Phone:</strong> <?php echo htmlspecialchars($student['student_phone']); ?></p>
            <p><strong>Department:</strong> <?php echo htmlspecialchars($student['department_name']); ?></p>
        </div>
        
        <!-- Courses Section -->
        <div class="courses-container">
            
            <table>
                <tr>
                    <th>Course</th>
                    <th>Status</th>
                </tr>
                <?php while ($course = $course_result->fetch_assoc()): ?>
                    <?php 
                        $status_class = 'status-blue';
                        if (strtolower($course['status_name']) == 'start') $status_class = 'status-green';
                        elseif (strtolower($course['status_name']) == 'in-progress') $status_class = 'status-yellow';
                        elseif (strtolower($course['status_name']) == 'ended') $status_class = 'status-red';
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($course['course_name']); ?></td>
                        <td><span class="status-icon <?php echo $status_class; ?>"><?php echo htmlspecialchars($course['status_name']); ?></span></td>
                    </tr>
                <?php endwhile; ?>
            </table>
        </div>

        <!-- Classes Section -->
        <div class="classes-container">
            
            <table>
                <tr>
                    <th>Class</th>
                    <th>Course</th>
                </tr>
                <?php while ($class = $class_result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($class['class_name']); ?></td>
                        <td><?php echo htmlspecialchars($class['course_name']); ?></td>
                    </tr>
                <?php endwhile; ?>
            </table>
        </div>
        <?php endif; ?>
    </main>
</body>
</html>
