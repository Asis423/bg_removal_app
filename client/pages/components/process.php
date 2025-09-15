<?php
// process.php
// You can pass $imageExists and $imagePath if needed from the main file
$imageExists = $imageExists ?? false;
$imagePath = $imagePath ?? '';
?>

<!-- Processing Section -->
<div id="processingContainer" class="container">
    <div class="processing-section">
        <div class="processing-header">
            <h2 class="processing-title">Processing Your Image</h2>
            <p class="processing-subtitle">Our AI is working its magic to remove the background</p>
        </div>

        <div class="progress-container">
            <div class="progress-bar" id="progressBar"></div>
        </div>

        <div class="process-steps">
            <!-- Step 1 -->
            <div class="process-step" id="step1">
                <div class="step-number">1</div>
                <h3 class="step-title">Upload Complete</h3>
                <p class="step-description">Your image has been uploaded successfully</p>
                <div class="step-image">
                    <img id="imagePreview" class="image-preview preview-target" src="" alt="Preview">
                    <div class="step-loading">Waiting for upload...</div>
                </div>
            </div>

            <!-- Step 2 -->
            <div class="process-step" id="step2">
                <div class="step-number">2</div>
                <h3 class="step-title">AI Analysis</h3>
                <p class="step-description">Analyzing image content and detecting subjects</p>
                <div class="step-image">
                    <!-- Magic visual placeholder -->
                    <div class="magic-loader" id="magicLoader">
                        <span></span><span></span><span></span><span></span>
                    </div>
                    <div class="step-loading">Analyzing...</div>
                </div>
            </div>

            <!-- Step 3 -->
            <div class="process-step" id="step3">
                <div class="step-number">3</div>
                <h3 class="step-title">Background Removal</h3>
                <p class="step-description">
                    <?php echo $imageExists ? 'Background removed successfully!' : 'Waiting for processing...'; ?>
                </p>
                <div class="step-image">
                    <img id="processedImage" src="<?php echo htmlspecialchars($imagePath); ?>" 
                         alt="Processed Image" style="<?php echo $imageExists ? 'display:block;' : 'display:none;'; ?>" />
                    <div class="step-loading" id="step3Loading" style="<?php echo $imageExists ? 'display:none;' : 'display:block;'; ?>">
                        Waiting for processing...
                    </div>
                </div>
            </div>
        </div>

        <!-- Resolution Selection -->
        <div id="resolutionContainer" class="resolution-container hidden">
            <h3>Select Resolution</h3>
            <div class="resolution-options"></div>
            <div class="download-actions">
                <button class="btn-download" onclick="downloadImage()">
                    <i class="fas fa-download"></i> Download Image
                </button>
                <button class="btn-reset" onclick="resetProcessor()">
                    <i class="fas fa-refresh"></i> Process Another
                </button>
            </div>
        </div>
    </div>
</div>
