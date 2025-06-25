<?php
session_start();

// Verify admin is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../public/login.php");
    exit();
}

// Database connection
$conn = new mysqli('localhost', 'root', '', 'strathconnect');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get product ID
if (!isset($_GET['id'])) {
    header("Location: admin_products.php");
    exit();
}

$product_id = intval($_GET['id']);

// Get product details with seller info
$query = "SELECT p.*, u.username as seller_username 
          FROM products p
          JOIN users u ON p.seller_id = u.id
          WHERE p.id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
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
        header("Location: admin_product_details.php?id=" . $product_id);
        exit();
    } else {
        $_SESSION['error'] = "Error updating product: " . $stmt->error;
    }
    $stmt->close();
}

// Get related products
$related_query = "SELECT p.* FROM products p 
                 WHERE p.category = ? AND p.id != ? 
                 LIMIT 4";
$stmt = $conn->prepare($related_query);
$stmt->bind_param("si", $product['category'], $product_id);
$stmt->execute();
$related_products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['name']); ?> - Admin - StrathConnect</title>
    <link rel="stylesheet" href="../assets/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        .product-header {
            display: flex;
            gap: 30px;
            margin-bottom: 30px;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .product-images {
            flex: 1;
        }
        .product-info {
            flex: 1;
        }
        .main-image {
            width: 100%;
            max-height: 500px;
            object-fit: contain;
            border: 1px solid #ddd;
            border-radius: 8px;
        }
        .price {
            font-size: 24px;
            color: #B12704;
            font-weight: bold;
            margin: 15px 0;
        }
        .admin-actions {
            display: flex;
            gap: 15px;
            margin: 25px 0;
        }
        .btn {
            padding: 10px 20px;
            border-radius: 4px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
            border: none;
        }
        .btn-approve {
            background: #28a745;
            color: white;
        }
        .btn-approve:hover {
            background: #218838;
        }
        .btn-reject {
            background: #dc3545;
            color: white;
        }
        .btn-reject:hover {
            background: #c82333;
        }
        .btn-view {
            background: #17a2b8;
            color: white;
        }
        .btn-view:hover {
            background: #138496;
        }
        .status-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-weight: bold;
            display: inline-block;
            margin-bottom: 15px;
        }
        .status-pending {
            background: #ffc107;
            color: #000;
        }
        .status-approved {
            background: #28a745;
            color: #fff;
        }
        .related-products {
            margin-top: 50px;
        }
        .related-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
        }
        .product-card {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }
        .product-card:hover {
            transform: translateY(-5px);
        }
        .product-card img {
            width: 100%;
            height: 180px;
            object-fit: cover;
        }
        .product-card-body {
            padding: 15px;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="logo">StrathConnect Admin</div>
        <ul class="nav-links">
            <li><a href="admin_dashboard.php">Dashboard</a></li>
            <li><a href="admin_users.php">Users</a></li>
            <li><a href="admin_products.php" class="active">Products</a></li>
            <li><a href="admin_services.php">Services</a></li>
            <li><a href="../public/login.php">Logout</a></li>
        </ul>
    </nav>

    <div class="container">
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

        <div class="product-header">
            <div class="product-images">
                <img src="../assets/images/products/<?php echo htmlspecialchars($product['image_path'] ?? 'default.jpg'); ?>" 
                     alt="<?php echo htmlspecialchars($product['name']); ?>" 
                     class="main-image">
            </div>
            
            <div class="product-info">
                <div class="status-badge status-<?php echo $product['is_approved'] ? 'approved' : 'pending'; ?>">
                    <?php echo $product['is_approved'] ? 'Approved' : 'Pending Approval'; ?>
                </div>
                <h1><?php echo htmlspecialchars($product['name']); ?></h1>
                <div class="seller-info">
                    <strong>Seller:</strong> <?php echo htmlspecialchars($product['seller_username']); ?>
                </div>
                <div class="category-info">
                    <strong>Category:</strong> <?php echo htmlspecialchars($product['category']); ?>
                </div>
                <div class="price">KSh <?php echo number_format($product['price'], 2); ?></div>
                
                <div class="product-description">
                    <h3>Description</h3>
                    <p><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
                </div>

                <div class="admin-actions">
                    <form method="POST" class="action-form">
                        <input type="hidden" name="action" value="approve">
                        <button type="submit" class="btn btn-approve">
                            <i class="fas fa-check"></i> Approve
                        </button>
                    </form>
                    <form method="POST" class="action-form">
                        <input type="hidden" name="action" value="reject">
                        <button type="submit" class="btn btn-reject">
                            <i class="fas fa-times"></i> Reject
                        </button>
                    </form>
                    <a href="admin_products.php" class="btn btn-view">
                        <i class="fas fa-arrow-left"></i> Back to Products
                    </a>
                </div>
            </div>
        </div>

        <?php if (!empty($related_products)): ?>
        <div class="related-products">
            <h2>Related Products</h2>
            <div class="related-grid">
                <?php foreach ($related_products as $related): ?>
                    <div class="product-card">
                        <img src="../assets/images/products/<?php echo htmlspecialchars($related['image_path'] ?? 'default.jpg'); ?>" 
                             alt="<?php echo htmlspecialchars($related['name']); ?>">
                        <div class="product-card-body">
                            <h3><?php echo htmlspecialchars($related['name']); ?></h3>
                            <div class="price">KSh <?php echo number_format($related['price'], 2); ?></div>
                            <a href="admin_product_details.php?id=<?php echo $related['id']; ?>" class="btn btn-view">
                                View Details
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <footer class="footer">
        <p>&copy; <?php echo date('Y'); ?> StrathConnect. All rights reserved.</p>
    </footer>
</body>
</html>