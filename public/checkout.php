<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'buyer') {
    header("Location: ../public/login.php");
    exit();
}

$conn = new mysqli('localhost', 'root', '', 'strathconnect');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$buyer_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $buyer_id);
$stmt->execute();
$buyer_result = $stmt->get_result();
$buyer = $buyer_result->fetch_assoc();

$stmt = $conn->prepare("SELECT * FROM user_addresses WHERE user_id = ? ORDER BY is_default DESC");
$stmt->bind_param("i", $buyer_id);
$stmt->execute();
$address_result = $stmt->get_result();
$addresses = $address_result->fetch_all(MYSQLI_ASSOC);

$stmt = $conn->prepare("SELECT c.*, p.name, p.price, p.seller_id, p.image_path, u.username as seller_name 
                        FROM cart c
                        JOIN products p ON c.product_id = p.id
                        JOIN users u ON p.seller_id = u.id
                        WHERE c.buyer_id = ?");
$stmt->bind_param("i", $buyer_id);
$stmt->execute();
$cart_items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$subtotal = 0;
$item_count = 0;
foreach ($cart_items as $item) {
    $subtotal += $item['price'] * $item['quantity'];
    $item_count += $item['quantity'];
}
$shipping = $item_count > 0 ? 150 : 0;
$total = $subtotal + $shipping;

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $phone = $_POST['phone'] ?? '';
    if (!preg_match('/^254[17]\d{8}$/', $phone)) {
        $message = "<div class='error'>Invalid phone number format</div>";
    } else {
        $consumerKey = 'HteSaVxyXAXlFzdIkpKxhtII5tCIYdQ9ByXkdDdDIBeyJfAG';
        $consumerSecret = 'BPSyzqFuaMMO5AUgtgy1n2Ks7OQA5bSXqgEu9TLhVs8uSjK6LVlz9NPOkdL4WlFK';
        $shortCode = '174379';
        $passkey = 'bfb279f9aa9bdbcf158e97dd71a467cd2e0c893059b10f78e6b72ada1ed2c919';

        $tokenUrl = 'https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials';
        $credentials = base64_encode("$consumerKey:$consumerSecret");

        $ch = curl_init($tokenUrl);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Basic ' . $credentials]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $tokenResponse = curl_exec($ch);
        curl_close($ch);

        $tokenData = json_decode($tokenResponse, true);
        if (!isset($tokenData['access_token'])) {
            $message = "<div class='error'>Failed to get M-Pesa access token</div>";
        } else {
            $accessToken = $tokenData['access_token'];
            $timestamp = date('YmdHis');
            $password = base64_encode($shortCode . $passkey . $timestamp);

            $stkUrl = 'https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest';
            $requestData = [
                'BusinessShortCode' => $shortCode,
                'Password' => $password,
                'Timestamp' => $timestamp,
                'TransactionType' => 'CustomerPayBillOnline',
                'Amount' => $total,
                'PartyA' => $phone,
                'PartyB' => $shortCode,
                'PhoneNumber' => $phone,
                'CallBackURL' => 'https://yourdomain.com/callback.php',
                'AccountReference' => 'StrathConnect',
                'TransactionDesc' => 'StrathConnect Order'
            ];

            $ch = curl_init($stkUrl);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $accessToken
            ]);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestData));
            $stkResponse = curl_exec($ch);
            curl_close($ch);

            $stkData = json_decode($stkResponse, true);

            if (isset($stkData['ResponseCode']) && $stkData['ResponseCode'] == '0') {
                $message = "<div class='success'>STK Push sent successfully. Check your phone to complete payment.</div>";
            } else {
                $errorMsg = $stkData['errorMessage'] ?? 'STK Push failed';
                $message = "<div class='error'>" . htmlspecialchars($errorMsg) . "</div>";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Checkout</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f5f7fa; margin: 0; padding: 0; }
        .container { max-width: 800px; margin: 2rem auto; background: #fff; padding: 2rem; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        .success { background: #d4edda; color: #155724; padding: 1rem; border-radius: 4px; margin-bottom: 1rem; }
        .error { background: #f8d7da; color: #721c24; padding: 1rem; border-radius: 4px; margin-bottom: 1rem; }
        input, button { width: 100%; padding: 0.75rem; margin: 0.5rem 0; border: 1px solid #ccc; border-radius: 4px; font-size: 1rem; }
        button { background: #003366; color: #fff; border: none; cursor: pointer; }
        button:hover { background: #004080; }
    </style>
</head>
<body>
<div class="container">
    <h1>Checkout</h1>
    <?= $message ?>
    <p>Total: <strong>KSh <?= number_format($total, 2) ?></strong></p>
    <form method="POST">
        <label for="phone">M-Pesa Phone Number</label>
        <input type="tel" name="phone" id="phone" required pattern="^254[17]\d{8}$" value="<?= htmlspecialchars($buyer['contact_number'] ?? '') ?>" placeholder="e.g. 2547XXXXXXXX">
        <button type="submit">Place Order & Pay with M-Pesa</button>
    </form>
</div>
</body>
</html>
