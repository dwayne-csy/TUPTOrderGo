<?php
session_start();
include '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $fullname = $_POST['fullname'];

    if (!empty($_FILES['photo']['name'])) {
        $target_dir = "../images/";
        $photo_name = basename($_FILES["photo"]["name"]);
        $target_file = $target_dir . $photo_name;
        move_uploaded_file($_FILES["photo"]["tmp_name"], $target_file);

        $stmt = $conn->prepare("UPDATE users SET fullname = ?, photo = ? WHERE id = ?");
        $stmt->bind_param("ssi", $fullname, $photo_name, $user_id);
    } else {
        $stmt = $conn->prepare("UPDATE users SET fullname = ? WHERE id = ?");
        $stmt->bind_param("si", $fullname, $user_id);
    }

    $stmt->execute();
    $stmt->close();
}

// Handle password change
$password_error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $old_password = $_POST['old_password'];
    $new_password = $_POST['new_password'];

    $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($current_hashed_password);
    $stmt->fetch();
    $stmt->close();

    if (password_verify($old_password, $current_hashed_password)) {
        $new_hashed = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->bind_param("si", $new_hashed, $user_id);
        $stmt->execute();
        $stmt->close();
    } else {
        $password_error = 'Old password is incorrect.';
    }
}

// Fetch user data
$stmt = $conn->prepare("SELECT fullname, email, photo FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($fullname, $email, $photo);
$stmt->fetch();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Update Profile</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
<style>
    body {
        font-family: 'Poppins', sans-serif;
        background: #f9f9f9;
        padding: 40px 20px;
    }
    .container {
        max-width: 500px;
        margin: auto;
        background: #fff;
        padding: 30px;
        border-radius: 16px;
        box-shadow: 0 8px 20px rgba(0,0,0,0.08);
        position: relative;
    }
    .back-btn {
        position: absolute;
        top: 16px;
        left: 16px;
        text-decoration: none;
        color: #fff;
        background: #e67e22;
        font-size: 13px;
        padding: 6px 14px;
        border-radius: 8px;
        transition: background 0.3s;
    }
    .back-btn:hover {
        background: #d35400;
    }
    h2 {
        text-align: center;
        margin-bottom: 25px;
        color: #e67e22;
    }
    input[type="text"],
    input[type="email"],
    input[type="file"],
    input[type="password"] {
        width: 100%;
        padding: 10px;
        margin: 8px 0 16px;
        border: 1px solid #ddd;
        border-radius: 8px;
        font-size: 14px;
    }
    input[readonly] {
        background-color: #f0f0f0;
        color: #666;
    }
    button {
        width: 100%;
        background: #e67e22;
        color: white;
        padding: 10px;
        border: none;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        transition: background 0.3s;
    }
    button:hover {
        background: #d35400;
    }
    .section {
        margin-bottom: 30px;
    }
    .profile-photo {
        display: block;
        margin: 0 auto 15px;
        width: 100px;
        height: 100px;
        object-fit: cover;
        border-radius: 50%;
        border: 3px solid #e67e22;
    }
    .error {
        color: red;
        margin-bottom: 15px;
        text-align: center;
        font-size: 13px;
    }
    label {
        font-size: 13px;
        font-weight: 500;
        color: #333;
    }
</style>
</head>
<body>

<div class="container">
    <a href="dashboard.php" class="back-btn">← Back</a>
    <h2>Update Profile</h2>

    <form method="POST" enctype="multipart/form-data" class="section">
        <?php if ($photo): ?>
            <div style="text-align:center;">
                <img src="../images/<?= htmlspecialchars($photo) ?>" alt="Profile Photo" class="profile-photo">
            </div>
        <?php endif; ?>

        <label>Full Name:</label>
        <input type="text" name="fullname" value="<?= htmlspecialchars($fullname) ?>" required>

        <label>Email:</label>
        <input type="email" value="<?= htmlspecialchars($email) ?>" readonly>

        <label>Change Photo:</label>
        <input type="file" name="photo">

        <button type="submit" name="update_profile">Update Profile</button>
    </form>

    <form method="POST" class="section">
        <?php if ($password_error): ?>
            <div class="error"><?= htmlspecialchars($password_error) ?></div>
        <?php endif; ?>
        <label>Old Password:</label>
        <input type="password" name="old_password" required>

        <label>New Password:</label>
        <input type="password" name="new_password" required>

        <button type="submit" name="change_password">Change Password</button>
    </form>
</div>

</body>
</html>
