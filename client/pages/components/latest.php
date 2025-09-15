 <?php
 
        // Database connection for latest processed image
        $host = "localhost";
        $dbname = "bg_removal_app";
        $user = "root";
        $pass = "";
        $imagePath = '';
        $imageExists = false;

        try {
            $pdo = new PDO(
                "mysql:host=$host;dbname=$dbname;charset=utf8",
                $user,
                $pass,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );

            // Get upload_id from query parameter
            $upload_id = isset($_GET['upload_id']) ? intval($_GET['upload_id']) : 0;

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
        } catch (Exception $e) {
            error_log("Database connection failed: " . $e->getMessage());
        }
        ?>

        <?php if ($imageExists): ?>
        <div class="container" style="margin-top: 40px;">
            <h3 style="text-align: center; margin-bottom: 20px;">Latest Processed Image</h3>
            <div class="process-step" id="latestStep3">
                <div class="step-number">3</div>
                <h3 class="step-title">Background Removal Complete</h3>
                <p class="step-description">Your processed image is ready!</p>
                <div class="step-image">
                    <img id="latestProcessedImage" src="<?php echo htmlspecialchars($imagePath); ?>" alt="Latest Processed Image" style="display:block; max-width: 100%; height: auto; border-radius: 4px;" />
                </div>
                <div style="text-align: center; margin-top: 15px;">
                    <a href="<?php echo htmlspecialchars($imagePath); ?>" download class="btn-primary" style="display: inline-block; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 4px;">
                        <i class="fas fa-download"></i> Download Image
                    </a>
                </div>
            </div>
        </div>
        <?php endif; ?>