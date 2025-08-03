<?php
session_start();
include '../../config/db.php';


// Only allow logged-in customers
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch all reviews by this customer (with product name)
$sql = "
SELECT 
    r.rating,
    r.comment,
    r.created_at,
    p.name AS product_name
FROM reviews r
JOIN orders o ON r.order_id = o.id
JOIN products p ON o.product_id = p.id
WHERE r.customer_id = ?
ORDER BY r.created_at DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$reviews = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>My Reviews</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            max-width: 950px;
            margin: 40px auto;
            background: #f9f9f9;
            padding: 20px;
        }
        h2 {
            text-align: center;
            margin-bottom: 30px;
            color: #e67e22;
        }
        .container {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
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
        .edit-button button {
            background-color: #e67e22;
            color: white;
            border: none;
            padding: 6px 12px;
            cursor: pointer;
            border-radius: 6px;
            font-size: 13px;
            transition: background 0.3s;
        }
        .edit-button button:hover {
            background-color: #d35400;
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
    <a href="../dashboard.php" class="back-btn">← Back</a>
    <h2>🌟 My Reviews</h2>

    <?php if (count($reviews) > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Rating</th>
                    <th>Comment</th>
                    <th>Date</th>
                    <th>Edit</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($reviews as $review): ?>
                    <tr>
                        <td><?= htmlspecialchars($review['product_name']) ?></td>
                        <td><?= str_repeat('⭐', (int)$review['rating']) ?> (<?= $review['rating'] ?>/5)</td>
                        <td><?= htmlspecialchars($review['comment']) ?></td>
                        <td><?= date('F j, Y g:i A', strtotime($review['created_at'])) ?></td>
                        <td>
                            <form action="editreviews.php" method="GET" style="margin:0;" class="edit-button">
                                <input type="hidden" name="product_name" value="<?= htmlspecialchars($review['product_name']) ?>">
                                <input type="hidden" name="created_at" value="<?= htmlspecialchars($review['created_at']) ?>">
                                <button type="submit">Edit</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>You haven't submitted any reviews yet.</p>
    <?php endif; ?>
</div>
</body>
</html>