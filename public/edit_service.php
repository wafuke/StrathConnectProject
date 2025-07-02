<?php
session_start();
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

// Check login & seller
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'seller') {
    header("Location: login.php");
    exit;
}

// Get service ID
if (!isset($_GET['id'])) {
    echo "Service ID is required.";
    exit;
}

$service_id = (int)$_GET['id'];

// Fetch service
$sql = "SELECT * FROM services WHERE id = ? AND seller_id = ?";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}
$stmt->bind_param("ii", $service_id, $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    echo "Service not found.";
    exit;
}

$service = $result->fetch_assoc();

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $category = trim($_POST['category']);
    $price = (float)$_POST['price'];
    $rate_type = trim($_POST['rate_type']);

    if (empty($title) || empty($description) || empty($category) || empty($price) || empty($rate_type)) {
        $message = "<div class='error'>All fields are required.</div>";
    } else {
        $update_sql = "UPDATE services SET title = ?, description = ?, category = ?, price = ?, rate_type = ? WHERE id = ? AND seller_id = ?";
        $update_stmt = $conn->prepare($update_sql);
        if (!$update_stmt) {
            die("Prepare failed: " . $conn->error);
        }
        $update_stmt->bind_param("sssdsii", $title, $description, $category, $price, $rate_type, $service_id, $_SESSION['user_id']);
        if ($update_stmt->execute()) {
            $message = "<div class='success'>Service updated successfully.</div>";
            // header("Location: seller_services.php");
            // exit;
        } else {
            $message = "<div class='error'>Update failed: " . htmlspecialchars($update_stmt->error) . "</div>";
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Edit Service</title>
    <style>
        body {
            font-family: 'Montserrat', sans-serif;
            background: #f5f7fa;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            background: white;
            padding: 2rem;
            margin: 2rem auto;
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        h1 {
            text-align: center;
            color: #003366;
            margin-bottom: 1.5rem;
        }
        .form-group {
            margin-bottom: 1.25rem;
        }
        label {
            display: block;
            margin-bottom: 0.5rem;
            color: #003366;
            font-weight: 600;
        }
        input[type="text"],
        input[type="number"],
        textarea,
        select {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-family: inherit;
            font-size: 1rem;
        }
        textarea {
            resize: vertical;
            min-height: 100px;
        }
        button {
            background: #003366;
            color: white;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 4px;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
            font-size: 1rem;
        }
        button:hover {
            background: #004080;
        }
        .success {
            background: #d4edda;
            color: #155724;
            padding: 0.75rem;
            border: 1px solid #c3e6cb;
            border-radius: 4px;
            margin-bottom: 1rem;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 0.75rem;
            border: 1px solid #f5c6cb;
            border-radius: 4px;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
<div class="container">
    <h1>Edit Service</h1>
    <?= $message ?>
    <form method="POST">
        <div class="form-group">
            <label for="title">Title</label>
            <input type="text" id="title" name="title" value="<?= htmlspecialchars($service['title']) ?>" required>
        </div>
        <div class="form-group">
            <label for="description">Description</label>
            <textarea id="description" name="description" required><?= htmlspecialchars($service['description']) ?></textarea>
        </div>
        <div class="form-group">
            <label for="category">Category</label>
            <input type="text" id="category" name="category" value="<?= htmlspecialchars($service['category']) ?>" required>
        </div>
        <div class="form-group">
            <label for="price">Price (Ksh)</label>
            <input type="number" id="price" name="price" step="0.01" value="<?= htmlspecialchars($service['price']) ?>" required>
        </div>
        <div class="form-group">
            <label for="rate_type">Rate Type</label>
            <select id="rate_type" name="rate_type" required>
                <option value="hourly" <?= $service['rate_type'] === 'hourly' ? 'selected' : '' ?>>Hourly</option>
                <option value="fixed" <?= $service['rate_type'] === 'fixed' ? 'selected' : '' ?>>Fixed</option>
                <option value="per_project" <?= $service['rate_type'] === 'per_project' ? 'selected' : '' ?>>Per Project</option>
            </select>
        </div>
        <button type="submit">Update Service</button>
    </form>
</div>
</body>
</html>
