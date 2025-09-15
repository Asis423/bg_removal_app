<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: index.php");
    exit;
}
$name = htmlspecialchars($_SESSION['user']['name']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Welcome, <?php echo $name; ?> - BG Remover Pro</title>
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet" />
<link rel="stylesheet" href="assets/css/style.css" />
</head>
<body>
<nav class="navbar">
    <div class="nav-container">
        <a href="users_home.php" class="brand"><i class="fas fa-magic"></i> BG Remover Pro</a>
        <ul class="nav-menu">
            <li>Hello, <?php echo $name; ?></li>
            <li><a href="./auth/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </div>
</nav>
<div class="container">
    <h1>Welcome back, <?php echo $name; ?>!</h1>
    <p>Start removing backgrounds or explore your account.</p>
</div>
</body>
</html>
