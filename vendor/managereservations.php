<?php
session_start();
include '../config/db.php';

// Only vendors can access this page
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'vendor') {
    header("Location: ../login.php");
    exit;
}

$vendor_id = $_SESSION['user_id'];
$vendor_name = $_SESSION['fullname'] ?? 'Vendor';

// Handle reservation acceptance
if (isset($_GET['pending'])) {
    $reservation_id = intval($_GET['pending']);

    // Accept the reservation
    $update_sql = "UPDATE reservations SET status = 'accepted' WHERE id = ? AND vendor_id = ?";
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param("ii", $reservation_id, $vendor_id);
    $stmt->execute();
    $stmt->close();

    // Get customer ID for notification
    $res_sql = "SELECT customer_id FROM reservations WHERE id = ?";
    $stmt = $conn->prepare($res_sql);
    $stmt->bind_param("i", $reservation_id);
    $stmt->execute();
    $res_result = $stmt->get_result();

    if ($res_row = $res_result->fetch_assoc()) {
        $customer_id = $res_row['customer_id'];
        $timestamp = date('M d, Y H:i');
        $message = "📅 $vendor_name accepted your reservation on $timestamp.";

        // Insert into notifications table
        $notif_sql = "INSERT INTO notifications (customer_id, vendor_id, reservation_id, message) VALUES (?, ?, ?, ?)";
        $notif_stmt = $conn->prepare($notif_sql);
        $notif_stmt->bind_param("iiis", $customer_id, $vendor_id, $reservation_id, $message);
        $notif_stmt->execute();
        $notif_stmt->close();
    }

    $stmt->close();
    header("Location: managereservations.php");
    exit;
}

// Fetch vendor-specific reservations
$sql = "SELECT r.*, u.fullname AS customer_name, p.name AS product_name 
        FROM reservations r
        JOIN users u ON r.customer_id = u.id
        JOIN products p ON r.product_id = p.id
        WHERE r.vendor_id = ?
        ORDER BY r.date, r.time";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $vendor_id);
$stmt->execute();
$reservations = $stmt->get_result();
?>


<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Manage Reservations - <?= htmlspecialchars($vendor_name) ?></title>
<style>
    body {
        background: #f8f8f8;
        font-family: 'Segoe UI', sans-serif;
        margin: 0;
        padding: 0;
    }

    .container {
        max-width: 1000px;
        margin: 40px auto;
        background: #fff;
        padding: 30px;
        border-radius: 20px;
        box-shadow: 0 0 15px rgba(0,0,0,0.1);
    }

    h2 {
        text-align: center;
        color: #d35400;
        margin-bottom: 20px;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
        border-radius: 10px;
        overflow: hidden;
        background: #fff;
    }

    th, td {
        padding: 12px;
        border-bottom: 1px solid #eee;
        text-align: center;
        font-size: 14px;
    }

    th {
        background: #e67e22;
        color: #fff;
    }

    tr:nth-child(even) {
        background: #fafafa;
    }

    .btn {
        padding: 8px 14px;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        font-size: 14px;
        color: #fff;
        background: #e67e22;
        text-decoration: none;
        display: inline-block;
        transition: background 0.3s ease;
    }

    .btn:hover {
        background: #d35400;
    }

    .accepted-text {
        color: green;
        font-weight: bold;
    }

    .pending-text {
        color: orange;
        font-weight: bold;
    }

    .back-btn {
        margin: 20px;
        display: inline-block;
        padding: 10px 18px;
        background: #e67e22;
        color: #fff;
        text-decoration: none;
        border-radius: 10px;
        font-size: 14px;
        transition: background 0.3s ease;
    }

    .back-btn:hover {
        background: #d35400;
    }

    @media screen and (max-width: 768px) {
        table, thead, tbody, th, td, tr {
            display: block;
        }

        td {
            text-align: right;
            position: relative;
            padding-left: 50%;
        }

        td::before {
            content: attr(data-label);
            position: absolute;
            left: 10px;
            font-weight: bold;
            color: #333;
        }

        tr {
            margin-bottom: 15px;
        }
    }
</style>
</head>
<body>
<div class="container">
    <a href="dashboard.php" class="back-btn">← Back</a>
    <h2>Reservations</h2>

    <table>
        <thead>
            <tr>
                <th>Customer</th>
                <th>Product</th>
                <th>Date</th>
                <th>Time</th>
                <th>Message</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
        <?php while ($row = $reservations->fetch_assoc()): ?>
            <tr>
                <td data-label="Customer"><?= htmlspecialchars($row['customer_name']) ?></td>
                <td data-label="Product"><?= htmlspecialchars($row['product_name']) ?></td>
                <td data-label="Date"><?= htmlspecialchars($row['date']) ?></td>
                <td data-label="Time"><?= htmlspecialchars($row['time']) ?></td>
                <td data-label="Message"><?= htmlspecialchars($row['message']) ?></td>
                <td data-label="Status" class="<?= $row['status'] === 'accepted' ? 'accepted-text' : 'pending-text' ?>">
                    <?= ucfirst($row['status']) ?>
                </td>
                <td data-label="Action">
                    <?php if ($row['status'] === 'pending'): ?>
                        <a href="?pending=<?= $row['id'] ?>" class="btn" onclick="return confirm('Accept this reservation?')">Accept</a>
                    <?php else: ?>
                        <span class="accepted-text">✔ Accepted</span>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
</div>
</body>
</html>
