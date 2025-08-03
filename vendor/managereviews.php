<?php
session_start();
include '../config/db.php';

// Only allow logged-in vendors
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'vendor') {
    header("Location: ../login.php");
    exit;
}

$vendor_id = $_SESSION['user_id'];

// Handle delete request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_review_id'])) {
    $delete_review_id = (int)$_POST['delete_review_id'];

    // Verify that the review belongs to this vendor's product before deleting
    $verifySql = "
        SELECT r.id
        FROM reviews r
        JOIN orders o ON r.order_id = o.id
        JOIN products p ON o.product_id = p.id
        WHERE r.id = ? AND p.vendor_id = ?
    ";
    $verifyStmt = $conn->prepare($verifySql);
    $verifyStmt->bind_param("ii", $delete_review_id, $vendor_id);
    $verifyStmt->execute();
    $verifyResult = $verifyStmt->get_result();
    
    if ($verifyResult->num_rows > 0) {
        // Delete the review
        $deleteSql = "DELETE FROM reviews WHERE id = ?";
        $deleteStmt = $conn->prepare($deleteSql);
        $deleteStmt->bind_param("i", $delete_review_id);
        $deleteStmt->execute();
        $deleteStmt->close();

        $message = "Review deleted successfully.";
    } else {
        $message = "You do not have permission to delete this review.";
    }
    $verifyStmt->close();
}

// Fetch reviews for products belonging to this vendor
$sql = "
SELECT 
    r.id AS review_id,
    p.name AS product_name,
    u.fullname AS customer_name,
    r.rating,
    r.comment,
    r.created_at
FROM reviews r
JOIN orders o ON r.order_id = o.id
JOIN products p ON o.product_id = p.id
JOIN users u ON r.customer_id = u.id
WHERE p.vendor_id = ?
ORDER BY r.created_at DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $vendor_id);
$stmt->execute();
$result = $stmt->get_result();

$reviews = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Customer Reviews</title>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background: #f8f8f8;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 1000px;
            margin: 40px auto;
            background: #fff;
            padding: 30px;
            border-radius: 20px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
            position: relative;
        }
        .back-link {
            display: inline-block;
            text-decoration: none;
            background: #e67e22;
            color: #fff;
            padding: 8px 14px;
            border-radius: 8px;
            font-size: 13px;
            transition: background 0.3s ease;
            position: absolute;
            top: 20px;
            left: 20px;
        }
        .back-link:hover {
            background: #d35400;
        }
        h2 {
            text-align: center;
            color: #d35400;
            margin-top: 10px;
            margin-bottom: 25px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            border-radius: 10px;
            overflow: hidden;
        }
        th, td {
            padding: 12px;
            text-align: left;
            vertical-align: top;
            border-bottom: 1px solid #eee;
            font-size: 14px;
        }
        th {
            background: #e67e22;
            color: white;
        }
        tr:nth-child(even) {
            background: #fafafa;
        }
        tr:hover {
            background: #f1f1f1;
        }
        .rating-stars {
            color: gold;
            font-size: 1.2em;
        }
        form.delete-form {
            margin: 0;
        }
        button.delete-btn {
            background: #c0392b;
            color: white;
            border: none;
            padding: 6px 12px;
            cursor: pointer;
            border-radius: 6px;
            font-size: 0.9em;
            transition: background 0.3s ease;
        }
        button.delete-btn:hover {
            background: #922b21;
        }
        .message {
            text-align: center;
            margin-bottom: 20px;
            color: green;
            font-weight: bold;
        }
        @media screen and (max-width: 768px) {
            table, thead, tbody, th, td, tr { display: block; }
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
            tr { margin-bottom: 15px; }
        }
    </style>
</head>
<body>
<div class="container">
    <a href="dashboard.php" class="back-link">← Back</a>
    <h2>📋 Customer Reviews</h2>

    <?php if (!empty($message)): ?>
        <p class="message"><?= htmlspecialchars($message) ?></p>
    <?php endif; ?>

    <?php if (count($reviews) > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Customer</th>
                    <th>Rating</th>
                    <th>Comment</th>
                    <th>Reviewed On</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($reviews as $review): ?>
                    <tr>
                        <td data-label="Product"><?= htmlspecialchars($review['product_name']) ?></td>
                        <td data-label="Customer"><?= htmlspecialchars($review['customer_name']) ?></td>
                        <td data-label="Rating" class="rating-stars">
                            <?php
                                for ($i = 1; $i <= 5; $i++) {
                                    echo $i <= $review['rating'] ? '★' : '☆';
                                }
                            ?>
                        </td>
                        <td data-label="Comment"><?= nl2br(htmlspecialchars($review['comment'])) ?></td>
                        <td data-label="Reviewed On"><?= date('F j, Y g:i A', strtotime($review['created_at'])) ?></td>
                        <td data-label="Action">
                            <form method="POST" class="delete-form" onsubmit="return confirm('Are you sure you want to delete this review?');">
                                <input type="hidden" name="delete_review_id" value="<?= $review['review_id'] ?>">
                                <button type="submit" class="delete-btn">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p style="text-align: center;">No reviews found for your products yet.</p>
    <?php endif; ?>
</div>
</body>
</html>
