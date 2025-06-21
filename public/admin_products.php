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
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['product_id'])) {
    $product_id = intval($_POST['product_id']);
    $action = $_POST['action'] === 'approve' ? 1 : 0;
    
    $query = "UPDATE products SET is_approved = ? WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $action, $product_id);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "Product " . ($action ? "approved" : "rejected") . " successfully";
    } else {
        $_SESSION['error'] = "Error updating product: " . $stmt->error;
    }
    $stmt->close();
    
    header("Location: admin_products.php");
    exit();
}

// Get all products with seller info
$query = "SELECT p.*, u.username as seller_name 
          FROM products p 
          JOIN users u ON p.seller_id = u.id 
          ORDER BY p.created_at DESC";
$result = $conn->query($query);
$products = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Products - StrathConnect</title>
    <link rel="stylesheet" href="../assets/css/admin_products.css">
    <link rel="stylesheet" href="../assets/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    
    
    <div class="dashboard-container">
        
        
        
        <main class="dashboard-content">
            <h1>Manage Products</h1>
            
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
            
            <div class="products-grid">
                <?php if (empty($products)): ?>
                    <div class="empty-state">
                        <i class="fas fa-box-open"></i>
                        <h3>No products found</h3>
                        <p>There are currently no products to review</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($products as $product): ?>
                        <div class="product-card">
                            <div class="product-image-container">
                                <img src="../assets/images/products/<?php echo htmlspecialchars($product['image_path'] ?? 'placeholder.jpg'); ?>" 
                                     alt="<?php echo htmlspecialchars($product['name']); ?>" class="product-image">
                                <div class="product-status status-<?php echo $product['is_approved'] ? 'approved' : 'pending'; ?>">
                                    <?php echo $product['is_approved'] ? 'Approved' : 'Pending'; ?>
                                </div>
                            </div>
                            
                            <div class="product-details">
                                <h3 class="product-title"><?php echo htmlspecialchars($product['name']); ?></h3>
                                <p class="product-seller"><strong>Seller:</strong> <?php echo htmlspecialchars($product['seller_name']); ?></p>
                                <p class="product-category"><strong>Category:</strong> <?php echo htmlspecialchars($product['category']); ?></p>
                                <p class="product-price"><strong>Price:</strong> KSh <?php echo number_format($product['price'], 2); ?></p>
                                
                                <div class="product-actions">
                                    <?php if (!$product['is_approved']): ?>
                                        <form class="action-form" method="POST">
                                            <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                            <input type="hidden" name="action" value="approve">
                                            <button type="submit" class="btn btn-approve">
                                                <i class="fas fa-check"></i> Approve
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    
                                    <form class="action-form" method="POST">
                                        <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                        <input type="hidden" name="action" value="reject">
                                        <button type="submit" class="btn btn-reject">
                                            <i class="fas fa-times"></i> <?php echo $product['is_approved'] ? 'Unapprove' : 'Reject'; ?>
                                        </button>
                                    </form>
                                    
                                    <a href="product_details.php?id=<?php echo $product['id']; ?>" class="btn btn-view">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </main>
    </div>
    
    
</body>
</html>
<?php $conn->close(); ?>