<?php
session_start();
include '../config/db.php';

// Ensure vendor is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'vendor') {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch vendor data
$query = $conn->prepare("SELECT fullname, email, photo FROM users WHERE id = ?");
$query->bind_param("i", $user_id);
$query->execute();
$result = $query->get_result();
$vendor = $result->fetch_assoc();
$query->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = trim($_POST['fullname']);

    // Handle photo upload
    $photo_path = $vendor['photo']; // Keep current photo by default
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
        $filename = 'vendor_' . $user_id . '_' . time() . '.' . $ext;
        $destination = '../images/' . $filename;

        if (move_uploaded_file($_FILES['photo']['tmp_name'], $destination)) {
            $photo_path = $filename; // Save filename only in DB
        } else {
            $error = "Failed to move uploaded file.";
        }
    }

    // Update vendor info
    $stmt = $conn->prepare("UPDATE users SET fullname = ?, photo = ? WHERE id = ?");
    $stmt->bind_param("ssi", $fullname, $photo_path, $user_id);
    if ($stmt->execute()) {
        $_SESSION['fullname'] = $fullname;
        header("Location: editstall.php?success=1");
        exit;
    } else {
        $error = "Failed to update profile.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Edit Stall Profile</title>
    <style>
    body {
        background: #f8f8f8;
        font-family: 'Segoe UI', sans-serif;
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: 100vh;
        margin: 0;
    }

    .container {
        background: #fff;
        padding: 30px 40px;
        border-radius: 20px;
        box-shadow: 0 0 15px rgba(0,0,0,0.1);
        width: 350px;
        position: relative;
        text-align: center;
    }

    .back-btn {
        position: absolute;
        top: 15px;
        left: 15px;
        background: #e67e22;
        color: white;
        border: none;
        padding: 6px 12px;
        border-radius: 8px;
        font-size: 14px;
        cursor: pointer;
        text-decoration: none;
        transition: background 0.3s ease;
    }

    .back-btn:hover {
        background: #d35400;
    }

    .profile-photo {
        display: flex;
        justify-content: center;
        margin-bottom: 20px;
    }

    .profile-photo img {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        object-fit: cover;
        border: 3px solid #f39c12;
    }

    .container h2 {
        margin: 10px 0 20px;
        color: #d35400;
    }

    form label {
        display: block;
        text-align: left;
        margin-bottom: 5px;
        font-weight: 600;
    }

    input[type="text"],
    input[type="email"],
    input[type="file"] {
        width: 100%;
        padding: 12px;
        margin: 10px 0 20px;
        border: 2px solid #f39c12;
        border-radius: 10px;
        font-size: 16px;
    }

    button[type="submit"] {
        width: 100%;
        padding: 12px;
        background: #e67e22;
        color: #fff;
        border: none;
        border-radius: 10px;
        font-size: 16px;
        cursor: pointer;
        transition: background 0.3s ease;
    }

    button[type="submit"]:hover {
        background: #d35400;
    }

    .message {
        text-align: center;
        font-weight: bold;
        margin-bottom: 15px;
        font-size: 14px;
    }

    .message.success {
        color: green;
    }

    .message.error {
        color: red;
    }
</style>
</head>
<body>
<div class="container">
    <a href="dashboard.php" class="back-btn">← Back</a>


        <?php if (!empty($vendor['photo'])): ?>
            <div class="profile-photo">
                <img src="../images/<?= htmlspecialchars($vendor['photo']) ?>" alt="Profile Photo">
            </div>
        <?php endif; ?>

        <h2>Edit Stall Profile</h2>

        <?php if (isset($_GET['success'])): ?>
            <div class="message success">✅ Profile updated successfully!</div>
        <?php elseif (isset($error)): ?>
            <div class="message error">❌ <?= $error ?></div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <label>Stall Name:</label>
            <input type="text" name="fullname" value="<?= htmlspecialchars($vendor['fullname']) ?>" required>

            <label>Email:</label>
            <input type="email" value="<?= htmlspecialchars($vendor['email']) ?>" readonly disabled>

            <label>Upload New Photo:</label>
            <input type="file" name="photo" accept="image/*">

            <button type="submit">💾 Save Changes</button>
        </form>
    </div>
</body>
</html>
