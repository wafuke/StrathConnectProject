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

// Handle user deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user'])) {
    $user_id = intval($_POST['user_id']);
    
    // Check if this is the last admin
    $check_admin = "SELECT COUNT(*) as admin_count FROM users WHERE user_type = 'admin'";
    $result = $conn->query($check_admin);
    $admin_count = $result->fetch_assoc()['admin_count'];
    
    if ($admin_count <= 1) {
        $_SESSION['error'] = "Cannot delete the last admin user";
        header("Location: admin_users.php");
        exit();
    }
    
    // Delete user
    $query = "DELETE FROM users WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "User deleted successfully";
    } else {
        $_SESSION['error'] = "Error deleting user: " . $stmt->error;
    }
    $stmt->close();
    
    header("Location: admin_users.php");
    exit();
}

// Get all users
$query = "SELECT id, username, email, user_type, created_at, profile_pic FROM users ORDER BY created_at DESC";
$result = $conn->query($query);
$users = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Manage Users - StrathConnect</title>
  <link rel="stylesheet" href="../assets/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <style>
    .user-avatar {
      width: 80px;
      height: 80px;
      border-radius: 50%;
      object-fit: cover;
      border: 2px solid #eee;
      margin-bottom: 10px;
    }
    .user-card {
      background: #fff;
      border-radius: 8px;
      padding: 20px;
      box-shadow: 0 2px 5px rgba(0,0,0,0.1);
      text-align: center;
    }
    .users-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
      gap: 20px;
    }
    .btn-delete {
      background: #dc3545;
      color: white;
      padding: 8px 12px;
      border: none;
      border-radius: 4px;
      cursor: pointer;
      transition: 0.3s;
    }
    .btn-delete:hover {
      background: #c82333;
    }
    .alert {
      padding: 10px 15px;
      border-radius: 4px;
      margin-bottom: 15px;
    }
    .alert-success {
      background: #d4edda;
      color: #155724;
      border: 1px solid #c3e6cb;
    }
    .alert-error {
      background: #f8d7da;
      color: #721c24;
      border: 1px solid #f5c6cb;
    }
  </style>
</head>
<body>

  <div class="dashboard-container">
    <main class="dashboard-content">
      <div class="users-container">
        <div class="users-header">
          <h1>Manage Users</h1>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
          <div class="alert alert-success">
            <?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
          </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
          <div class="alert alert-error">
            <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
          </div>
        <?php endif; ?>

        <div class="users-grid">
          <?php if (empty($users)): ?>
            <div class="empty-state">
              <i class="fas fa-users"></i>
              <h3>No users found</h3>
              <p>There are currently no users in the system</p>
            </div>
          <?php else: ?>
            <?php foreach ($users as $user): ?>
              <div class="user-card">
                <img 
                  src="<?php echo htmlspecialchars(!empty($user['profile_pic']) ? $user['profile_pic'] : '../assets/images/profile-placeholder.png'); ?>" 
                  alt="User Avatar" 
                  class="user-avatar"
                >
                <h3 class="user-name"><?php echo htmlspecialchars($user['username']); ?></h3>
                <p class="user-username">@<?php echo htmlspecialchars($user['username']); ?></p>
                <div class="user-type type-<?php echo $user['user_type']; ?>">
                  <?php echo ucfirst($user['user_type']); ?>
                </div>
                <div class="user-details">
                  <div class="user-detail-item">
                    <span class="user-detail-label">Email:</span>
                    <span class="user-detail-value"><?php echo htmlspecialchars($user['email']); ?></span>
                  </div>
                  <div class="user-detail-item">
                    <span class="user-detail-label">Joined:</span>
                    <span class="user-detail-value"><?php echo date('M j, Y', strtotime($user['created_at'])); ?></span>
                  </div>
                </div>
                <div class="user-actions">
                  <form method="POST" onsubmit="return confirm('Are you sure you want to delete this user?');">
                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                    <button type="submit" name="delete_user" class="btn-delete">
                      <i class="fas fa-trash"></i> Delete
                    </button>
                  </form>
                </div>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>
    </main>
  </div>

</body>
</html>
<?php $conn->close(); ?>
