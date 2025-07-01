<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("HTTP/1.1 401 Unauthorized");
    exit();
}

$host = 'localhost';
$user = 'root';
$pass = '';
$db = 'strathconnect';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    header("HTTP/1.1 500 Internal Server Error");
    die(json_encode(['error' => 'Database connection failed']));
}

$seller_id = $_SESSION['user_id'];
$response = [
    'product_count' => 0,
    'service_count' => 0
];

// Get product count
$product_query = "SELECT COUNT(*) as count FROM products WHERE seller_id = ?";
if ($stmt = $conn->prepare($product_query)) {
    $stmt->bind_param("i", $seller_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $response['product_count'] = $result->fetch_assoc()['count'];
    $stmt->close();
}

// Get service count
$service_query = "SELECT COUNT(*) as count FROM services WHERE seller_id = ?";
if ($stmt = $conn->prepare($service_query)) {
    $stmt->bind_param("i", $seller_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $response['service_count'] = $result->fetch_assoc()['count'];
    $stmt->close();
}

header('Content-Type: application/json');
echo json_encode($response);
$conn->close();
?>