<?php
session_start();
include '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header("Location: ../login.php");
    exit;
}

if (!isset($_GET['product_id'])) {
    echo "No product selected.";
    exit;
}

$product_id = intval($_GET['product_id']);
$customer_id = $_SESSION['user_id'];

// Fetch product info
$stmt = $conn->prepare("SELECT p.*, u.fullname AS vendor_name FROM products p JOIN users u ON p.vendor_id = u.id WHERE p.id = ?");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "Product not found.";
    exit;
}

$product = $result->fetch_assoc();
$imageFile = !empty($product['image_url']) ? htmlspecialchars($product['image_url']) : 'no-image.png';
$imagePath = "../images/" . $imageFile;
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Confirm Order</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
<style>
    body {
        font-family: 'Poppins', sans-serif;
        background: #f9fbfd;
        margin: 0;
        padding: 40px 20px;
        display: flex;
        justify-content: center;
    }
    .confirm-box {
        background: #fff;
        max-width: 500px;
        width: 100%;
        border-radius: 16px;
        padding: 24px;
        box-shadow: 0 8px 24px rgba(0,0,0,0.08);
        text-align: center;
        position: relative;
    }
    .back-btn {
        position: absolute;
        top: 16px;
        left: 16px;
        text-decoration: none;
        color: #fff;
        font-size: 13px;
        font-weight: 500;
        background: #e67e22;
        padding: 6px 12px;
        border-radius: 8px;
        transition: background 0.3s;
    }
    .back-btn:hover {
        background: #d35400;
    }
    h2 {
        color: #e67e22;
        margin-bottom: 18px;
        font-size: 1.6rem;
        font-weight: 600;
    }
    .product-image {
        width: 180px;
        height: 180px;
        object-fit: cover;
        border-radius: 12px;
        border: 3px solid #ffeaa7;
        margin-bottom: 14px;
    }
    p {
        margin: 6px 0;
        font-size: 15px;
        color: #2d3436;
    }
    p strong {
        color: #e67e22;
    }
    form {
        margin-top: 18px;
        text-align: left;
    }
    label {
        display: block;
        margin-bottom: 10px;
        font-size: 14px;
        color: #2d3436;
        font-weight: 500;
    }
    input[type="number"],
    select {
        width: 100%;
        padding: 9px 12px;
        border: 1px solid #ccc;
        border-radius: 10px;
        font-size: 14px;
        margin-top: 4px;
        margin-bottom: 12px;
        background: #f9fcff;
        transition: border-color 0.3s;
    }
    input[type="number"]:focus,
    select:focus {
        outline: none;
        border-color: #e67e22;
    }
    button {
        width: 100%;
        padding: 12px;
        background: #e67e22;
        color: white;
        font-weight: 500;
        font-size: 15px;
        border: none;
        border-radius: 10px;
        cursor: pointer;
        transition: background 0.3s ease;
    }
    button:hover {
        background: #d35400;
    }
</style>
</head>
<body>

<div class="confirm-box">
    <a href="dashboard.php" class="back-btn">← Back</a>
    <h2>Confirm Your Order</h2>
    <img src="<?= $imagePath ?>" alt="Product Image" class="product-image">
    <p><strong>Product:</strong> <?= htmlspecialchars($product['name']) ?></p>
    <p><strong>Price:</strong> ₱<?= number_format($product['price'], 2) ?></p>
    <p><strong>Vendor:</strong> <?= htmlspecialchars($product['vendor_name']) ?></p>

    <form action="add_order.php" method="POST">
        <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
        <input type="hidden" name="vendor_id" value="<?= $product['vendor_id'] ?>">
        <input type="hidden" name="price" value="<?= $product['price'] ?>">

        <label>Quantity:
            <input type="number" name="quantity" value="1" min="1" required>
        </label>

        <label>Payment Method:
            <select name="payment_method" required>
                <option value="">-- Select Payment Method --</option>
                <option value="Cash on Delivery">Cash on Delivery</option>
                <option value="GCash">GCash</option>
            </select>
        </label>

        <button type="submit">Place Order</button>
    </form>
</div>

</body>
</html>