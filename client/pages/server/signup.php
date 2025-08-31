<?php
session_start();
require "./server/db.php";

$error = "";
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST["username"]);
    $email = trim($_POST["email"]);
    $phone = trim($_POST["phone"]);
    $gender = $_POST["gender"];
    $password = $_POST["password"];
    $confirm = $_POST["confirm_password"];

    if ($username && $email && $phone && $gender && $password && $confirm) {
        if ($password === $confirm) {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->rowCount() > 0) {
                $error = "Email already registered!";
            } else {
                $hash = password_hash($password, PASSWORD_BCRYPT);
                $stmt = $pdo->prepare("INSERT INTO users (username,email,password_hash,is_admin,created_at) VALUES (?,?,?,?,NOW())");
                $stmt->execute([$username, $email, $hash, 0]);
                header("Location: login.php?msg=Signup successful! Please login.");
                exit();
            }
        } else {
            $error = "Passwords do not match!";
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
  <title>Signup</title>
  <style>
    body { margin:0; height:100vh; display:flex; align-items:center; justify-content:center;
      background: linear-gradient(135deg, var(--primary-blue), var(--dark-blue));
      font-family: "Segoe UI", sans-serif; }
    :root {
      --primary-blue: rgb(0, 64, 145);
      --primary-orange: rgb(255, 140, 0);
      --accent-blue: rgb(20, 84, 165);
      --light-blue: rgb(240, 248, 255);
      --dark-blue: rgb(0, 44, 105);
    }
    .card { background: rgba(255,255,255,0.1); backdrop-filter: blur(12px);
      padding:2rem; border-radius:20px; width:400px; color:white; text-align:center; }
    .input-group { margin-bottom:1rem; text-align:left; }
    .input-group label { font-size:0.9rem; }
    .input-group input, select {
      width:100%; padding:0.7rem; border:none; border-radius:10px;
      margin-top:0.3rem; background: rgba(255,255,255,0.2); color:white;
    }
    .btn { width:100%; padding:0.8rem; border:none; border-radius:10px;
      background: var(--primary-orange); color:white; font-weight:bold; cursor:pointer; }
    .btn:hover { background: orange; }
    .error { color: #ff6b6b; margin-bottom:1rem; }
    a { color: var(--light-blue); }
  </style>
</head>
<body>
  <div class="card">
    <h2>📝 Signup</h2>
    <?php if($error) echo "<p class='error'>$error</p>"; ?>
    <form method="post">
      <div class="input-group">
        <label>Username</label>
        <input type="text" name="username" required>
      </div>
      <div class="input-group">
        <label>Email</label>
        <input type="email" name="email" required>
      </div>
      <div class="input-group">
        <label>Phone</label>
        <input type="text" name="phone" required>
      </div>
      <div class="input-group">
        <label>Gender</label>
        <select name="gender" required>
          <option value="">--Select--</option>
          <option>Male</option>
          <option>Female</option>
          <option>Other</option>
        </select>
      </div>
      <div class="input-group">
        <label>Password</label>
        <input type="password" name="password" required>
      </div>
      <div class="input-group">
        <label>Confirm Password</label>
        <input type="password" name="confirm_password" required>
      </div>
      <button type="submit" class="btn">Signup</button>
    </form>
    <p style="margin-top:1rem;">Already have an account? <a href="login.php">Login</a></p>
  </div>
</body>
</html>
