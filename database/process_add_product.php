<?php
session_start();

// Database connection (directly in file)
$host = 'localhost';
$user = 'root';
$pass = '';
$db = 'strathconnect';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Verify user is logged in and is a seller
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'seller') {
    header("Location: ../public/login.html");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $seller_id = $_POST['seller_id'];
    $name = $conn->real_escape_string(trim($_POST['name']));
    $category = (int)$_POST['category'];
    $price = (float)$_POST['price'];
    $description = $conn->real_escape_string(trim($_POST['description']));
    
    // Validate inputs
    $errors = [];
    
    if (empty($name)) {
        $errors[] = "Product name is required";
    }
    
    if ($price <= 0) {
        $errors[] = "Price must be greater than 0";
    }
    
    // Handle file upload
    $image_path = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png'];
        $max_size = 2 * 1024 * 1024; // 2MB
        
        if (!in_array($_FILES['image']['type'], $allowed_types)) {
            $errors[] = "Only JPEG and PNG images are allowed";
        } elseif ($_FILES['image']['size'] > $max_size) {
            $errors[] = "Image size must be less than 2MB";
        } else {
            // Generate unique filename
            $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $filename = uniqid('product_') . '.' . $ext;
            $target_path = "../assets/images/products/" . $filename;
            
            if (move_uploaded_file($_FILES['image']['tmp_name'], $target_path)) {
                $image_path = $filename;
            } else {
                $errors[] = "Failed to upload image";
            }
        }
    } else {
        $errors[] = "Product image is required";
    }
    
    if (empty($errors)) {
        // Insert into database (using prepared statement)
        $stmt = $conn->prepare("INSERT INTO products 
            (seller_id, name, category, description, price, image_path) 
            VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isssds", $seller_id, $name, $category, $description, $price, $image_path);
        
        if ($stmt->execute()) {
            $_SESSION['product_success'] = "Product added successfully! Waiting for admin approval.";
            header("Location: seller_products.php");
            exit();
        } else {
            // Delete uploaded image if DB insert failed
            if (!empty($image_path)) {
                unlink("../assets/images/products/" . $image_path);
            }
            $_SESSION['product_error'] = "Error adding product: " . $conn->error;
        }
    } else {
        $_SESSION['product_error'] = implode("<br>", $errors);
    }
    
    header("Location: add_product.php");
    exit();
}