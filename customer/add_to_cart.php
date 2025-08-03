<?php
session_start();
include '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['product_id'])) {
    $product_id = intval($_GET['product_id']);
    $user_id = $_SESSION['user_id'] ?? null;

    if (!$user_id) {
        header("Location: ../login.php");
        exit;
    }

    // Insert into cart_items (assuming you have this table)
    $stmt = $conn->prepare("INSERT INTO cart_items (user_id, product_id, quantity) VALUES (?, ?, 1)");
    $stmt->bind_param("ii", $user_id, $product_id);
    $stmt->execute();

    // Redirect to cart after adding
    header("Location: cart.php");
    exit;
} else {
    // Fallback if no product ID
    header("Location: dashboard.php");
    exit;
}
?>
