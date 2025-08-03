<?php
session_start();
include '../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customer_id = $_SESSION['user_id'];
    $vendor_id = $_POST['stall'];
    $product_id = $_POST['product'];
    $message = $_POST['message'];
    $date = $_POST['date'];
    $time = $_POST['time'];
    $status = 'pending';

    $sql = "INSERT INTO reservations 
        (customer_id, vendor_id, product_id, message, date, time, status) 
        VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiissss", $customer_id, $vendor_id, $product_id, $message, $date, $time, $status);

    if ($stmt->execute()) {
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <title>Reservation Receipt</title>
            <style>
                body {
                    font-family: Arial, sans-serif;
                    background-color: #f4f4f4;
                    margin: 0;
                    padding: 0;
                    display: flex;
                    justify-content: center;
                    align-items: center;
                    height: 100vh;
                }
                .receipt-container {
                    background: #fff;
                    padding: 30px 40px;
                    border-radius: 10px;
                    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
                    max-width: 500px;
                    width: 100%;
                }
                h2 {
                    color: #333;
                    text-align: center;
                    margin-bottom: 20px;
                }
                .info {
                    margin: 10px 0;
                    font-size: 16px;
                }
                .back-btn {
                    display: block;
                    width: 100%;
                    padding: 12px;
                    text-align: center;
                    background-color: #007bff;
                    color: white;
                    border: none;
                    border-radius: 5px;
                    text-decoration: none;
                    font-size: 16px;
                    margin-top: 20px;
                    transition: background-color 0.3s ease;
                }
                .back-btn:hover {
                    background-color: #0056b3;
                }
            </style>
        </head>
        <body>
            <div class="receipt-container">
                <h2>Reservation Receipt</h2>
                <div class="info"><strong>Stall ID:</strong> <?= htmlspecialchars($vendor_id) ?></div>
                <div class="info"><strong>Product ID:</strong> <?= htmlspecialchars($product_id) ?></div>
                <div class="info"><strong>Message:</strong> <?= htmlspecialchars($message) ?></div>
                <div class="info"><strong>Date:</strong> <?= htmlspecialchars($date) ?></div>
                <div class="info"><strong>Time:</strong> <?= htmlspecialchars($time) ?></div>
                <div class="info"><strong>Status:</strong> <?= htmlspecialchars($status) ?></div>
                
                <a href="reservations.php" class="back-btn">Back to Reservations</a>
            </div>
        </body>
        </html>
        <?php
    } else {
        echo "Error saving reservation.";
    }
} else {
    header('Location: reservation.php');
    exit;
}
?>
