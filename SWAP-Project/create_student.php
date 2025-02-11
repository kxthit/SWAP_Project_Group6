<?php

// Database connection
include 'db_connection.php';
include 'csrf_protection.php';

$error_message = ""; // Variable to store error messages
$error_message_logout = "";

// Check if the user is authenticated
if (!isset($_SESSION['session_userid']) || !isset($_SESSION['session_roleid'])) {
    $error_message_logout = "Session expired or unauthorized access. Please log in.";
}

// Validate CSRF token on POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token'])) {
    $error_message = "Invalid CSRF token. Please reload the page and try again.";
}

// Check the user's role
$role_id = $_SESSION['session_roleid'];

if ($role_id == 1) {
    // Admin Role: Fetch all departments
    $departments_query = "SELECT department_id, department_name FROM department";
    $departments_result = mysqli_query($conn, $departments_query);
} elseif ($role_id == 2) {
    // Faculty Role: Fetch faculty's department using faculty_id from session
    if (!isset($_SESSION['session_facultyid'])) {
        $error_message_logout = "Faculty ID not found in session.";
    } else {
        $faculty_id = $_SESSION['session_facultyid'];

        $stmt = $conn->prepare("
            SELECT d.department_id, d.department_name 
            FROM department d 
            JOIN faculty f ON d.department_id = f.department_id 
            WHERE f.faculty_id = ?
        ");
        $stmt->bind_param("i", $faculty_id);
        $stmt->execute();
        $faculty_department_result = $stmt->get_result();
        $faculty_department = $faculty_department_result->fetch_assoc();
        $stmt->close();
    }
}

// Initialize error message array
$errors = [];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($error_message)) {
    // Extract form data
    $student_name = trim($_POST['student_name'] ?? '');
    $admission_number = trim($_POST['admission_number'] ?? '');
    $student_phone = trim($_POST['student_phone'] ?? '');
    $student_email = trim($_POST['student_email'] ?? '');
    $hashed_password = trim($_POST['hashed_password'] ?? '');
    $department_id = trim($_POST['department_id'] ?? '');

    // Validate input data
    // Check for empty fields
    if (empty($student_name) || empty($admission_number) || empty($student_phone) || empty($student_email) || empty($hashed_password) || empty($department_id)) {
        $errors[] = "All fields must be filled up.";
    }

    // Validate student name (no special characters allowed)
    if (!empty($student_name) && !preg_match('/^[a-zA-Z\s]+$/', $student_name)) {
        $errors[] = "Invalid name. Only letters and spaces are allowed.";
    }

    // Validate admission number (7 numbers followed by 1 uppercase letter)
    if (!empty($admission_number) && !preg_match('/^\d{7}[A-Z]$/', $admission_number)) {
        $errors[] = "Invalid admission number. It should be 7 digits followed by an uppercase letter.";
    }

    // Validate phone number (exactly 8 digits)
    if (!empty($student_phone) && !preg_match('/^\d{8}$/', $student_phone)) {
        $errors[] = "Invalid phone number. It should be exactly 8 digits.";
    }

    // Validate email (must end with allowed domains)
    $allowed_domains = ['@gmail.com', '@yahoo.com', '@hotmail.com', '@outlook.com'];
    if (!empty($student_email)) {
        $email_valid = false;
        foreach ($allowed_domains as $domain) {
            if (str_ends_with($student_email, $domain)) {
                $email_valid = true;
                break;
            }
        }
        if (!$email_valid) {
            $errors[] = "Invalid email. It must end with @gmail.com, @yahoo.com, @hotmail.com, or @outlook.com.";
        }
    }

    // Validate password (at least one uppercase, one lowercase, and one number)
    if (!empty($hashed_password) && !preg_match('/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d).{8,}$/', $hashed_password)) {
        $errors[] = "Password must be at least 8 characters, containing one uppercase letter, one lowercase letter, and one number.";
    }

    // If no errors, process and store data
    if (empty($errors)) {
        if ($role_id == 2 && isset($faculty_department)) {
            // Faculty role: Auto-assign department
            $department_id = $faculty_department['department_id'];
        }

        $_SESSION['student_data'] = [
            'admission_number' => htmlspecialchars($admission_number),
            'student_name' => htmlspecialchars($student_name),
            'student_email' => htmlspecialchars($student_email),
            'student_phone' => htmlspecialchars($student_phone),
            'hashed_password' => htmlspecialchars($hashed_password), // Secure hashing
            'department_id' => htmlspecialchars($department_id)
        ];

        // Redirect user to the next step
        header("Location: create_student_course.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Student</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="css/create_student.css">
</head>

<body>
    <?php include('admin_header.php'); ?>
    <main class="main-content">

        <div class="page-wrapper">
            <!-- Top Section with Back Button -->
            <div class="top-section">
                <a href="student.php" class="back-button">← Back</a>
            </div>
            <?php if (!empty($error_message)): ?>
                <div class="error-modal" id="errorModal" style="display: flex;">
                    <div class="error-modal-content">
                        <h2>Error</h2>
                        <p><?php echo htmlspecialchars($error_message); ?></p>
                        <form method="POST" action="student.php">
                            <button type="submit">Go Back</button>
                        </form>
                    </div>
                </div>
            <?php else: ?>
                <?php if (!empty($error_message_logout)): ?>
                    <div class="error-modal" id="errorModal" style="display: flex;">
                        <div class="error-modal-content">
                            <h2>Error</h2>
                            <p><?php echo htmlspecialchars($error_message_logout); ?></p>
                            <form method="POST" action="logout.php">
                                <button type="submit">Go Back</button>
                            </form>
                        </div>
                    </div>
                <?php else: ?>
                    <main class="main-content">
                        <h1>Step 1: Add New Student</h1>
                        <div class="form-container">
                            <div class="form-card">
                                <h2>Student Details</h2>
                                <?php if (!empty($errors)): ?>
                                    <div class="error-messages">
                                        <ul>
                                            <?php foreach ($errors as $error): ?>
                                                <li><?php echo htmlspecialchars($error); ?></li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                <?php endif; ?>
                                <form action="" method="POST">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                    <!-- Row with Photo and Table -->
                                    <div class="form-row">
                                        <!-- Left Column: Photo -->
                                        <div class="form-group photo-upload">
                                            <label for="profile_picture">Profile Picture *</label>
                                            <div class="photo-box">
                                                <p>Drag and drop or click here to select file</p>
                                            </div>
                                        </div>
                                        <!-- Right Column: Table -->
                                        <table class="details-table">
                                            <tr>
                                                <td>
                                                    <label for="student_name">Full Name *</label>
                                                    <input type="text" id="student_name" name="student_name" placeholder="Samantha William" value="<?php echo htmlspecialchars($student_name ?? ''); ?>" required>
                                                </td>
                                                <td>
                                                    <label for="admission_number">Admission No. *</label>
                                                    <input type="text" id="admission_number" name="admission_number" placeholder="2301118B" value="<?php echo htmlspecialchars($admission_number ?? ''); ?>" required>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>
                                                    <label for="student_phone">Phone *</label>
                                                    <input type="text" id="student_phone" name="student_phone" placeholder="34567890" value="<?php echo htmlspecialchars($student_phone ?? ''); ?>" required>
                                                </td>
                                                <td>
                                                    <label for="student_email">Email *</label>
                                                    <input type="email" id="student_email" name="student_email" placeholder="william@mail.com" value="<?php echo htmlspecialchars($student_email ?? ''); ?>" required>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>
                                                    <label for="hashed_password">Password *</label>
                                                    <input type="password" id="hashed_password" name="hashed_password" required>
                                                </td>
                                                <td></td>
                                            </tr>
                                            <tr>
                                                <td colspan="2">
                                                    <label for="department_id">Department *</label>
                                                    <select id="department_id" name="department_id" required>
                                                        <?php if ($role_id == 1): ?>
                                                            <!-- Admin: Show all departments -->
                                                            <option value="">Select Department</option>
                                                            <?php while ($row = $departments_result->fetch_assoc()): ?>
                                                                <option value="<?php echo $row['department_id']; ?>">
                                                                    <?php echo htmlspecialchars($row['department_name']); ?>
                                                                </option>
                                                            <?php endwhile; ?>
                                                        <?php elseif ($role_id == 2): ?>
                                                            <!-- Faculty: Auto-select faculty's department -->
                                                            <option value="<?php echo $faculty_department['department_id']; ?>" selected>
                                                                <?php echo htmlspecialchars($faculty_department['department_name']); ?>
                                                            </option>
                                                        <?php endif; ?>
                                                    </select>
                                                </td>
                                            </tr>
                                        </table>
                                    </div>
                                    <button type="submit">Next</button>
                                </form>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
                    </main>
        </div>
        <script>
            // Select elements
            const photoBox = document.getElementById('photo-box');
            const fileInput = document.getElementById('file-input');

            // Trigger the file input when the photo box is clicked
            photoBox.addEventListener('click', function() {
                fileInput.click();
            });

            // When a file is selected, display the file name
            fileInput.addEventListener('change', function(event) {
                const file = event.target.files[0];
                if (file) {
                    const fileName = file.name;
                    photoBox.innerHTML = `<p>Selected file: ${fileName}</p>`;
                }
            });

            // Drag and Drop functionality
            photoBox.addEventListener('dragover', function(event) {
                event.preventDefault(); // Prevent default behavior (open as link for some browsers)
                photoBox.style.borderColor = '#6c63ff'; // Optional: Change border color when dragging over
            });

            photoBox.addEventListener('dragleave', function() {
                photoBox.style.borderColor = '#ccc'; // Reset border color when drag leaves
            });

            photoBox.addEventListener('drop', function(event) {
                event.preventDefault(); // Prevent default behavior
                const file = event.dataTransfer.files[0]; // Get the dropped file

                if (file) {
                    const fileName = file.name;
                    photoBox.innerHTML = `<p>Dropped file: ${fileName}</p>`;
                    // You can trigger the file input change here as well if needed
                    fileInput.files = event.dataTransfer.files;
                }
            });
        </script>
    </main>
</body>

</html>