<?php
session_start();

// Verify buyer is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'buyer') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Database configuration
$host = 'localhost';
$user = 'root';
$pass = '';
$db = 'strathconnect';

// Create connection
$conn = new mysqli($host, $user, $pass, $db);

// Check connection
if ($conn->connect_error) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

// Get wishlist ID from POST data
$wishlist_id = isset($_POST['wishlist_id']) ? intval($_POST['wishlist_id']) : 0;

if ($wishlist_id <= 0) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid wishlist item']);
    exit();
}

$buyer_id = $_SESSION['user_id'];

// Verify the wishlist item belongs to the buyer
$verify_stmt = $conn->prepare("SELECT id FROM wishlist WHERE id = ? AND buyer_id = ?");
$verify_stmt->bind_param("ii", $wishlist_id, $buyer_id);
$verify_stmt->execute();
$verify_result = $verify_stmt->get_result();

if ($verify_result->num_rows === 0) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Wishlist item not found or not owned by you']);
    exit();
}

// Remove from wishlist
$delete_stmt = $conn->prepare("DELETE FROM wishlist WHERE id = ?");
$delete_stmt->bind_param("i", $wishlist_id);

if ($delete_stmt->execute()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => 'Item removed from wishlist']);
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Failed to remove item']);
}

// Close connections
$verify_stmt->close();
$delete_stmt->close();
$conn->close();
?>