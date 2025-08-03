<?php
include '../config/db.php';

// Fetch customer count
$customer_sql = "SELECT COUNT(*) AS total FROM users WHERE role = 'customer'";
$customer_result = $conn->query($customer_sql);
$customer_count = $customer_result->fetch_assoc()['total'];

// Fetch vendor count
$vendor_sql = "SELECT COUNT(*) AS total FROM users WHERE role = 'vendor'";
$vendor_result = $conn->query($vendor_sql);
$vendor_count = $vendor_result->fetch_assoc()['total'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Dashboard</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
<style>
body {
    font-family: 'Poppins', sans-serif;
    background: #f9fafb;
    margin: 0;
    padding: 0;
}
.header {
    background-color: #e67e22;
    color: white;
    padding: 12px 20px;
    display: flex;
    align-items: center;
    justify-content: space-between;
}
.hamburger {
    cursor: pointer;
    display: flex;
    flex-direction: column;
    gap: 4px;
}
.hamburger div {
    width: 24px;
    height: 3px;
    background-color: white;
    border-radius: 2px;
}
.dropdown {
    position: absolute;
    top: 50px;
    left: 20px;
    background: white;
    border: 1px solid #ccc;
    border-radius: 10px;
    display: none;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    z-index: 100;
}
.dropdown a {
    display: block;
    padding: 10px 20px;
    color: #333;
    text-decoration: none;
    font-weight: 500;
}
.dropdown a:hover {
    background: #f0f0f0;
}
h1 {
    text-align: center;
    margin: 24px;
    font-size: 1.8rem;
    color: #e67e22;
}
.dashboard-container {
    display: flex;
    justify-content: center;
    gap: 40px;
    flex-wrap: wrap;
    padding: 20px;
}
.card {
    background: white;
    border-radius: 15px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.08);
    width: 260px;
    padding: 20px;
    text-align: center;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}
.card:hover {
    transform: translateY(-6px);
    box-shadow: 0 8px 20px rgba(0,0,0,0.12);
}
.card h2 {
    font-size: 20px;
    margin-bottom: 10px;
    color: #2d3436;
}
.count {
    font-size: 40px;
    color: #e67e22;
    margin-bottom: 16px;
}
.view-btn {
    display: inline-block;
    padding: 9px 18px;
    border-radius: 8px;
    background: #e67e22;
    color: white;
    text-decoration: none;
    font-size: 14px;
    font-weight: 500;
    transition: background 0.3s ease;
}
.view-btn:hover {
    background: #d35400;
}
</style>
</head>
<body>

<div class="header">
    <div class="hamburger" onclick="toggleDropdown()">
        <div></div>
        <div></div>
        <div></div>
    </div>
    <h2>Admin Panel</h2>
</div>

<div class="dropdown" id="dropdownMenu">
    <a href="../logout.php">🔓 Logout</a>
</div>

<h1>👨‍💼 Admin Dashboard</h1>
<div class="dashboard-container">
    <div class="card">
        <h2>👥 Customers</h2>
        <div class="count"><?= $customer_count ?></div>
        <a href="managecustomer.php" class="view-btn">View</a>
    </div>

    <div class="card">
        <h2>🏪 Vendors</h2>
        <div class="count"><?= $vendor_count ?></div>
        <a href="managevendor.php" class="view-btn">View</a>
    </div>
</div>

<script>
function toggleDropdown() {
    const menu = document.getElementById('dropdownMenu');
    menu.style.display = menu.style.display === 'block' ? 'none' : 'block';
}
window.addEventListener('click', function(e) {
    const menu = document.getElementById('dropdownMenu');
    const hamburger = document.querySelector('.hamburger');
    if (!menu.contains(e.target) && !hamburger.contains(e.target)) {
        menu.style.display = 'none';
    }
});
</script>

</body>
</html>