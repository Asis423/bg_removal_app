<?php
session_start();
require "./server/db.php";

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['is_admin']) {
        header("Location: admin_dashboard.php");
    } else {
        header("Location: user_dashboard.php");
    }
    exit();
}

$error = "";
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST["email"]);
    $password = $_POST["password"];

    if (!empty($email) && !empty($password)) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user["password_hash"])) {
            $_SESSION["user_id"] = $user["id"];
            $_SESSION["username"] = $user["username"];
            $_SESSION["is_admin"] = $user["is_admin"];

            if ($user["is_admin"]) {
                header("Location: admin_dashboard.php");
            } else {
                header("Location: user_dashboard.php");
            }
            exit();
        } else {
            $error = "Invalid email or password.";
        }
    } else {
        $error = "Please fill all fields.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Login</title>
  <style>
    :root {
      --primary-blue: rgb(0, 64, 145);
      --primary-orange: rgb(255, 140, 0);
      --accent-blue: rgb(20, 84, 165);
      --light-blue: rgb(240, 248, 255);
      --dark-blue: rgb(0, 44, 105);
    }
    body {
      margin: 0; height: 100vh;
      display: flex; align-items: center; justify-content: center;
      background: linear-gradient(135deg, var(--primary-blue), var(--dark-blue));
      font-family: "Segoe UI", sans-serif;
    }
    .card {
      background: rgba(255,255,255,0.1);
      backdrop-filter: blur(12px);
      padding: 2rem;
      border-radius: 20px;
      box-shadow: 0 8px 32px rgba(0,0,0,0.3);
      width: 350px;
      color: white;
      text-align: center;
      animation: fadeIn 1s ease;
    }
    .card h2 { margin-bottom: 1rem; }
    .input-group {
      margin-bottom: 1rem;
      text-align: left;
    }
    .input-group label { font-size: 0.9rem; }
    .input-group input {
      width: 100%; padding: 0.7rem;
      border: none; border-radius: 10px;
      margin-top: 0.3rem;
      background: rgba(255,255,255,0.2);
      color: white;
    }
    .btn {
      width: 100%;
      padding: 0.8rem;
      border: none; border-radius: 10px;
      background: var(--primary-orange);
      color: white; font-size: 1rem; font-weight: bold;
      cursor: pointer;
      transition: 0.3s;
    }
    .btn:hover { background: orange; }
    .error { color: #ff6b6b; margin-bottom: 1rem; }
    a { color: var(--light-blue); text-decoration: none; }
    @keyframes fadeIn { from {opacity:0; transform:scale(0.9);} to {opacity:1; transform:scale(1);} }
  </style>
</head>
<body>
  <div class="card">
    <h2>🔐 Login</h2>
    <?php if($error) echo "<p class='error'>$error</p>"; ?>
    <form method="post">
      <div class="input-group">
        <label>Email</label>
        <input type="email" name="email" required>
      </div>
      <div class="input-group">
        <label>Password</label>
        <input type="password" name="password" required>
      </div>
      <button type="submit" class="btn">Login</button>
    </form>
    <p style="margin-top:1rem;">No account? <a href="register.php">Signup</a></p>
  </div>
</body>
</html>
