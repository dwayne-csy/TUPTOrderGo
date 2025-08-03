<?php
session_start();
include '../../config/db.php';

// Check if customer is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header('Location: ../../login.php');
    exit;
}

// Fetch available stalls (vendors with products)
$stall_sql = "SELECT DISTINCT u.id, u.fullname 
              FROM users u 
              JOIN products p ON u.id = p.vendor_id 
              WHERE u.role = 'vendor'";
$stalls_result = $conn->query($stall_sql);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Reserve Table</title>
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
        form label {
            display: block;
            margin-top: 10px;
            margin-bottom: 4px;
            font-size: 14px;
            font-weight: 500;
            color: #333;
        }
        select, input[type="submit"], input[type="text"], input[type="date"], input[type="time"] {
            width: 100%;
            padding: 8px 10px;
            margin-bottom: 10px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
        }
        input[type="submit"] {
            background-color: #e67e22;
            color: white;
            border: none;
            font-weight: 500;
            cursor: pointer;
            transition: background 0.3s;
        }
        input[type="submit"]:hover {
            background-color: #d35400;
        }
        .view-btn a, .back-btn {
            display: inline-block;
            margin-top: 15px;
            text-decoration: none;
            background-color: #e67e22;
            color: white;
            padding: 8px 14px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 500;
            transition: background 0.3s;
        }
        .view-btn a:hover, .back-btn:hover {
            background: #d35400;
        }
        .view-btn {
            text-align: center;
            margin-top: 10px;
        }
    </style>
</head>
<body>
<div class="container">
    <a href="../dashboard.php" class="back-btn">← Back</a>
    <h2>🍽 Reserve a Table</h2>

    <form method="POST" action="process_reservation.php">
        <label for="stall">Select Stall:</label>
        <select name="stall" id="stall" required>
            <option value="">-- Select Stall --</option>
            <?php while ($row = $stalls_result->fetch_assoc()): ?>
                <option value="<?= $row['id'] ?>"><?= htmlspecialchars($row['fullname']) ?></option>
            <?php endwhile; ?>
        </select>

        <label for="product">Select Product:</label>
        <select name="product" id="product" required>
            <option value="">-- Select Product --</option>
        </select>

        <label for="message">Message (optional):</label>
        <input type="text" name="message" id="message">

        <label for="date">Reservation Date:</label>
        <input type="date" name="date" id="date" required>

        <label for="time">Reservation Time:</label>
        <input type="time" name="time" id="time" required>

        <input type="submit" value="Reserve">
    </form>

    <div class="view-btn">
        <a href="my_reservations.php">📄 View My Reservations</a>
    </div>
</div>

<script>
document.getElementById('stall').addEventListener('change', function () {
    const vendorId = this.value;
    const productSelect = document.getElementById('product');
    productSelect.innerHTML = '<option value="">Loading...</option>';

    fetch('fetch_products.php?vendor_id=' + vendorId)
        .then(response => response.json())
        .then(data => {
            productSelect.innerHTML = '<option value="">-- Select Product --</option>';
            if (data.length > 0) {
                data.forEach(product => {
                    const option = document.createElement('option');
                    option.value = product.id;
                    option.textContent = product.name + ' (₱' + parseFloat(product.price).toFixed(2) + ')';
                    productSelect.appendChild(option);
                });
            } else {
                productSelect.innerHTML = '<option value="">No products available</option>';
            }
        })
        .catch(error => {
            console.error('Error fetching products:', error);
            productSelect.innerHTML = '<option value="">Error loading products</option>';
        });
});
</script>
</body>
</html>