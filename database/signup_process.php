<?php
// Start session (optional: for future login functionality)
session_start();

// Database connection settings
$servername = "localhost";
$username = "root";        // Default XAMPP username
$password = "";            
$dbname = "strathconnect"; //  DB name

// Create DB connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Sanitize and collect form data
$user = htmlspecialchars(trim($_POST['username']));
$email = htmlspecialchars(trim($_POST['email']));
$type = htmlspecialchars(trim($_POST['user_type']));
$pass = htmlspecialchars(trim($_POST['password']));

// Basic validation
if (empty($user) || empty($email) || empty($type) || empty($pass)) {
    die("Please fill in all fields.");
}

// Password hashing
$hashedPassword = password_hash($pass, PASSWORD_DEFAULT);

// Insert user into DB
$sql = "INSERT INTO users (username, email, user_type, password)
        VALUES (?, ?, ?, ?)";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ssss", $user, $email, $type, $hashedPassword);

if ($stmt->execute()) {
    echo "Signup successful! You can now <a href='../public/login.html'>login</a>.";
} else {
    echo "Error: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>
