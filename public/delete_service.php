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

// Verify service ID
if (!isset($_GET['id'])) {
    $_SESSION['error'] = "No service specified";
    header("Location: seller_services.php");
    exit();
}

$service_id = intval($_GET['id']);
$seller_id = $_SESSION['user_id'];

// Get service details for image deletion
$query = "SELECT image_path FROM services WHERE id = ? AND seller_id = ?";
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

// Delete the service
$query = "DELETE FROM services WHERE id = ? AND seller_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $service_id, $seller_id);

if ($stmt->execute()) {
    // Delete associated image if exists
    if (!empty($service['image_path']) && $service['image_path'] !== 'placeholder.jpg') {
        $image_path = "../assets/images/services/" . $service['image_path'];
        if (file_exists($image_path)) {
            unlink($image_path);
        }
    }
    $_SESSION['success'] = "Service deleted successfully";
} else {
    $_SESSION['error'] = "Error deleting service: " . $stmt->error;
}

$stmt->close();
$conn->close();

header("Location: seller_services.php");
exit();
?>