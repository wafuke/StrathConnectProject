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
$activities = [];

$query = "(
    SELECT 'product' as type, name as title, created_at 
    FROM products 
    WHERE seller_id = ?
    ORDER BY created_at DESC 
    LIMIT 3
) UNION ALL (
    SELECT 'service' as type, title, created_at 
    FROM services 
    WHERE seller_id = ?
    ORDER BY created_at DESC 
    LIMIT 2
) ORDER BY created_at DESC LIMIT 5";

$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $seller_id, $seller_id);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $activities[] = $row;
}

header('Content-Type: application/json');
echo json_encode($activities);
$conn->close();
?>