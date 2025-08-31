<?php
require_once '../includes/functions.php';
require_once '../config/session.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: ../dashboard.php');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitizeInput($_POST['email']);
    $password = $_POST['password'];
    
    if (empty($email) || empty($password)) {
        $error = 'Please enter both email and password';
    } else {
        $user = getUserByEmail($email);
        if ($user && verifyPassword($password, $user['password_hash'])) {
            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['is_admin'] = $user['is_admin'];
            
            // Redirect based on admin status
            if ($user['is_admin']) {
                header('Location: ../admin/dashboard.php');
            } else {
                header('Location: ../dashboard.php');
            }
            exit();
        } else {
            $error = 'Invalid email or password';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - BG Remover Pro</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../client/components/css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <a href="../index.php" class="brand">
                <div class="brand-icon"><i class="fas fa-magic"></i></div>
                BG Remover Pro
            </a>
            <ul class="nav-menu">
                <li><a class="nav-link" href="../index.php">Home</a></li>
                <li><a class="nav-link active" href="login.php">Login</a></li>
                <li><a class="nav-link" href="register.php">Sign Up</a></li>
            </ul>
        </div>
    </nav>

    <div class="container">
        <div class="auth-container">
            <div class="auth-card">
                <div class="auth-header">
                    <h1>Welcome Back</h1>
                    <p>Login to access your dashboard and continue removing backgrounds</p>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" class="auth-form">
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" required>
                    </div>

                    <button type="submit" class="btn-primary btn-full">
                        <i class="fas fa-sign-in-alt"></i>
                        Login
                    </button>
                </form>

                <div class="auth-footer">
                    <p>Don't have an account? <a href="register.php">Sign up here</a></p>
                </div>

                <div class="demo-accounts">
                    <h3>Demo Accounts</h3>
                    <div class="demo-account">
                        <strong>Admin:</strong> admin@bgremover.com / admin123
                    </div>
                    <div class="demo-account">
                        <strong>User:</strong> user@demo.com / user123
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
