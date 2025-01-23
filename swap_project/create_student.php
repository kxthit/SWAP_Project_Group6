<?php
session_start();

// Check if the user is authenticated
if (!isset($_SESSION['session_userid']) || !isset($_SESSION['session_role'])) {
    echo "<h2>Unauthorized access. Please log in.</h2>";
    header('Refresh: 3; URL=login.php');
    exit;
}

// Database connection
include 'db_connection.php';

// Fetch departments for dropdown
$stmt = $conn->prepare("SELECT department_id, department_name FROM department");
$stmt->execute();
$departments_result = $stmt->get_result();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['department_id']) && $_POST['department_id'] !== '') {
        // Save department_id into session
        $_SESSION['student_department_id'] = $_POST['department_id'];

      
        $_SESSION['student_data'] = [
            'admission_number' => htmlspecialchars($_POST['admission_number']),
            'student_name' => htmlspecialchars($_POST['student_name']),
            'student_email' => htmlspecialchars($_POST['student_email']),
            'student_phone' => htmlspecialchars($_POST['student_phone']),
            'hashed_password' => password_hash($_POST['hashed_password'], PASSWORD_DEFAULT), // Secure hashing
            'department_id' => htmlspecialchars($_POST['department_id'])
        ];


        // Redirect user to the next step
        header("Location: create_student_course.php");
        exit;
    } else {
        echo "<h2>Please select a department before proceeding.</h2>";
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
            margin: 2rem auto;
            background-color: #fff;
            border-radius: 10px;
            padding: 2rem;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        h1 {
            color: #1a1a1a;
            margin-bottom: 3rem;
            margin-left: 10px;
        }

        h2 {
            background-color: #6495ed;
            color: white;
            padding: 1rem;
            border-radius: 10px 10px 0 0;
            margin-left: -32px;
            text-align: left;
            width: 103.2%;
            margin-top: -30px;
        }

        /* Form Styles */
        form {
            padding: 1.5rem;
        }

        .form-row {
            display: flex;
            gap: 4rem; /* Space between photo and table */
            flex-wrap: wrap;
            margin-bottom: 1.5rem;
        }

        .photo-upload {
            flex: 0 0 10%; /* Photo box column takes 30% width */
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-start;
            height: 100px;
            margin-left: 15px;
            margin-top: 12px;
        }
        .photo-upload label {
            align-self: flex-start; /* Ensure the label aligns to the left */ 
            margin-left: -30px;
        }

        .photo-box {
            border: 2px dashed #ccc;
            border-radius: 5px;
            padding: 2rem;
            text-align: center;
            color: #888;
            cursor: pointer;
            height: 200px; /* Adjust height as needed */
            width: 100%;
        }

        #image-preview {
            margin-top: 20px;
            border: 1px solid #ccc;
            display: block;
        }

        .details-table {
            flex: 1; /* Table takes up the remaining space */
            width: 100%;
            border-collapse: collapse;
        }

        .details-table th, .details-table td {
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

        input, textarea {
            border: 1px solid #ccc;
            border-radius: 5px;
            padding: 0.8rem;
            font-size: 1rem;
            outline: none;
            width: 100%;
            box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        input:focus, textarea:focus {
            border-color: #6c63ff;
        }

        textarea {
            resize: none;
        }

        /* Character Limit */
        .char-limit {
            font-size: 0.8rem;
            color: #888;
            text-align: right;
        }

        /* Responsive Design */
        @media (max-width: 600px) {
            .form-row {
                flex-direction: column;
            }

            .photo-upload {
                flex: 1; /* Stretches full width on small screens */
            }

            .details-table {
                flex: 1;
            }
        }

        select{
            width: 300px;
            padding: 10px;
            margin: 10px 0;
            border-radius: 10px;
            border: 1px solid #ddd;
        }

        button {
            display: block;
            width: 20%;
            padding: 0.5rem;
            background-color: #6495ed;
            color: #fff;
            border: none;
            border-radius: 5px;
            font-size: 1rem;
            cursor: pointer;
            margin-top: 3rem;
            margin-left: 370px;
        }

        button:hover {
            background-color: #5a52d4;
        }

    </style>
</head>
<body>
<?php include('admin_header.php'); ?>
    <main class="main-content">
        <h1>Step 1: Add New Student</h1>
        <div class="form-container">
            <div class="form-card">
                <h2>Student Details</h2>
                <form action="create_student.php" method="POST">
                    <!-- Row with Photo and Table -->
                    <div class="form-row">
                        <!-- Left Column: Photo -->
                        <div class="form-group photo-upload">
                            <label for="profile_picture">Profile Picture *</label>
                            <div class="photo-box">
                                <p>Drag and drop or click here to select file</p>
                                <input type="file" id="file-input" accept=".jpg,.jpeg" hidden>
                            </div>
                            <input type="file" id="file-input" accept=".jpg,.jpeg" hidden>
                        </div>
                        <!-- Right Column: Table -->
                        <table class="details-table">
                            <tr>
                                <td>
                                    <label for="student_name">Full Name *</label>
                                    <input type="text" id="student_name" name="student_name" placeholder="Samantha William" required>
                                </td>
                                <td>
                                    <label for="admission_number">Admission No. *</label>
                                    <input type="text" id="admission_number" name="admission_number" placeholder="2301118B" required>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <label for="student_phone">Phone *</label>
                                    <input type="text" id="student_phone" name="student_phone" placeholder="34567890" required>
                                </td>
                                <td>
                                    <label for="student_email">Email *</label>
                                    <input type="email" id="student_email" name="student_email" placeholder="william@mail.com" required>
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
                                        <option value="">Select Department</option>
                                        <?php while ($row = $departments_result->fetch_assoc()): ?>
                                            <option value="<?php echo $row['department_id']; ?>">
                                                <?php echo htmlspecialchars($row['department_name']); ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </td>
                            </tr>
                        </table>
                    </div>
                    <button type="submit">Next</button>
                </form>
            </div>
        </div>
    </main>
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
            event.preventDefault();  // Prevent default behavior (open as link for some browsers)
            photoBox.style.borderColor = '#6c63ff'; // Optional: Change border color when dragging over
        });

        photoBox.addEventListener('dragleave', function() {
            photoBox.style.borderColor = '#ccc'; // Reset border color when drag leaves
        });

        photoBox.addEventListener('drop', function(event) {
            event.preventDefault();  // Prevent default behavior
            const file = event.dataTransfer.files[0];  // Get the dropped file

            if (file) {
                const fileName = file.name;
                photoBox.innerHTML = `<p>Dropped file: ${fileName}</p>`;
                // You can trigger the file input change here as well if needed
                fileInput.files = event.dataTransfer.files;
            }
        });
    </script>

</body>
</html>
