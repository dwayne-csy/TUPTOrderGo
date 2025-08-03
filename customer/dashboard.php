<?php
session_start();
include '../config/db.php';

// Check if user is logged in and is a customer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header("Location: ../login.php");
    exit;
}

$fullname = $_SESSION['fullname'];
$customer_id = $_SESSION['user_id'];

// Fetch cart count from cart_items table
$cart_count = 0;
$cart_stmt = $conn->prepare("SELECT SUM(quantity) as total FROM cart_items WHERE user_id = ?");
$cart_stmt->bind_param("i", $customer_id);
$cart_stmt->execute();
$cart_result = $cart_stmt->get_result();
if ($row = $cart_result->fetch_assoc()) {
    $cart_count = $row['total'] ?? 0;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Customer Dashboard - TUPTOrderGo</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <style>
        * {
            box-sizing: border-box;
            font-family: 'Segoe UI', sans-serif;
        }

        body {
            margin: 0;
            background: #fef7ef;
        }

        .navbar {
            background-color: #d35400;
            padding: 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: white;
            position: relative;
            z-index: 2;
        }

        .hamburger {
            font-size: 26px;
            cursor: pointer;
        }

        .cart-icon {
            color: white;
            text-decoration: none;
            position: relative;
            font-size: 18px;
        }

        .cart-count {
            position: absolute;
            top: -8px;
            right: -12px;
            background: red;
            color: white;
            font-size: 12px;
            border-radius: 50%;
            padding: 2px 6px;
        }

        .sidebar {
            position: fixed;
            top: 0;
            left: -250px;
            width: 250px;
            height: 100%;
            background-color: #fff;
            box-shadow: 2px 0 5px rgba(0,0,0,0.3);
            padding-top: 60px;
            transition: left 0.3s ease;
            z-index: 1;
        }

        .sidebar a {
            display: block;
            padding: 15px;
            color: #333;
            text-decoration: none;
            border-bottom: 1px solid #eee;
        }

        .sidebar a:hover {
            background-color: #f2f2f2;
        }

        .content {
            padding: 2rem;
        }

        .search-form {
            text-align: center;
            margin-bottom: 20px;
        }

        .search-form input {
            padding: 8px;
            width: 250px;
            border-radius: 6px;
            border: 1px solid #ccc;
        }

        .search-form button {
            padding: 8px 12px;
            border: none;
            background-color: #d35400;
            color: white;
            border-radius: 6px;
            cursor: pointer;
        }

        .search-form button:hover {
            background-color: #a84300;
        }

        h1 {
            text-align: center;
            margin-bottom: 30px;
            color: #e67e22;
        }

        .stall-container {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            justify-content: center;
        }

        .stall {
            width: 220px;
            padding: 15px;
            background-color: #fff8f0;
            border-radius: 10px;
            text-align: center;
            box-shadow: 2px 2px 8px #ccc;
            border: 2px solid #f5cba7;
        }

        .stall img {
            width: 100%;
            height: 150px;
            object-fit: cover;
            border-radius: 8px;
        }

        .stall h3 {
            margin-top: 10px;
            color: #d35400;
        }

        .stall button {
            padding: 6px 12px;
            margin-top: 10px;
            border: none;
            background-color: #007bff;
            color: white;
            border-radius: 6px;
            cursor: pointer;
        }

        .stall button:hover {
            background-color: #0056b3;
        }

        @media (max-width: 1000px) {
            .stall-container {
                flex-direction: column;
                align-items: center;
            }
        }
    </style>
</head>
<body>

<div class="navbar">
    <div class="hamburger" onclick="toggleSidebar()">&#9776;</div>
    <div>Welcome, <?php echo htmlspecialchars($fullname); ?>!</div>
    <div>
        <a href="cart.php" class="cart-icon">
            🛒 Cart
            <?php if ($cart_count > 0): ?>
                <span class="cart-count"><?php echo $cart_count; ?></span>
            <?php endif; ?>
        </a>
    </div>
</div>

<div class="sidebar" id="sidebar">
    <a href="updateprofile.php">Update Profile</a>
    <a href="orderhistory.php">My Orders</a>
    <a href="reviews/myreviews.php">My Reviews</a>
    <a href="reservations/reservations.php">Reservation</a>
    <a href="notification.php">Notification</a>
    <a href="../logout.php">Logout</a>
</div>

<div class="content">

    <form method="GET" class="search-form">
        <input type="text" name="search" placeholder="Search stall or vendor..." 
               value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
        <button type="submit">Search</button>
    </form>

    <h1>Available Stalls</h1>

    <div class="stall-container">
        <?php
        $search = isset($_GET['search']) ? trim($_GET['search']) : '';
        if ($search !== '') {
            $sql = "SELECT * FROM users WHERE role = 'vendor' AND status = 'active' AND (fullname LIKE ? OR id LIKE ?) ORDER BY id ASC";
            $stmt = $conn->prepare($sql);
            $searchParam = '%' . $search . '%';
            $stmt->bind_param("ss", $searchParam, $searchParam);
            $stmt->execute();
            $result = $stmt->get_result();
        } else {
            $sql = "SELECT * FROM users WHERE role = 'vendor' AND status = 'active' ORDER BY id ASC";
            $result = $conn->query($sql);
        }

        if ($result && $result->num_rows > 0) {
            $stallNumber = 1;
            while ($row = $result->fetch_assoc()) {
                $vendorId = $row['id'];
                $vendorName = htmlspecialchars($row['fullname']);
                $photo = !empty($row['photo']) ? $row['photo'] : 'default-stall.jpg';
                $imagePath = "../images/" . $photo;

                echo "
                <div class='stall'>
                    <img src='{$imagePath}' alt='Stall {$stallNumber}'>
                    <h3>Stall {$stallNumber} - {$vendorName}</h3>
                    <button onclick=\"window.location.href='stall_view.php?vendor_id={$vendorId}'\">View</button>
                </div>";
                $stallNumber++;
            }
        } else {
            echo "<p style='text-align:center;'>No stalls found.</p>";
        }

        $conn->close();
        ?>
    </div>
</div>

<script>
    function toggleSidebar() {
        const sidebar = document.getElementById('sidebar');
        sidebar.style.left = sidebar.style.left === '0px' ? '-250px' : '0px';
    }

    document.addEventListener('click', function (e) {
        const sidebar = document.getElementById('sidebar');
        const hamburger = document.querySelector('.hamburger');
        if (!sidebar.contains(e.target) && !hamburger.contains(e.target)) {
            sidebar.style.left = '-250px';
        }
    });
</script>

</body>
</html>
