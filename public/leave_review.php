<?php
session_start();
// Start session and verify admin access
session_start();
// Database connection
$host = 'localhost';
$user = 'root';
$pass = '';
$db = 'strathconnect';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
// Verify buyer is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'buyer') {
    header("Location: ../public/login.php");
    exit();
}

// Get product and order IDs
$product_id = filter_input(INPUT_GET, 'product_id', FILTER_VALIDATE_INT);
$order_id = filter_input(INPUT_GET, 'order_id', FILTER_VALIDATE_INT);

// Verify the order belongs to the buyer and is completed
$check_query = "SELECT 1 FROM orders 
                WHERE id = ? AND buyer_id = ? AND status = 'Completed'";
$stmt = $conn->prepare($check_query);
$stmt->bind_param("ii", $order_id, $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Invalid order or order not completed");
}

// Get product details
$product_query = "SELECT name FROM products WHERE id = ?";
$stmt = $conn->prepare($product_query);
$stmt->bind_param("i", $product_id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rating = filter_input(INPUT_POST, 'rating', FILTER_VALIDATE_INT, [
        'options' => ['min_range' => 1, 'max_range' => 5]
    ]);
    $review = filter_input(INPUT_POST, 'review', FILTER_SANITIZE_STRING);
    
    if ($rating && $review) {
        $insert_query = "INSERT INTO reviews 
                        (product_id, buyer_id, order_id, rating, review, created_at)
                        VALUES (?, ?, ?, ?, ?, NOW())";
        $stmt = $conn->prepare($insert_query);
        $stmt->bind_param("iiiss", $product_id, $_SESSION['user_id'], $order_id, $rating, $review);
        $stmt->execute();
        
        header("Location: buyer_orders.php?review_success=1");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Leave Review - StrathConnect</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
   <nav class="navbar">
        <div class="logo">StrathConnect</div>
        <ul class="nav-links">
            <li><a href="../public/index.php">Home</a></li>
            <li><a href="marketplace.php">Marketplace</a></li>
            <li><a href="services.php">Services</a></li>
            <li><a href="buyer_orders.php" class="active">My Orders</a></li>
            <li><a href="buyer_messages.php">Messages</a></li>
            <li><a href="../public/login.php">Logout</a></li>
        </ul>
    </nav>

    <div class="container">
        <h1>Leave Review</h1>
        <p>Reviewing: <?= htmlspecialchars($product['name']) ?></p>
        
        <form method="POST" class="review-form">
            <div class="form-group">
                <label>Rating:</label>
                <div class="rating-stars">
                    <?php for ($i = 5; $i >= 1; $i--): ?>
                    <input type="radio" id="star<?= $i ?>" name="rating" value="<?= $i ?>" required>
                    <label for="star<?= $i ?>">★</label>
                    <?php endfor; ?>
                </div>
            </div>
            
            <div class="form-group">
                <label for="review">Your Review:</label>
                <textarea id="review" name="review" rows="5" required></textarea>
            </div>
            
            <button type="submit" class="btn">Submit Review</button>
        </form>
    </div>
</body>
</html>