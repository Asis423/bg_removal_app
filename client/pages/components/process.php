<?php
// Database connection (same as check_status.php)
$host = "localhost";
$dbname = "bg_removal_app";
$user = "root";
$pass = "";

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8",
        $user,
        $pass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (Exception $e) {
    error_log("Database connection failed: " . $e->getMessage());
    $imagePath = '';
    $imageExists = false;
}

// Get upload_id from query parameter
$upload_id = isset($_GET['upload_id']) ? intval($_GET['upload_id']) : 0;

// Fetch processed image
$imagePath = '';
$imageExists = false;

if ($upload_id > 0) {
    // Query for specific upload_id
    $stmt = $pdo->prepare("SELECT output_path FROM processed_images WHERE upload_id = ? ORDER BY processed_at DESC LIMIT 1");
    $stmt->execute([$upload_id]);
    $processed = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($processed) {
        $imagePath = "http://localhost/frontend/client/pages/processed/" . basename($processed['output_path']);
        $imageExists = true;
    }
} else {
    // Fallback: Get the latest processed image
    $stmt = $pdo->prepare("SELECT output_path FROM processed_images ORDER BY processed_at DESC LIMIT 1");
    $stmt->execute();
    $processed = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($processed) {
        $imagePath = "http://localhost/frontend/client/pages/processed/" . basename($processed['output_path']);
        $imageExists = true;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Processed Image - BG Remover Pro</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../components/css/style.css" />
    <link rel="stylesheet" href="../components/css/upload.css" />
    <link rel="stylesheet" href="../components/css/dashboard.css" />
    <style>
        .process-step {
            text-align: center;
            margin: 20px auto;
            max-width: 600px;
            padding: 20px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .step-number {
            font-size: 24px;
            font-weight: bold;
            color: #007bff;
            margin-bottom: 10px;
        }
        .step-title {
            font-size: 20px;
            margin: 10px 0;
            font-weight: 600;
        }
        .step-description {
            font-size: 16px;
            color: #555;
            margin-bottom: 20px;
        }
        .step-image {
            margin-top: 20px;
        }
        .step-image img {
            max-width: 100%;
            height: auto;
            border-radius: 4px;
        }
        .step-loading {
            font-size: 16px;
            color: #888;
            display: none;
        }
    </style>
</head>
<body>


    <div class="process-step" id="step3">
        <div class="step-number">3</div>
        <h3 class="step-title">Background Removal</h3>
        <p class="step-description"><?php echo $imageExists ? 'Background removed successfully!' : 'Waiting for processing...'; ?></p>
        <div class="step-image">
            <img id="processedImage" src="<?php echo htmlspecialchars($imagePath); ?>" alt="Processed Image" style="<?php echo $imageExists ? 'display:block;' : 'display:none;'; ?>" />
            <div class="step-loading" id="step3Loading" style="<?php echo $imageExists ? 'display:none;' : 'display:block;'; ?>">Waiting for processing...</div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const processedImage = document.getElementById('processedImage');
            const step3Loading = document.getElementById('step3Loading');

            // Check if image is available
            if (processedImage.src && <?php echo json_encode($imageExists); ?>) {
                processedImage.style.display = 'block';
                step3Loading.style.display = 'none';
                processedImage.onload = function() {
                    console.log('Processed image loaded successfully');
                };
                processedImage.onerror = function() {
                    console.error('Failed to load processed image');
                    step3Loading.style.display = 'block';
                    step3Loading.textContent = 'Error loading image';
                };
            }
        });
    </script>
</body>
</html>