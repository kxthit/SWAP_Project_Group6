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
        die("Student record not found for this user.");
    }
}
$student_id = $_SESSION['session_studentid'];

$query = "
    SELECT 
        s.student_id,
        s.student_name,
        s.student_email,
        s.student_phone,
        u.admission_number,
        c.class_name,
        d.department_name,
        GROUP_CONCAT(CONCAT(co.course_name, ' [', st.status_name, ']') ORDER BY co.course_name SEPARATOR ', ') AS courses
    FROM student s
    JOIN user u ON s.user_id = u.user_id
    JOIN student_class sc ON s.student_id = sc.student_id
    JOIN class c ON sc.class_id = c.class_id
    JOIN department d ON s.department_id = d.department_id
    JOIN student_course sc2 ON s.student_id = sc2.student_id
    JOIN course co ON sc2.course_id = co.course_id
    JOIN status st ON co.status_id = st.status_id
    WHERE s.student_id = ?
    GROUP BY s.student_id
";

// Prepare and execute the query
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();
$student = $result->fetch_assoc();
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Details</title>
    <link rel="stylesheet" href="student_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* General Reset */
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }

        h1 {
            text-align: center;
            color: #0d2444;
        }

        /* Student Card Fixed Size & Centered */
        .student-card {
            width: 800px; /* Fixed width */
            min-height: 400px; /* Adjust as needed */
            margin: 0 auto; /* Center the card */
            background: #2c6485;
            border-radius: 10px;
            padding: 20px;
            color: white;
            border: 2px solid #ecdfce;
        }


        .student-card h2 {
            color: #f1eaeb;
            margin-bottom: 10px;
        }

        .student-card p {
            margin: 5px 0;
            font-size: 16px;
            color: #ecdfce;
        }

        .badges {
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin-top: 10px;
        }

        .status-icon {
            font-size: 0.875rem;
            line-height: 1.25rem;
            padding: 4px 0.75rem;
            border-radius: 0.375rem;
            border: 1px solid transparent;
            text-align: center;
            display: inline-block;
            font-weight: bold;
        }

        .status-green {
            background-color: rgba(34, 197, 94, 0.10);
            color: rgb(34, 197, 94);
            border-color: rgb(34, 197, 94);
        }

        .status-yellow {
            background-color: rgba(255, 200, 35, 0.1);
            color: rgb(234, 179, 8);
            border-color: rgb(234, 179, 8);
        }

        .status-red {
            background-color: rgba(239, 68, 68, 0.10);
            color: rgb(239, 68, 68);
            border-color: rgb(239, 68, 68);
        }

        .status-blue {
            background-color: rgba(59, 130, 246, 0.10);
            color: rgb(59, 130, 246);
            border-color: rgb(59, 130, 246);
        }

        .class-item {
            font-size: 16px;
            font-weight: bold;
            color: white;
            background: rgba(255, 255, 255, 0.2); /* Light transparent background */
            padding: 6px 12px;
            border-radius: 6px;
            display: inline-block;
            border: 1px solid white;
        }


        .class-item {
            font-size: 16px;
            font-weight: bold;
            color: white;
            background: rgba(255, 255, 255, 0.2);
            padding: 6px 12px;
            border-radius: 6px;
            display: inline-block;
            border: 1px solid white;
        }

        /* Ensure Edit Button is Fixed in Position */
        .action-buttons {
            text-align: center;
            margin-top: 20px;
        }

        .action-buttons a {
            padding: 10px 20px;
            text-decoration: none;
            color: white;
            background-color: #22303f;
            border-radius: 5px;
            font-size: 1rem;
        }

        .action-buttons a:hover {
            background-color: #10171e;
        }

        .delete-button {
            background-color: #dc3545;
        }

        .delete-button:hover {
            background-color: #b52b3a;
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
            background-color: #f4f4f4;
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
            background-color: #2c6485;
            color: white;
            text-decoration: none;
            border-radius: 4px;
        }

        .modal-buttons a:hover {
            background-color: #22303f;
        }

        .modal-buttons .btn-cancel {
            background-color: #6c757d;
        }

        .modal-buttons .btn-cancel:hover {
            background-color: #545b62;
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
<main class="main-content">
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
    <div class="container">
        <div class="student-card">
            <h2><?php echo htmlspecialchars($student['student_name']); ?></h2>
            <p><strong>Admission No:</strong> <?php echo htmlspecialchars($student['admission_number']); ?></p>
            <p><strong>Email:</strong> <?php echo htmlspecialchars($student['student_email']); ?></p>
            <p><strong>Phone:</strong> <?php echo htmlspecialchars($student['student_phone']); ?></p>
            <p><strong>Department:</strong> <?php echo htmlspecialchars($student['department_name']); ?></p>

            <!-- Courses Section -->
            <h3>Courses:</h3>
            <div class="badges">
                <?php 
                $courses = explode(',', $student['courses']);
                foreach ($courses as $course): 
                    $course_parts = explode(' [', $course);
                    $course_name = trim($course_parts[0]);
                    $course_status = isset($course_parts[1]) ? rtrim($course_parts[1], ']') : '';

                    $status_class = 'status-blue';
                    if (strtolower($course_status) == 'start') $status_class = 'status-green';
                    elseif (strtolower($course_status) == 'in-progress') $status_class = 'status-yellow';
                    elseif (strtolower($course_status) == 'ended') $status_class = 'status-red';
                ?>
                    <p>
                        <strong><?php echo htmlspecialchars($course_name); ?></strong> 
                        <span class="status-icon <?php echo $status_class; ?>">
                            <?php echo htmlspecialchars($course_status); ?>
                        </span>
                    </p>
                <?php endforeach; ?>
            </div>

            <!-- Classes Section -->
            <h3>Classes:</h3>
            <div class="class-list">
                <?php 
                $classes = explode(',', $student['class_name']);
                foreach ($classes as $class): ?>
                    <p class="class-item"><?php echo htmlspecialchars(trim($class)); ?></p>
                <?php endforeach; ?>
            </div>

            <!-- Change Password Button -->
            <div class="action-buttons">
                <a href="change_password_form.php" class="change-password-button">Change Password</a>
            </div>
        </div>

    <?php endif; ?>
</main>

</body>
</html>