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

$user_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_address'])) {
        // Add new address
        $full_name = $conn->real_escape_string($_POST['full_name']);
        $address_line1 = $conn->real_escape_string($_POST['address_line1']);
        $address_line2 = $conn->real_escape_string($_POST['address_line2'] ?? '');
        $city = $conn->real_escape_string($_POST['city']);
        $county = $conn->real_escape_string($_POST['county']);
        $postal_code = $conn->real_escape_string($_POST['postal_code']);
        $phone = $conn->real_escape_string($_POST['phone']);
        $is_default = isset($_POST['is_default']) ? 1 : 0;
        
        // Validate phone number
        if (!preg_match('/^254[17]\d{8}$/', $phone)) {
            $error_message = "Invalid phone number format. Use 2547XXXXXXXX";
        } else {
            // If setting as default, remove default from other addresses
            if ($is_default) {
                $conn->query("UPDATE user_addresses SET is_default = 0 WHERE user_id = $user_id");
            }
            
            // Insert new address
            $query = "INSERT INTO user_addresses (user_id, full_name, address_line1, address_line2, city, county, postal_code, phone, is_default)
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("isssssssi", $user_id, $full_name, $address_line1, $address_line2, $city, $county, $postal_code, $phone, $is_default);
            
            if ($stmt->execute()) {
                $success_message = "Address added successfully!";
            } else {
                $error_message = "Error adding address: " . $conn->error;
            }
        }
    } elseif (isset($_POST['set_default'])) {
        // Set address as default
        $address_id = intval($_POST['address_id']);
        
        $conn->begin_transaction();
        try {
            $conn->query("UPDATE user_addresses SET is_default = 0 WHERE user_id = $user_id");
            $conn->query("UPDATE user_addresses SET is_default = 1 WHERE id = $address_id AND user_id = $user_id");
            $conn->commit();
            $success_message = "Default address updated!";
        } catch (Exception $e) {
            $conn->rollback();
            $error_message = "Error updating default address";
        }
    } elseif (isset($_POST['delete_address'])) {
        // Delete address
        $address_id = intval($_POST['address_id']);
        
        $query = "DELETE FROM user_addresses WHERE id = ? AND user_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ii", $address_id, $user_id);
        
        if ($stmt->execute()) {
            $success_message = "Address deleted successfully!";
        } else {
            $error_message = "Error deleting address";
        }
    }
}

// Get all addresses for the user
$addresses = [];
$query = "SELECT * FROM user_addresses WHERE user_id = ? ORDER BY is_default DESC, id DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$addresses = $result->fetch_all(MYSQLI_ASSOC);

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Addresses - StrathConnect</title>
    <link rel="stylesheet" href="../assets/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary: #FF9900;
            --primary-hover: #e68a00;
            --secondary: #f8f9fa;
            --dark: #343a40;
            --light: #f8f9fa;
            --danger: #dc3545;
            --success: #28a745;
            --border: #dee2e6;
            --text-muted: #6c757d;
        }
        
        .address-container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .page-title {
            font-size: 28px;
            margin-bottom: 25px;
            color: var(--dark);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .address-list {
            margin-bottom: 40px;
        }
        
        .address-card {
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            position: relative;
            transition: all 0.3s;
        }
        
        .address-card:hover {
            border-color: var(--primary);
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }
        
        .address-card.default {
            border-color: var(--primary);
            background-color: rgba(255, 153, 0, 0.05);
        }
        
        .default-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            background: var(--success);
            color: white;
            padding: 3px 10px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .address-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
            flex-wrap: wrap;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 8px 15px;
            border-radius: 6px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            gap: 8px;
            font-size: 14px;
            border: none;
        }
        
        .btn-sm {
            padding: 6px 12px;
            font-size: 13px;
        }
        
        .btn-primary {
            background: var(--primary);
            color: white;
        }
        
        .btn-primary:hover {
            background: var(--primary-hover);
            transform: translateY(-2px);
        }
        
        .btn-outline {
            background: transparent;
            border: 1px solid var(--primary);
            color: var(--primary);
        }
        
        .btn-outline:hover {
            background: var(--primary);
            color: white;
        }
        
        .btn-danger {
            background: var(--danger);
            color: white;
        }
        
        .btn-danger:hover {
            background: #c82333;
        }
        
        .add-address-form {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.05);
            padding: 25px;
            margin-top: 30px;
        }
        
        .form-title {
            font-size: 20px;
            margin-bottom: 20px;
            color: var(--dark);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--dark);
        }
        
        .form-control {
            width: 100%;
            padding: 12px;
            border: 1px solid var(--border);
            border-radius: 6px;
            font-size: 16px;
            transition: all 0.3s;
        }
        
        .form-control:focus {
            border-color: var(--primary);
            outline: none;
            box-shadow: 0 0 0 3px rgba(255, 153, 0, 0.1);
        }
        
        .form-row {
            display: flex;
            gap: 20px;
        }
        
        .form-col {
            flex: 1;
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .alert {
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .alert-success {
            background-color: #d4edda;
            border-color: #c3e6cb;
            color: #155724;
        }
        
        .alert-danger {
            background-color: #f8d7da;
            border-color: #f5c6cb;
            color: #721c24;
        }
        
        @media (max-width: 768px) {
            .form-row {
                flex-direction: column;
                gap: 0;
            }
            
            .address-actions {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="logo">StrathConnect</div>
        <ul class="nav-links">
            <li><a href="marketplace.php">Marketplace</a></li>
            <li><a href="services.php">Services</a></li>
            <li><a href="buyer_orders.php">My Orders</a></li>
            <li><a href="../public/login.php">Logout</a></li>
        </ul>
    </nav>

    <div class="dashboard-container">
        <main class="dashboard-content">
            <div class="address-container">
                <h1 class="page-title">
                    <i class="fas fa-map-marker-alt"></i> Manage Addresses
                </h1>
                
                <?php if ($success_message): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> <?= $success_message ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($error_message): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i> <?= $error_message ?>
                    </div>
                <?php endif; ?>
                
                <div class="address-list">
                    <?php if (empty($addresses)): ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-info-circle"></i> You don't have any saved addresses yet.
                        </div>
                    <?php else: ?>
                        <?php foreach ($addresses as $address): ?>
                            <div class="address-card <?= $address['is_default'] ? 'default' : '' ?>">
                                <?php if ($address['is_default']): ?>
                                    <span class="default-badge">DEFAULT</span>
                                <?php endif; ?>
                                
                                <h3><?= htmlspecialchars($address['full_name']) ?></h3>
                                <p><?= htmlspecialchars($address['address_line1']) ?></p>
                                <?php if (!empty($address['address_line2'])): ?>
                                    <p><?= htmlspecialchars($address['address_line2']) ?></p>
                                <?php endif; ?>
                                <p><?= htmlspecialchars($address['city']) ?>, <?= htmlspecialchars($address['county']) ?> <?= htmlspecialchars($address['postal_code']) ?></p>
                                <p><i class="fas fa-phone"></i> <?= htmlspecialchars($address['phone']) ?></p>
                                
                                <div class="address-actions">
                                    <?php if (!$address['is_default']): ?>
                                        <form method="post" style="display: inline;">
                                            <input type="hidden" name="address_id" value="<?= $address['id'] ?>">
                                            <button type="submit" name="set_default" class="btn btn-sm btn-outline">
                                                <i class="fas fa-star"></i> Set as Default
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    
                                    <form method="post" style="display: inline;">
                                        <input type="hidden" name="address_id" value="<?= $address['id'] ?>">
                                        <button type="submit" name="delete_address" class="btn btn-sm btn-danger" 
                                                onclick="return confirm('Are you sure you want to delete this address?')">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <div class="add-address-form">
                    <h2 class="form-title">
                        <i class="fas fa-plus-circle"></i> Add New Address
                    </h2>
                    
                    <form method="post">
                        <div class="form-row">
                            <div class="form-col">
                                <div class="form-group">
                                    <label for="full_name" class="form-label">Full Name</label>
                                    <input type="text" id="full_name" name="full_name" class="form-control" required>
                                </div>
                            </div>
                            <div class="form-col">
                                <div class="form-group">
                                    <label for="phone" class="form-label">Phone Number</label>
                                    <input type="tel" id="phone" name="phone" class="form-control" 
                                           placeholder="2547XXXXXXXX" pattern="^254[17]\d{8}$" required>
                                    <small style="color: var(--text-muted);">Format: 2547XXXXXXXX</small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="address_line1" class="form-label">Address Line 1</label>
                            <input type="text" id="address_line1" name="address_line1" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="address_line2" class="form-label">Address Line 2 (Optional)</label>
                            <input type="text" id="address_line2" name="address_line2" class="form-control">
                        </div>
                        
                        <div class="form-row">
                            <div class="form-col">
                                <div class="form-group">
                                    <label for="city" class="form-label">City</label>
                                    <input type="text" id="city" name="city" class="form-control" required>
                                </div>
                            </div>
                            <div class="form-col">
                                <div class="form-group">
                                    <label for="county" class="form-label">County</label>
                                    <input type="text" id="county" name="county" class="form-control" required>
                                </div>
                            </div>
                            <div class="form-col">
                                <div class="form-group">
                                    <label for="postal_code" class="form-label">Postal Code</label>
                                    <input type="text" id="postal_code" name="postal_code" class="form-control" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <div class="checkbox-group">
                                <input type="checkbox" id="is_default" name="is_default">
                                <label for="is_default">Set as default shipping address</label>
                            </div>
                        </div>
                        
                        <button type="submit" name="add_address" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save Address
                        </button>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <footer class="footer">
        <p>&copy; <?= date('Y') ?> StrathConnect. All rights reserved.</p>
    </footer>
</body>
</html>