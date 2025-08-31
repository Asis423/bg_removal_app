<?php
session_start();
require "./server/db.php";

// Redirect if not logged in
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

// Get user initials for profile circle
$username = $_SESSION["username"];
$nameParts = explode(" ", $username);
$initials = strtoupper(substr($nameParts[0], 0, 1) . (isset($nameParts[1]) ? substr($nameParts[1], 0, 1) : ""));

// Handle Image Upload
$uploadMessage = "";
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_FILES["image"])) {
    $userId = $_SESSION["user_id"];
    $file = $_FILES["image"];
    
    if ($file["error"] === 0) {
        $allowed = ["jpg","jpeg","png","webp"];
        $ext = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
        
        if (in_array($ext, $allowed)) {
            $uploadDir = __DIR__ . "/uploads/";
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            
            $newFileName = uniqid("img_", true) . "." . $ext;
            $savedPath = "uploads/" . $newFileName;
            
            if (move_uploaded_file($file["tmp_name"], $uploadDir . $newFileName)) {
                // Save record into DB
                $stmt = $pdo->prepare("INSERT INTO uploads (user_id, original_filename, saved_path, uploaded_at) VALUES (?, ?, ?, NOW())");
                $stmt->execute([$userId, $file["name"], $savedPath]);
                $uploadMessage = "✅ Upload successful!";
            } else {
                $uploadMessage = "❌ Error moving file.";
            }
        } else {
            $uploadMessage = "❌ Invalid file type. Only JPG, PNG, WEBP allowed.";
        }
    } else {
        $uploadMessage = "❌ Upload error.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>User Dashboard</title>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet"/>
  <link rel="stylesheet" href="../components/css/style.css"/>
  <style>
    body { font-family: "Segoe UI", sans-serif; background: #f5f7fa; margin: 0; }
    .navbar { background: var(--primary-blue); padding: 1rem; color: white; }
    .nav-container { display: flex; justify-content: space-between; align-items: center; max-width: 1200px; margin: auto; }
    .brand { font-size: 1.5rem; font-weight: bold; color: white; text-decoration: none; display: flex; align-items: center; gap: 0.5rem; }
    .profile-circle {
        background: var(--primary-orange); 
        color: white; 
        border-radius: 50%; 
        width: 40px; 
        height: 40px; 
        display: flex; 
        align-items: center; 
        justify-content: center; 
        font-weight: bold; 
        cursor: pointer;
        position: relative;
    }
    .dropdown {
        display: none;
        position: absolute;
        right: 0;
        top: 50px;
        background: white;
        color: black;
        border-radius: 8px;
        box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        min-width: 150px;
        z-index: 999;
    }
    .dropdown a {
        display: block;
        padding: 10px;
        color: black;
        text-decoration: none;
    }
    .dropdown a:hover {
        background: #eee;
    }
    .profile-wrapper { position: relative; }
    .upload-section {
        max-width: 600px;
        margin: 2rem auto;
        padding: 2rem;
        background: white;
        border-radius: 12px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        text-align: center;
    }
    .upload-section h2 { margin-bottom: 1rem; }
    .upload-input {
        margin-top: 1rem;
    }
    .message { margin-top: 1rem; color: green; font-weight: bold; }
    .error { color: red; }
  </style>
</head>
<body>
  <nav class="navbar">
    <div class="nav-container">
      <a href="#" class="brand"><i class="fas fa-magic"></i> BG Remover Pro</a>
      <div class="profile-wrapper">
        <div class="profile-circle" onclick="toggleDropdown()"><?= htmlspecialchars($initials) ?></div>
        <div class="dropdown" id="profileDropdown">
          <a href="user_dashboard.php">Dashboard</a>
          <a href="#">Settings</a>
          <a href="./server/logout.php">Logout</a>
        </div>
      </div>
    </div>
  </nav>

  <div class="upload-section">
    <h2>Upload Your Image</h2>
    <form method="post" enctype="multipart/form-data">
      <input class="upload-input" type="file" name="image" accept="image/*" required>
      <br><br>
      <button type="submit" class="btn-primary"><i class="fas fa-upload"></i> Upload</button>
    </form>
    <?php if ($uploadMessage): ?>
      <p class="message"><?= htmlspecialchars($uploadMessage) ?></p>
    <?php endif; ?>
  </div>

  <script>
    function toggleDropdown() {
        document.getElementById("profileDropdown").style.display =
            document.getElementById("profileDropdown").style.display === "block" ? "none" : "block";
    }
    window.onclick = function(event) {
        if (!event.target.closest('.profile-wrapper')) {
            document.getElementById("profileDropdown").style.display = "none";
        }
    }
  </script>
</body>
</html>
