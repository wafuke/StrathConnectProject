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
    }

    // Get service count
    $service_query = "SELECT COUNT(*) as count FROM services WHERE seller_id = ?";
    if ($stmt = $conn->prepare($service_query)) {
        $stmt->bind_param("i", $seller_id);
        $stmt->execute();
        $service_result = $stmt->get_result();
        $service_count = $service_result->fetch_assoc()['count'];
        $stmt->close();
    }

    // Get recent activities (last 5)
    $activity_query = "(
        SELECT 'product' as type, name as title, created_at 
        FROM products 
        WHERE seller_id = ?
        ORDER BY created_at DESC 
        LIMIT 3
    ) UNION ALL (
        SELECT 'service' as type, title, created_at 
        FROM services 
        WHERE seller_id = ?
        ORDER BY created_at DESC 
        LIMIT 2
    ) ORDER BY created_at DESC LIMIT 5";
    
    $stmt = $conn->prepare($activity_query);
    $stmt->bind_param("ii", $seller_id, $seller_id);
    $stmt->execute();
    $recent_activities = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

} catch (Exception $e) {
    error_log("Database error: " . $e->getMessage());
    $recent_activities = [];
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
    <style>
        .count-updating {
            transition: all 0.3s ease;
            transform: scale(1.1);
            color: #4CAF50;
        }
        .activity-item {
            transition: all 0.3s ease;
        }
        .new-activity {
            background-color: #f8f9fa;
            border-left: 4px solid #4CAF50;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="logo">StrathConnect</div>
        <ul class="nav-links">
            <li><a href="../public/index.php">Home</a></li>
            <li><a href="seller_dashboard.php" class="active">Dashboard</a></li>
            <li><a href="seller_products.php">My Products</a></li>
            <li><a href="seller_services.php">My Services</a></li>
            <li><a href="../public/login.php">Logout</a></li>
        </ul>
    </nav>

    <div class="dashboard-container">
        <aside class="sidebar">
            <div class="profile-summary">
                <img src="<?php echo htmlspecialchars($_SESSION['profile_pic'] ?? '../assets/images/profile-placeholder.png'); ?>" alt="Profile" class="profile-pic">
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
                <a href="seller_settings.php"><i class="fas fa-cog"></i> Settings</a>
            </nav>
        </aside>

        <main class="dashboard-content">
            <h1>Seller Dashboard</h1>
            
            <div class="stats-grid">
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
                </div>
            </section>

            <section class="recent-activity">
                <h2>Recent Activity</h2>
                <div class="activity-list" id="activity-list">
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
                            <div class="activity-item" data-id="<?php echo $activity['type'].'-'.$activity['created_at']; ?>">
                                <div class="activity-icon">
                                    <?php if ($activity['type'] === 'product'): ?>
                                        <i class="fas fa-box-open"></i>
                                    <?php else: ?>
                                        <i class="fas fa-concierge-bell"></i>
                                    <?php endif; ?>
                                </div>
                                <div class="activity-content">
                                    <p>
                                        <?php if ($activity['type'] === 'product'): ?>
                                            New product added: "<?php echo htmlspecialchars($activity['title']); ?>"
                                        <?php else: ?>
                                            New service listed: "<?php echo htmlspecialchars($activity['title']); ?>"
                                        <?php endif; ?>
                                    </p>
                                    <small><?php echo date('M j, Y g:i A', strtotime($activity['created_at'])); ?></small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </section>
        </main>
    </div>

    <footer class="footer">
        <p>&copy; <?php echo date('Y'); ?> StrathConnect. All rights reserved.</p>
    </footer>

    <script>
    // Store current activity IDs for comparison
    let currentActivities = Array.from(document.querySelectorAll('.activity-item')).map(item => item.dataset.id);
    
    function updateDashboard() {
        fetch('get_seller_counts.php')
            .then(response => {
                if (!response.ok) throw new Error('Network response was not ok');
                return response.json();
            })
            .then(data => {
                // Update counts with animation
                animateCountChange('product-count', data.product_count);
                animateCountChange('service-count', data.service_count);
                
                // Check for new activities
                fetch('get_recent_activities.php')
                    .then(response => response.json())
                    .then(activities => {
                        updateActivities(activities);
                    });
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

    function updateActivities(activities) {
        const activityList = document.getElementById('activity-list');
        const newActivities = activities.filter(activity => 
            !currentActivities.includes(`${activity.type}-${activity.created_at}`)
        );

        if (newActivities.length > 0) {
            // Add new activities with animation
            newActivities.forEach(activity => {
                const activityItem = document.createElement('div');
                activityItem.className = 'activity-item new-activity';
                activityItem.dataset.id = `${activity.type}-${activity.created_at}`;
                
                const icon = activity.type === 'product' ? 
                    '<i class="fas fa-box-open"></i>' : 
                    '<i class="fas fa-concierge-bell"></i>';
                
                const title = activity.type === 'product' ?
                    `New product added: "${activity.title}"` :
                    `New service listed: "${activity.title}"`;
                
                activityItem.innerHTML = `
                    <div class="activity-icon">${icon}</div>
                    <div class="activity-content">
                        <p>${title}</p>
                        <small>${new Date(activity.created_at).toLocaleString()}</small>
                    </div>
                `;
                
                activityList.prepend(activityItem);
                
                // Remove new-activity class after animation
                setTimeout(() => {
                    activityItem.classList.remove('new-activity');
                }, 1000);
            });

            // Update current activities
            currentActivities = activities.map(activity => `${activity.type}-${activity.created_at}`);
            
            // Remove excess activities (keep only last 5)
            while (activityList.children.length > 5) {
                activityList.removeChild(activityList.lastChild);
            }
        }
    }

    // Update every 30 seconds
    setInterval(updateDashboard, 30000);
    
    // Also update when the page gains focus
    window.addEventListener('focus', updateDashboard);
    
    // Initial update
    document.addEventListener('DOMContentLoaded', updateDashboard);
    </script>
</body>
</html>
<?php $conn->close(); ?>