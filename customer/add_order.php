<?php
session_start();
include '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header("Location: ../login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customer_id = $_SESSION['user_id'];
    $product_id = intval($_POST['product_id']);
    $vendor_id = intval($_POST['vendor_id']);
    $quantity = intval($_POST['quantity']);
    $payment_method = $_POST['payment_method'];

    // Insert into orders table
    $stmt = $conn->prepare("INSERT INTO orders (customer_id, vendor_id, product_id, quantity, payment_method, status) VALUES (?, ?, ?, ?, ?, 'pending')");
    $stmt->bind_param("iiiis", $customer_id, $vendor_id, $product_id, $quantity, $payment_method);

    if ($stmt->execute()) {
        $order_id = $stmt->insert_id;
        header("Location: order_success.php?order_id=$order_id");
        exit;
    } else {
        echo "Failed to place order.";
    }
}
?>
