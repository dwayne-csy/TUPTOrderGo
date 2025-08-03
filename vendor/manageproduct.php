<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'vendor') {
    header('Location: ../login.php');
    exit;
}

$vendor_id = $_SESSION['user_id'];
$fullname = $_SESSION['fullname'];

include '../config/db.php';

// Handle Add Product
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_product'])) {
    $name = $_POST['name'] ?? '';
    $price = floatval($_POST['price']);

    if (!empty($name) && $price > 0) {
        $image_url = null;

        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../images/';
            $imageName = basename($_FILES['image']['name']);
            $imageFileType = strtolower(pathinfo($imageName, PATHINFO_EXTENSION));
            $allowedTypes = ['jpg', 'jpeg', 'png'];

            if (in_array($imageFileType, $allowedTypes)) {
                $newFileName = time() . "_" . $imageName;
                $targetFile = $uploadDir . $newFileName;

                if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
                    $image_url = $newFileName;
                }
            }
        }

        $stmt = $conn->prepare("INSERT INTO products (vendor_id, name, price, image_url, is_approved) VALUES (?, ?, ?, ?, 0)");
        $stmt->bind_param("isds", $vendor_id, $name, $price, $image_url);
        $stmt->execute();
        $stmt->close();
    }
}

// Handle Edit Product
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_product'])) {
    $product_id = intval($_POST['product_id']);
    $name = $_POST['name'] ?? '';
    $price = floatval($_POST['price']);
    $image_url = null;

    if ($product_id > 0 && !empty($name) && $price > 0) {
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../images/';
            $imageName = basename($_FILES['image']['name']);
            $imageFileType = strtolower(pathinfo($imageName, PATHINFO_EXTENSION));
            $allowedTypes = ['jpg', 'jpeg', 'png'];

            if (in_array($imageFileType, $allowedTypes)) {
                $newFileName = time() . "_" . $imageName;
                $targetFile = $uploadDir . $newFileName;

                if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
                    $image_url = $newFileName;
                }
            }
        }

        if ($image_url) {
            $stmt = $conn->prepare("UPDATE products SET name = ?, price = ?, image_url = ?, is_approved = 0 WHERE id = ? AND vendor_id = ?");
            $stmt->bind_param("sdsii", $name, $price, $image_url, $product_id, $vendor_id);
        } else {
            $stmt = $conn->prepare("UPDATE products SET name = ?, price = ?, is_approved = 0 WHERE id = ? AND vendor_id = ?");
            $stmt->bind_param("sdii", $name, $price, $product_id, $vendor_id);
        }

        $stmt->execute();
        $stmt->close();
    }
}

// Handle Delete Product
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_product'])) {
    $product_id = intval($_POST['product_id']);

    if ($product_id > 0) {
        $stmt = $conn->prepare("DELETE FROM products WHERE id = ? AND vendor_id = ?");
        $stmt->bind_param("ii", $product_id, $vendor_id);
        $stmt->execute();
        $stmt->close();
    }
}

// Fetch products
$stmt = $conn->prepare("SELECT * FROM products WHERE vendor_id = ?");
$stmt->bind_param("i", $vendor_id);
$stmt->execute();
$result = $stmt->get_result();
$products = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Manage Products</title>
    <style>
        body {
            background: #f8f8f8;
            font-family: 'Segoe UI', sans-serif;
            margin: 0;
            padding: 30px;
        }

        .container {
            max-width: 1000px;
            margin: auto;
            background: #fff;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
        }

        h2, h3 {
            color: #d35400;
            margin-bottom: 15px;
        }

        .back-btn {
            display: inline-block;
            margin-bottom: 20px;
            padding: 8px 16px;
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

        form {
            margin-bottom: 20px;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        input[type="text"],
        input[type="number"],
        input[type="file"] {
            flex: 1 1 180px;
            padding: 10px;
            border: 2px solid #f39c12;
            border-radius: 10px;
            font-size: 14px;
        }

        input[type="submit"] {
            padding: 10px 20px;
            background: #e67e22;
            color: #fff;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-size: 14px;
            transition: background 0.3s ease;
        }

        input[type="submit"]:hover {
            background: #d35400;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
            border-radius: 10px;
            overflow: hidden;
            margin-top: 20px;
        }

        th, td {
            padding: 12px;
            border-bottom: 1px solid #eee;
            text-align: left;
            font-size: 14px;
        }

        th {
            background: #e67e22;
            color: #fff;
        }

        img {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
            border: 2px solid #f39c12;
        }

        .status-approved {
            color: green;
            font-weight: bold;
        }

        .status-pending {
            color: orange;
            font-weight: bold;
        }

        .product-actions form {
            display: inline-block;
            margin-right: 5px;
        }

        .product-actions input[type="text"],
        .product-actions input[type="number"],
        .product-actions input[type="file"] {
            margin: 5px 0;
        }
    </style>
</head>
<body>
<div class="container">
    
    <a href="dashboard.php" class="back-btn">← Back</a>

    <h3>Add New Product</h3>
    <form method="post" enctype="multipart/form-data">
        <input type="text" name="name" placeholder="Product Name" required>
        <input type="number" name="price" placeholder="Price" step="0.01" min="0" required>
        <input type="file" name="image" accept="image/*">
        <input type="submit" name="add_product" value="Add Product">
    </form>

    <h3>Your Products</h3>
    <table>
        <tr>
            <th>Name</th>
            <th>Price</th>
            <th>Image</th>
            <th>Status</th>
            <th>Actions</th>
        </tr>
        <?php foreach ($products as $product): ?>
        <tr>
            <td><?= htmlspecialchars($product['name']) ?></td>
            <td>₱<?= number_format($product['price'], 2) ?></td>
            <td>
                <?php if (!empty($product['image_url'])): ?>
                    <img src="../images/<?= htmlspecialchars($product['image_url']) ?>" alt="Image">
                <?php else: ?>
                    N/A
                <?php endif; ?>
            </td>
            <td>
                <?= $product['is_approved'] ? '<span class="status-approved">Approved</span>' : '<span class="status-pending">Pending</span>' ?>
            </td>
            <td class="product-actions">
                <form method="post" enctype="multipart/form-data">
                    <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                    <input type="text" name="name" value="<?= htmlspecialchars($product['name']) ?>" required>
                    <input type="number" name="price" value="<?= $product['price'] ?>" step="0.01" required>
                    <input type="file" name="image" accept="image/*">
                    <input type="submit" name="edit_product" value="Update">
                </form>
                <form method="post" onsubmit="return confirm('Are you sure?')">
                    <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                    <input type="submit" name="delete_product" value="Delete">
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
</div>
</body>
</html>
