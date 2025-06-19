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

// Initialize variables
$product = [];
$errors = [];
$categories = ['Books', 'Electronics', 'Clothing', 'Furniture', 'Other'];

// Get product details if ID is provided
if (isset($_GET['id'])) {
    $product_id = intval($_GET['id']);
    $seller_id = $_SESSION['user_id'];
    
    $query = "SELECT * FROM products WHERE id = ? AND seller_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $product_id, $seller_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $product = $result->fetch_assoc();
    } else {
        $_SESSION['error'] = "Product not found or you don't have permission to edit it";
        header("Location: seller_products.php");
        exit();
    }
    $stmt->close();
} else {
    $_SESSION['error'] = "No product specified";
    header("Location: seller_products.php");
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate and sanitize inputs
    $name = trim($_POST['name']);
    $category = trim($_POST['category']);
    $price = floatval($_POST['price']);
    $description = trim($_POST['description']);
    
    // Validate inputs
    if (empty($name)) {
        $errors[] = "Product name is required";
    }
    
    if (!in_array($category, $categories)) {
        $errors[] = "Invalid category selected";
    }
    
    if (!is_numeric($price) || $price <= 0) {
        $errors[] = "Valid price is required";
    }
    
    // Process if no errors
    if (empty($errors)) {
        // Handle image upload if new image was provided
        $image_path = $product['image_path'];
        
        if ($_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $target_dir = "../assets/images/products/";
            $allowed_types = ['image/jpeg', 'image/png'];
            $file_type = $_FILES['image']['type'];
            
            // Validate file type
            if (!in_array($file_type, $allowed_types)) {
                $errors[] = "Only JPEG and PNG images are allowed";
            } 
            // Validate file size (2MB max)
            elseif ($_FILES['image']['size'] > 2097152) {
                $errors[] = "Image size must be less than 2MB";
            } else {
                // Generate unique filename
                $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                $new_image_name = uniqid('product_') . '.' . $ext;
                $target_file = $target_dir . $new_image_name;
                
                if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                    // Delete old image if it exists and is not the placeholder
                    if ($image_path && $image_path !== 'placeholder.jpg') {
                        $old_image = $target_dir . $image_path;
                        if (file_exists($old_image)) {
                            unlink($old_image);
                        }
                    }
                    $image_path = $new_image_name;
                } else {
                    $errors[] = "Failed to upload image";
                }
            }
        }
        
        // Update product if no errors
        if (empty($errors)) {
            $query = "UPDATE products SET name = ?, category = ?, description = ?, price = ?, image_path = ? WHERE id = ? AND seller_id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("sssdsii", $name, $category, $description, $price, $image_path, $product_id, $seller_id);
            
            if ($stmt->execute()) {
                $_SESSION['success'] = "Product updated successfully";
                header("Location: seller_products.php");
                exit();
            } else {
                $errors[] = "Error updating product: " . $stmt->error;
            }
            $stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Product - StrathConnect</title>
    <link rel="stylesheet" href="../assets/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    
    
    <div class="dashboard-container">
        
        
        <main class="dashboard-content">
            <div class="dashboard-header">
                <h1>Edit Product</h1>
                <a href="seller_products.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Products
                </a>
            </div>
            
            <?php if (!empty($errors)): ?>
                <div class="alert alert-error">
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <form class="product-form" method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="name">Product Name*</label>
                    <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($product['name']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="category">Category*</label>
                    <select id="category" name="category" required>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo $product['category'] === $cat ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="price">Price (KSh)*</label>
                    <input type="number" id="price" name="price" min="0" step="0.01" 
                           value="<?php echo htmlspecialchars($product['price']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="4"><?php echo htmlspecialchars($product['description']); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label>Current Image</label>
                    <div class="current-image">
                        <img src="../assets/images/products/<?php echo htmlspecialchars($product['image_path'] ?? 'placeholder.jpg'); ?>" 
                             alt="Current product image">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="image">Update Image</label>
                    <input type="file" id="image" name="image" accept="image/*">
                    <small>Leave blank to keep current image (JPEG/PNG, max 2MB)</small>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                    <a href="seller_products.php" class="btn btn-secondary">
                        Cancel
                    </a>
                </div>
            </form>
        </main>
    </div>
    
    
</body>
</html>
<?php
$conn->close();
?>