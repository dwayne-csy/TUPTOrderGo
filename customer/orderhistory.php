<?php
session_start();
include '../config/db.php';

// Only allow logged-in customers
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch all orders for the customer
$sql = "
SELECT 
    o.id AS order_id,
    p.name AS product_name,
    u.fullname AS vendor_name,
    o.quantity,
    p.price,
    o.payment_method,
    o.created_at
FROM orders o
JOIN products p ON o.product_id = p.id
JOIN users u ON o.vendor_id = u.id
WHERE o.customer_id = ?
ORDER BY o.created_at DESC
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$orders = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Fetch all reviewed order IDs by this customer
$reviewed_order_ids = [];
$review_sql = "SELECT order_id FROM reviews WHERE customer_id = ?";
$stmt = $conn->prepare($review_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$review_result = $stmt->get_result();
while ($row = $review_result->fetch_assoc()) {
    $reviewed_order_ids[] = $row['order_id'];
}
$stmt->close();
?>
<!DOCTYPE html>
<html>
<head>
    <title>My Order History</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            max-width: 950px;
            margin: 40px auto;
            background: #f9f9f9;
            padding: 20px;
        }
        h2 {
            text-align: center;
            margin-bottom: 30px;
            color: #e67e22;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        }
        th, td {
            padding: 12px 10px;
            border-bottom: 1px solid #eee;
            text-align: center;
            font-size: 14px;
        }
        th {
            background-color: #e67e22;
            color: white;
            font-weight: 500;
        }
        tr:nth-child(even) {
            background: #fdf6f0;
        }
        tr:hover {
            background-color: #fceee0;
        }
        .back-link {
            display: block;
            text-align: center;
            margin-top: 25px;
            text-decoration: none;
            color: #e67e22;
            font-weight: 500;
        }
        .back-link:hover {
            color: #d35400;
        }
        .done {
            color: #27ae60;
            font-weight: 600;
        }
        .review-button button {
            background-color: #e67e22;
            color: white;
            border: none;
            padding: 6px 12px;
            cursor: pointer;
            border-radius: 6px;
            font-size: 13px;
            transition: background 0.3s;
        }
        .review-button button:hover {
            background-color: #d35400;
        }
        p {
            text-align: center;
            color: #777;
            font-size: 14px;
        }

        .container {
    background: white;
    padding: 25px;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.05);
}

.back-btn {
    display: inline-block;
    margin-bottom: 20px;
    text-decoration: none;
    background-color: #e67e22;
    color: white;
    padding: 8px 14px;
    border-radius: 6px;
    font-size: 13px;
    font-weight: 500;
    transition: background 0.3s;
}

    .back-btn:hover {
        background: #d35400;
    }

    </style>
</head>
<body>
<div class="container">
    <a href="dashboard.php" class="back-btn">← Back</a>
    <h2>🧾 My Order History</h2>

    <?php if (count($orders) > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Product</th>
                    <th>Vendor</th>
                    <th>Price</th>
                    <th>Quantity</th>
                    <th>Total</th>
                    <th>Payment</th>
                    <th>Review</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $order): ?>
                    <tr>
                        <td><?= date('F j, Y g:i A', strtotime($order['created_at'])) ?></td>
                        <td><?= htmlspecialchars($order['product_name']) ?></td>
                        <td><?= htmlspecialchars($order['vendor_name']) ?></td>
                        <td>₱<?= number_format($order['price'], 2) ?></td>
                        <td><?= $order['quantity'] ?></td>
                        <td>₱<?= number_format($order['price'] * $order['quantity'], 2) ?></td>
                        <td><?= htmlspecialchars($order['payment_method']) ?></td>
                        <td>
                            <?php if (in_array($order['order_id'], $reviewed_order_ids)): ?>
                                <span class="done">Done</span>
                            <?php else: ?>
                                <form action="reviews/reviews.php" method="GET" style="margin:0;" class="review-button">
                                    <input type="hidden" name="order_id" value="<?= $order['order_id'] ?>">
                                    <input type="hidden" name="product_name" value="<?= htmlspecialchars($order['product_name']) ?>">
                                    <button type="submit">Review</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p style="text-align: center;">You have no order history yet.</p>
    <?php endif; ?>
</div>

</body>

</html>
