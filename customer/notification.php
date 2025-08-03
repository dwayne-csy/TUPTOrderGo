<?php
session_start();
include '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header("Location: ../login.php");
    exit;
}

$customer_id = $_SESSION['user_id'];

// Delete single notification
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $notification_id = intval($_GET['delete']);
    $deleteStmt = $conn->prepare("DELETE FROM notifications WHERE id = ? AND customer_id = ?");
    $deleteStmt->bind_param("ii", $notification_id, $customer_id);
    $deleteStmt->execute();
    header("Location: notification.php");
    exit;
}

// Delete all notifications
if (isset($_GET['delete_all'])) {
    $deleteAllStmt = $conn->prepare("DELETE FROM notifications WHERE customer_id = ?");
    $deleteAllStmt->bind_param("i", $customer_id);
    $deleteAllStmt->execute();
    header("Location: notification.php");
    exit;
}

// Fetch notifications
$sql = "SELECT id, message, created_at FROM notifications WHERE customer_id = ? ORDER BY created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <title>My Notifications</title>
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
        .notification {
            position: relative;
            background-color: #fff7f0;
            border-left: 5px solid #e67e22;
            padding: 12px 16px;
            margin-bottom: 12px;
            border-radius: 8px;
            font-size: 14px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.05);
        }
        .notification small {
            display: block;
            color: #777;
            margin-top: 5px;
            font-size: 12px;
        }
        .delete-btn {
            position: absolute;
            top: 8px;
            right: 12px;
            background: none;
            border: none;
            color: #e74c3c;
            font-size: 18px;
            cursor: pointer;
            transition: color 0.3s;
        }
        .delete-btn:hover {
            color: #c0392b;
        }
        .delete-all-btn {
            display: block;
            margin: 25px auto 0;
            background-color: #e67e22;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: background 0.3s;
        }
        .delete-all-btn:hover {
            background-color: #d35400;
        }
        .no-data {
            text-align: center;
            color: #777;
            font-size: 14px;
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
    <h2>🔔 My Notifications</h2>

    <?php if ($result->num_rows > 0): ?>
        <?php while ($row = $result->fetch_assoc()): ?>
            <div class="notification">
                <?= htmlspecialchars($row['message']) ?>
                <small><?= date('M d, Y H:i', strtotime($row['created_at'])) ?></small>
                <form method="get" style="margin:0;">
                    <button class="delete-btn" name="delete" value="<?= $row['id'] ?>" title="Delete">🗑️</button>
                </form>
            </div>
        <?php endwhile; ?>
        <form method="get">
            <button class="delete-all-btn" name="delete_all" onclick="return confirm('Delete all notifications?')">Delete All 🗑️</button>
        </form>
    <?php else: ?>
        <p class="no-data">You have no notifications yet.</p>
    <?php endif; ?>
</div>
</body>
</html>