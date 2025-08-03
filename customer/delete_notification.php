<?php
session_start();
include '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header("Location: ../login.php");
    exit;
}

$customer_id = $_SESSION['user_id'];

if (isset($_GET['id'])) {
    $notification_id = intval($_GET['id']);

    // Ensure the notification belongs to the current customer
    $check = $conn->prepare("SELECT id FROM notifications WHERE id = ? AND customer_id = ?");
    $check->bind_param("ii", $notification_id, $customer_id);
    $check->execute();
    $check_result = $check->get_result();

    if ($check_result->num_rows > 0) {
        $delete = $conn->prepare("DELETE FROM notifications WHERE id = ?");
        $delete->bind_param("i", $notification_id);
        $delete->execute();
    }
}

header("Location: notification.php");
exit;
