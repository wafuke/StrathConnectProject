<?php
session_start();

// Verify seller is logged in
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

// Get seller's services
$seller_id = $_SESSION['user_id'];
$query = "SELECT * FROM services WHERE seller_id = ? ORDER BY created_at DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $seller_id);
$stmt->execute();
$result = $stmt->get_result();
$services = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Services - StrathConnect</title>
    <link rel="stylesheet" href="../assets/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <nav class="navbar">
        <div class="logo">StrathConnect</div>
        <ul class="nav-links">
            <li><a href="../public/index.php">Home</a></li>
            <li><a href="seller_dashboard.php">Dashboard</a></li>
            <li><a href="seller_products.php">My Products</a></li>
            <li><a href="seller_services.php" class="active">My Services</a></li>
            <li><a href="../public/login.php">Logout</a></li>
        </ul>
    </nav>

    <div class="dashboard-container">
        <aside class="sidebar">
            <div class="profile-summary">
                <img src="../assets/images/profile-placeholder.png" alt="Profile" class="profile-pic">
                <h3><?php echo htmlspecialchars($_SESSION['username'] ?? 'Seller'); ?></h3>
                <p>@<?php echo htmlspecialchars($_SESSION['username'] ?? 'username'); ?></p>
            </div>
            <nav class="sidebar-nav">
                <a href="seller_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                <a href="seller_products.php"><i class="fas fa-box-open"></i> My Products</a>
                <a href="seller_services.php" class="active"><i class="fas fa-concierge-bell"></i> My Services</a>
                <a href="seller_settings.php"><i class="fas fa-cog"></i> Settings</a>
            </nav>
        </aside>

        <main class="dashboard-content">
            <div class="dashboard-header">
                <h1>My Services</h1>
                <a href="add_service.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Add New Service
                </a>
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
            
            <div class="services-grid">
                <?php if (empty($services)): ?>
                    <div class="empty-state">
                        <i class="fas fa-concierge-bell"></i>
                        <h3>No services found</h3>
                        <p>You haven't listed any services yet. Get started by adding your first service!</p>
                        <a href="add_service.php" class="btn btn-primary">Add Service</a>
                    </div>
                <?php else: ?>
                    <?php foreach ($services as $service): ?>
                        <div class="service-card">
                            <div class="service-image">
                                <img src="../assets/images/services/<?php echo htmlspecialchars($service['image_path'] ?? 'service-placeholder.jpg'); ?>" 
                                     alt="<?php echo htmlspecialchars($service['title']); ?>">
                                <div class="service-status <?php echo $service['is_approved'] ? 'approved' : 'pending'; ?>">
                                    <?php echo $service['is_approved'] ? 'Approved' : 'Pending Approval'; ?>
                                </div>
                            </div>
                            <div class="service-details">
                                <h3><?php echo htmlspecialchars($service['title']); ?></h3>
                                <p class="service-category"><?php echo htmlspecialchars($service['category']); ?></p>
                                <p class="service-rate">KSh <?php echo number_format($service['price'], 2); ?> 
                                    <small>(<?php echo htmlspecialchars($service['rate_type']); ?>)</small>
                                </p>
                                <p class="service-description"><?php echo htmlspecialchars(substr($service['description'], 0, 100)); ?>...</p>
                                <div class="service-actions">
                                    <a href="edit_service.php?id=<?php echo $service['id']; ?>" class="btn btn-edit">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                    <a href="delete_service.php?id=<?php echo $service['id']; ?>" class="btn btn-delete" 
                                       onclick="return confirm('Are you sure you want to delete this service?')">
                                        <i class="fas fa-trash"></i> Delete
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <footer class="footer">
        <p>&copy; <?php echo date('Y'); ?> StrathConnect. All rights reserved.</p>
    </footer>
</body>
</html>
<?php
$conn->close();
?>