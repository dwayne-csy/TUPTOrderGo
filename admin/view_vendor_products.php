<?php
session_start();
include '../config/db.php';

// Only allow admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// Get vendor_id from URL
$vendorId = isset($_GET['vendor_id']) ? intval($_GET['vendor_id']) : 1;

// Handle approval
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['approve_product'])) {
    $product_id = intval($_POST['product_id']);
    $stmt = $conn->prepare("UPDATE products SET is_approved = 1 WHERE id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $stmt->close();

    // Redirect to prevent resubmission
    header("Location: view_vendor_products.php?vendor_id=$vendorId");
    exit;
}

// Fetch all products (approved and unapproved) for the specified vendor
$sql = "SELECT * FROM products WHERE vendor_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $vendorId);
$stmt->execute();
$product_query = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Vendor <?= $vendorId; ?> - Products</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
<style>
body {
    font-family: 'Poppins', sans-serif;
    background: #f9fafb;
    padding: 20px;
    margin: 0;
}
.top-bar {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    background: #d35400;
    color: white;
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    z-index: 1000;
}
.top-bar h2 {
    margin: 0;
    font-size: 18px;
    font-weight: 500;
}
.back-btn {
    background: #e67e22;
    color: white;
    padding: 7px 12px;
    border-radius: 6px;
    text-decoration: none;
    font-size: 14px;
    font-weight: 500;
    transition: background 0.3s;
}
.back-btn:hover {
    background: #d35400;
}
h1 {
    text-align: center;
    margin: 80px 0 30px;
    color: #2d3436;
}
.product {
    display: inline-block;
    width: 220px;
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 6px 18px rgba(0,0,0,0.08);
    margin: 10px;
    padding: 12px;
    text-align: center;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}
.product:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(0,0,0,0.12);
}
.product img {
    width: 100%;
    height: 140px;
    object-fit: cover;
    border-radius: 8px;
    margin-bottom: 8px;
}
.product h3 {
    font-size: 15px;
    margin: 5px 0;
    color: #2d3436;
}
.product p {
    font-weight: 500;
    color: #27ae60;
    margin: 0;
}
.approve-btn {
    background: #27ae60;
    color: white;
    border: none;
    padding: 6px 12px;
    margin-top: 8px;
    cursor: pointer;
    border-radius: 6px;
    font-size: 13px;
    font-weight: 500;
    transition: background 0.3s;
}
.approve-btn:hover {
    background: #219150;
}
.approved-label {
    display: inline-block;
    margin-top: 8px;
    font-size: 13px;
    color: #888;
}
@media (max-width: 600px) {
    .product {
        width: 100%;
    }
}
</style>
</head>
<body>

<div class="top-bar">
    <a href="managevendor.php" class="back-btn">← Back</a>
    <h2>TUPT OrderGO</h2>
    <div></div>
</div>

<h1>Products from Vendor #<?= $vendorId; ?></h1>

<?php
if ($product_query->num_rows > 0) {
    while ($product = $product_query->fetch_assoc()) {
        $name = htmlspecialchars($product['name']);
        $price = number_format($product['price'], 2);
        $image = !empty($product['image_url']) ? $product['image_url'] : 'default-product.jpg';
        $is_approved = $product['is_approved'];

        echo "<div class='product'>
                <img src='../images/$image' alt='$name'>
                <h3>$name</h3>
                <p>₱$price</p>";
        if ($is_approved) {
            echo "<span class='approved-label'>✅ Approved</span>";
        } else {
            echo "<form method='post'>
                    <input type='hidden' name='product_id' value='{$product['id']}'>
                    <input type='submit' name='approve_product' value='Approve' class='approve-btn'>
                  </form>";
        }
        echo "</div>";
    }
} else {
    echo "<p style='text-align:center;color:#888;'>No products found for this vendor.</p>";
}
?>

</body>
</html>