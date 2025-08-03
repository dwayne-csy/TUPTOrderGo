<?php
session_start();
include '../../config/db.php';


if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$product_name = isset($_GET['product_name']) ? $_GET['product_name'] : '';
$created_at = isset($_GET['created_at']) ? $_GET['created_at'] : '';

// Get existing review by product name + created_at
$sql = "
SELECT r.id, r.rating, r.comment, p.name AS product_name
FROM reviews r
JOIN orders o ON r.order_id = o.id
JOIN products p ON o.product_id = p.id
WHERE r.customer_id = ? AND p.name = ? AND r.created_at = ?
LIMIT 1
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iss", $user_id, $product_name, $created_at);
$stmt->execute();
$result = $stmt->get_result();
$review = $result->fetch_assoc();
$stmt->close();

if (!$review) {
    die("Review not found or access denied.");
}

// If form submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_rating = (int)$_POST['rating'];
    $new_comment = trim($_POST['comment']);

    if ($new_rating >= 1 && $new_rating <= 5) {
        $stmt = $conn->prepare("UPDATE reviews SET rating = ?, comment = ? WHERE id = ? AND customer_id = ?");
        $stmt->bind_param("isii", $new_rating, $new_comment, $review['id'], $user_id);
        if ($stmt->execute()) {
            $stmt->close();
            // Redirect to myreviews.php after successful update
            header("Location: myreviews.php");
            exit;
        } else {
            $error = "❌ Failed to update review.";
            $stmt->close();
        }
    } else {
        $error = "❌ Invalid rating.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Review</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 600px; margin: 40px auto; background-color: #f9f9f9; padding: 20px; }
        h2 { text-align: center; }
        form { background-color: white; padding: 20px; border-radius: 8px; }
        label { display: block; margin-top: 10px; }
        textarea { width: 100%; height: 80px; resize: vertical; }
        button { margin-top: 15px; background-color: #007BFF; color: white; border: none; padding: 8px 16px; cursor: pointer; border-radius: 4px; }
        button:hover { background-color: #0056b3; }
        .message { text-align: center; margin-top: 10px; color: green; }
        .error { text-align: center; margin-top: 10px; color: red; }
        .star-rating {
            direction: rtl;
            display: inline-flex;
            justify-content: center;
            font-size: 2em;
        }
        .star-rating input[type=radio] { display: none; }
        .star-rating label {
            color: #ccc;
            cursor: pointer;
            transition: color 0.2s;
            padding: 0 3px;
        }
        .star-rating input[type=radio]:checked ~ label { color: gold; }
        .star-rating label:hover,
        .star-rating label:hover ~ label { color: gold; }
        .back-link { display: block; text-align: center; margin-top: 20px; text-decoration: none; color: #007BFF; }
    </style>
</head>
<body>
    <h2>✏️ Edit Review for: <?= htmlspecialchars($review['product_name']) ?></h2>

    <?php if (!empty($success)): ?>
        <p class="message"><?= $success ?></p>
    <?php elseif (!empty($error)): ?>
        <p class="error"><?= $error ?></p>
    <?php endif; ?>

    <form method="POST">
        <label>Rating:</label>
        <div class="star-rating">
            <?php for ($i=5; $i>=1; $i--): ?>
                <input type="radio" id="star<?= $i ?>" name="rating" value="<?= $i ?>" <?= ($review['rating'] == $i) ? 'checked' : '' ?> required>
                <label for="star<?= $i ?>">★</label>
            <?php endfor; ?>
        </div>

        <label>Comment:</label>
        <textarea name="comment" required><?= htmlspecialchars($review['comment']) ?></textarea>

        <button type="submit">Update Review</button>
    </form>

    <a href="myreviews.php" class="back-link">← Back to My Reviews</a>
</body>
</html>
