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
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>BG Remover Pro - User Dashboard</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="../components/css/style.css" />
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
        
        /* Dashboard specific styles */
        .dashboard-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        .welcome-section {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .welcome-section h1 {
            margin-top: 0;
            color: var(--primary-blue);
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            text-align: center;
        }
        .stat-card i {
            font-size: 2rem;
            color: var(--primary-blue);
            margin-bottom: 1rem;
        }
        .stat-card h3 {
            margin: 0;
            font-size: 2.5rem;
            color: var(--primary-orange);
        }
        .stat-card p {
            margin: 0.5rem 0 0;
            color: #666;
        }
        
        /* Upload section styling */
        .upload-section {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            text-align: center;
        }
        .upload-section h2 { 
            margin-bottom: 1rem; 
            color: var(--primary-blue);
        }
        .upload-input {
            margin-top: 1rem;
        }
        .message { 
            margin-top: 1rem; 
            color: green; 
            font-weight: bold; 
        }
        .error { color: red; }
        
        /* Recent uploads */
        .uploads-section {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .uploads-section h2 {
            margin-top: 0;
            color: var(--primary-blue);
        }
        .uploads-table {
            width: 100%;
            border-collapse: collapse;
        }
        .uploads-table th, 
        .uploads-table td {
            padding: 0.75rem 1rem;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        .uploads-table th {
            background-color: #f8f9fa;
            font-weight: 600;
        }
        .no-uploads {
            text-align: center;
            padding: 2rem;
            color: #666;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <a href="user_dashboard.php" class="brand">
                <div class="brand-icon"><i class="fas fa-magic"></i></div>
                BG Remover Pro
            </a>
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

    <div class="dashboard-container">
        <!-- Welcome Section -->
        <section class="welcome-section">
            <h1>Welcome back, <?= htmlspecialchars($username) ?>!</h1>
            <p>Upload your images to remove backgrounds with our AI-powered tool.</p>
        </section>
        
        <!-- Stats Section -->
        <div class="stats-grid">
            <div class="stat-card">
                <i class="fas fa-images"></i>
                <h3>15</h3>
                <p>Total Uploads</p>
            </div>
            <div class="stat-card">
                <i class="fas fa-check-circle"></i>
                <h3>12</h3>
                <p>Processed</p>
            </div>
            <div class="stat-card">
                <i class="fas fa-download"></i>
                <h3>10</h3>
                <p>Downloads</p>
            </div>
        </div>
        
        <!-- Upload Section -->

<section class="upload-section">
  <div class="upload-container">
    <div class="upload-area" id="uploadArea">
      <div class="upload-icon">
        <i class="fas fa-cloud-upload-alt"></i>
      </div>
      <div class="upload-text">Drag & Drop or Click to Browse</div>
      <div class="upload-subtext">Supports JPG, PNG, WEBP - Max 10MB</div>
    </div>

    <input type="file" id="fileInput" class="file-input" accept="image/*">

    <div class="preview-container" id="previewContainer">
      <div class="preview-title">Image Preview</div>
      <img id="imagePreview" class="image-preview preview-target" src="" alt="Preview">
    </div>

    <button id="uploadButton" class="btn-upload" disabled>
      <i class="fas fa-upload"></i> Upload Image
    </button>

    <div id="message" class="message"></div>
  </div>
</section>
        <!-- Recent Uploads Section -->
        <section class="uploads-section">
            <h2>Recent Uploads</h2>
            <table class="uploads-table">
                <thead>
                    <tr>
                        <th>Filename</th>
                        <th>Upload Date</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>portrait.jpg</td>
                        <td>2 hours ago</td>
                        <td><span class="status-complete">Processed</span></td>
                        <td><button class="btn-small">Download</button></td>
                    </tr>
                    <tr>
                        <td>product.png</td>
                        <td>1 day ago</td>
                        <td><span class="status-complete">Processed</span></td>
                        <td><button class="btn-small">Download</button></td>
                    </tr>
                    <tr>
                        <td>group_photo.webp</td>
                        <td>3 days ago</td>
                        <td><span class="status-processing">Processing</span></td>
                        <td><button class="btn-small" disabled>Wait</button></td>
                    </tr>
                </tbody>
            </table>
        </section>
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