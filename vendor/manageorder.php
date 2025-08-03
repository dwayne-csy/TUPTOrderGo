<?php
session_start();
include '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'vendor') {
    header("Location: ../login.php");
    exit;
}

$vendor_id = $_SESSION['user_id'];
$vendor_name = $_SESSION['fullname'] ?? 'Vendor';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'], $_POST['action'])) {
    $order_id = intval($_POST['order_id']);
    $action = $_POST['action'];
    $status = '';

    if ($action === 'accept') {
        $status = 'accepted';
    } elseif ($action === 'reject') {
        $status = 'rejected';
    }

    if ($status !== '') {
        $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ? AND vendor_id = ?");
        $stmt->bind_param("sii", $status, $order_id, $vendor_id);
        $stmt->execute();

        $order_stmt = $conn->prepare("SELECT customer_id FROM orders WHERE id = ?");
        $order_stmt->bind_param("i", $order_id);
        $order_stmt->execute();
        $order_result = $order_stmt->get_result();
        $order_row = $order_result->fetch_assoc();

        if ($order_row) {
            $customer_id = $order_row['customer_id'];
            $timestamp = date('M d, Y H:i');
            $emoji = ($status === 'accepted') ? '✅' : '❎';
            $message = "$emoji $vendor_name $status your order on $timestamp.";

            $notif_stmt = $conn->prepare("INSERT INTO notifications (customer_id, vendor_id, order_id, message) VALUES (?, ?, ?, ?)");
            $notif_stmt->bind_param("iiis", $customer_id, $vendor_id, $order_id, $message);
            $notif_stmt->execute();
        }
    }

    header("Location: manageorder.php");
    exit;
}

$sql = "SELECT o.*, u.fullname AS customer_name FROM orders o JOIN users u ON o.customer_id = u.id WHERE o.vendor_id = ? ORDER BY o.created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $vendor_id);
$stmt->execute();
$orders = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Manage Orders - <?= htmlspecialchars($vendor_name) ?></title>
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
            background: #fff;
            border-radius: 10px;
            overflow: hidden;
        }

        th, td {
            padding: 12px;
            text-align: center;
            border-bottom: 1px solid #eee;
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
            text-decoration: none;
            transition: background 0.3s ease;
        }

        .btn-accept {
            background: #27ae60;
        }

        .btn-accept:hover {
            background: #1e8449;
        }

        .btn-reject {
            background: #c0392b;
        }

        .btn-reject:hover {
            background: #922b21;
        }

        .status-accepted {
            color: green;
            font-weight: bold;
        }

        .status-rejected {
            color: red;
            font-weight: bold;
        }

        .status-pending {
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

        .action-btns form {
            display: inline-block;
            margin: 2px;
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

            .action-btns {
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="dashboard.php" class="back-btn">← Back</a>
        <h2>Orders</h2>

        <table>
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Customer</th>
                    <th>Product ID</th>
                    <th>Quantity</th>
                    <th>Status</th>
                    <th>Placed At</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
            <?php while ($row = $orders->fetch_assoc()): ?>
                <tr>
                    <td data-label="Order ID">#<?= $row['id'] ?></td>
                    <td data-label="Customer"><?= htmlspecialchars($row['customer_name']) ?></td>
                    <td data-label="Product"><?= $row['product_id'] ?></td>
                    <td data-label="Qty"><?= $row['quantity'] ?></td>
                    <td data-label="Status" class="status-<?= $row['status'] ?>">
                        <?= ucfirst($row['status']) ?>
                    </td>
                    <td data-label="Placed At"><?= $row['created_at'] ?></td>
                    <td data-label="Action" class="action-btns">
                        <?php if ($row['status'] === 'pending'): ?>
                            <form method="POST">
                                <input type="hidden" name="order_id" value="<?= $row['id'] ?>">
                                <button type="submit" name="action" value="accept" class="btn btn-accept">Accept</button>
                            </form>
                            <form method="POST">
                                <input type="hidden" name="order_id" value="<?= $row['id'] ?>">
                                <button type="submit" name="action" value="reject" class="btn btn-reject">Reject</button>
                            </form>
                        <?php else: ?>
                            <em><?= ucfirst($row['status']) ?></em>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</body>

</html>
