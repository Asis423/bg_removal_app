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
                <div id="message" class="message"></div>
            </div>
        </section>

        <!-- Processing Section (Visible by default, no hiding) -->
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
                    <div class="process-step" id="step1">
                        <div class="step-number">1</div>
                        <h3 class="step-title">Upload Complete</h3>
                        <p class="step-description">Your image has been uploaded successfully</p>
                        <div class="step-image">
                            <img id="imagePreview" class="image-preview preview-target" src="" alt="Preview">
                            <div class="step-loading">Waiting for upload...</div>
                        </div>
                    </div>

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

                   <?php include './components/process.php'; ?>
                </div>

                <!-- Resolution Selection (Always visible) -->
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

        <!-- Latest Processed Image Display (Optional - shows if upload_id is provided in URL) -->
       <?php include './components/latest.php';?>
    </div>

    <div id="about" class="page-section">
        <div class="container section">
            <div class="text-center">
                <h1>About BG Remover Pro</h1>
                <p>We're revolutionizing image editing with cutting-edge AI technology that makes professional
                    background removal accessible to everyone.</p>
            </div>
        </div>
    </div>

    <div id="login" class="page-section">
        <div class="container">
            <form action="login.php" method="POST">
                <input type="email" name="email" required placeholder="Email" />
                <input type="password" name="password" required placeholder="Password" />
                <button type="submit">Login</button>
            </form>
        </div>
    </div>

    <div id="register" class="page-section">
        <div class="container">
            <form action="signup.php" method="POST">
                <input type="text" name="name" required placeholder="Full Name" />
                <input type="email" name="email" required placeholder="Email" />
                <input type="password" name="password" required placeholder="Password" />
                <button type="submit">Register</button>
            </form>
        </div>
    </div>

    <div id="admin" class="page-section">
        <div class="container section">
            <h1>Admin Dashboard</h1>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Action</th>
                        <th>Time</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>demo@user.com</td>
                        <td>Upload</td>
                        <td>Just now</td>
                        <td>Success</td>
                    </tr>
                </tbody>
            </table>
        </div>
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

        // Authentication
        function handleLogin(event) {
            event.preventDefault();
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;

            fetch('login.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `email=${encodeURIComponent(email)}&password=${encodeURIComponent(password)}`
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        currentUser = data.user;
                        updateAuthUI();
                        if (data.user.role === 'admin') {
                            showPage('admin');
                            alert('Welcome back, Admin!');
                        } else {
                            showPage('home');
                            alert('Login successful!');
                        }
                    } else {
                        alert(data.message || 'Login failed');
                    }
                })
                .catch(err => {
                    console.error(err);
                    alert('Server error during login.');
                });
        }

        function handleRegister(event) {
            event.preventDefault();
            const name = document.getElementById('regName').value;
            const email = document.getElementById('regEmail').value;
            const password = document.getElementById('regPassword').value;

            fetch('signup.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `name=${encodeURIComponent(name)}&email=${encodeURIComponent(email)}&password=${encodeURIComponent(password)}`
            })
                .then(response => {
                    if (response.redirected) {
                        window.location.href = response.url;
                    } else {
                        return response.text();
                    }
                })
                .catch(err => {
                    console.error(err);
                    alert('Server error during registration.');
                });
        }

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

        function showLogin() { showPage('login'); }
        function showRegister() { showPage('register'); }

       
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

            alert('Image downloaded successfully!');
        }

        function resetProcessor() {
            document.getElementById('processingContainer').classList.add('hidden');
            document.getElementById('resolutionContainer').classList.add('hidden');
            document.querySelectorAll('.process-step').forEach(step => {
                step.classList.remove('active', 'completed');
            });
            document.getElementById('progressBar').style.width = '0%';
            document.getElementById('originalImage').style.display = 'none';
            document.getElementById('processedImage').style.display = 'none';
            document.getElementById('analysisSpinner').style.display = 'none';
            document.querySelectorAll('.step-loading').forEach((loading, index) => {
                const texts = ['Waiting for upload...', 'Ready for analysis...', 'Awaiting processing...'];
                loading.textContent = texts[index];
                loading.style.display = 'block';
            });
            document.getElementById('fileInput').value = '';
            processedImageData = null;
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

        function showMessage(message, type) {
            // Create or find message element
            let messageDiv = document.getElementById('message');
            if (!messageDiv) {
                messageDiv = document.createElement('div');
                messageDiv.id = 'message';
                document.body.appendChild(messageDiv);
            }
            
            messageDiv.textContent = message;
            messageDiv.className = `message ${type}`;
            messageDiv.style.display = 'block';

            // Auto hide after 5 seconds
            setTimeout(() => {
                messageDiv.style.display = 'none';
            }, 5000);
        }
</script>
<script src="../components/js/generalFunctions.js"></script>
</body>

</html>