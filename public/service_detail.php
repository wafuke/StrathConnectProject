<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'buyer') {
    header("Location: ../public/login.php");
    exit();
}

// Database connection
$conn = new mysqli('localhost', 'root', '', 'strathconnect');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get service ID from URL
$service_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Fetch service details
$service = [];
$query = "SELECT s.*, u.username as seller_username, u.profile_pic as seller_profile, 
          u.business_name, u.contact_number
          FROM services s
          JOIN users u ON s.seller_id = u.id
          WHERE s.id = ? AND s.is_approved = 1";
          
if (!$stmt = $conn->prepare($query)) {
    die("Error preparing query: " . $conn->error);
}

if (!$stmt->bind_param("i", $service_id)) {
    die("Error binding parameters: " . $stmt->error);
}

if (!$stmt->execute()) {
    die("Error executing query: " . $stmt->error);
}

$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: services.php");
    exit();
}

$service = $result->fetch_assoc();

// Fetch related services from same seller
$related_services = [];
$related_query = "SELECT id, title, price 
                 FROM services 
                 WHERE seller_id = ? AND id != ? AND is_approved = 1
                 LIMIT 4";
                 
if (!$stmt = $conn->prepare($related_query)) {
    die("Error preparing related services query: " . $conn->error);
}

if (!$stmt->bind_param("ii", $service['seller_id'], $service_id)) {
    die("Error binding parameters: " . $stmt->error);
}

if (!$stmt->execute()) {
    die("Error executing related services query: " . $stmt->error);
}

$related_result = $stmt->get_result();
$related_services = $related_result->fetch_all(MYSQLI_ASSOC);

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($service['title']); ?> - StrathConnect</title>
    <link rel="stylesheet" href="../assets/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .service-detail-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .service-header {
            display: flex;
            gap: 30px;
            margin-bottom: 40px;
            flex-wrap: wrap;
        }
        
        .service-gallery {
            flex: 1;
            min-width: 300px;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 300px;
            background: #f5f5f5;
            border-radius: 8px;
            font-size: 100px;
            color: #ccc;
        }
        
        .service-info {
            flex: 1;
            min-width: 300px;
        }
        
        .service-title {
            font-size: 28px;
            margin-bottom: 10px;
            color: #333;
        }
        
        .service-price {
            font-size: 24px;
            font-weight: bold;
            color: #FF9900;
            margin-bottom: 15px;
        }
        
        .service-meta {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        
        .seller-info {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
            padding: 10px;
            background: #f9f9f9;
            border-radius: 8px;
        }
        
        .seller-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
        }
        
        .service-description {
            margin-bottom: 30px;
            line-height: 1.6;
            white-space: pre-line;
        }
        
        .action-buttons {
            display: flex;
            gap: 15px;
            margin-top: 30px;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 12px 24px;
            border-radius: 4px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
            text-align: center;
            display: inline-block;
            text-decoration: none;
        }
        
        .btn-primary {
            background-color: #FF9900;
            color: white;
            border: none;
        }
        
        .btn-primary:hover {
            background-color: #e68a00;
        }
        
        .btn-outline {
            background-color: transparent;
            border: 2px solid #FF9900;
            color: #FF9900;
        }
        
        .btn-outline:hover {
            background-color: #FF9900;
            color: white;
        }
        
        .related-services {
            margin-top: 50px;
        }
        
        .related-title {
            font-size: 24px;
            margin-bottom: 20px;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }
        
        .related-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
        }
        
        .related-card {
            border: 1px solid #eee;
            border-radius: 8px;
            overflow: hidden;
            transition: transform 0.3s;
            text-decoration: none;
            color: inherit;
            padding: 20px;
            background: white;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }
        
        .related-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .related-icon {
            font-size: 60px;
            color: #ccc;
            margin-bottom: 10px;
        }
        
        .related-details {
            text-align: center;
        }
        
        .related-price {
            font-weight: bold;
            color: #FF9900;
            margin-top: 5px;
        }
        
        @media (max-width: 768px) {
            .service-header {
                flex-direction: column;
            }
            
            .action-buttons {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="logo">StrathConnect</div>
        <ul class="nav-links">
            <li><a href="marketplace.php">Marketplace</a></li>
            <li><a href="services.php" class="active">Services</a></li>
            <li><a href="buyer_orders.php">My Orders</a></li>
            <li><a href="../public/login.php">Logout</a></li>
        </ul>
    </nav>

    <div class="dashboard-container">
        <main class="dashboard-content">
            <div class="service-detail-container">
                <div class="service-header">
                    <div class="service-gallery">
                        <i class="fas fa-concierge-bell"></i>
                    </div>
                    
                    <div class="service-info">
                        <h1 class="service-title"><?php echo htmlspecialchars($service['title']); ?></h1>
                        <div class="service-price">KSh <?php echo number_format($service['price'], 2); ?></div>
                        
                        <div class="seller-info">
                            <img src="<?php echo htmlspecialchars($service['seller_profile'] ?? '../assets/images/profile-placeholder.png'); ?>" 
                                 alt="<?php echo htmlspecialchars($service['seller_username']); ?>" class="seller-avatar">
                            <div>
                                <div class="seller-name"><?php 
                                    echo !empty($service['business_name']) 
                                        ? htmlspecialchars($service['business_name']) 
                                        : '@' . htmlspecialchars($service['seller_username']); 
                                ?></div>
                                <?php if (!empty($service['contact_number'])): ?>
                                    <div class="seller-contact">
                                        <i class="fas fa-phone"></i> <?php echo htmlspecialchars($service['contact_number']); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="service-description">
                            <?php echo nl2br(htmlspecialchars($service['description'])); ?>
                        </div>
                        
                        <div class="action-buttons">
                            <form action="add_to_cart.php" method="post" style="display: inline;">
                                <input type="hidden" name="service_id" value="<?php echo $service['id']; ?>">
                                <button type="submit" class="btn btn-outline">
                                    <i class="fas fa-cart-plus"></i> Add to Cart
                                </button>
                            </form>
                            
                            <form action="checkout.php" method="post" style="display: inline;">
                                <input type="hidden" name="service_id" value="<?php echo $service['id']; ?>">
                                <input type="hidden" name="quantity" value="1">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-credit-card"></i> Checkout Now
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                
                <?php if (!empty($related_services)): ?>
                    <div class="related-services">
                        <h3 class="related-title">More Services from This Seller</h3>
                        <div class="related-grid">
                            <?php foreach ($related_services as $related): ?>
                                <a href="service_detail.php?id=<?php echo $related['id']; ?>" class="related-card">
                                    <div class="related-icon"><i class="fas fa-concierge-bell"></i></div>
                                    <div class="related-details">
                                        <h4><?php echo htmlspecialchars($related['title']); ?></h4>
                                        <div class="related-price">KSh <?php echo number_format($related['price'], 2); ?></div>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <footer class="footer">
        <p>&copy; <?php echo date('Y'); ?> StrathConnect. All rights reserved.</p>
    </footer>
</body>
</html>
