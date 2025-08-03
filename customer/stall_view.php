<?php
session_start();
include '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header("Location: ../login.php");
    exit;
}

$customer_id = $_SESSION['user_id'];
$fullname = $_SESSION['fullname'] ?? 'Customer';

// Get vendor_id from URL
$vendorId = isset($_GET['vendor_id']) ? intval($_GET['vendor_id']) : 1;

// Fetch cart item quantity
$cart_count = 0;
$cart_stmt = $conn->prepare("SELECT SUM(quantity) AS total FROM cart_items WHERE user_id = ?");
$cart_stmt->bind_param("i", $customer_id);
$cart_stmt->execute();
$cart_result = $cart_stmt->get_result();
if ($cart_row = $cart_result->fetch_assoc()) {
    $cart_count = $cart_row['total'] ?? 0;
}

// Fetch products for the specified vendor
$sql = "SELECT * FROM products WHERE vendor_id = ? AND is_approved = 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $vendorId);
$stmt->execute();
$product_query = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
<title>Vendor <?php echo $vendorId; ?> - Products</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
<style>
body {
    font-family: 'Poppins', sans-serif;
    margin: 0;
    padding: 80px 20px 20px;
    background: #f9fafb;
}
.top-bar {
    position: fixed;
    top: 0; left: 0; right: 0;
    background: #fff;
    border-bottom: 1px solid #ddd;
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 20px;
    box-shadow: 0 2px 6px rgba(0,0,0,0.05);
    z-index: 999;
}
.top-bar h2 {
    margin: 0;
    font-size: 20px;
    color: #e67e22;
}
.back-btn {
    background: #e67e22;
    color: #fff;
    padding: 6px 12px;
    text-decoration: none;
    border-radius: 6px;
    font-size: 13px;
}
.back-btn:hover { background: #d35400; }
.cart-icon {
    background: #e67e22;
    color: #fff;
    padding: 6px 12px;
    border-radius: 6px;
    text-decoration: none;
    position: relative;
    font-size: 13px;
}
.cart-icon:hover { background: #d35400; }
.cart-badge {
    position: absolute;
    top: -6px; right: -6px;
    background: red;
    color: white;
    font-size: 11px;
    padding: 2px 5px;
    border-radius: 50%;
}
h1 {
    color: #e67e22;
    font-size: 1.5rem;
    margin-bottom: 20px;
}
.product {
    display: inline-block;
    width: 260px;
    background: #fff;
    border-radius: 10px;
    margin: 10px;
    padding: 15px;
    text-align: center;
    box-shadow: 0 4px 10px rgba(0,0,0,0.05);
    vertical-align: top;
    transition: transform 0.2s;
}
.product:hover { transform: translateY(-4px); }
.product img {
    width: 100%;
    height: 150px;
    object-fit: cover;
    border-radius: 8px;
}
h3 { font-size: 16px; margin: 8px 0; color: #333; }
p { margin: 4px 0; color: #555; font-size: 14px; }
.button-group {
    display: flex;
    justify-content: space-between;
    margin-top: 8px;
}
.cart-btn, .order-btn {
    flex: 1;
    margin: 0 2px;
    font-size: 13px;
    color: white;
    padding: 6px;
    text-decoration: none;
    border-radius: 6px;
}
.cart-btn { background: #e67e22; }
.cart-btn:hover { background: #d35400; }
.order-btn { background: #27ae60; }
.order-btn:hover { background: #219150; }
.reviews {
    margin-top: 8px;
    text-align: left;
    max-height: 130px;
    overflow-y: auto;
    border-top: 1px solid #eee;
    padding-top: 6px;
}
.review {
    border-bottom: 1px solid #f1f1f1;
    margin-bottom: 4px;
    padding-bottom: 4px;
}
.review:last-child { border-bottom: none; }
.review-customer {
    font-weight: 500;
    font-size: 13px;
    color: #34495e;
}
.review-stars {
    color: gold;
    font-size: 12px;
}
.review-comment {
    font-size: 12px;
    color: #555;
    margin-top: 2px;
    font-style: italic;
}
@media(max-width:600px){
    .product { width: 100%; }
}
</style>
</head>
<body>

<div class="top-bar">
    <a href="dashboard.php" class="back-btn">← Back</a>
    <h2>TUPTOrderGo</h2>
    <a href="cart.php" class="cart-icon">🛒
        <?php if ($cart_count > 0): ?>
            <span class="cart-badge"><?php echo $cart_count; ?></span>
        <?php endif; ?>
    </a>
</div>

<h1>Products for Vendor <?php echo $vendorId; ?></h1>

<?php
if ($product_query->num_rows > 0) {
    while ($product = $product_query->fetch_assoc()) {
        $id = $product['id'];
        $name = htmlspecialchars($product['name']);
        $price = number_format($product['price'],2);
        $image = $product['image_url'] ? htmlspecialchars($product['image_url']) : 'default-product.jpg';

        // Fetch reviews
        $reviewStmt = $conn->prepare("
            SELECT r.rating, r.comment, u.fullname
            FROM reviews r
            JOIN orders o ON r.order_id = o.id
            JOIN users u ON r.customer_id = u.id
            WHERE o.product_id = ?
            ORDER BY r.created_at DESC
            LIMIT 5
        ");
        $reviewStmt->bind_param("i", $id);
        $reviewStmt->execute();
        $reviews = $reviewStmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $reviewStmt->close();

        echo "<div class='product'>
            <img src='../images/$image' alt='$name'>
            <h3>$name</h3>
            <p>₱$price</p>
            <div class='button-group'>
                <a href='add_to_cart.php?product_id=$id' class='cart-btn'>🛒 Cart</a>
                <a href='orders.php?product_id=$id' class='order-btn'>🧾 Order</a>
            </div>";

        if ($reviews) {
            echo "<div class='reviews'>";
            foreach ($reviews as $r) {
                $stars = str_repeat('★',$r['rating']) . str_repeat('☆',5-$r['rating']);
                $customer = htmlspecialchars($r['fullname']);
                $comment = nl2br(htmlspecialchars($r['comment']));
                echo "<div class='review'>
                    <div class='review-customer'>$customer</div>
                    <div class='review-stars'>$stars</div>
                    <div class='review-comment'>$comment</div>
                </div>";
            }
            echo "</div>";
        } else {
            echo "<p style='font-size:12px;color:#999;'>No reviews yet.</p>";
        }
        echo "</div>";
    }
} else {
    echo "<p>No products found for this stall.</p>";
}
?>

</body>
</html>