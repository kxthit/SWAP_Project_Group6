<?php
// Include the database connection
include 'db_connection.php';

session_start();

// Check if the user is authenticated
if (!isset($_SESSION['session_userid']) || !isset($_SESSION['session_role'])) {
    echo "<script>
            alert('Unauthorized user. Redirecting To Login.');
            window.location.href = 'login.php'; // Redirect to homepage or any page you want
          </script>";
    exit;
}

$role_id = $_SESSION['session_role']; // Get role ID from the session
$user_id = $_SESSION['session_userid']; // Get user ID from the session

// Fetch departments and classes for filter options
$departments_query = "SELECT * FROM department";
$departments_result = mysqli_query($conn, $departments_query);

$classes_query = "SELECT * FROM class";
$classes_result = mysqli_query($conn, $classes_query);


// Build the SQL query dynamically based on user input for search and filters
$search_term = isset($_GET['search']) ? $_GET['search'] : '';
$department_filter = isset($_GET['department']) ? $_GET['department'] : '';
$class_filter = isset($_GET['class']) ? $_GET['class'] : '';

// Base query for fetching students
$query = "
    SELECT 
        student.student_id, 
        student.student_name, 
        student.profile_picture, 
        user.admission_number, 
        department.department_name, 
        class.class_name
    FROM student 
    JOIN user ON student.user_id = user.user_id
    JOIN student_course sc ON student.student_id = sc.student_id
    JOIN class ON sc.course_id = class.course_id
    JOIN department ON student.department_id = department.department_id
    WHERE student.student_name LIKE ?
";

$params = ["%$search_term%"];

// If the user is a faculty (role_id == 2), restrict students to their courses
if ($role_id == 2) {
    $query .= "
        AND sc.course_id IN (
            SELECT fc.course_id
            FROM faculty_course fc
            JOIN faculty f ON fc.faculty_id = f.faculty_id
            WHERE f.user_id = ?
        )
    ";

    $params[] = $user_id;
}


// Apply department filter if provided
if ($department_filter) {
    $query .= " AND department.department_id = ?";
    $params[] = $department_filter;
}

// Apply class filter if provided
if ($class_filter) {
    $query .= " AND class.class_id = ?";
    $params[] = $class_filter;
}

$stmt = $conn->prepare($query);
$stmt->bind_param(str_repeat('s', count($params)), ...$params);
$stmt->execute();
$result = $stmt->get_result();

// Check if the query was successful
if ($result) {
    $students = mysqli_fetch_all($result, MYSQLI_ASSOC);
} else {
    echo "<h2>Error fetching students data: " . mysqli_error($conn) . "</h2>";
    $students = [];
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Josefin+Sans:wght@300;400;700&family=Source+Sans+Pro:wght@300;400;700&display=swap" rel="stylesheet">
    <title>Dashboard</title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* Container for the entire section */

        .students-container {
            display: flex;
            justify-content: center;
            align-items: center;
            flex-direction: column;
            margin: 20px auto;
            max-width: 1500px;
            /* Increased max-width for a bigger container */
            padding: 30px;
            background-color: #f0f8ff;
            /* Light blue background to differentiate the container */
            border-radius: 15px;
            /* Rounded corners for the container */
            box-shadow: 0px 12px 40px rgba(0, 0, 0, 0.2);
            /* Stronger shadow for a floating effect */
            transition: box-shadow 0.3s ease-in-out, transform 0.3s ease-in-out;
            /* Smooth transition for hover effect */
        }

        /* Hover effect for container */
        .students-container:hover {
            box-shadow: 0px 18px 50px rgba(0, 0, 0, 0.25);
            transform: scale(1.01);
            /* Slight zoom effect on hover */
        }

        /* Adjust height and make only the student card section scrollable */
        .students {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            justify-content: center;
            padding: 5px;
            max-height: 500px;
            /* Set a fixed height */
            overflow-y: auto;
            /* Enables vertical scrolling */
            width: 100%;
            /* Ensure it takes full width */
        }


        /* Student card style (unchanged) */
        .student-card-link {
            text-decoration: none;
            color: inherit;
        }

        .student-card {
            width: 220px;
            /* Slightly bigger cards */
            height: 240px;
            /* Increased height for more content space */
            background-color: #2c6485;
            border-radius: 20px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            text-align: center;
            padding: 20px;
            transition: transform 0.2s ease-in-out;
            border: 2px solid #ecdfce;
            /* Add border with desired color */
        }

        .student-card:hover {
            transform: scale(1.05);
            background-color: rgb(31, 64, 100);
            box-shadow: 0 0 15px 4px rgb(95, 142, 174);
            /* Glowing effect */
        }

        .student-profile-pic {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 10px;
        }

        .student-card h3 {
            font-size: 18px;
            margin: 10px 0;
            color: #f1eaeb;
            font-family: 'Source Sans Pro', sans-serif;
            text-transform: uppercase;
            font-weight: 700;
        }

        .student-card p {
            font-size: 14px;
            color: #ecdfce;
            font-family: 'Source Sans Pro', sans-serif;
            font-weight: 700;
        }


        /* Filters Container */
        .filters {
            margin-bottom: 30px;
            display: flex;
            gap: 20px;
            justify-content: center;
            align-items: center;
        }

        /* Styling for the search bar */
        .filters input[type="text"] {
            width: 350px;
            /* Slightly wider for a more balanced look */
            padding: 10px;
            border-radius: 25px;
            /* More rounded corners */
            border: 1px solid #ccc;
            font-size: 14px;
            /* Slightly bigger text for readability */
            background-color: #F5EFEB;
            /* Light background for contrast */
            color: #333;
            /* Darker text for better contrast */
            transition: all 0.3s ease-in-out;
            /* Smooth transition on focus */
            border-color: #C8D9E6;
        }

        .filters input[type="text"]:focus {
            outline: none;
            border-color: #38caef;
            /* Light blue border when focused */
            background-color: #fff;
            /* White background on focus */
            box-shadow: 0 0 10px rgba(152, 185, 255, 0.6);
            /* Soft shadow on focus */
        }

        /* Styling for the dropdown filters */
        .filters select {
            padding: 10px;
            font-size: 14px;
            /* Slightly bigger text */
            border-radius: 25px;
            /* Rounded corners */
            border: 1px solid #ccc;
            height: 40px;
            /* Increased height for a more modern look */
            background-color: #F5EFEB;
            color: #333;
            transition: all 0.3s ease-in-out;
            cursor: pointer;
            /* Cursor changes to pointer */
            border-color: #C8D9E6;
        }

        .filters select:focus {
            outline: none;
            border-color: #38caef;
            background-color: #fff;
            box-shadow: 0 0 10px rgba(152, 185, 255, 0.6);
            /* Soft shadow on focus */
        }

        /* Optional: Add styling for the dropdown options */
        .filters select option {
            padding: 12px;
            background-color: #fff;
            color: #333;
        }

        .filters button {
            padding: 8px 20px;
            /* Smaller button size */
            border-radius: 50px;
            cursor: pointer;
            border: 0;
            background-color: #10171e;
            /* Dark blue/charcoal */
            color: white;
            box-shadow: rgb(0 0 0 / 10%) 0 0 8px;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            font-size: 12px;
            transition: all 0.5s ease;
        }

        .filters button:hover {
            letter-spacing: 3px;
            background-color: #FCD34D;
            /* Warm gold */
            color: black;
            box-shadow: rgb(252 211 77) 0px 7px 29px 0px;
            /* Soft gold glow */
        }

        .filters button:active {
            letter-spacing: 3px;
            background-color: #FCD34D;
            /* Warm gold */
            color: black;
            box-shadow: rgb(252 211 77) 0px 0px 0px 0px;
            transform: translateY(5px);
            transition: 100ms;
        }
    </style>
</head>

<body>
    <?php include('admin_header.php'); ?>

    <main class="main-content">

        <!-- Filters Section -->
        <section class="filters">
            <form method="get" action="">
                <input type="text" name="search" placeholder="Search by name" value="<?php echo htmlspecialchars($search_term); ?>">

                <select name="department">
                    <option value="">Search Department</option>
                    <?php while ($department = mysqli_fetch_assoc($departments_result)): ?>
                        <option value="<?php echo $department['department_id']; ?>" <?php echo ($department_filter == $department['department_id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($department['department_name']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>

                <select name="class">
                    <option value="">Search Class</option>
                    <?php while ($class = mysqli_fetch_assoc($classes_result)): ?>
                        <option value="<?php echo $class['class_id']; ?>" <?php echo ($class_filter == $class['class_id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($class['class_name']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>

                <button type="submit">Apply</button>
            </form>
        </section>

        <!-- Container for Student Cards -->
        <section class="students-container">
            <div class="students">
                <?php foreach ($students as $student): ?>
                    <a href="display_grades.php?student_id=<?php echo urlencode($student['student_id']); ?>" class="student-card-link">
                        <div class="student-card">
                            <img src="<?php echo htmlspecialchars($student['profile_picture']); ?>" alt="Profile Picture" class="student-profile-pic">
                            <h3><?php echo htmlspecialchars($student['student_name']); ?></h3>
                            <p>Admission No: <?php echo htmlspecialchars($student['admission_number']); ?></p>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>
    </main>



</body>

</html>