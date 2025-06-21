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

// Get product details
if (!isset($_GET['id'])) {
    $_SESSION['error'] = "No product specified";
    header("Location: admin_products.php");
    exit();
}

$product_id = intval($_GET['id']);

$query = "SELECT p.*, u.username as seller_name, u.email as seller_email 
          FROM products p 
          JOIN users u ON p.seller_id = u.id 
          WHERE p.id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    $_SESSION['error'] = "Product not found";
    header("Location: admin_products.php");
    exit();
}

$product = $result->fetch_assoc();
$stmt->close();

// Handle approval/rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'] === 'approve' ? 1 : 0;
    
    $query = "UPDATE products SET is_approved = ? WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $action, $product_id);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "Product " . ($action ? "approved" : "rejected") . " successfully";
        header("Location: admin_products.php");
        exit();
    } else {
        $_SESSION['error'] = "Error updating product: " . $stmt->error;
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Details - StrathConnect</title>
    <link rel="stylesheet" href="../assets/style.css">
    <link rel="stylesheet" href="../assets/css/product_details.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    
    
    <div class="dashboard-container">
        <main class="dashboard-content">
            <div class="product-card-container">
                <div class="product-header">
                    <h1>Product Details</h1>
                    <a href="admin_products.php" class="btn btn-back">
                        <i class="fas fa-arrow-left"></i> Back to Products
                    </a>
                </div>
                
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-error">
                        <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
                    </div>
                <?php endif; ?>
                
                <div class="product-content">
                    <div class="product-image-container">
                        <img src="../assets/images/products/<?php echo htmlspecialchars($product['image_path'] ?? 'placeholder.jpg'); ?>" 
                             alt="<?php echo htmlspecialchars($product['name']); ?>" class="product-image">
                        <div class="product-status-badge status-<?php echo $product['is_approved'] ? 'approved' : 'pending'; ?>">
                            <?php echo $product['is_approved'] ? 'Approved' : 'Pending Approval'; ?>
                        </div>
                    </div>
                    
                    <div class="product-info">
                        <h2 class="product-title"><?php echo htmlspecialchars($product['name']); ?></h2>
                        
                        <div class="product-meta">
                            <div class="meta-item">
                                <span class="meta-label">Category:</span>
                                <span class="meta-value"><?php echo htmlspecialchars($product['category']); ?></span>
                            </div>
                            <div class="meta-item">
                                <span class="meta-label">Price:</span>
                                <span class="meta-value">KSh <?php echo number_format($product['price'], 2); ?></span>
                            </div>
                            <div class="meta-item">
                                <span class="meta-label">Date Listed:</span>
                                <span class="meta-value"><?php echo date('M j, Y', strtotime($product['created_at'])); ?></span>
                            </div>
                        </div>
                        
                        <div class="seller-info">
                            <h3>Seller Information</h3>
                            <div class="meta-item">
                                <span class="meta-label">Name:</span>
                                <span class="meta-value"><?php echo htmlspecialchars($product['seller_name']); ?></span>
                            </div>
                            <div class="meta-item">
                                <span class="meta-label">Email:</span>
                                <span class="meta-value"><?php echo htmlspecialchars($product['seller_email']); ?></span>
                            </div>
                        </div>
                        
                        <h3>Description</h3>
                        <p><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
                        
                        <div class="product-actions">
                            <?php if (!$product['is_approved']): ?>
                                <form method="POST" class="action-form">
                                    <input type="hidden" name="action" value="approve">
                                    <button type="submit" class="btn btn-approve">
                                        <i class="fas fa-check"></i> Approve Product
                                    </button>
                                </form>
                            <?php endif; ?>
                            
                            <form method="POST" class="action-form">
                                <input type="hidden" name="action" value="<?php echo $product['is_approved'] ? 'reject' : 'approve'; ?>">
                                <button type="submit" class="btn btn-<?php echo $product['is_approved'] ? 'reject' : 'approve'; ?>">
                                    <i class="fas fa-<?php echo $product['is_approved'] ? 'times' : 'check'; ?>"></i>
                                    <?php echo $product['is_approved'] ? 'Reject Product' : 'Approve Product'; ?>
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