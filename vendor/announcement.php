<?php
session_start();
include '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'vendor') {
    header('Location: ../login.php');
    exit;
}

$fullname = $_SESSION['fullname'] ?? 'Vendor';
$vendor_id = $_SESSION['user_id'];

// Insert new announcement and generate customer notifications
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['title']) && !empty($_POST['content'])) {
    $title = trim($_POST['title']);
    $content = trim($_POST['content']); // for message column in announcements

    // Insert into announcements table
    $stmt = $conn->prepare("INSERT INTO announcements (vendor_id, title, message, date_posted) VALUES (?, ?, ?, NOW())");
    $stmt->bind_param("iss", $vendor_id, $title, $content);
    $stmt->execute();
    $stmt->close();

    // Fetch all customers
    $customer_result = $conn->query("SELECT id FROM users WHERE role = 'customer'");
    if ($customer_result && $customer_result->num_rows > 0) {
        $notif_stmt = $conn->prepare("INSERT INTO notifications (customer_id, vendor_id, message, created_at) VALUES (?, ?, ?, NOW())");
        while ($row = $customer_result->fetch_assoc()) {
            $customer_id = $row['id'];
            $notif_msg = "$title - $content";
            $notif_stmt->bind_param("iis", $customer_id, $vendor_id, $notif_msg);
            $notif_stmt->execute();
        }
        $notif_stmt->close();
    }
}

// Fetch announcements for this vendor
$stmt = $conn->prepare("SELECT * FROM announcements WHERE vendor_id = ? ORDER BY id DESC");
$stmt->bind_param("i", $vendor_id);
$stmt->execute();
$result = $stmt->get_result();
$announcements = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Manage Announcements - TUPT OrderGo</title>
  <style>
    body {
      font-family: 'Segoe UI', sans-serif;
      background: #f8f8f8;
      margin: 0;
      padding: 20px;
    }

    .container {
      max-width: 1000px;
      margin: 0 auto;
      background: #fff;
      padding: 25px;
      border-radius: 12px;
      box-shadow: 0 0 15px rgba(0,0,0,0.1);
    }

    .top-bar {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 20px;
    }

    .back-button button {
      background: #e67e22;
      color: #fff;
      padding: 10px 18px;
      border: none;
      border-radius: 8px;
      font-size: 14px;
      font-weight: bold;
      cursor: pointer;
      transition: background 0.3s ease;
    }

    .back-button button:hover {
      background: #d35400;
    }

    h1 {
      color: #d35400;
      margin: 0 auto;
      text-align: center;
      flex: 1;
    }

    .add-form {
      background: #fafafa;
      padding: 20px 25px;
      border-radius: 12px;
      margin-bottom: 30px;
    }

    .add-form h2 {
      margin-top: 0;
      margin-bottom: 15px;
      color: #e67e22;
      font-size: 20px;
      text-align: center;
    }

    .add-form input,
    .add-form textarea {
      width: 100%;
      padding: 10px 12px;
      margin-bottom: 12px;
      border: 1px solid #ccc;
      border-radius: 8px;
      font-size: 14px;
    }

    .add-form button {
      background: #27ae60;
      color: #fff;
      padding: 10px 18px;
      border: none;
      border-radius: 8px;
      font-size: 14px;
      font-weight: bold;
      cursor: pointer;
      transition: background 0.3s ease;
      width: 100%;
    }

    .add-form button:hover {
      background: #1e8449;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 20px;
      border-radius: 12px;
      overflow: hidden;
    }

    th, td {
      padding: 14px;
      text-align: center;
      border-bottom: 1px solid #eee;
      font-size: 14px;
    }

    th {
      background: #e67e22;
      color: #fff;
    }

    tr:nth-child(even) {
      background: #fafafa;
    }

    tr:hover {
      background: #f1f1f1;
    }

    @media (max-width: 768px) {
      th, td {
        padding: 10px;
      }
    }
  </style>
</head>
<body>

  <div class="container">
    <div class="top-bar">
      <div class="back-button">
        <form action="dashboard.php" method="get">
          <button type="submit">← Back</button>
        </form>
      </div>
      <h1>Manage Announcements</h1>
    </div>

    <div class="add-form">
      <h2>Create New Announcement</h2>
      <form method="post">
        <input type="text" name="title" placeholder="Announcement Title" required>
        <textarea name="content" rows="4" placeholder="Write your announcement here..." required></textarea>
        <button type="submit">Post Announcement</button>
      </form>
    </div>

    <table>
      <tr>
        <th>ID</th>
        <th>Title</th>
        <th>Message</th>
        <th>Date</th>
      </tr>
      <?php foreach ($announcements as $announcement): ?>
        <tr>
          <td><?= $announcement['id'] ?></td>
          <td><?= htmlspecialchars($announcement['title']) ?></td>
          <td><?= htmlspecialchars($announcement['message']) ?></td>
          <td><?= $announcement['date_posted'] ?></td>
        </tr>
      <?php endforeach; ?>
    </table>
  </div>
</body>
</html>
