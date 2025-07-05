<?php
session_start();

// Verify admin is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
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

// Handle approval/rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['service_id'])) {
    $service_id = intval($_POST['service_id']);
    $action = $_POST['action'] === 'approve' ? 1 : 0;

    $query = "UPDATE services SET is_approved = ? WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $action, $service_id);

    if ($stmt->execute()) {
        $_SESSION['success'] = "Service " . ($action ? "approved" : "rejected") . " successfully";
    } else {
        $_SESSION['error'] = "Error updating service: " . $stmt->error;
    }
    $stmt->close();

    header("Location: admin_services.php");
    exit();
}

// Get all services with seller info
$query = "SELECT s.*, u.username as seller_name 
          FROM services s 
          JOIN users u ON s.seller_id = u.id 
          ORDER BY s.created_at DESC";
$result = $conn->query($query);
$services = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manage Services - StrathConnect</title>
  <link rel="stylesheet" href="../assets/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <style>
    .services-container {
      max-width: 1200px;
      margin: 0 auto;
      padding: 20px;
    }

    .services-header {
      margin-bottom: 20px;
    }

    .services-header h1 {
      font-size: 28px;
      color: #333;
    }

    .alert {
      padding: 10px 15px;
      margin-bottom: 20px;
      border-radius: 6px;
    }

    .alert-success {
      background-color: #d4edda;
      color: #155724;
      border: 1px solid #c3e6cb;
    }

    .alert-error {
      background-color: #f8d7da;
      color: #721c24;
      border: 1px solid #f5c6cb;
    }

    .services-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
      gap: 20px;
    }

    .service-card {
      background: #fff;
      border-radius: 8px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.05);
      padding: 20px;
      display: flex;
      flex-direction: column;
      gap: 10px;
    }

    .service-status {
      font-size: 12px;
      font-weight: bold;
      padding: 4px 8px;
      border-radius: 4px;
      display: inline-block;
    }

    .status-approved {
      background-color: #d4edda;
      color: #155724;
    }

    .status-pending {
      background-color: #fff3cd;
      color: #856404;
    }

    .service-title {
      font-size: 20px;
      font-weight: bold;
      color: #333;
    }

    .service-category {
      font-size: 14px;
      color: #666;
    }

    .service-price {
      font-size: 16px;
      font-weight: bold;
      color: #FF9900;
    }

    .service-description {
      font-size: 14px;
      color: #555;
    }

    .meta-item {
      font-size: 13px;
      color: #555;
    }

    .meta-label {
      font-weight: bold;
    }

    .service-actions {
      display: flex;
      gap: 10px;
      flex-wrap: wrap;
      margin-top: 10px;
    }

    .btn {
      padding: 8px 12px;
      border: none;
      border-radius: 4px;
      font-size: 14px;
      cursor: pointer;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: 5px;
    }

    .btn-approve {
      background-color: #28a745;
      color: #fff;
    }

    .btn-approve:hover {
      background-color: #218838;
    }

    .btn-reject {
      background-color: #dc3545;
      color: #fff;
    }

    .btn-reject:hover {
      background-color: #c82333;
    }

    .btn-view {
      background-color: #007bff;
      color: #fff;
    }

    .btn-view:hover {
      background-color: #0069d9;
    }

    .empty-state {
      grid-column: 1 / -1;
      text-align: center;
      padding: 50px 20px;
      background: #fafafa;
      border-radius: 8px;
    }

    .empty-state i {
      font-size: 50px;
      color: #ddd;
      margin-bottom: 10px;
    }

    .empty-state h3 {
      font-size: 22px;
      margin-bottom: 5px;
      color: #333;
    }

    .empty-state p {
      color: #666;
    }
  </style>
</head>
<body>  
  <div class="dashboard-container">
    <main class="dashboard-content">
      <div class="services-container">
        <div class="services-header">
          <h1>Manage Services</h1>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
          <div class="alert alert-success">
            <?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
          </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
          <div class="alert alert-error">
            <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
          </div>
        <?php endif; ?>

        <div class="services-grid">
          <?php if (empty($services)): ?>
            <div class="empty-state">
              <i class="fas fa-concierge-bell"></i>
              <h3>No services found</h3>
              <p>There are currently no services to review</p>
            </div>
          <?php else: ?>
            <?php foreach ($services as $service): ?>
              <div class="service-card">
                <div class="service-status status-<?php echo $service['is_approved'] ? 'approved' : 'pending'; ?>">
                  <?php echo $service['is_approved'] ? 'Approved' : 'Pending'; ?>
                </div>

                <div class="service-title"><?php echo htmlspecialchars($service['title']); ?></div>
                <div class="service-category"><?php echo htmlspecialchars($service['category']); ?></div>

                <div class="service-price">
                  KSh <?php echo number_format($service['price'], 2); ?>
                  <span class="service-rate-type">(<?php echo htmlspecialchars($service['rate_type']); ?>)</span>
                </div>

                <p class="service-description">
                  <?php echo htmlspecialchars(substr($service['description'], 0, 150)); ?>
                  <?php if (strlen($service['description']) > 150): ?>...<?php endif; ?>
                </p>

                <div class="meta-item">
                  <span class="meta-label">Seller:</span>
                  <span class="meta-value"><?php echo htmlspecialchars($service['seller_name']); ?></span>
                </div>

                <div class="service-actions">
                  <?php if (!$service['is_approved']): ?>
                    <form method="POST" class="action-form">
                      <input type="hidden" name="service_id" value="<?php echo $service['id']; ?>">
                      <input type="hidden" name="action" value="approve">
                      <button type="submit" class="btn btn-approve">
                        <i class="fas fa-check"></i> Approve
                      </button>
                    </form>
                  <?php endif; ?>

                  <form method="POST" class="action-form">
                    <input type="hidden" name="service_id" value="<?php echo $service['id']; ?>">
                    <input type="hidden" name="action" value="<?php echo $service['is_approved'] ? 'reject' : 'approve'; ?>">
                    <button type="submit" class="btn btn-<?php echo $service['is_approved'] ? 'reject' : 'approve'; ?>">
                      <i class="fas fa-<?php echo $service['is_approved'] ? 'times' : 'check'; ?>"></i>
                      <?php echo $service['is_approved'] ? 'Reject' : 'Approve'; ?>
                    </button>
                  </form>

                  <a href="service_details.php?id=<?php echo $service['id']; ?>" class="btn btn-view">
                    <i class="fas fa-eye"></i> View
                  </a>
                </div>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>
    </main>
  </div>
</body>
</html>
<?php $conn->close(); ?>
