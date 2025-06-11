<?php
session_start();

// Database connection settings
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "strathconnect";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize and validate input
    $user = trim($_POST['username']);
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $type = trim($_POST['user_type']);
    $pass = trim($_POST['password']);

    // Basic validation
    $errors = [];
    
    if (empty($user)) {
        $errors[] = "Username is required.";
    } elseif (strlen($user) < 4) {
        $errors[] = "Username must be at least 4 characters.";
    }

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Valid email is required.";
    }

    if (!in_array($type, ['admin', 'seller', 'buyer'])) {
        $errors[] = "Invalid user type.";
    }

    if (empty($pass)) {
        $errors[] = "Password is required.";
    } elseif (strlen($pass) < 8) {
        $errors[] = "Password must be at least 8 characters.";
    }

    if (!empty($errors)) {
        $_SESSION['signup_errors'] = $errors;
        header("Location: ../public/signup.html");
        exit();
    }

    // Check if username or email already exists
    $check_sql = "SELECT id FROM users WHERE username = ? OR email = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("ss", $user, $email);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows > 0) {
        $_SESSION['signup_errors'] = ["Username or email already exists."];
        header("Location: ../public/signup.html");
        $check_stmt->close();
        $conn->close();
        exit();
    }

    // Hash password
    $hashedPassword = password_hash($pass, PASSWORD_DEFAULT);

    // Insert user into DB
    $sql = "INSERT INTO users (username, email, user_type, password) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssss", $user, $email, $type, $hashedPassword);

    if ($stmt->execute()) {
        // Redirect to login page after successful signup
        $stmt->close();
        $check_stmt->close();
        $conn->close();
        header("Location: ../public/login.html");
        exit();
    } else {
        $_SESSION['signup_errors'] = ["Error: " . $stmt->error];
        $stmt->close();
        $check_stmt->close();
        $conn->close();
        header("Location: ../public/signup.html");
        exit();
    }
} else {
    header("Location: ../public/signup.html");
    exit();
}
?>
