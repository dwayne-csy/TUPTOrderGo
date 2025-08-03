<?php
session_start();
include '../../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header('Location: ../login.php');
    exit;
}

$customer_id = $_SESSION['user_id'];

$sql = "SELECT r.*, 
               u.fullname AS vendor_name, 
               p.name AS product_name 
        FROM reservations r
        JOIN users u ON r.vendor_id = u.id
        JOIN products p ON r.product_id = p.id
        WHERE r.customer_id = ?
        ORDER BY r.date DESC, r.time DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$reservations = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <title>My Reservations</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            max-width: 950px;
            margin: 40px auto;
            background: #f9f9f9;
            padding: 20px;
        }
        .container {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        }
        h2 {
            text-align: center;
            margin-bottom: 25px;
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
        p {
            text-align: center;
            color: #777;
            font-size: 14px;
        }
    </style>
</head>
<body>
<div class="container">
    <a href="reservations.php" class="back-btn">← Back</a>
    <h2>📄 Reservation Receipts</h2>

    <?php if ($reservations->num_rows > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>Vendor</th>
                    <th>Product</th>
                    <th>Date</th>
                    <th>Time</th>
                    <th>Message</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $reservations->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['vendor_name']) ?></td>
                        <td><?= htmlspecialchars($row['product_name']) ?></td>
                        <td><?= htmlspecialchars($row['date']) ?></td>
                        <td><?= htmlspecialchars($row['time']) ?></td>
                        <td><?= htmlspecialchars($row['message']) ?></td>
                        <td><?= ucfirst(htmlspecialchars($row['status'])) ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>No reservations found.</p>
    <?php endif; ?>
</div>