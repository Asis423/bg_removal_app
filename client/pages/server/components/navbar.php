<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Generate initials if user is logged in
$initials = '';
if (isset($_SESSION['username'])) {
    $username = $_SESSION['username'];
    $name_parts = explode(' ', $username);
    $initials = strtoupper(substr($name_parts[0], 0, 1));
    if (isset($name_parts[1])) {
        $initials .= strtoupper(substr($name_parts[1], 0, 1));
    }
}
?>

<nav class="navbar">
    <div class="nav-container">
        <a href="index.php" class="brand">
            <div class="brand-icon"><i class="fas fa-magic"></i></div>
            BG Remover Pro
        </a>
        <ul class="nav-menu">
            <li><a class="nav-link" href="../index.php">Home</a></li>
            <li><a class="nav-link" href="../about.php">About</a></li>

            <?php if (isset($_SESSION['user_id'])): ?>
                <li class="profile-wrapper">
                    <div class="profile-circle"><?= htmlspecialchars($initials) ?></div>
                    <div class="dropdown">
                        <?php if ($is_admin): ?>
                            <a class="nav-link" href="./admin_dashboard.php">Dashboard</a>
                        <?php else: ?>
                            <a class="nav-link" href="./user_dashboard.php">Dashboard</a>
                        <?php endif; ?>
                        <a href="../settings.php">Settings</a>
                        <a href="./auth/logout.php">Logout</a>
                    </div>
                </li>
            <?php else: ?>
                <li><a class="nav-link" href="./auth/login.php">Login</a></li>
                <li><a class="nav-link" href="./auth/register.php">Sign Up</a></li>
            <?php endif; ?>
        </ul>
    </div>
</nav>
