<?php
include 'config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = $_POST['fullname'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $sql = "INSERT INTO users (fullname, email, password) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $fullname, $email, $password);

    if ($stmt->execute()) {
        // Redirect to login.php after successful registration
        header("Location: login.php?success=1");
        exit;
    } else {
        $error = "❌ Error: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Register - TUPT OrderGo</title>
  <style>
    body {
      background: #f8f8f8;
      font-family: 'Segoe UI', sans-serif;
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
      margin: 0;
    }

    .register-box {
      background: #fff;
      padding: 30px 40px;
      border-radius: 20px;
      box-shadow: 0 0 15px rgba(0,0,0,0.1);
      width: 340px;
      text-align: center;
    }

    .register-box h2 {
      margin: 15px 0;
      color: #d35400;
    }

    .register-box input[type="text"],
    .register-box input[type="email"],
    .register-box input[type="password"] {
      width: 100%;
      padding: 12px;
      margin: 10px 0 20px;
      border: 2px solid #f39c12;
      border-radius: 10px;
      font-size: 16px;
    }

    .register-box label {
      display: block;
      text-align: left;
      margin-bottom: 5px;
      font-weight: 600;
    }

    .register-box button {
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

    .register-box button:hover {
      background: #d35400;
    }

    .register-box .emojis {
      font-size: 24px;
    }

    .register-box p a {
      color: #d35400;
      text-decoration: none;
    }

    .message {
      margin: 15px 0;
    }

    .message.error {
      color: red;
    }
  </style>
</head>
<body>
  <div class="register-box">
    <div class="emojis">🍛 🥤 🍽️</div>
    <h2>TUPT OrderGo</h2>

    <?php if (isset($error)) echo "<p class='message error'>$error</p>"; ?>

    <form method="POST" action="register.php">
      <label for="fullname">Full Name</label>
      <input type="text" name="fullname" id="fullname" placeholder="Enter your full name" required>

      <label for="email">Email</label>
      <input type="email" name="email" id="email" placeholder="Enter your email" required>

      <label for="password">Password</label>
      <input type="password" name="password" id="password" placeholder="Enter your password" required>

      <button type="submit">Register</button>
    </form>
    <p><a href="login.php">Already have an account? Login</a></p>
  </div>
</body>
</html>
