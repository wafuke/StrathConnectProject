<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'seller') {
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

// Initialize counts with default values
$product_count = 0;
$service_count = 0;
$order_count = 0;
$total_sales = "KSh 0";

// Get seller stats
$seller_id = $_SESSION['user_id'];

try {
    // Get product count
    $product_query = "SELECT COUNT(*) as count FROM products WHERE seller_id = ?";
    if ($stmt = $conn->prepare($product_query)) {
        $stmt->bind_param("i", $seller_id);
        $stmt->execute();
        $product_result = $stmt->get_result();
        $product_count = $product_result->fetch_assoc()['count'];
        $stmt->close();
    } else {
        error_log("Failed to prepare product query: " . $conn->error);
    }

    // Get service count
    $service_query = "SELECT COUNT(*) as count FROM services WHERE seller_id = ?";
    if ($stmt = $conn->prepare($service_query)) {
        $stmt->bind_param("i", $seller_id);
        $stmt->execute();
        $service_result = $stmt->get_result();
        $service_count = $service_result->fetch_assoc()['count'];
        $stmt->close();
    } else {
        error_log("Failed to prepare service query: " . $conn->error);
    }

    // Get order count (pending)
    $order_query = "SELECT COUNT(*) as count FROM orders WHERE seller_id = ? AND status = 'pending'";
    if ($stmt = $conn->prepare($order_query)) {
        $stmt->bind_param("i", $seller_id);
        $stmt->execute();
        $order_result = $stmt->get_result();
        $order_count = $order_result->fetch_assoc()['count'];
        $stmt->close();
    } else {
        error_log("Failed to prepare order query: " . $conn->error);
    }
} catch (Exception $e) {
    error_log("Database error: " . $e->getMessage());
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
                <h3><?php echo htmlspecialchars($_SESSION['username'] ?? 'Seller'); ?></h3>
                <p>@<?php echo htmlspecialchars($_SESSION['username'] ?? 'username'); ?></p>
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
                    <p><?php echo $total_sales; ?></p>
                    <i class="fas fa-wallet"></i>
                </div>
                <div class="stat-card">
                    <h3>Active Products</h3>
                    <p id="product-count"><?php echo $product_count; ?></p>
                    <i class="fas fa-boxes"></i>
                </div>
                <div class="stat-card">
                    <h3>Active Services</h3>
                    <p id="service-count"><?php echo $service_count; ?></p>
                    <i class="fas fa-tools"></i>
                </div>
                <div class="stat-card">
                    <h3>Pending Orders</h3>
                    <p id="order-count"><?php echo $order_count; ?></p>
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
        <p>&copy; <?php echo date('Y'); ?> StrathConnect. All rights reserved.</p>
    </footer>

    <script>
    function updateCounts() {
        fetch('get_seller_counts.php')
            .then(response => response.json())
            .then(data => {
                // Update counts with animation
                animateCountChange('product-count', data.product_count);
                animateCountChange('service-count', data.service_count);
                animateCountChange('order-count', data.order_count);
            })
            .catch(error => console.error('Error:', error));
    }

    function animateCountChange(elementId, newValue) {
        const element = document.getElementById(elementId);
        if (element.textContent !== newValue.toString()) {
            element.classList.add('count-updating');
            setTimeout(() => {
                element.textContent = newValue;
                element.classList.remove('count-updating');
            }, 300);
        }
    }

    // Update every 30 seconds
    setInterval(updateCounts, 30000);
    
    // Also update when the page gains focus
    window.addEventListener('focus', updateCounts);
    </script>
</body>
</html>
<?php $conn->close(); ?>