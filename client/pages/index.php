<?php
session_start();
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
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>BG Remover Pro - AI Background Removal</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../components/css/style.css" />
    <link rel="stylesheet" href="../components/css/upload.css" />
    <link rel="stylesheet" href="../components/css/dashboard.css" />
    <link rel="stylesheet" href="../components/css/process.css" />
</head>

<body>
   <?php include('./components/navbar.php'); ?>

    <!-- Home Section -->
    <div id="home" class="page-section active">
       <?php include './components/hero.php'; ?>
       <?php include './components/upload.php'; ?>
       <?php include './components/process.php'; ?>
     </div>

   <script >
        // Global variables
        let currentUser = null;
        let processedImageData = null;
        let selectedResolution = '1'; // default original
        let currentUploadId = null;
        let isUploading = false;
        let pollInterval = null;

        // Page navigation
        function showPage(pageId) {
            document.querySelectorAll('.page-section').forEach(page => {
                page.classList.remove('active');
            });
            document.getElementById(pageId).classList.add('active');

            document.querySelectorAll('.nav-link').forEach(link => {
                link.classList.remove('active');
            });
            const navLinks = document.querySelectorAll('.nav-link');
            navLinks.forEach(link => {
                if (link.textContent.toLowerCase().includes(pageId.toLowerCase()) ||
                    (pageId === 'home' && link.textContent === 'Home')) {
                    link.classList.add('active');
                }
            });
        }

      

        function showLogin() { showPage('login'); }
        function showRegister() { showPage('register'); }

          // Authentication


        function updateAuthUI() {
            const authLink = document.getElementById('auth-link');
            const adminLink = document.getElementById('admin-link');
            const logoutLink = document.getElementById('logout-link');

            if (!authLink || !adminLink || !logoutLink) {
                console.warn("Auth UI elements not found in DOM");
                return;
            }

            if (currentUser) {
                authLink.style.display = 'none';
                logoutLink.style.display = 'inline-flex';
                if (currentUser.role === 'admin') {
                    adminLink.style.display = 'block';
                }
            } else {
                authLink.style.display = 'block';
                adminLink.style.display = 'none';
                logoutLink.style.display = 'none';
            }
        }
       
        // Drag-drop
        function handleDrop(event) {
            event.preventDefault();
            event.stopPropagation();
            const uploadArea = event.currentTarget;
            uploadArea.classList.remove('dragover');
            const files = event.dataTransfer.files;
            if (files.length > 0) processFile(files[0]);
        }
        function handleDragOver(event) {
            event.preventDefault();
            event.stopPropagation();
            event.currentTarget.classList.add('dragover');
        }
        function handleDragLeave(event) {
            event.preventDefault();
            event.stopPropagation();
            event.currentTarget.classList.remove('dragover');
        }
        function handleFileSelect(event) {
            const file = event.target.files[0];
            if (file) processFile(file);
        }

        // Upload & process
        // ==========================
        // File Upload & Processing
        // ==========================
        function processFile(file) {
            if (isUploading) return; // prevent double upload
            isUploading = true;

            if (!file.type.startsWith('image/')) {
                alert('Please select a valid image file.');
                isUploading = false;
                return;
            }

            if (file.size > 10 * 1024 * 1024) {
                alert('File size must be less than 10MB.');
                isUploading = false;
                return;
            }

            // Show Step 1 preview
            const reader = new FileReader();
            reader.onload = e => {
                const img = document.getElementById('imagePreview');
                img.src = e.target.result;
                img.style.display = 'block';
                document.getElementById('step1').classList.add('completed');
                scrollToPreview();
            };
            reader.readAsDataURL(file);

            // Upload to PHP
            const formData = new FormData();
            formData.append('image', file);

            fetch('upload.php', { method: 'POST', body: formData })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        currentUploadId = data.upload_id;
                        pollProcessing(currentUploadId);
                    } else {
                        alert(data.message || 'Upload failed.');
                        isUploading = false;
                    }
                })
                .catch(err => {
                    console.error(err);
                    alert('Error uploading image.');
                    isUploading = false;
                });
        }

        // ==========================
        // Dynamic Resolution Cards
        // ==========================
        function showDynamicResolutions(img) {
            const createResolutionOptions = function() {
                const w = img.naturalWidth;
                const h = img.naturalHeight;

                const container = document.querySelector('.resolution-options');
                if (!container) return;

                container.innerHTML = `
                    <div class="resolution-card selected" data-scale="1" tabindex="0">
                        <div class="resolution-label">Original</div>
                        <div class="resolution-size">${w} x ${h}</div>
                    </div>
                    <div class="resolution-card" data-scale="0.75" tabindex="0">
                        <div class="resolution-label">75% Quality</div>
                        <div class="resolution-size">${Math.round(w*0.75)} x ${Math.round(h*0.75)}</div>
                    </div>
                    <div class="resolution-card" data-scale="0.5" tabindex="0">
                        <div class="resolution-label">50% Quality</div>
                        <div class="resolution-size">${Math.round(w*0.5)} x ${Math.round(h*0.5)}</div>
                    </div>
                `;

                const cards = container.querySelectorAll('.resolution-card');
                cards.forEach(card => {
                    card.addEventListener('click', function() {
                        cards.forEach(c => c.classList.remove('selected'));
                        this.classList.add('selected');
                        selectedResolution = this.dataset.scale;
                    });
                });

                container.style.display = 'block';
            };

            if (img.complete && img.naturalWidth !== 0) {
                createResolutionOptions();
            } else {
                img.onload = createResolutionOptions;
            }
        }

        // ==========================
        // Download Image
        // ==========================
        function downloadImage() {
            if (!processedImageData) {
                alert('No processed image available.');
                return;
            }

            const link = document.createElement('a');
            link.download = `bg-removed-${Date.now()}.png`;
            link.href = processedImageData;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);

            // Optional: Send download info to DB
            fetch('log_download.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `processed_image_url=${encodeURIComponent(processedImageData)}&user_id=${currentUser ? currentUser.id : 0}`
            });
        }

        function resetProcessor() {
    // Reset all steps
    document.querySelectorAll('.process-step').forEach(step => {
        step.classList.remove('active', 'completed');
    });

    // Reset progress bar
    const progressBar = document.getElementById('progressBar');
    if (progressBar) progressBar.style.width = '0%';

    // Hide images
    const originalImage = document.getElementById('originalImage');
    if (originalImage) originalImage.style.display = 'none';
    const processedImage = document.getElementById('processedImage');
    if (processedImage) processedImage.style.display = 'none';

    // Hide any spinner
    const analysisSpinner = document.getElementById('analysisSpinner');
    if (analysisSpinner) analysisSpinner.style.display = 'none';

    // Reset loading text for steps
    const stepLoadings = document.querySelectorAll('.step-loading');
    const defaultTexts = ['Waiting for upload...', 'Ready for analysis...', 'Awaiting processing...'];
    stepLoadings.forEach((loading, index) => {
        loading.textContent = defaultTexts[index] || 'Processing...';
        loading.style.display = 'block';
    });

    // Clear file input
    const fileInput = document.getElementById('fileInput');
    if (fileInput) fileInput.value = '';

    // Hide resolution container and clear selections
    const resolutionContainer = document.getElementById('resolutionContainer');
    if (resolutionContainer) {
        resolutionContainer.classList.add('hidden');
        const cards = resolutionContainer.querySelectorAll('.resolution-card');
        cards.forEach(card => card.classList.remove('selected'));
    }

    // Reset processed image data
    processedImageData = null;


    const imagePreview = document.getElementById('imagePreview');
if (imagePreview) {
    imagePreview.style.display = 'none';
    imagePreview.src = '';
}

}


        // Admin stats
        function updateAdminStats() {
            if (!currentUser || currentUser.role !== 'admin') return;
            const stats = {
                totalUsers: Math.floor(Math.random() * 100) + 1200,
                imagesProcessed: Math.floor(Math.random() * 1000) + 15000,
                activeToday: Math.floor(Math.random() * 50) + 50,
                successRate: (98 + Math.random() * 2).toFixed(1) + '%'
            };
            Object.keys(stats).forEach(key => {
                const element = document.getElementById(key);
                if (element) element.textContent = stats[key];
            });
        }

        document.addEventListener('DOMContentLoaded', function () {
            updateAuthUI();
            setInterval(updateAdminStats, 30000);

            // Handle latest processed image if exists
            const latestProcessedImage = document.getElementById('latestProcessedImage');
            if (latestProcessedImage) {
                latestProcessedImage.onload = function() {
                    console.log('Latest processed image loaded successfully');
                };
                latestProcessedImage.onerror = function() {
                    console.error('Failed to load latest processed image');
                    this.parentElement.innerHTML = '<p style="color: #ff0000;">Error loading latest processed image.</p>';
                };
            }

            // Initialize upload functionality
            const uploadArea = document.getElementById('uploadArea');
            const fileInput = document.getElementById('fileInput');
            
            // Click on upload area to trigger file input
            uploadArea.addEventListener('click', () => {
                fileInput.click();
            });

            // Drag and drop functionality
            uploadArea.addEventListener('dragover', handleDragOver);
            uploadArea.addEventListener('dragleave', handleDragLeave);
            uploadArea.addEventListener('drop', handleDrop);
            fileInput.addEventListener('change', handleFileSelect);
        });

        function pollProcessing(uploadId) {
            const interval = setInterval(async () => {
                try {
                    const response = await fetch(`check_status.php?upload_id=${uploadId}`);
                    const result = await response.json();
                    console.log('Polling result:', result); // Debug log

                    if (result.success) {
                        const step2 = document.getElementById('step2');
                        const step3 = document.getElementById('step3');
                        const processedImage = document.getElementById('processedImage');
                        
                        // Use more robust element selection
                        const step3Loading = document.querySelector('#step3 .step-loading') || 
                                            document.getElementById('step3Loading');

                        if (result.data.status === "processing") {
                            step2.classList.add("active");
                            const step2Desc = step2.querySelector(".step-description");
                            if (step2Desc) step2Desc.textContent = "Analyzing image...";
                        }

                        if (result.data.status === "completed") {
                            clearInterval(interval);
                            console.log('Processing completed!'); // Debug log

                            // Update step 2
                            step2.classList.remove("active");
                            step2.classList.add("completed");
                            const step2Desc = step2.querySelector(".step-description");
                            if (step2Desc) step2Desc.textContent = "Analysis complete!";

                            // Update step 3
                            step3.classList.add("active");
                            const step3Desc = step3.querySelector(".step-description");
                            if (step3Desc) step3Desc.textContent = "Background removed!";
                            
                            // Hide loading and show image
                            if (step3Loading) step3Loading.style.display = "none";
                            
                            // Set image source and make sure it's visible
                            processedImage.src = result.data.processed_image_url;
                            processedImage.style.display = "block";
                            processedImage.onload = function() {
                                console.log('Processed image loaded successfully');
                            };
                            processedImage.onerror = function() {
                                console.error('Failed to load processed image');
                                if (step3Loading) {
                                    step3Loading.style.display = "block";
                                    step3Loading.textContent = "Error loading image";
                                }
                            };

                            // Show resolution options
                            showDynamicResolutions(processedImage);
                            
                            // Store the processed image data for download
                            processedImageData = result.data.processed_image_url;
                            
                            // Show download section
                            const resolutionContainer = document.getElementById("resolutionContainer");
                            if (resolutionContainer) resolutionContainer.classList.remove("hidden");
                        }
                    } else {
                        clearInterval(interval);
                        showMessage("Error: " + (result.message || 'Unknown error'), "error");
                    }
                } catch (error) {
                    clearInterval(interval);
                    showMessage("Error polling status: " + error.message, "error");
                    console.error('Polling error:', error);
                }
            }, 3000); // Check every 3 seconds
        }

        function showDynamicResolutions(img) {
            img.onload = function() {
                const w = img.naturalWidth;
                const h = img.naturalHeight;

                const container = document.querySelector('.resolution-options');
                container.innerHTML = `
                    <div class="resolution-card selected" data-scale="1" tabindex="0">
                        <div class="resolution-label">Original</div>
                        <div class="resolution-size">${w} x ${h}</div>
                    </div>
                    <div class="resolution-card" data-scale="0.75" tabindex="0">
                        <div class="resolution-label">75% Quality</div>
                        <div class="resolution-size">${Math.round(w*0.75)} x ${Math.round(h*0.75)}</div>
                    </div>
                    <div class="resolution-card" data-scale="0.5" tabindex="0">
                        <div class="resolution-label">50% Quality</div>
                        <div class="resolution-size">${Math.round(w*0.5)} x ${Math.round(h*0.5)}</div>
                    </div>
                `;

                // Add click event for selection
                const cards = container.querySelectorAll('.resolution-card');
                cards.forEach(card => {
                    card.addEventListener('click', function() {
                        cards.forEach(c => c.classList.remove('selected'));
                        this.classList.add('selected');
                        selectedResolution = this.dataset.scale;
                    });
                });
            };
        }


</script>
<script src="../components/js/generalFunctions.js"></script>
<script src="../components/js/main.js"></script>
</body>

</html>