<?php
include '../config/db.php';

// Handle activate/inactivate action
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $action = $_GET['action'] === 'deactivate' ? 'inactive' : 'active';

    $stmt = $conn->prepare("UPDATE users SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $action, $id);
    $stmt->execute();
    header("Location: managecustomer.php");
    exit;
}

// Fetch all customers
$sql = "SELECT id, fullname, email, photo, status FROM users WHERE role = 'customer'";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Manage Customers</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
<style>
body {
    font-family: 'Poppins', sans-serif;
    background: #f9fafb;
    margin: 0;
    padding: 30px;
}
.back-btn {
    display: inline-block;
    background-color: #e67e22;
    color: white;
    padding: 9px 16px;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 500;
    margin-bottom: 20px;
    transition: background 0.3s;
}
.back-btn:hover {
    background-color: #d35400;
}
h1 {
    text-align: center;
    margin-bottom: 30px;
    color: #2d3436;
}
.customer-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
    gap: 24px;
}
.customer-card {
    background: #fff;
    border-radius: 14px;
    box-shadow: 0 6px 18px rgba(0,0,0,0.08);
    padding: 20px;
    text-align: center;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}
.customer-card:hover {
    transform: translateY(-6px);
    box-shadow: 0 10px 25px rgba(0,0,0,0.12);
}
.customer-photo {
    width: 90px;
    height: 90px;
    border-radius: 50%;
    object-fit: cover;
    border: 3px solid #e67e22;
    margin-bottom: 12px;
}
.customer-name {
    font-size: 17px;
    font-weight: 600;
    margin-bottom: 4px;
    color: #2d3436;
}
.customer-email {
    font-size: 13px;
    color: #636e72;
    margin-bottom: 10px;
}
.status {
    margin-bottom: 10px;
    font-weight: 500;
    font-size: 14px;
}
.status.active {
    color: #27ae60;
}
.status.inactive {
    color: #c0392b;
}
.toggle-btn {
    padding: 8px 14px;
    border-radius: 8px;
    background: #e67e22;
    color: white;
    text-decoration: none;
    font-size: 13px;
    font-weight: 500;
    display: inline-block;
    transition: background 0.3s ease;
}
.toggle-btn:hover {
    background: #d35400;
}
</style>
</head>
<body>

<a href="dashboard.php" class="back-btn">← Back</a>

<h1>🛠️ Manage Customers</h1>

<div class="customer-grid">
<?php while ($row = $result->fetch_assoc()): ?>
    <div class="customer-card">
        <img src="../images/<?= htmlspecialchars($row['photo'] ?? 'default.jpg') ?>" alt="Customer Photo" class="customer-photo">
        <div class="customer-name"><?= htmlspecialchars($row['fullname']) ?></div>
        <div class="customer-email"><?= htmlspecialchars($row['email']) ?></div>
        <div class="status <?= $row['status'] === 'active' ? 'active' : 'inactive' ?>">
            <?= ucfirst($row['status']) ?>
        </div>
        <?php if ($row['status'] === 'active'): ?>
            <a href="?action=deactivate&id=<?= $row['id'] ?>" class="toggle-btn">Deactivate</a>
        <?php else: ?>
            <a href="?action=activate&id=<?= $row['id'] ?>" class="toggle-btn">Activate</a>
        <?php endif; ?>
    </div>
<?php endwhile; ?>
</div>

</body>
</html>