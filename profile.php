<?php
require_once 'includes/functions.php';
require_once 'config/session.php';

// Require login
requireLogin();

$userId = getCurrentUserId();
$user = getUserById($userId);
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitizeInput($_POST['name']);
    $currentPassword = $_POST['current_password'];
    $newPassword = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];
    
    if (empty($name)) {
        $error = 'Name is required';
    } elseif (!empty($newPassword)) {
        // Password change requested
        if (!verifyPassword($currentPassword, $user['password'])) {
            $error = 'Current password is incorrect';
        } elseif (strlen($newPassword) < 6) {
            $error = 'New password must be at least 6 characters long';
        } elseif ($newPassword !== $confirmPassword) {
            $error = 'New passwords do not match';
        } else {
            // Update name and password
            $hashedPassword = hashPassword($newPassword);
            $stmt = $pdo->prepare("UPDATE users SET name = ?, password = ? WHERE id = ?");
            if ($stmt->execute([$name, $hashedPassword, $userId])) {
                $success = 'Profile updated successfully!';
                $user['name'] = $name;
            } else {
                $error = 'Failed to update profile. Please try again.';
            }
        }
    } else {
        // Only name update
        $stmt = $pdo->prepare("UPDATE users SET name = ? WHERE id = ?");
        if ($stmt->execute([$name, $userId])) {
            $success = 'Name updated successfully!';
            $user['name'] = $name;
        } else {
            $error = 'Failed to update name. Please try again.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - BG Remover Pro</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="client/components/css/style.css">
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
                <li><a class="nav-link" href="dashboard.php">Dashboard</a></li>
                <li><a class="nav-link" href="upload.php">Upload Image</a></li>
                <li class="nav-item dropdown">
                    <a class="nav-link active" href="#" id="userDropdown">
                        <i class="fas fa-user"></i>
                        <?php echo htmlspecialchars($user['name']); ?>
                    </a>
                    <div class="dropdown-menu">
                        <a href="dashboard.php">Dashboard</a>
                        <a href="profile.php">Profile</a>
                        <a href="auth/logout.php">Logout</a>
                    </div>
                </li>
            </ul>
        </div>
    </nav>

    <div class="container">
        <div class="profile-header">
            <h1>My Profile</h1>
            <p>Manage your account information and settings</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?php echo $success; ?>
            </div>
        <?php endif; ?>

        <div class="profile-container">
            <div class="profile-card">
                <div class="profile-header-card">
                    <h2>Account Information</h2>
                    <p>Update your personal information</p>
                </div>

                <form method="POST" class="profile-form">
                    <div class="form-group">
                        <label for="name">Full Name</label>
                        <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" value="<?php echo htmlspecialchars($user['email']); ?>" disabled>
                        <small>Email cannot be changed</small>
                    </div>

                    <div class="form-group">
                        <label for="role">Account Type</label>
                        <input type="text" id="role" value="<?php echo ucfirst($user['role']); ?>" disabled>
                    </div>

                    <div class="form-group">
                        <label for="joined">Member Since</label>
                        <input type="text" id="joined" value="<?php echo formatDate($user['created_at']); ?>" disabled>
                    </div>

                    <div class="profile-section-divider">
                        <h3>Change Password</h3>
                        <p>Leave blank if you don't want to change your password</p>
                    </div>

                    <div class="form-group">
                        <label for="current_password">Current Password</label>
                        <input type="password" id="current_password" name="current_password">
                    </div>

                    <div class="form-group">
                        <label for="new_password">New Password</label>
                        <input type="password" id="new_password" name="new_password">
                        <small>Minimum 6 characters</small>
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">Confirm New Password</label>
                        <input type="password" id="confirm_password" name="confirm_password">
                    </div>

                    <div class="profile-actions">
                        <button type="submit" class="btn-primary">
                            <i class="fas fa-save"></i>
                            Update Profile
                        </button>
                        <a href="dashboard.php" class="btn-secondary">
                            <i class="fas fa-arrow-left"></i>
                            Back to Dashboard
                        </a>
                    </div>
                </form>
            </div>

            <div class="profile-stats">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-images"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number"><?php echo getUserImageCount($userId); ?></div>
                        <div class="stat-label">Total Images</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number"><?php echo count(array_filter(getUserImages($userId), function($img) { return $img['status'] === 'completed'; })); ?></div>
                        <div class="stat-label">Completed</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Toggle dropdown menu
        document.getElementById('userDropdown').addEventListener('click', function(e) {
            e.preventDefault();
            const dropdown = this.nextElementSibling;
            dropdown.classList.toggle('show');
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.dropdown')) {
                document.querySelectorAll('.dropdown-menu').forEach(menu => {
                    menu.classList.remove('show');
                });
            }
        });
    </script>
</body>
</html>
