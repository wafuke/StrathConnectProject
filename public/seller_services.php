<?php
session_start();

// Verify user is logged in and is a seller
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

// Helper functions
function get_service_icon($category) {
    $icons = [
        'Tutoring' => 'chalkboard-teacher',
        'Design' => 'palette',
        'Programming' => 'code',
        'Writing' => 'pen-fancy',
        'Other' => 'concierge-bell'
    ];
    return $icons[$category] ?? 'concierge-bell';
}

function truncate_description($text, $length) {
    if (strlen($text) > $length) {
        return substr($text, 0, $length) . '...';
    }
    return $text;
}

// Get seller's services
$seller_id = $_SESSION['user_id'];
$query = "SELECT * FROM services WHERE seller_id = ? ORDER BY created_at DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $seller_id);
$stmt->execute();
$result = $stmt->get_result();
$services = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Services - StrathConnect</title>
    <link rel="stylesheet" href="../assets/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Service Cards without Images */
        .service-card.no-image {
            display: flex;
            flex-direction: column;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
            transition: transform 0.3s ease;
            height: 100%;
        }

        .service-card.no-image:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .service-icon {
            background: var(--strath-blue);
            color: white;
            padding: 1.5rem;
            text-align: center;
            font-size: 2rem;
        }

        .service-content {
            padding: 1.5rem;
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .service-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 0.5rem;
        }

        .service-header h3 {
            margin: 0;
            color: var(--strath-blue);
            font-size: 1.1rem;
            flex: 1;
        }

        .service-status {
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .service-status.pending {
            background: #FFF3E0;
            color: #E65100;
        }

        .service-status.approved {
            background: #E8F5E9;
            color: #2E7D32;
        }

        .service-meta {
            display: flex;
            gap: 1rem;
            margin-bottom: 0.75rem;
            font-size: 0.85rem;
            color: #666;
            flex-wrap: wrap;
        }

        .service-meta span {
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        .service-description {
            color: #555;
            margin: 0 0 1rem 0;
            font-size: 0.9rem;
            line-height: 1.5;
            flex: 1;
        }

        .service-actions {
            display: flex;
            gap: 0.5rem;
            margin-top: auto;
        }

        /* Services Grid Layout */
        .services-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-top: 2rem;
        }

        /* Empty State */
        .empty-state {
            grid-column: 1 / -1;
            text-align: center;
            padding: 3rem;
            background: #f9f9f9;
            border-radius: 8px;
        }

        .empty-state i {
            font-size: 3rem;
            color: var(--strath-blue);
            margin-bottom: 1rem;
        }

        .empty-state h3 {
            margin: 0 0 0.5rem 0;
            color: var(--black);
        }

        .empty-state p {
            margin: 0 0 1.5rem 0;
            color: #666;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="logo">StrathConnect</div>
        <ul class="nav-links">
            
            <li><a href="seller_dashboard.php">Dashboard</a></li>
            <li><a href="seller_products.php">My Products</a></li>
            <li><a href="seller_services.php" class="active">My Services</a></li>
            <li><a href="../public/login.php">Logout</a></li>
        </ul>
    </nav>

    <div class="dashboard-container">
        <aside class="sidebar">
            <div class="profile-summary">
                <img src="<?php echo htmlspecialchars($_SESSION['profile_pic'] ?? '../assets/images/profile-placeholder.png'); ?>" alt="Profile" class="profile-pic">
                <h3><?php echo htmlspecialchars($_SESSION['username'] ?? 'Seller'); ?></h3>
                <p>@<?php echo htmlspecialchars($_SESSION['username'] ?? 'username'); ?></p>
            </div>
            <nav class="sidebar-nav">
                <a href="seller_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                <a href="seller_products.php"><i class="fas fa-box-open"></i> My Products</a>
                <a href="seller_services.php" class="active"><i class="fas fa-concierge-bell"></i> My Services</a>
                <a href="seller_settings.php"><i class="fas fa-cog"></i> Settings</a>
            </nav>
        </aside>

        <main class="dashboard-content">
            <div class="dashboard-header">
                <h1>My Services</h1>
                <a href="add_service.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Add New Service
                </a>
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
                        <p>You haven't listed any services yet. Get started by adding your first service!</p>
                        <a href="add_service.php" class="btn btn-primary">Add Service</a>
                    </div>
                <?php else: ?>
                    <?php foreach ($services as $service): ?>
                        <div class="service-card no-image">
                            <div class="service-icon">
                                <i class="fas fa-<?php echo get_service_icon($service['category']); ?>"></i>
                            </div>
                            
                            <div class="service-content">
                                <div class="service-header">
                                    <h3><?php echo htmlspecialchars($service['title']); ?></h3>
                                    <span class="service-status <?php echo $service['is_approved'] ? 'approved' : 'pending'; ?>">
                                        <?php echo $service['is_approved'] ? 'Approved' : 'Pending'; ?>
                                    </span>
                                </div>
                                
                                <div class="service-meta">
                                    <span class="service-category">
                                        <i class="fas fa-tag"></i> <?php echo htmlspecialchars($service['category']); ?>
                                    </span>
                                    <span class="service-rate">
                                        <i class="fas fa-money-bill-wave"></i> KSh <?php echo number_format($service['price'], 2); ?>
                                        <small>(<?php echo htmlspecialchars($service['rate_type']); ?>)</small>
                                    </span>
                                </div>
                                
                                <p class="service-description">
                                    <?php echo htmlspecialchars(truncate_description($service['description'], 120)); ?>
                                </p>
                                
                                <div class="service-actions">
                                    <a href="edit_service.php?id=<?php echo $service['id']; ?>" class="btn btn-edit">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                    <a href="delete_service.php?id=<?php echo $service['id']; ?>" class="btn btn-delete" 
                                       onclick="return confirm('Are you sure you want to delete this service?')">
                                        <i class="fas fa-trash"></i> Delete
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <footer class="footer">
        <p>&copy; <?php echo date('Y'); ?> StrathConnect. All rights reserved.</p>
    </footer>
</body>
</html>
<?php $conn->close(); ?>