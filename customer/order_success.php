<?php
session_start();
include '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header("Location: ../login.php");
    exit;
}

$order_ids = [];

if (isset($_GET['order_id'])) {
    $order_ids[] = intval($_GET['order_id']);
} elseif (isset($_GET['order_ids'])) {
    $ids = explode(",", $_GET['order_ids']);
    foreach ($ids as $id) {
        $order_ids[] = intval($id);
    }
}

$orders = [];
$grand_total = 0;

foreach ($order_ids as $id) {
    $stmt = $conn->prepare("
        SELECT o.*, p.name AS product_name, p.price, p.image_url
        FROM orders o
        JOIN products p ON o.product_id = p.id
        WHERE o.id = ? AND o.customer_id = ?
    ");
    $stmt->bind_param("ii", $id, $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $orders[] = $row;
        $grand_total += $row['price'] * $row['quantity'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Order Receipt - TUPTOrderGo</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
<style>
    body {
        font-family: 'Poppins', sans-serif;
        background: #f9f9f9;
        margin: 0;
        padding: 30px;
        display: flex;
        justify-content: center;
    }
    .receipt-box {
        background: #fff;
        max-width: 850px;
        width: 100%;
        border-radius: 14px;
        padding: 30px;
        box-shadow: 0 6px 20px rgba(0,0,0,0.08);
    }
    h2 {
        text-align: center;
        font-size: 1.8rem;
        font-weight: 600;
        color: #e67e22;
        margin-bottom: 24px;
    }
    .order-card {
        display: flex;
        gap: 18px;
        background: #fdf6f1;
        padding: 16px;
        border-radius: 12px;
        margin-bottom: 14px;
        align-items: center;
        transition: all 0.2s;
    }
    .order-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    }
    .order-card img {
        width: 80px;
        height: 80px;
        object-fit: cover;
        border-radius: 10px;
        border: 2px solid #f6c89f;
    }
    .order-details {
        flex: 1;
    }
    .order-details p {
        margin: 3px 0;
        font-size: 13px;
    }
    .order-details p strong {
        color: #d35400;
    }
    .grand-total {
        text-align: right;
        font-size: 17px;
        font-weight: 600;
        color: #e67e22;
        margin-top: 10px;
        background: #fdf6f1;
        padding: 10px 14px;
        border-radius: 8px;
    }
    .back-btn {
        display: inline-block;
        margin-top: 20px;
        text-decoration: none;
        padding: 10px 20px;
        background: #e67e22;
        color: white;
        border-radius: 8px;
        font-weight: 500;
        transition: background 0.3s ease;
    }
    .back-btn:hover {
        background: #d35400;
    }
    .no-order {
        text-align: center;
        color: #636e72;
        font-style: italic;
        margin-top: 20px;
    }
    @media(max-width:600px){
        .order-card {
            flex-direction: column;
            align-items: flex-start;
        }
        .order-card img {
            width: 75px;
            height: 75px;
        }
    }
    .top-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 20px;
    }
    .top-bar h2 {
        flex: 1;
        text-align: center;
        margin: 0;
    }

</style>
</head>
<body>

<div class="receipt-box">
    <div class="top-bar">
        <a href="dashboard.php" class="back-btn">← Back</a>
        <h2>🧾 Order Receipt</h2>
    </div>

    <?php if (empty($orders)): ?>
        <p class="no-order">No order found.</p>
    <?php else: ?>
        <?php foreach ($orders as $order): ?>
            <?php
                $imageFile = !empty($order['image_url']) ? htmlspecialchars($order['image_url']) : 'no-image.png';
                $imagePath = "../images/" . $imageFile;
            ?>
            <div class="order-card">
                <img src="<?= $imagePath ?>" alt="Product Image">
                <div class="order-details">
                    <p><strong>Product:</strong> <?= htmlspecialchars($order['product_name']) ?></p>
                    <p><strong>Quantity:</strong> <?= $order['quantity'] ?></p>
                    <p><strong>Total:</strong> ₱<?= number_format($order['price'] * $order['quantity'], 2) ?></p>
                    <p><strong>Payment:</strong> <?= htmlspecialchars($order['payment_method']) ?></p>
                    <p><strong>Status:</strong> <?= htmlspecialchars($order['status']) ?></p>
                    <p><strong>Order Date:</strong> <?= htmlspecialchars($order['created_at']) ?></p>
                </div>
            </div>
        <?php endforeach; ?>
        <div class="grand-total">Grand Total: ₱<?= number_format($grand_total, 2) ?></div>
    <?php endif; ?>

  
</div>

</body>
</html>