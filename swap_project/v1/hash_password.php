<?php
// Include the database connection
include 'db_connection.php';

// Query to select all users and their plaintext passwords
$stmt = $pdo->query("SELECT user_id, hashed_password FROM user");

while ($user = $stmt->fetch(PDO::FETCH_ASSOC)) {
    // Get the plaintext password from the database
    $plaintextPassword = $user['hashed_password'];  // Assuming it's stored in 'hashed_password' column (even though it's plaintext)

    // Hash the password using password_hash() function
    $hashedPassword = password_hash($plaintextPassword, PASSWORD_DEFAULT);

    // Update the user's password to the hashed password
    $updateStmt = $pdo->prepare("UPDATE user SET hashed_password = :hashed_password WHERE user_id = :user_id");
    $updateStmt->bindParam(':hashed_password', $hashedPassword);
    $updateStmt->bindParam(':user_id', $user['user_id']);
    $updateStmt->execute();
}

echo "All passwords have been hashed and updated in the database.";
?>
