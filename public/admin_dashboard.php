<?php
// Start session and verify admin access
session_start();
if (!isset($_SESSION['user_id'])|| $_SESSION['user_type'] !== 'admin') {
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
                <h3>Admin Name</h3>
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
                    <p>1,245</p>
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-card">
                    <h3>Active Products</h3>
                    <p>356</p>
                    <i class="fas fa-boxes"></i>
                </div>
                <div class="stat-card">
                    <h3>Active Services</h3>
                    <p>128</p>
                    <i class="fas fa-tools"></i>
                </div>
                <div class="stat-card">
                    <h3>Transactions Today</h3>
                    <p>KSh 42,800</p>
                    <i class="fas fa-money-bill-wave"></i>
                </div>
            </div>

            <section class="recent-activity">
                <h2>Recent Activity</h2>
                <div class="activity-list">
                    <div class="activity-item">
                        <div class="activity-icon">
                            <i class="fas fa-user-plus"></i>
                        </div>
                        <div class="activity-content">
                            <p>New user registered: @newstudent</p>
                            <small>30 minutes ago</small>
                        </div>
                    </div>
                    <div class="activity-item">
                        <div class="activity-icon">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <div class="activity-content">
                            <p>Product reported: "Used Laptop" - Under review</p>
                            <small>2 hours ago</small>
                        </div>
                    </div>
                    <div class="activity-item">
                        <div class="activity-icon">
                            <i class="fas fa-ban"></i>
                        </div>
                        <div class="activity-content">
                            <p>User @spammer suspended for policy violation</p>
                            <small>5 hours ago</small>
                        </div>
                    </div>
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
        <p>&copy; 2023 StrathConnect. All rights reserved.</p>
    </footer>
</body>
</html>