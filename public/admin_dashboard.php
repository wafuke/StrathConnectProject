<?php
// Start session and verify admin access
session_start();
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

// Get statistics
$stats = [];

// Total Users
$query = "SELECT COUNT(*) as total FROM users";
$result = $conn->query($query);
$stats['total_users'] = $result->fetch_assoc()['total'];

// Active Products (approved)
$query = "SELECT COUNT(*) as total FROM products WHERE is_approved = 1";
$result = $conn->query($query);
$stats['active_products'] = $result->fetch_assoc()['total'];

// Active Services (approved)
$query = "SELECT COUNT(*) as total FROM services WHERE is_approved = 1";
$result = $conn->query($query);
$stats['active_services'] = $result->fetch_assoc()['total'];

// Pending Products
$query = "SELECT COUNT(*) as total FROM products WHERE is_approved = 0";
$result = $conn->query($query);
$stats['pending_products'] = $result->fetch_assoc()['total'];

// Recent Activity (last 5 activities)
$query = "(
            SELECT 'product' as type, p.name as title, u.username, p.created_at 
            FROM products p 
            JOIN users u ON p.seller_id = u.id 
            ORDER BY p.created_at DESC 
            LIMIT 2
          ) UNION ALL (
            SELECT 'service' as type, s.title, u.username, s.created_at 
            FROM services s 
            JOIN users u ON s.seller_id = u.id 
            ORDER BY s.created_at DESC 
            LIMIT 2
          ) UNION ALL (
            SELECT 'user' as type, username as title, username, created_at 
            FROM users 
            ORDER BY created_at DESC 
            LIMIT 1
          ) 
          ORDER BY created_at DESC 
          LIMIT 5";
$recent_activities = $conn->query($query)->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - StrathConnect</title>
    <link rel="stylesheet" href="../assets/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <nav class="navbar">
        <div class="logo">StrathConnect Admin</div>
        <ul class="nav-links">
            <li><a href="admin_dashboard.php" class="active">Dashboard</a></li>
            <li><a href="admin_users.php">Users</a></li>
            <li><a href="admin_products.php">Products</a></li>
            <li><a href="admin_services.php">Services</a></li>
            <li><a href="admin_reports.php">Reports</a></li>
            <li><a href="../public/login.php">Logout</a></li>
        </ul>
    </nav>

    <div class="dashboard-container">
        <aside class="sidebar">
            <div class="profile-summary">
                <img src="../assets/images/admin-placeholder.png" alt="Profile" class="profile-pic">
                <h3><?php echo htmlspecialchars($_SESSION['username'] ?? 'Admin'); ?></h3>
                <p>Administrator</p>
            </div>
            <nav class="sidebar-nav">
                <a href="admin_dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                <a href="admin_users.php"><i class="fas fa-users"></i> User Management</a>
                <a href="admin_products.php"><i class="fas fa-box-open"></i> Product Management</a>
                <a href="admin_services.php"><i class="fas fa-concierge-bell"></i> Service Management</a>
                <a href="admin_orders.php"><i class="fas fa-shopping-cart"></i> Order Management</a>
                <a href="admin_reports.php"><i class="fas fa-chart-bar"></i> Reports</a>
                <a href="admin_settings.php"><i class="fas fa-cog"></i> System Settings</a>
            </nav>
        </aside>

        <main class="dashboard-content">
            <h1>Admin Dashboard</h1>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <h3>Total Users</h3>
                    <p><?php echo number_format($stats['total_users']); ?></p>
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-card">
                    <h3>Active Products</h3>
                    <p><?php echo number_format($stats['active_products']); ?></p>
                    <i class="fas fa-boxes"></i>
                </div>
                <div class="stat-card">
                    <h3>Active Services</h3>
                    <p><?php echo number_format($stats['active_services']); ?></p>
                    <i class="fas fa-tools"></i>
                </div>
                <div class="stat-card">
                    <h3>Pending Products</h3>
                    <p><?php echo number_format($stats['pending_products']); ?></p>
                    <i class="fas fa-clock"></i>
                </div>
            </div>

            <section class="recent-activity">
                <h2>Recent Activity</h2>
                <div class="activity-list">
                    <?php if (empty($recent_activities)): ?>
                        <div class="activity-item">
                            <div class="activity-icon">
                                <i class="fas fa-info-circle"></i>
                            </div>
                            <div class="activity-content">
                                <p>No recent activity found</p>
                            </div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($recent_activities as $activity): ?>
                            <div class="activity-item">
                                <div class="activity-icon">
                                    <?php if ($activity['type'] === 'product'): ?>
                                        <i class="fas fa-box-open"></i>
                                    <?php elseif ($activity['type'] === 'service'): ?>
                                        <i class="fas fa-concierge-bell"></i>
                                    <?php else: ?>
                                        <i class="fas fa-user-plus"></i>
                                    <?php endif; ?>
                                </div>
                                <div class="activity-content">
                                    <p>
                                        <?php if ($activity['type'] === 'product'): ?>
                                            New product added: "<?php echo htmlspecialchars($activity['title']); ?>" by @<?php echo htmlspecialchars($activity['username']); ?>
                                        <?php elseif ($activity['type'] === 'service'): ?>
                                            New service listed: "<?php echo htmlspecialchars($activity['title']); ?>" by @<?php echo htmlspecialchars($activity['username']); ?>
                                        <?php else: ?>
                                            New user registered: @<?php echo htmlspecialchars($activity['username']); ?>
                                        <?php endif; ?>
                                    </p>
                                    <small><?php echo date('M j, Y g:i A', strtotime($activity['created_at'])); ?></small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </section>

            <section class="quick-actions">
                <h2>Quick Actions</h2>
                <div class="action-buttons">
                    <button class="action-btn" onclick="location.href='admin_users.php'">
                        <i class="fas fa-user-cog"></i> Manage Users
                    </button>
                    <button class="action-btn" onclick="location.href='admin_products.php'">
                        <i class="fas fa-boxes"></i> Review Products
                    </button>
                    <button class="action-btn" onclick="location.href='admin_reports.php'">
                        <i class="fas fa-flag"></i> View Reports
                    </button>
                </div>
            </section>
        </main>
    </div>

    <footer class="footer">
        <p>&copy; <?php echo date('Y'); ?> StrathConnect. All rights reserved.</p>
    </footer>
</body>
</html>
<?php $conn->close(); ?>