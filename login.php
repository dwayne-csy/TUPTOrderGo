<?php
session_start();
include 'config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $sql = "SELECT * FROM users WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();

    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user) {
        if ($user['status'] !== 'active') {
            $error = "Your account is inactive.";
        } elseif (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['fullname'] = $user['fullname'];
            $_SESSION['role'] = $user['role'];

            // Redirect based on role
            if ($user['role'] === 'customer') {
                header("Location: customer/dashboard.php");
                exit;
            } elseif ($user['role'] === 'vendor') {
                header("Location: vendor/dashboard.php");
                exit;
            } elseif ($user['role'] === 'admin') {
                header("Location: admin/dashboard.php");
                exit;
            } else {
                $error = "Unknown user role.";
            }
        } else {
            $error = "Invalid email or password.";
        }
    } else {
        $error = "Invalid email or password.";
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
  <title>Login - TUPT OrderGo</title>
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

    .login-box {
      background: #fff;
      padding: 30px 40px;
      border-radius: 20px;
      box-shadow: 0 0 15px rgba(0,0,0,0.1);
      width: 320px;
      text-align: center;
    }

    .login-box h2 {
      margin: 15px 0;
      color: #d35400;
    }

    .login-box input[type="email"],
    .login-box input[type="password"] {
      width: 100%;
      padding: 12px;
      margin: 10px 0 20px;
      border: 2px solid #f39c12;
      border-radius: 10px;
      font-size: 16px;
    }

    .login-box label {
      display: block;
      text-align: left;
      margin-bottom: 5px;
      font-weight: 600;
    }

    .login-box button {
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

    .login-box button:hover {
      background: #d35400;
    }

    .login-box .emojis {
      font-size: 24px;
    }

    .login-box p a {
      color: #d35400;
      text-decoration: none;
    }

    .error {
      color: red;
      margin-bottom: 10px;
    }
  </style>
</head>
<body>
  <div class="login-box">
    <div class="emojis">🍛 🥤 🍽️</div>
    <h2>TUPT OrderGo</h2>

    <?php if (isset($error)) echo "<p class='error'>$error</p>"; ?>

    <form method="POST" action="login.php">
      <label for="email">Username</label>
      <input type="email" name="email" id="email" placeholder="Enter your email" required>

      <label for="password">Password</label>
      <input type="password" name="password" id="password" placeholder="Enter your password" required>

      <button type="submit">Login</button>
    </form>
    <p><a href="register.php">Don't have an account? Register</a></p>
  </div>
</body>
</html>
