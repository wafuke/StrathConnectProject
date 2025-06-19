<?php
// Start session and verify admin access
session_start();
if (!isset($_SESSION['user_id'])|| $_SESSION['user_type'] !== 'seller') {
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
    <title>Seller Dashboard - StrathConnect</title>
    <link rel="stylesheet" href="../assets/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <nav class="navbar">
        <div class="logo">StrathConnect</div>
        <ul class="nav-links">
            <li><a href="../public/index.php">Home</a></li>
            <li><a href="seller_dashboard.php" class="active">Dashboard</a></li>
            <li><a href="seller_products.php">My Products</a></li>
            <li><a href="seller_services.php">My Services</a></li>
            <li><a href="seller_orders.php">Orders</a></li>
            <li><a href="../public/login.php">Logout</a></li>
        </ul>
    </nav>

    <div class="dashboard-container">
        <aside class="sidebar">
            <div class="profile-summary">
                <img src="../assets/images/profile-placeholder.png" alt="Profile" class="profile-pic">
                <h3>Seller Name</h3>
                <p>@username</p>
                <div class="rating">
                    <i class="fas fa-star"></i>
                    <i class="fas fa-star"></i>
                    <i class="fas fa-star"></i>
                    <i class="fas fa-star"></i>
                    <i class="far fa-star"></i>
                    <span>(24 reviews)</span>
                </div>
            </div>
            <nav class="sidebar-nav">
                <a href="seller_dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                <a href="seller_products.php"><i class="fas fa-box-open"></i> My Products</a>
                <a href="seller_services.php"><i class="fas fa-concierge-bell"></i> My Services</a>
                <a href="seller_orders.php"><i class="fas fa-shopping-cart"></i> Orders</a>
                <a href="seller_messages.php"><i class="fas fa-envelope"></i> Messages</a>
                <a href="seller_settings.php"><i class="fas fa-cog"></i> Settings</a>
            </nav>
        </aside>

        <main class="dashboard-content">
            <h1>Seller Dashboard</h1>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <h3>Total Sales</h3>
                    <p>KSh 24,500</p>
                    <i class="fas fa-wallet"></i>
                </div>
                <div class="stat-card">
                    <h3>Active Products</h3>
                    <p>12</p>
                    <i class="fas fa-boxes"></i>
                </div>
                <div class="stat-card">
                    <h3>Active Services</h3>
                    <p>5</p>
                    <i class="fas fa-tools"></i>
                </div>
                <div class="stat-card">
                    <h3>Pending Orders</h3>
                    <p>3</p>
                    <i class="fas fa-shopping-cart"></i>
                </div>
            </div>

            <section class="quick-actions">
                <h2>Quick Actions</h2>
                <div class="action-buttons">
                    <button class="action-btn" onclick="location.href='../database/add_product.php'">
                        <i class="fas fa-plus-circle"></i> Add Product
                    </button>
                    <button class="action-btn" onclick="location.href='add_service.php'">
                        <i class="fas fa-plus-circle"></i> Add Service
                    </button>
                    <button class="action-btn" onclick="location.href='seller_orders.php'">
                        <i class="fas fa-clipboard-list"></i> View Orders
                    </button>
                </div>
            </section>

            <section class="recent-activity">
                <h2>Recent Activity</h2>
                <div class="activity-list">
                    <div class="activity-item">
                        <div class="activity-icon">
                            <i class="fas fa-shopping-cart"></i>
                        </div>
                        <div class="activity-content">
                            <p>New order for "Used Textbooks" from John Doe</p>
                            <small>2 hours ago</small>
                        </div>
                    </div>
                    <div class="activity-item">
                        <div class="activity-icon">
                            <i class="fas fa-comment"></i>
                        </div>
                        <div class="activity-content">
                            <p>New message from Jane Smith about your tutoring service</p>
                            <small>5 hours ago</small>
                        </div>
                    </div>
                    <div class="activity-item">
                        <div class="activity-icon">
                            <i class="fas fa-thumbs-up"></i>
                        </div>
                        <div class="activity-content">
                            <p>Your product "Wireless Headphones" received a 5-star review</p>
                            <small>1 day ago</small>
                        </div>
                    </div>
                </div>
            </section>
        </main>
    </div>

    <footer class="footer">
        <p>&copy; 2023 StrathConnect. All rights reserved.</p>
    </footer>
</body>
</html>