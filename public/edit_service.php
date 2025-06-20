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
$service = [];
$errors = [];
$categories = ['Website', 'Design', 'Programming', 'Writing', 'Other'];
$rate_types = ['hourly', 'fixed', 'per_project'];

// Get service details
if (!isset($_GET['id'])) {
    $_SESSION['error'] = "No service specified";
    header("Location: seller_services.php");
    exit();
}

$service_id = intval($_GET['id']);
$seller_id = $_SESSION['user_id'];

$query = "SELECT * FROM services WHERE id = ? AND seller_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $service_id, $seller_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    $_SESSION['error'] = "Service not found or you don't have permission";
    header("Location: seller_services.php");
    exit();
}

$service = $result->fetch_assoc();
$stmt->close();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate inputs
    $title = trim($_POST['title']);
    $category = trim($_POST['category']);
    $description = trim($_POST['description']);
    $price = floatval($_POST['price']);
    $rate_type = trim($_POST['rate_type']);
    
    if (empty($title)) $errors[] = "Title is required";
    if (!in_array($category, $categories)) $errors[] = "Invalid category";
    if (empty($description)) $errors[] = "Description is required";
    if ($price <= 0) $errors[] = "Price must be positive";
    if (!in_array($rate_type, $rate_types)) $errors[] = "Invalid rate type";

    // Process image if no errors
    $image_path = $service['image_path'];
    if (empty($errors) && isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $target_dir = "../assets/images/services/";
        $allowed_types = ['image/jpeg', 'image/png'];
        
        if (!in_array($_FILES['image']['type'], $allowed_types)) {
            $errors[] = "Only JPEG/PNG images allowed";
        } elseif ($_FILES['image']['size'] > 2097152) {
            $errors[] = "Image too large (max 2MB)";
        } else {
            $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $new_image = uniqid('service_') . '.' . $ext;
            
            if (move_uploaded_file($_FILES['image']['tmp_name'], $target_dir . $new_image)) {
                // Delete old image if not placeholder
                if ($image_path && $image_path !== 'placeholder.jpg' && file_exists($target_dir . $image_path)) {
                    unlink($target_dir . $image_path);
                }
                $image_path = $new_image;
            } else {
                $errors[] = "Failed to upload image";
            }
        }
    }

    // Update service if no errors
    if (empty($errors)) {
        $query = "UPDATE services SET title=?, category=?, description=?, price=?, rate_type=?, image_path=? WHERE id=? AND seller_id=?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("sssdssii", $title, $category, $description, $price, $rate_type, $image_path, $service_id, $seller_id);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Service updated successfully";
            header("Location: seller_services.php");
            exit();
        } else {
            $errors[] = "Error updating service: " . $stmt->error;
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Service - StrathConnect</title>
    <link rel="stylesheet" href="../assets/style.css">
    <link rel="stylesheet" href="../assets/css/services.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    
    
    <div class="dashboard-container">
        
        
        <main class="dashboard-content">
            <div class="dashboard-header">
                <h1>Edit Service</h1>
                <a href="seller_services.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back
                </a>
            </div>
            
            <?php if (!empty($errors)): ?>
                <div class="alert alert-error">
                    <h3>Error:</h3>
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?= htmlspecialchars($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <form class="service-form" method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label>Service Title*</label>
                    <input type="text" name="title" value="<?= htmlspecialchars($service['title']) ?>" required>
                </div>
                
                <div class="form-group">
                    <label>Category*</label>
                    <select name="category" required>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat ?>" <?= $service['category'] === $cat ? 'selected' : '' ?>>
                                <?= $cat ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Description*</label>
                    <textarea name="description" rows="5" required><?= htmlspecialchars($service['description']) ?></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Price (KSh)*</label>
                        <input type="number" name="price" min="0" step="0.01" 
                               value="<?= htmlspecialchars($service['price']) ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Rate Type*</label>
                        <select name="rate_type" required>
                            <?php foreach ($rate_types as $rate): ?>
                                <option value="<?= $rate ?>" <?= $service['rate_type'] === $rate ? 'selected' : '' ?>>
                                    <?= ucfirst($rate) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Current Image</label>
                    <div class="current-image-preview">
                        <img src="../assets/images/services/<?= htmlspecialchars($service['image_path'] ?? 'placeholder.jpg') ?>" 
                             alt="Current service image">
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Update Image</label>
                    <input type="file" name="image" accept="image/*">
                    <p class="form-hint">Leave blank to keep current image (JPEG/PNG, max 2MB)</p>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                    <a href="seller_services.php" class="btn btn-secondary">
                        Cancel
                    </a>
                </div>
            </form>
        </main>
    </div>
    
    
</body>
</html>
<?php $conn->close(); ?>