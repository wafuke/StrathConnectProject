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
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $password = trim($_POST['password']);

    // Basic validation
    if (empty($email)) {  // ✅ Fixed syntax error: missing closing parenthesis
        $_SESSION['login_error'] = "Email is required.";
        header("Location: ../public/login.html");
        exit();
    }

    if (empty($password)) {
        $_SESSION['login_error'] = "Password is required.";
        header("Location: ../public/login.html");
        exit();
    }

    // Check if user exists
    $sql = "SELECT id, username, password, user_type FROM users WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        
        // Verify password
        if (password_verify($password, $user['password'])) {
            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['user_type'] = $user['user_type'];
            
            // Redirect based on user type
            switch($user['user_type']) {
                case 'admin':
                    header("Location: ../public/admin_dashboard.html");
                    break;
                case 'seller':
                    header("Location: ../public/seller_dashboard.html");
                    break;
                case 'buyer':
                    header("Location: ../public/buyer_dashboard.html");
                    break;
                default:
                    header("Location: ../public/index.html");
            }
            $stmt->close();
            $conn->close();
            exit();
        } else {
            $_SESSION['login_error'] = "Invalid email or password.";
            header("Location: ../public/login.html");
            $stmt->close();
            $conn->close();
            exit();
        }
    } else {
        $_SESSION['login_error'] = "Invalid email or password.";
        header("Location: ../public/login.html");
        $stmt->close();
        $conn->close();
        exit();
    }
} else {
    header("Location: ../public/login.html");
    exit();
}
?>
