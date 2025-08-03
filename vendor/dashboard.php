<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'vendor') {
    header('Location: ../login.php');
    exit;
}

include '../config/db.php';  // your mysqli connection $conn

$vendor_id = $_SESSION['user_id'];
$fullname = $_SESSION['fullname'];

// Check if vendor is active
$status = '';
$stmt = $conn->prepare("SELECT status FROM users WHERE id = ? AND role = 'vendor' LIMIT 1");
$stmt->bind_param("i", $vendor_id);
$stmt->execute();
$stmt->bind_result($status);
$stmt->fetch();
$stmt->close();

if ($status !== 'active') {
    // Force logout and redirect to login page
    session_destroy();
    header('Location: ../login.php?error=inactive');
    exit;
}

// Total products count for vendor
$productCount = 0;
$stmt = $conn->prepare("SELECT COUNT(*) FROM products WHERE vendor_id = ?");
$stmt->bind_param("i", $vendor_id);
$stmt->execute();
$stmt->bind_result($productCount);
$stmt->fetch();
$stmt->close();

// Total reviews count for vendor's products
$reviewCount = 0;
$stmt = $conn->prepare("
    SELECT COUNT(*) 
    FROM reviews r
    JOIN orders o ON r.order_id = o.id
    WHERE o.vendor_id = ?
");
$stmt->bind_param("i", $vendor_id);
$stmt->execute();
$stmt->bind_result($reviewCount);
$stmt->fetch();
$stmt->close();

// Sales data
$salesData = [];
$stmt = $conn->prepare("
    SELECT p.name, COALESCE(SUM(o.quantity), 0) AS total_quantity
    FROM products p
    LEFT JOIN orders o ON p.id = o.product_id AND o.vendor_id = ?
    WHERE p.vendor_id = ?
    GROUP BY p.id, p.name
");
$stmt->bind_param("ii", $vendor_id, $vendor_id);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $salesData[] = [
        'name' => $row['name'],
        'quantity' => (int)$row['total_quantity']
    ];
}
$stmt->close();
?>


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Vendor Dashboard - TUPT OrderGo</title>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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

    h2 {
      text-align: center;
      margin-bottom: 30px;
      color: #e67e22;
    }

    .dashboard-grid {
      display: flex;
      flex-wrap: wrap;
      gap: 20px;
      justify-content: center;
    }

    .card {
      background: #fff8f0;
      border-radius: 15px;
      padding: 20px;
      box-shadow: 0 3px 8px rgba(0, 0, 0, 0.1);
      flex: 1 1 300px;
      max-width: 350px;
      min-width: 280px;
      text-align: center;
      border: 2px solid #f5cba7;
    }

    .card h3 {
      color: #d35400;
      margin-bottom: 10px;
    }

    .card p {
      font-size: 36px;
      color: #e67e22;
      margin: 0;
    }

    .chart-container {
      width: 100%;
      max-width: 300px;
      margin: 0 auto;
    }

    .emoji {
      font-size: 100px;
      margin-top: 15px;
    }

    @media (max-width: 1000px) {
      .dashboard-grid {
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
</div>

<div class="sidebar" id="sidebar">
  <a href="editstall.php">Edit Stall</a>
  <a href="manageproduct.php">Manage Products</a>
  <a href="managereservations.php">Manage Reservations</a>
  <a href="manageorder.php">Manage Orders</a>
  <a href="managereviews.php">Manage Reviews</a>
  <a href="announcement.php">Announcement</a>
  <a href="../logout.php">Logout</a>
</div>

<div class="content">
  <h2>Vendor Dashboard</h2>

  <div class="dashboard-grid">
    <div class="card">
      <h3>Total Products</h3>
      <p><?php echo $productCount; ?></p>
      <div class="emoji">🍽️</div>
    </div>

    <div class="card">
      <h3>Total Reviews</h3>
      <p><?php echo $reviewCount; ?></p>
      <div class="emoji">⭐</div>
    </div>

    <div class="card">
      <h3>Sales Chart</h3>
      <div class="chart-container">
        <canvas id="salesChart"></canvas>
      </div>
    </div>
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

  const ctx = document.getElementById('salesChart').getContext('2d');
  new Chart(ctx, {
    type: 'pie',
    data: {
      labels: <?php echo json_encode(array_column($salesData, 'name')); ?>,
      datasets: [{
        data: <?php echo json_encode(array_column($salesData, 'quantity')); ?>,
        backgroundColor: ['#e67e22', '#d35400', '#f39c12', '#f1c40f', '#e74c3c', '#8e44ad', '#3498db'],
        borderColor: '#fff',
        borderWidth: 2
      }]
    },
    options: {
      responsive: true,
      plugins: {
        legend: {
          position: 'bottom',
          labels: {
            color: '#333'
          }
        }
      }
    }
  });
</script>

</body>
</html>
