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

// Scroll to upload
function scrollToUpload() {
    showPage('home');
    setTimeout(() => {
        document.querySelector('.upload-section').scrollIntoView({ behavior: 'smooth' });
    }, 100);
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


