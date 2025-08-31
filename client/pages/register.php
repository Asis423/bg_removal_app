<?php
session_start();
require_once './server/db.php';

// If logged in, redirect
if (isset($_SESSION['user_id'])) {
    header("Location: user_dashboard.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm = $_POST['confirm'];

    if (empty($username) || empty($email) || empty($password) || empty($confirm)) {
        $error = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } elseif ($password !== $confirm) {
        $error = "Passwords do not match.";
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
        $stmt->execute(['email' => $email]);
        if ($stmt->fetch()) {
            $error = "Email already registered.";
        } else {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash) VALUES (:username, :email, :password_hash)");
            if ($stmt->execute(['username'=>$username,'email'=>$email,'password_hash'=>$password_hash])) {
                header("Location: login.php?success=Account created, please login.");
                exit;
            } else {
                $error = "Registration failed.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Sign Up</title>
  <style>
    :root {
      --primary-blue: rgb(0, 64, 145);
      --primary-orange: rgb(255, 140, 0);
      --accent-blue: rgb(20, 84, 165);
      --light-blue: rgb(240, 248, 255);
      --dark-blue: rgb(0, 44, 105);
    }
    body {
      background: var(--light-blue);
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
      margin: 0;
      font-family: Arial, sans-serif;
    }
    .form-container {
      background: #fff;
      padding: 2rem;
      border-radius: 12px;
      box-shadow: 0px 4px 12px rgba(0,0,0,0.2);
      width: 350px;
      text-align: center;
    }
    h2 {
      color: var(--primary-blue);
      margin-bottom: 1rem;
    }
    input {
      width: 100%;
      padding: 0.8rem;
      margin: 0.5rem 0;
      border: 1px solid #ccc;
      border-radius: 8px;
    }
    button {
      background: var(--primary-orange);
      color: white;
      border: none;
      padding: 0.8rem;
      border-radius: 8px;
      width: 100%;
      cursor: pointer;
      font-size: 1rem;
      transition: 0.3s;
    }
    button:hover {
      background: rgb(230, 120, 0);
    }
    .link {
      margin-top: 1rem;
    }
    .link a {
      color: var(--primary-blue);
      text-decoration: none;
    }
    .error {
      color: red;
      margin-bottom: 1rem;
    }
  </style>
</head>
<body>
  <div class="form-container">
    <h2>Create Account</h2>
    <?php if (!empty($error)): ?>
      <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <form method="POST" action="">
      <input type="text" name="username" placeholder="Username" required>
      <input type="email" name="email" placeholder="Email" required>
      <input type="password" name="password" placeholder="Password" required>
      <input type="password" name="confirm" placeholder="Confirm Password" required>
      <button type="submit">Sign Up</button>
    </form>
    <div class="link">
      Already have an account? <a href="login.php">Login</a>
    </div>
  </div>
</body>
</html>
