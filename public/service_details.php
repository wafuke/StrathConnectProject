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

// Get service details
if (!isset($_GET['id'])) {
    $_SESSION['error'] = "No service specified";
    header("Location: admin_services.php");
    exit();
}

$service_id = intval($_GET['id']);

$query = "SELECT s.*, u.username as seller_name, u.email as seller_email 
          FROM services s 
          JOIN users u ON s.seller_id = u.id 
          WHERE s.id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $service_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    $_SESSION['error'] = "Service not found";
    header("Location: admin_services.php");
    exit();
}

$service = $result->fetch_assoc();
$stmt->close();

// Handle approval/rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'] === 'approve' ? 1 : 0;
    
    $query = "UPDATE services SET is_approved = ? WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $action, $service_id);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "Service " . ($action ? "approved" : "rejected") . " successfully";
        header("Location: admin_services.php");
        exit();
    } else {
        $_SESSION['error'] = "Error updating service: " . $stmt->error;
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Service Details - StrathConnect</title>
    <link rel="stylesheet" href="../assets/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    
    <div class="dashboard-container">
        <main class="dashboard-content">
            <div class="service-detail-container">
                <div class="service-header">
                    <h1>Service Details</h1>
                    <a href="admin_services.php" class="btn btn-back">
                        <i class="fas fa-arrow-left"></i> Back to Services
                    </a>
                </div>
                
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-error">
                        <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
                    </div>
                <?php endif; ?>
                
                <div class="service-content">
                    <div class="service-image-container">
                        <img src="../assets/images/services/<?php echo htmlspecialchars($service['image_path'] ?? 'service-placeholder.jpg'); ?>" 
                             alt="<?php echo htmlspecialchars($service['title']); ?>" class="service-image">
                        <div class="service-status-badge status-<?php echo $service['is_approved'] ? 'approved' : 'pending'; ?>">
                            <?php echo $service['is_approved'] ? 'Approved' : 'Pending Approval'; ?>
                        </div>
                    </div>
                    
                    <div class="service-info">
                        <h2 class="service-title"><?php echo htmlspecialchars($service['title']); ?>
                            <span class="rate-type-badge"><?php echo ucfirst($service['rate_type']); ?></span>
                        </h2>
                        
                        <div class="service-meta">
                            <div class="meta-item">
                                <span class="meta-label">Category:</span>
                                <span class="meta-value"><?php echo htmlspecialchars($service['category']); ?></span>
                            </div>
                            <div class="meta-item">
                                <span class="meta-label">Price:</span>
                                <span class="meta-value">KSh <?php echo number_format($service['price'], 2); ?></span>
                            </div>
                            <div class="meta-item">
                                <span class="meta-label">Date Listed:</span>
                                <span class="meta-value"><?php echo date('M j, Y', strtotime($service['created_at'])); ?></span>
                            </div>
                        </div>
                        
                        <div class="seller-info">
                            <h3>Seller Information</h3>
                            <div class="meta-item">
                                <span class="meta-label">Name:</span>
                                <span class="meta-value"><?php echo htmlspecialchars($service['seller_name']); ?></span>
                            </div>
                            <div class="meta-item">
                                <span class="meta-label">Email:</span>
                                <span class="meta-value"><?php echo htmlspecialchars($service['seller_email']); ?></span>
                            </div>
                        </div>
                        
                        <div class="service-description">
                            <h3>Service Description</h3>
                            <p><?php echo nl2br(htmlspecialchars($service['description'])); ?></p>
                        </div>
                        
                        <div class="service-actions">
                            <?php if (!$service['is_approved']): ?>
                                <form method="POST" class="action-form">
                                    <input type="hidden" name="action" value="approve">
                                    <button type="submit" class="btn btn-approve">
                                        <i class="fas fa-check"></i> Approve Service
                                    </button>
                                </form>
                            <?php endif; ?>
                            
                            <form method="POST" class="action-form">
                                <input type="hidden" name="action" value="<?php echo $service['is_approved'] ? 'reject' : 'approve'; ?>">
                                <button type="submit" class="btn btn-<?php echo $service['is_approved'] ? 'reject' : 'approve'; ?>">
                                    <i class="fas fa-<?php echo $service['is_approved'] ? 'times' : 'check'; ?>"></i>
                                    <?php echo $service['is_approved'] ? 'Reject Service' : 'Approve Service'; ?>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
   
</body>
</html>
<?php $conn->close(); ?>