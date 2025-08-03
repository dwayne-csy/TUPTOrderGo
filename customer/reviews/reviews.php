<?php
session_start();
include '../../config/db.php';


// Only allow logged-in customers
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header("Location: ../login.php");
    exit;
}

// Get order_id and product_name from GET
$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
$product_name = isset($_GET['product_name']) ? htmlspecialchars($_GET['product_name']) : '';

// Handle form submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rating = (int)$_POST['rating'];
    $comment = trim($_POST['comment']);

    if ($rating >= 1 && $rating <= 5 && $order_id) {
        $stmt = $conn->prepare("INSERT INTO reviews (order_id, customer_id, rating, comment, created_at) VALUES (?, ?, ?, ?, NOW())");
        $stmt->bind_param("iiis", $order_id, $_SESSION['user_id'], $rating, $comment);
        if ($stmt->execute()) {
            $stmt->close();
            // Redirect to orderhistory.php after successful submission
            header("Location: ../orderhistory.php");
            exit;
        } else {
            $error = "❌ Failed to submit review.";
            $stmt->close();
        }
    } else {
        $error = "❌ Invalid input.";
    }
}
?>


<!DOCTYPE html>
<html>
<head>
    <title>Leave a Review</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 600px;
            margin: 40px auto;
            background-color: #f9f9f9;
            padding: 20px;
        }
        h2 {
            text-align: center;
        }
        form {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
        }
        label {
            display: block;
            margin-top: 10px;
        }
        textarea {
            width: 100%;
            height: 80px;
            resize: vertical;
        }
        button {
            margin-top: 15px;
            background-color: #007BFF;
            color: white;
            border: none;
            padding: 8px 16px;
            cursor: pointer;
            border-radius: 4px;
        }
        button:hover {
            background-color: #0056b3;
        }
        .message {
            text-align: center;
            margin-top: 10px;
            color: green;
        }
        .error {
            text-align: center;
            margin-top: 10px;
            color: red;
        }
        .star-rating {
            direction: rtl;
            display: inline-flex;
            justify-content: center;
            font-size: 2em;
        }
        .star-rating input[type=radio] {
            display: none;
        }
        .star-rating label {
            color: #ccc;
            cursor: pointer;
            transition: color 0.2s;
            padding: 0 3px;
        }
        .star-rating input[type=radio]:checked ~ label {
            color: gold;
        }
        .star-rating label:hover,
        .star-rating label:hover ~ label {
            color: gold;
        }
        .back-link {
            display: block;
            text-align: center;
            margin-top: 20px;
            text-decoration: none;
            color: #007BFF;
        }
    </style>
</head>
<body>
    <h2>⭐ Leave a Review for: <?= $product_name ?></h2>

    <?php if (!empty($success)): ?>
        <p class="message"><?= $success ?></p>
    <?php elseif (!empty($error)): ?>
        <p class="error"><?= $error ?></p>
    <?php endif; ?>

    <form method="POST">
        <label>Rating:</label>
        <div class="star-rating">
            <input type="radio" id="star5" name="rating" value="5" required><label for="star5">★</label>
            <input type="radio" id="star4" name="rating" value="4"><label for="star4">★</label>
            <input type="radio" id="star3" name="rating" value="3"><label for="star3">★</label>
            <input type="radio" id="star2" name="rating" value="2"><label for="star2">★</label>
            <input type="radio" id="star1" name="rating" value="1"><label for="star1">★</label>
        </div>

        <label>Comment:</label>
        <textarea name="comment" required></textarea>

        <button type="submit">Submit Review</button>
    </form>

    <a href="../orderhistory.php" class="back-link">← Back to Order History</a>
</body>
</html>
