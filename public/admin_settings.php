<?php
session_start();

// Verify admin is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../public/login.php");
    exit();
}

// Database connection
$host = 'localhost';
$user = 'root';
$pass = '';
$db = 'strathconnect';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Initialize variables
$error = '';
$success = '';
$username = $_SESSION['username'] ?? '';
$profile_pic = '../assets/images/admin-placeholder.png';

// Get current user data
$user_id = $_SESSION['user_id'];
$query = "SELECT username, profile_pic FROM users WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user_data = $result->fetch_assoc();
    $username = $user_data['username'];
    if (!empty($user_data['profile_pic'])) {
        $profile_pic = $user_data['profile_pic'];
        $_SESSION['profile_pic'] = $user_data['profile_pic']; // Ensure session has current pic
    }
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Update profile picture
    if (isset($_FILES['profile_pic'])) {
        $target_dir = "../assets/images/profiles/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        
        $target_file = $target_dir . basename($_FILES["profile_pic"]["name"]);
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        $new_filename = "user_" . $user_id . "_" . time() . "." . $imageFileType;
        $target_file = $target_dir . $new_filename;
        
        // Check if image file is a actual image
        $check = getimagesize($_FILES["profile_pic"]["tmp_name"]);
        if ($check === false) {
            $error = "File is not an image.";
        } 
        // Check file size (max 2MB)
        elseif ($_FILES["profile_pic"]["size"] > 2000000) {
            $error = "Sorry, your file is too large (max 2MB).";
        }
        // Allow certain file formats
        elseif (!in_array($imageFileType, ['jpg', 'jpeg', 'png', 'gif'])) {
            $error = "Sorry, only JPG, JPEG, PNG & GIF files are allowed.";
        }
        // Try to upload file
        elseif (move_uploaded_file($_FILES["profile_pic"]["tmp_name"], $target_file)) {
            // Update database
            $update_stmt = $conn->prepare("UPDATE users SET profile_pic = ? WHERE id = ?");
            $update_stmt->bind_param("si", $target_file, $user_id);
            if ($update_stmt->execute()) {
                $profile_pic = $target_file;
                $_SESSION['profile_pic'] = $target_file; // Update session immediately
                $success = "Profile picture updated successfully!";
            } else {
                $error = "Error updating profile picture in database.";
                // Delete the uploaded file if database update failed
                unlink($target_file);
            }
            $update_stmt->close();
        } else {
            $error = "Sorry, there was an error uploading your file.";
        }
    }
    
    // Update username
    if (isset($_POST['update_username'])) {
        $new_username = trim($_POST['username']);
        
        if (empty($new_username)) {
            $error = "Username cannot be empty.";
        } elseif ($new_username !== $username) {
            // Check if username is already taken
            $check_stmt = $conn->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
            $check_stmt->bind_param("si", $new_username, $user_id);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            
            if ($check_result->num_rows > 0) {
                $error = "Username already taken. Please choose another.";
            } else {
                $update_stmt = $conn->prepare("UPDATE users SET username = ? WHERE id = ?");
                $update_stmt->bind_param("si", $new_username, $user_id);
                if ($update_stmt->execute()) {
                    $username = $new_username;
                    $_SESSION['username'] = $new_username;
                    $success = "Username updated successfully!";
                } else {
                    $error = "Error updating username.";
                }
                $update_stmt->close();
            }
            $check_stmt->close();
        }
    }
    
    // Update password
    if (isset($_POST['update_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        // Verify current password
        $verify_stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
        $verify_stmt->bind_param("i", $user_id);
        $verify_stmt->execute();
        $verify_result = $verify_stmt->get_result();
        $user = $verify_result->fetch_assoc();
        
        if (!password_verify($current_password, $user['password'])) {
            $error = "Current password is incorrect.";
        } elseif ($new_password !== $confirm_password) {
            $error = "New passwords do not match.";
        } elseif (strlen($new_password) < 8) {
            $error = "Password must be at least 8 characters long.";
        } else {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $update_stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $update_stmt->bind_param("si", $hashed_password, $user_id);
            if ($update_stmt->execute()) {
                $success = "Password updated successfully!";
            } else {
                $error = "Error updating password.";
            }
            $update_stmt->close();
        }
        $verify_stmt->close();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Settings - StrathConnect</title>
    <link rel="stylesheet" href="../assets/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .settings-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .settings-header {
            margin-bottom: 30px;
            border-bottom: 1px solid #ddd;
            padding-bottom: 15px;
        }
        
        .settings-title {
            font-size: 28px;
            font-weight: bold;
            color: #111;
        }
        
        .settings-section {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 25px;
            margin-bottom: 30px;
        }
        
        .section-title {
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 20px;
            color: #111;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .profile-pic-container {
            display: flex;
            align-items: center;
            gap: 30px;
            margin-bottom: 20px;
        }
        
        .profile-pic-preview {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #eee;
        }
        
        .profile-pic-upload {
            flex: 1;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #333;
        }
        
        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }
        
        .form-control:focus {
            border-color: #FF9900;
            outline: none;
            box-shadow: 0 0 0 2px rgba(255,153,0,0.2);
        }
        
        .btn {
            background: #FF9900;
            color: white;
            border: none;
            border-radius: 4px;
            padding: 10px 20px;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn:hover {
            background: #e68a00;
        }
        
        .btn-secondary {
            background: #6c757d;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
        }
        
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .password-strength {
            margin-top: 5px;
            height: 5px;
            background: #eee;
            border-radius: 3px;
            overflow: hidden;
        }
        
        .password-strength-bar {
            height: 100%;
            width: 0%;
            background: #dc3545;
            transition: width 0.3s;
        }
        
        .password-hint {
            font-size: 14px;
            color: #6c757d;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="logo">StrathConnect Admin</div>
        <ul class="nav-links">
            <li><a href="admin_dashboard.php">Dashboard</a></li>
            <li><a href="admin_users.php">Users</a></li>
            <li><a href="admin_products.php">Products</a></li>
            <li><a href="admin_services.php">Services</a></li>
            <li><a href="../public/login.php">Logout</a></li>
        </ul>
    </nav>

    <div class="dashboard-container">
        <aside class="sidebar">
            <div class="profile-summary">
                <img src="<?php echo htmlspecialchars($profile_pic); ?>" alt="Profile" class="profile-pic">
                <h3><?php echo htmlspecialchars($username); ?></h3>
                <p>Administrator</p>
            </div>
            <nav class="sidebar-nav">
                <a href="admin_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                <a href="admin_users.php"><i class="fas fa-users"></i> User Management</a>
                <a href="admin_products.php"><i class="fas fa-box-open"></i> Product Management</a>
                <a href="admin_services.php"><i class="fas fa-concierge-bell"></i> Service Management</a>
                <a href="admin_orders.php"><i class="fas fa-shopping-cart"></i> Order Management</a>
                <a href="admin_settings.php" class="active"><i class="fas fa-cog"></i> Settings</a>
            </nav>
        </aside>

        <main class="dashboard-content">
            <div class="settings-container">
                <div class="settings-header">
                    <h1 class="settings-title">Admin Settings</h1>
                </div>
                
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($success)): ?>
                    <div class="alert alert-success">
                        <?php echo htmlspecialchars($success); ?>
                    </div>
                <?php endif; ?>
                
                <div class="settings-section">
                    <h2 class="section-title"><i class="fas fa-user-circle"></i> Profile Picture</h2>
                    
                    <div class="profile-pic-container">
                        <img src="<?php echo htmlspecialchars($profile_pic); ?>" alt="Profile Picture" class="profile-pic-preview" id="profilePicPreview">
                        
                        <div class="profile-pic-upload">
                            <form method="post" enctype="multipart/form-data">
                                <div class="form-group">
                                    <label for="profile_pic">Upload new profile picture</label>
                                    <input type="file" id="profile_pic" name="profile_pic" class="form-control" accept="image/*">
                                </div>
                                <button type="submit" class="btn">Update Picture</button>
                            </form>
                        </div>
                    </div>
                </div>
                
                <div class="settings-section">
                    <h2 class="section-title"><i class="fas fa-user"></i> Account Information</h2>
                    
                    <form method="post">
                        <div class="form-group">
                            <label for="username">Username</label>
                            <input type="text" id="username" name="username" class="form-control" value="<?php echo htmlspecialchars($username); ?>" required>
                        </div>
                        <button type="submit" name="update_username" class="btn">Update Username</button>
                    </form>
                </div>
                
                <div class="settings-section">
                    <h2 class="section-title"><i class="fas fa-lock"></i> Change Password</h2>
                    
                    <form method="post">
                        <div class="form-group">
                            <label for="current_password">Current Password</label>
                            <input type="password" id="current_password" name="current_password" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="new_password">New Password</label>
                            <input type="password" id="new_password" name="new_password" class="form-control" required>
                            <div class="password-strength">
                                <div class="password-strength-bar" id="passwordStrengthBar"></div>
                            </div>
                            <div class="password-hint">Password must be at least 8 characters long</div>
                        </div>
                        
                        <div class="form-group">
                            <label for="confirm_password">Confirm New Password</label>
                            <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
                        </div>
                        
                        <button type="submit" name="update_password" class="btn">Change Password</button>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <footer class="footer">
        <p>&copy; <?php echo date('Y'); ?> StrathConnect. All rights reserved.</p>
    </footer>

    <script>
    // Preview profile picture before upload
    document.getElementById('profile_pic').addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(event) {
                document.getElementById('profilePicPreview').src = event.target.result;
            }
            reader.readAsDataURL(file);
        }
    });
    
    // Password strength indicator
    document.getElementById('new_password').addEventListener('input', function() {
        const password = this.value;
        const strengthBar = document.getElementById('passwordStrengthBar');
        let strength = 0;
        
        // Check password length
        if (password.length >= 8) strength += 1;
        if (password.length >= 12) strength += 1;
        
        // Check for mixed case
        if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength += 1;
        
        // Check for numbers
        if (/\d/.test(password)) strength += 1;
        
        // Check for special characters
        if (/[^a-zA-Z0-9]/.test(password)) strength += 1;
        
        // Update strength bar
        switch(strength) {
            case 0:
            case 1:
                strengthBar.style.width = '25%';
                strengthBar.style.backgroundColor = '#dc3545'; // red
                break;
            case 2:
                strengthBar.style.width = '50%';
                strengthBar.style.backgroundColor = '#fd7e14'; // orange
                break;
            case 3:
                strengthBar.style.width = '75%';
                strengthBar.style.backgroundColor = '#ffc107'; // yellow
                break;
            case 4:
            case 5:
                strengthBar.style.width = '100%';
                strengthBar.style.backgroundColor = '#28a745'; // green
                break;
            default:
                strengthBar.style.width = '0%';
        }
    });
    </script>
</body>
</html>