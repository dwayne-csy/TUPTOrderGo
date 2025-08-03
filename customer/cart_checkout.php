<?php
session_start();
include '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header("Location: ../login.php");
    exit;
}

$customer_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $payment_method = $_POST['payment_method'];

    $cart_stmt = $conn->prepare("SELECT product_id, quantity FROM cart_items WHERE user_id = ?");
    $cart_stmt->bind_param("i", $customer_id);
    $cart_stmt->execute();
    $cart_result = $cart_stmt->get_result();

    $order_ids = [];

    while ($row = $cart_result->fetch_assoc()) {
        $product_id = $row['product_id'];
        $quantity = $row['quantity'];

        $vendor_stmt = $conn->prepare("SELECT vendor_id FROM products WHERE id = ?");
        $vendor_stmt->bind_param("i", $product_id);
        $vendor_stmt->execute();
        $vendor_result = $vendor_stmt->get_result();
        $vendor_id = $vendor_result->fetch_assoc()['vendor_id'];

        $order_stmt = $conn->prepare("INSERT INTO orders (customer_id, vendor_id, product_id, quantity, payment_method, status) VALUES (?, ?, ?, ?, ?, 'pending')");
        $order_stmt->bind_param("iiiis", $customer_id, $vendor_id, $product_id, $quantity, $payment_method);
        $order_stmt->execute();
        $order_ids[] = $order_stmt->insert_id;
    }

    $delete_cart = $conn->prepare("DELETE FROM cart_items WHERE user_id = ?");
    $delete_cart->bind_param("i", $customer_id);
    $delete_cart->execute();

    $encoded = urlencode(implode(",", $order_ids));
    header("Location: order_success.php?order_ids=$encoded");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Checkout - TUPTOrderGo</title>
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
    .cart-box {
        background: #fff;
        max-width: 900px;
        width: 100%;
        border-radius: 14px;
        padding: 30px;
        box-shadow: 0 6px 20px rgba(0,0,0,0.08);
        position: relative;
    }
    header {
        text-align: center;
        font-size: 1.8rem;
        font-weight: 600;
        color: #e67e22;
        margin-bottom: 25px;
        position: relative;
    }
    .back-btn {
        text-decoration: none;
        color: white;
        background: #e67e22;
        font-size: 13px;
        font-weight: 500;
        padding: 6px 14px;
        border-radius: 6px;
        position: absolute;
        top: 20px;
        left: 20px;
        transition: background 0.3s;
    }
    .back-btn:hover {
        background: #d35400;
    }
    h2 {
        font-size: 1.4rem;
        color: #e67e22;
        margin-bottom: 20px;
        text-align: center;
    }
    .cart-item {
        display: flex;
        align-items: center;
        gap: 20px;
        background: #fdf6f1;
        padding: 16px;
        border-radius: 12px;
        margin-bottom: 14px;
    }
    .cart-item img {
        width: 80px;
        height: 80px;
        object-fit: cover;
        border-radius: 10px;
        border: 2px solid #f6c89f;
    }
    .cart-details {
        flex: 1;
    }
    .cart-details span.name {
        font-size: 16px;
        font-weight: 600;
        color: #2d3436;
        display: block;
        margin-bottom: 4px;
    }
    .cart-details span {
        font-size: 13px;
        color: #636e72;
        display: block;
    }
    .empty {
        background: #f6c89f;
        color: #2d3436;
        text-align: center;
        padding: 16px;
        border-radius: 10px;
        font-style: italic;
        margin-bottom: 20px;
    }
    .total {
        text-align: right;
        font-size: 17px;
        font-weight: 600;
        color: #e67e22;
        margin-top: 10px;
        background: #fdf6f1;
        padding: 8px 12px;
        border-radius: 8px;
    }
    form {
        margin-top: 20px;
        text-align: right;
    }
    select {
        padding: 9px 14px;
        font-size: 13px;
        border: 1px solid #ccc;
        border-radius: 8px;
        margin-right: 10px;
        background: #fffaf5;
        transition: border-color 0.3s;
    }
    select:focus {
        outline: none;
        border-color: #e67e22;
    }
    button {
        padding: 10px 20px;
        background: #e67e22;
        color: white;
        font-size: 14px;
        font-weight: 500;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        transition: background 0.3s ease;
    }
    button:hover {
        background: #d35400;
    }
    @media(max-width:680px){
        .cart-item {
            flex-direction: column;
            align-items: flex-start;
        }
        form {
            text-align: center;
        }
        select {
            margin-bottom: 10px;
        }
    }
</style>
</head>
<body>

<div class="cart-box">
    <header>
        <a href="cart.php" class="back-btn">← Back</a>
        TUPTOrderGo Checkout
    </header>
    <h2>🛒 Review Your Cart</h2>

    <?php
    $cart_display_stmt = $conn->prepare("
        SELECT p.name, p.image_url, p.price, ci.quantity 
        FROM cart_items ci
        JOIN products p ON ci.product_id = p.id
        WHERE ci.user_id = ?
    ");
    $cart_display_stmt->bind_param("i", $customer_id);
    $cart_display_stmt->execute();
    $cart_display_result = $cart_display_stmt->get_result();

    $total = 0;

    if ($cart_display_result->num_rows === 0) {
        echo "<div class='empty'>Your cart is empty. Add items first!</div>";
    } else {
        while ($item = $cart_display_result->fetch_assoc()) {
            $imageFile = !empty($item['image_url']) ? htmlspecialchars($item['image_url']) : 'no-image.png';
            $imagePath = "../images/" . $imageFile;

            $price = $item['price'];
            $quantity = $item['quantity'];
            $subtotal = $price * $quantity;
            $total += $subtotal;

            echo "<div class='cart-item'>";
            echo "<img src='$imagePath' alt='Product'>";
            echo "<div class='cart-details'>";
            echo "<span class='name'>" . htmlspecialchars($item['name']) . "</span>";
            echo "<span>Quantity: " . htmlspecialchars($quantity) . "</span>";
            echo "<span>Price: ₱" . number_format($price, 2) . "</span>";
            echo "<span>Subtotal: ₱" . number_format($subtotal, 2) . "</span>";
            echo "</div></div>";
        }
        echo "<div class='total'>Total: ₱" . number_format($total, 2) . "</div>";
    }
    ?>

    <?php if ($cart_display_result->num_rows > 0): ?>
    <form method="POST">
        <select name="payment_method" required>
            <option value="">-- Select Payment Method --</option>
            <option value="cash">Cash</option>
            <option value="gcash">GCash</option>
        </select>
        <button type="submit">Confirm All Orders</button>
    </form>
    <?php endif; ?>
</div>

</body>
</html>