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

// Handle approval/rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['service_id'])) {
    $service_id = intval($_POST['service_id']);
    $action = $_POST['action'] === 'approve' ? 1 : 0;
    
    $query = "UPDATE services SET is_approved = ? WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $action, $service_id);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "Service " . ($action ? "approved" : "rejected") . " successfully";
    } else {
        $_SESSION['error'] = "Error updating service: " . $stmt->error;
    }
    $stmt->close();
    
    header("Location: admin_services.php");
    exit();
}

// Get all services with seller info
$query = "SELECT s.*, u.username as seller_name 
          FROM services s 
          JOIN users u ON s.seller_id = u.id 
          ORDER BY s.created_at DESC";
$result = $conn->query($query);
$services = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Services - StrathConnect</title>
    <link rel="stylesheet" href="../assets/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>  
    <div class="dashboard-container">
        <main class="dashboard-content">
            <div class="services-container">
                <div class="services-header">
                    <h1>Manage Services</h1>
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
                            <p>There are currently no services to review</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($services as $service): ?>
                            <div class="service-card">
                                <div class="service-image-container">
                                    <img src="../assets/images/services/<?php echo htmlspecialchars($service['image_path'] ?? 'service-placeholder.jpg'); ?>" 
                                         alt="<?php echo htmlspecialchars($service['title']); ?>" class="service-image">
                                    <div class="service-status status-<?php echo $service['is_approved'] ? 'approved' : 'pending'; ?>">
                                        <?php echo $service['is_approved'] ? 'Approved' : 'Pending'; ?>
                                    </div>
                                </div>
                                
                                <div class="service-details">
                                    <h3 class="service-title"><?php echo htmlspecialchars($service['title']); ?></h3>
                                    <span class="service-category"><?php echo htmlspecialchars($service['category']); ?></span>
                                    
                                    <div class="service-price">
                                        KSh <?php echo number_format($service['price'], 2); ?>
                                        <span class="service-rate-type">(<?php echo htmlspecialchars($service['rate_type']); ?>)</span>
                                    </div>
                                    
                                    <p class="service-description">
                                        <?php echo htmlspecialchars(substr($service['description'], 0, 150)); ?>
                                        <?php if (strlen($service['description']) > 150): ?>...<?php endif; ?>
                                    </p>
                                    
                                    <div class="meta-item">
                                        <span class="meta-label">Seller:</span>
                                        <span class="meta-value"><?php echo htmlspecialchars($service['seller_name']); ?></span>
                                    </div>
                                    
                                    <div class="service-actions">
                                        <?php if (!$service['is_approved']): ?>
                                            <form method="POST" class="action-form">
                                                <input type="hidden" name="service_id" value="<?php echo $service['id']; ?>">
                                                <input type="hidden" name="action" value="approve">
                                                <button type="submit" class="btn btn-approve">
                                                    <i class="fas fa-check"></i> Approve
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                        
                                        <form method="POST" class="action-form">
                                            <input type="hidden" name="service_id" value="<?php echo $service['id']; ?>">
                                            <input type="hidden" name="action" value="<?php echo $service['is_approved'] ? 'reject' : 'approve'; ?>">
                                            <button type="submit" class="btn btn-<?php echo $service['is_approved'] ? 'reject' : 'approve'; ?>">
                                                <i class="fas fa-<?php echo $service['is_approved'] ? 'times' : 'check'; ?>"></i>
                                                <?php echo $service['is_approved'] ? 'Reject' : 'Approve'; ?>
                                            </button>
                                        </form>
                                        
                                        <a href="service_details.php?id=<?php echo $service['id']; ?>" class="btn btn-view">
                                            <i class="fas fa-eye"></i> View
                                        </a>
                                    </div>
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