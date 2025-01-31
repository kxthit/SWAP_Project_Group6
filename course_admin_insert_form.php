<?php
include('session_management.php');
include('db_connection.php');

// Check if the session 'course_data' is set, initialize if not.
if (!isset($_SESSION['course_data'])) {
    $_SESSION['course_data'] = []; // Initialize it as an empty array if not set.
}

// Check if the user is authenticated
if (!isset($_SESSION['session_userid']) || !isset($_SESSION['session_roleid'])) {
    echo "<h2>Unauthorized access. Please log in.</h2>";
    header('Refresh: 3; URL=login.php');
    exit;
}

// Check if the user is either Admin or Faculty (role_id 1 or 2)
if ($_SESSION['session_roleid'] != 1 && $_SESSION['session_roleid'] != 2) {
    echo "<h2>You do not have permission to access this page.</h2>";
    exit;
}

elseif ($_SESSION['session_roleid'] == 2) {  // Faculty
    echo "<h2>You do not have permission use this form as Faculty. Please use your assigned Insert Form instead.</h2>";
    header('Refresh: 3; URL=course_insert_form.php');
}

// Fetch departments for dropdown menu
if ($_SESSION['session_roleid'] == 1) {
    $department_query = "SELECT * FROM Department";
    $department_stmt = $conn->prepare($department_query);
    $department_stmt->execute();
    $department_result = $department_stmt->get_result();

    // Fetch all faculties for Admin
    $faculty_query = "SELECT * FROM Faculty";
    $faculty_stmt = $conn->prepare($faculty_query);
    $faculty_stmt->execute();
    $faculty_result = $faculty_stmt->get_result();
} 

else {
    // Handle cases where the user is neither faculty nor admin
    echo "<h2>Unauthorized access. Invalid role.</h2>";
    exit;
}

// Query to get all status options
$status_query = "SELECT * FROM status";
$status_result = mysqli_query($conn, $status_query);

// If the return button was clicked, clear session data and redirect
if (isset($_POST['return_button'])) {
    unset($_SESSION['course_data']); // Clear session data
    unset($_SESSION['error_message']); // Clear session data
    header("Location: courses.php");
    exit;
}

// Retrieve any errors and form data from the session
$errors = isset($_SESSION['error_message']) ? $_SESSION['error_message'] : [];
$form_data = isset($_SESSION['course_data']) ? $_SESSION['course_data'] : [];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Course</title>
    <link rel="stylesheet" href="css/course_insertform.css">
</head>
<body>
<?php include('admin_header.php') ;?>
    <h1 class="title">Add New Course</h1>

    <div class="form-container">
        <!-- Display error message if any -->
        <?php if (isset($errors) && !empty($errors)): ?>
            <div class="error-container">
                <p style="color: red;">
                    <!-- If the error message is an array, join the elements into a single string -->
                    <?php 
                    echo is_array($errors) ? htmlspecialchars(implode(", ", $errors)) : htmlspecialchars($errors);
                    ?>
                </p>
                <?php unset($_SESSION['error_message']); ?> <!-- Clear after displaying -->
            </div>
        <?php endif; ?>

        <form action="course_admin_insert.php" method="POST" onsubmit="return handleFormSubmit(event);">
            <label for="course_name">Course Name:</label>
            <input type="text" id="course_name" name="course_name" class="form-input"
                   value="<?php echo isset($form_data['course_name']) ? htmlspecialchars($form_data['course_name'], ENT_QUOTES, 'UTF-8') : ''; ?>" required>

            <label for="course_code">Course Code:</label>
            <input type="text" id="course_code" name="course_code" class="form-input"
                   value="<?php echo isset($form_data['course_code']) ? htmlspecialchars($form_data['course_code'], ENT_QUOTES, 'UTF-8') : ''; ?>" required>

            <label for="start_date">Start Date:</label>
            <input type="date" id="start_date" name="start_date" class="form-input"
                   value="<?php echo isset($form_data['start_date']) ? $form_data['start_date'] : ''; ?>" required>

            <label for="end_date">End Date:</label>
            <input type="date" id="end_date" name="end_date" class="form-input"
                   value="<?php echo isset($form_data['end_date']) ? $form_data['end_date'] : ''; ?>" required>

            <label for="status_id">Status:</label>
            <select id="status_id" name="status_id" class="form-select" required>
                <option value="" disabled>--- Select Status ---</option>
                <?php
                while ($status = mysqli_fetch_assoc($status_result)) {
                    $selected = (isset($form_data['status_id']) && $form_data['status_id'] == $status['status_id']) ? 'selected' : '';
                    echo "<option value='{$status['status_id']}' $selected>".htmlspecialchars($status['status_name'], ENT_QUOTES, 'UTF-8')."</option>";
                }
                ?>
            </select>
            
            <label for="department_id">Department:</label>
            <select id="department_id" name="department_id" class="form-select" required>
                <option value="" disabled>--- Select Department ---</option>
                <?php
                // Fetch and display departments (Admin can choose any, Faculty see only their own)
                while ($department = mysqli_fetch_assoc($department_result)) {
                    $selected = (isset($form_data['department_id']) && $form_data['department_id'] == $department['department_id']) ? 'selected' : '';
                    echo "<option value='{$department['department_id']}' $selected>".htmlspecialchars($department['department_name'], ENT_QUOTES, 'UTF-8')."</option>";
                }
                ?>
            </select>

            <?php if ($_SESSION['session_roleid'] == 1): ?>
                <!-- Admin: Add Faculty dropdown -->
                <label for="faculty_id">Faculty:</label>
                <select id="faculty_id" name="faculty_id" class="form-select" required>
                    <option value="" disabled>--- Select Faculty ---</option>
                    <?php
                    while ($faculty = mysqli_fetch_assoc($faculty_result)) {
                        $selected = (isset($form_data['faculty_id']) && $form_data['faculty_id'] == $faculty['faculty_id']) ? 'selected' : '';
                        echo "<option value='{$faculty['faculty_id']}' $selected>".htmlspecialchars($faculty['faculty_name'], ENT_QUOTES, 'UTF-8')."</option>";
                    }
                    ?>
                </select>
            <?php endif; ?>

            <!-- Button Container for Proper Alignment -->
            <div class="button-container">
                <button type="button" name="return_button" class="return-button">Return</button>
                <?php // If the return button was clicked, clear session data and redirect
                if (isset($_POST['return_button'])) {
                    unset($_SESSION['course_data']); // Clear session data
                    unset($_SESSION['error_message']); // Clear session data
                    header("Location: courses.php");
                    exit;
                } ?>
                
                <button type="submit" class="form-button">Add Course</button>
            </div>
        </form>
    </div>
</body>
</html>
