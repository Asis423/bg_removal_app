<?php
session_start();
include_once "./server/db.php";

// Redirect to login if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$is_admin = $_SESSION['is_admin'];

// Get user information
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Get upload statistics
$upload_stmt = $pdo->prepare("SELECT COUNT(*) as upload_count FROM uploads WHERE user_id = ?");
$upload_stmt->execute([$user_id]);
$upload_count = $upload_stmt->fetch(PDO::FETCH_ASSOC)['upload_count'];

$processed_stmt = $pdo->prepare("
    SELECT COUNT(*) as processed_count 
    FROM processed_images pi 
    JOIN uploads u ON pi.upload_id = u.id 
    WHERE u.user_id = ?
");
$processed_stmt->execute([$user_id]);
$processed_count = $processed_stmt->fetch(PDO::FETCH_ASSOC)['processed_count'];

$download_stmt = $pdo->prepare("
    SELECT COUNT(*) as download_count 
    FROM downloads d 
    JOIN processed_images pi ON d.processed_image_id = pi.id 
    JOIN uploads u ON pi.upload_id = u.id 
    WHERE u.user_id = ?
");
$download_stmt->execute([$user_id]);
$download_count = $download_stmt->fetch(PDO::FETCH_ASSOC)['download_count'];

// Handle form submissions
$email_error = $password_error = $success_msg = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Update email
    if (isset($_POST['update_email'])) {
        $new_email = trim($_POST["email"]);
        
        if (!empty($new_email) && filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
            // Check if email already exists
            $check_stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $check_stmt->execute([$new_email, $user_id]);
            
            if ($check_stmt->rowCount() === 0) {
                $update_stmt = $pdo->prepare("UPDATE users SET email = ? WHERE id = ?");
                if ($update_stmt->execute([$new_email, $user_id])) {
                    $success_msg = "Email updated successfully!";
                    $user['email'] = $new_email; // Update local user data
                } else {
                    $email_error = "Error updating email. Please try again.";
                }
            } else {
                $email_error = "Email already exists. Please use a different email.";
            }
        } else {
            $email_error = "Please enter a valid email address.";
        }
    }
    
    // Update password
    if (isset($_POST['update_password'])) {
        $current_password = $_POST["current_password"];
        $new_password = $_POST["new_password"];
        $confirm_password = $_POST["confirm_password"];
        
        if (!empty($current_password) && !empty($new_password) && !empty($confirm_password)) {
            // Verify current password
            if (password_verify($current_password, $user["password_hash"])) {
                if ($new_password === $confirm_password) {
                    if (strlen($new_password) >= 8) {
                        $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
                        $update_stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
                        if ($update_stmt->execute([$new_password_hash, $user_id])) {
                            $success_msg = "Password updated successfully!";
                        } else {
                            $password_error = "Error updating password. Please try again.";
                        }
                    } else {
                        $password_error = "New password must be at least 8 characters long.";
                    }
                } else {
                    $password_error = "New passwords do not match.";
                }
            } else {
                $password_error = "Current password is incorrect.";
            }
        } else {
            $password_error = "Please fill all password fields.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Settings | Bg Removal App</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet" />
    <style>
        :root {
            --primary-blue: rgb(0, 64, 145);
            --primary-orange: rgb(255, 140, 0);
            --accent-blue: rgb(20, 84, 165);
            --light-blue: rgb(240, 248, 255);
            --dark-blue: rgb(0, 44, 105);
            --gray-light: #f5f5f5;
            --gray: #ddd;
            --gray-dark: #666;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: "Segoe UI", sans-serif;
        }
        
        body {
            background-color: var(--gray-light);
            color: #333;
            line-height: 1.6;
        }
        
        .navbar {
            background: linear-gradient(90deg, var(--primary-blue), var(--accent-blue));
            padding: 1rem 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .nav-container {
            width: 90%;
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .brand {
            display: flex;
            align-items: center;
            color: white;
            text-decoration: none;
            font-size: 1.5rem;
            font-weight: bold;
        }
        
        .brand-icon {
            margin-right: 0.5rem;
            font-size: 1.8rem;
        }
        
        .nav-menu {
            display: flex;
            list-style: none;
        }
        
        .nav-link {
            color: white;
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            transition: background 0.3s;
        }
        .logout{
            color: white;
            background-color: var(--primary-orange);
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            transition: background 0.3s;
        }
        .nav-link:hover {
            background: rgba(255,255,255,0.1);
        }
        
        .container {
            width: 90%;
            max-width: 1000px;
            margin: 2rem auto;
            display: grid;
            grid-template-columns: 1fr 3fr;
            gap: 2rem;
        }
        
        .sidebar {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            padding: 1.5rem;
        }
        
        .user-info {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .user-avatar {
            width: 80px;
            height: 80px;
            background: var(--primary-blue);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            color: white;
            font-size: 2rem;
        }
        
        .username {
            font-weight: bold;
            font-size: 1.2rem;
        }
        
        .user-email {
            color: var(--gray-dark);
            margin-bottom: 0.5rem;
        }
        
        .user-role {
            display: inline-block;
            background: var(--primary-orange);
            color: white;
            padding: 0.2rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
        }
        
        .stats {
            margin-top: 2rem;
        }
        
        .stat-item {
            display: flex;
            justify-content: space-between;
            padding: 0.8rem 0;
            border-bottom: 1px solid var(--gray);
        }
        
        .stat-item:last-child {
            border-bottom: none;
        }
        
        .stat-value {
            font-weight: bold;
            color: var(--primary-blue);
        }
        
        .main-content {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            padding: 2rem;
        }
        
        .section-title {
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid var(--gray);
            color: var(--primary-blue);
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }
        
        .form-input {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid var(--gray);
            border-radius: 5px;
            font-size: 1rem;
        }
        
        .form-input:focus {
            outline: none;
            border-color: var(--primary-blue);
            box-shadow: 0 0 0 2px rgba(0,64,145,0.2);
        }
        
        .btn {
            padding: 0.8rem 1.5rem;
            border: none;
            border-radius: 5px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background: var(--primary-blue);
            color: white;
        }
        
        .btn-primary:hover {
            background: var(--accent-blue);
        }
        
        .error {
            color: #e74c3c;
            margin-top: 0.5rem;
            font-size: 0.9rem;
        }
        
        .success {
            color: #27ae60;
            margin-bottom: 1rem;
            padding: 0.8rem;
            background: rgba(39, 174, 96, 0.1);
            border-radius: 5px;
        }
        
        @media (max-width: 768px) {
            .container {
                grid-template-columns: 1fr;
            }
            
            .nav-menu {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <a href="index.php" class="brand">
                <div class="brand-icon"><i class="fas fa-magic"></i></div>
                BG Remover Pro
            </a>
            <ul class="nav-menu">
                <li><a class="nav-link" href="index.php">Home</a></li>
                <li><a class="nav-link" href="about.php">About</a></li>
                <?php if ($is_admin): ?>
                    <li><a class="nav-link" href="admin_dashboard.php">Dashboard</a></li>
                <?php else: ?>
                    <li><a class="nav-link" href="user_dashboard.php">Dashboard</a></li>
                <?php endif; ?>
                <li><a class="nav-link logout" href="./server/logout.php">Logout</a></li>
            </ul>
        </div>
    </nav>
    
    <div class="container">
        <div class="sidebar">
            <div class="user-info">
                <div class="user-avatar">
                    <i class="fas fa-user"></i>
                </div>
                <div class="username"><?php echo htmlspecialchars($user['username']); ?></div>
                <div class="user-email"><?php echo htmlspecialchars($user['email']); ?></div>
                <span class="user-role"><?php echo $is_admin ? 'Administrator' : 'User'; ?></span>
            </div>
            
            <div class="stats">
                <div class="stat-item">
                    <span>Uploads</span>
                    <span class="stat-value"><?php echo $upload_count; ?></span>
                </div>
                <div class="stat-item">
                    <span>Processed Images</span>
                    <span class="stat-value"><?php echo $processed_count; ?></span>
                </div>
                <div class="stat-item">
                    <span>Downloads</span>
                    <span class="stat-value"><?php echo $download_count; ?></span>
                </div>
            </div>
        </div>
        
        <div class="main-content">
            <h2 class="section-title">Account Settings</h2>
            
            <?php if (!empty($success_msg)): ?>
                <div class="success"><?php echo $success_msg; ?></div>
            <?php endif; ?>
            
            <form method="post">
                <h3>Update Email</h3>
                <div class="form-group">
                    <label class="form-label" for="email">Email Address</label>
                    <input type="email" class="form-input" id="email" name="email" 
                           value="<?php echo htmlspecialchars($user['email']); ?>" required>
                    <?php if (!empty($email_error)): ?>
                        <div class="error"><?php echo $email_error; ?></div>
                    <?php endif; ?>
                </div>
                <button type="submit" class="btn btn-primary" name="update_email">Update Email</button>
            </form>
            
            <hr style="margin: 2rem 0; border: 0; border-top: 1px solid var(--gray);">
            
            <form method="post">
                <h3>Change Password</h3>
                <div class="form-group">
                    <label class="form-label" for="current_password">Current Password</label>
                    <input type="password" class="form-input" id="current_password" name="current_password" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="new_password">New Password</label>
                    <input type="password" class="form-input" id="new_password" name="new_password" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="confirm_password">Confirm New Password</label>
                    <input type="password" class="form-input" id="confirm_password" name="confirm_password" required>
                    <?php if (!empty($password_error)): ?>
                        <div class="error"><?php echo $password_error; ?></div>
                    <?php endif; ?>
                </div>
                
                <button type="submit" class="btn btn-primary" name="update_password">Update Password</button>
            </form>
        </div>
    </div>
</body>
</html>