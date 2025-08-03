<?php
include '../../config/db.php';


if (isset($_GET['vendor_id'])) {
    $vendor_id = intval($_GET['vendor_id']);

    $sql = "SELECT id, name, price FROM products WHERE vendor_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $vendor_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $products = [];
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }

    echo json_encode($products);
}
?>
