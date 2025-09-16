
<?php
session_start();
require "./server/db.php";

header('Content-Type: application/json');

if (!isset($_SESSION["user_id"]) || !$_SESSION["is_admin"]) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit;
}

$current_user_id = $_SESSION["user_id"];
$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action === 'get_users') {
    // Get overall statistics
    $total_users_stmt = $pdo->prepare("SELECT COUNT(*) as count FROM users");
    $total_users_stmt->execute();
    $total_users = $total_users_stmt->fetch(PDO::FETCH_ASSOC)['count'];

    $total_uploads_stmt = $pdo->prepare("SELECT COUNT(*) as count FROM uploads");
    $total_uploads_stmt->execute();
    $total_uploads = $total_uploads_stmt->fetch(PDO::FETCH_ASSOC)['count'];

    $total_processed_stmt = $pdo->prepare("SELECT COUNT(*) as count FROM processed_images");
    $total_processed_stmt->execute();
    $total_processed = $total_processed_stmt->fetch(PDO::FETCH_ASSOC)['count'];

    $total_downloads_stmt = $pdo->prepare("SELECT COUNT(*) as count FROM downloads");
    $total_downloads_stmt->execute();
    $total_downloads = $total_downloads_stmt->fetch(PDO::FETCH_ASSOC)['count'];

    // Get all users
    $users_stmt = $pdo->prepare("
        SELECT id, username, email, created_at, is_admin, is_active 
        FROM users 
        ORDER BY created_at DESC
    ");
    $users_stmt->execute();
    $all_users = $users_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Sanitize output
    $users = array_map(function($user) {
        return [
            'id' => $user['id'],
            'username' => htmlspecialchars($user['username']),
            'email' => htmlspecialchars($user['email']),
            'created_at' => $user['created_at'],
            'is_admin' => $user['is_admin'],
            'is_active' => $user['is_active']
        ];
    }, $all_users);

    echo json_encode([
        'success' => true,
        'users' => $users,
        'total_users' => $total_users,
        'total_uploads' => $total_uploads,
        'total_processed' => $total_processed,
        'total_downloads' => $total_downloads
    ]);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && in_array($action, ['toggle_admin', 'toggle_active', 'delete_user'])) {
    $target_user_id = $_POST["user_id"] ?? 0;

    if ($target_user_id == $current_user_id && $action !== 'toggle_active') {
        echo json_encode(['success' => false, 'message' => 'Cannot perform this action on yourself.']);
        exit;
    }

    try {
        if ($action === "toggle_admin") {
            $toggle_stmt = $pdo->prepare("UPDATE users SET is_admin = NOT is_admin WHERE id = ?");
            $toggle_stmt->execute([$target_user_id]);
            echo json_encode(['success' => true, 'message' => 'Admin status toggled.']);
        } elseif ($action === "toggle_active") {
            $toggle_stmt = $pdo->prepare("UPDATE users SET is_active = NOT is_active WHERE id = ?");
            $toggle_stmt->execute([$target_user_id]);
            echo json_encode(['success' => true, 'message' => 'User status toggled.']);
        } elseif ($action === "delete_user") {
            // Delete related records first (if needed)
            $delete_uploads_stmt = $pdo->prepare("DELETE FROM uploads WHERE user_id = ?");
            $delete_uploads_stmt->execute([$target_user_id]);
            $delete_processed_stmt = $pdo->prepare("DELETE FROM processed_images WHERE upload_id IN (SELECT id FROM uploads WHERE user_id = ?)");
            $delete_processed_stmt->execute([$target_user_id]);
            $delete_downloads_stmt = $pdo->prepare("
                DELETE FROM downloads 
                WHERE processed_image_id IN (
                    SELECT id FROM processed_images WHERE upload_id IN (
                        SELECT id FROM uploads WHERE user_id = ?
                    )
                )
            ");
            $delete_downloads_stmt->execute([$target_user_id]);
            $delete_stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $delete_stmt->execute([$target_user_id]);
            echo json_encode(['success' => true, 'message' => 'User deleted.']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid action.']);
?>
