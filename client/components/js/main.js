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
                startPolling(currentUploadId);
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
// Poll Processed Image
// ==========================
function startPolling(uploadId) {
    const step2 = document.getElementById('step2');
    const step3 = document.getElementById('step3');
    const progressBar = document.getElementById('progressBar');

    // Reset steps
    step2.classList.remove('completed', 'active');
    step3.classList.remove('completed', 'active');
    progressBar.style.width = '0%';

    step2.classList.add('active');
    let dots = 0;

    // Clear any previous interval
    if (pollInterval) clearInterval(pollInterval);

    pollInterval = setInterval(() => {
        const loadingText = step2.querySelector('.step-loading');
        dots = (dots + 1) % 4;
        loadingText.textContent = 'Analyzing image' + '.'.repeat(dots);

        fetch(`check_status.php?upload_id=${uploadId}`)
            .then(res => res.json())
            .then(result => {
                if (result.success && result.status === 'completed') {
                    clearInterval(pollInterval);

                    step2.classList.remove('active');
                    step2.classList.add('completed');
                    loadingText.textContent = 'Analysis complete!';

                    // Step 3 - show processed image
                    step3.classList.add('active');
                    const processedImg = document.getElementById('processedImage');
                    processedImg.src = result.data.processed_image_url;
                    processedImg.style.display = 'block';
                    step3.querySelector('.step-loading').style.display = 'none';
                    progressBar.style.width = '100%';

                    processedImageData = result.data.processed_image_url;

                    // Show resolution options
                    document.getElementById('resolutionContainer').classList.remove('hidden');
                    showDynamicResolutions(processedImg);

                    isUploading = false;
                } else if (result.success && result.status === 'pending') {
                    let currentWidth = parseInt(progressBar.style.width) || 10;
                    if (currentWidth < 80) progressBar.style.width = (currentWidth + 2) + '%';
                }
            })
            .catch(err => console.error(err));
    }, 800); // polling every 0.8s
}

// ==========================
// Dynamic Resolution Cards
// ==========================
function showDynamicResolutions(img) {
    img.onload = function () {
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

        container.querySelectorAll('.resolution-card').forEach(card => {
            card.addEventListener('click', function() {
                container.querySelectorAll('.resolution-card').forEach(c => c.classList.remove('selected'));
                this.classList.add('selected');
                selectedResolution = this.dataset.scale;
            });
        });
    };
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



function pollProcessedImage(upload_id) {
    const step2 = document.getElementById('step2');
    const step3 = document.getElementById('step3');
    const progressBar = document.getElementById('progressBar');

    let dots = 0;

    // Ensure only one interval is active
    if (window.pollInterval) clearInterval(window.pollInterval);

    step2.classList.add('active');
    step2.querySelector('.step-loading').textContent = 'Analyzing image...';

    window.pollInterval = setInterval(() => {
        // Animate dots for AI Analysis
        dots = (dots + 1) % 4;
        step2.querySelector('.step-loading').textContent = 'Analyzing image' + '.'.repeat(dots);

        fetch(`check_status.php?upload_id=${upload_id}`)
            .then(res => res.json())
            .then(result => {
                if (result.success && result.status === 'completed') {
                    clearInterval(window.pollInterval);
                    window.pollInterval = null;

                    // Step 2 complete
                    step2.classList.remove('active');
                    step2.classList.add('completed');
                    step2.querySelector('.step-loading').textContent = 'Analysis complete!';

                    // Step 3: show processed image
                    step3.classList.add('active');
                    const processedImg = document.getElementById('processedImage');
                    processedImg.src = result.data.processed_image_url;
                    processedImg.style.display = 'block';
                    step3.querySelector('.step-loading').style.display = 'none';

                    // Update progress bar
                    progressBar.style.width = '100%';

                    // Show resolution options dynamically
                    showDynamicResolutions(processedImg);

                    // Make resolution container visible
                    document.getElementById('resolutionContainer').classList.remove('hidden');
                } else if (result.success && result.status === 'pending') {
                    // Optional: slow progress bar animation while pending
                    let currentWidth = parseInt(progressBar.style.width) || 20;
                    if (currentWidth < 80) progressBar.style.width = (currentWidth + 1) + '%';
                }
            })
            .catch(err => {
                console.error('Error polling processed image:', err);
            });
    }, 1000); // poll every 1 second
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


