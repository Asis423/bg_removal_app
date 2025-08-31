
        // Global variables
        let currentUser = null;
        let processedImageData = null;
        let selectedResolution = 'original';

        // Page navigation
        function showPage(pageId) {
            // Hide all pages
            document.querySelectorAll('.page-section').forEach(page => {
                page.classList.remove('active');
            });
            
            // Show selected page
            document.getElementById(pageId).classList.add('active');
            
            // Update navigation
            document.querySelectorAll('.nav-link').forEach(link => {
                link.classList.remove('active');
            });
            
            // Find and activate the correct nav link
            const navLinks = document.querySelectorAll('.nav-link');
            navLinks.forEach(link => {
                if (link.textContent.toLowerCase().includes(pageId.toLowerCase()) ||
                    (pageId === 'home' && link.textContent === 'Home')) {
                    link.classList.add('active');
                }
            });
        }

        // Authentication functions
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

        function showLogin() {
            showPage('login');
        }

        function showRegister() {
            showPage('register');
        }

        // Scroll to upload section
        function scrollToUpload() {
            showPage('home');
            setTimeout(() => {
                document.querySelector('.upload-section').scrollIntoView({ 
                    behavior: 'smooth' 
                });
            }, 100);
        }

        // File handling functions
        function handleDrop(event) {
            event.preventDefault();
            event.stopPropagation();
            
            const uploadArea = event.currentTarget;
            uploadArea.classList.remove('dragover');
            
            const files = event.dataTransfer.files;
            if (files.length > 0) {
                processFile(files[0]);
            }
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
            if (file) {
                processFile(file);
            }
        }

        function processFile(file) {
            // Validate file type
            if (!file.type.startsWith('image/')) {
                alert('Please select a valid image file.');
                return;
            }

            // Validate file size (10MB limit)
            if (file.size > 10 * 1024 * 1024) {
                alert('File size must be less than 10MB.');
                return;
            }

            // Show processing section
            document.getElementById('processingContainer').classList.remove('hidden');
            
            // Scroll to processing area
            document.getElementById('processingContainer').scrollIntoView({ 
                behavior: 'smooth' 
            });

            // Load and display original image
            const reader = new FileReader();
            reader.onload = function(e) {
                const originalImage = document.getElementById('originalImage');
                originalImage.src = e.target.result;
                originalImage.style.display = 'block';
                originalImage.parentNode.querySelector('.step-loading').style.display = 'none';
                
                // Update step 1
                document.getElementById('step1').classList.add('completed');
                
                // Start processing simulation
                simulateProcessing();
            };
            reader.readAsDataURL(file);
        }

        function simulateProcessing() {
            const progressBar = document.getElementById('progressBar');
            const step2 = document.getElementById('step2');
            const step3 = document.getElementById('step3');
            
            // Step 2: Analysis
            setTimeout(() => {
                step2.classList.add('active');
                document.getElementById('analysisSpinner').style.display = 'block';
                step2.querySelector('.step-loading').textContent = 'Analyzing image...';
                progressBar.style.width = '33%';
            }, 500);

            // Step 3: Processing
            setTimeout(() => {
                step2.classList.remove('active');
                step2.classList.add('completed');
                document.getElementById('analysisSpinner').style.display = 'none';
                step2.querySelector('.step-loading').textContent = 'Analysis complete!';
                
                step3.classList.add('active');
                step3.querySelector('.step-loading').textContent = 'Removing background...';
                progressBar.style.width = '66%';
            }, 2000);

            // Complete processing
            setTimeout(() => {
                step3.classList.remove('active');
                step3.classList.add('completed');
                
                // Create a simple processed image simulation
                const canvas = document.createElement('canvas');
                const ctx = canvas.getContext('2d');
                const originalImage = document.getElementById('originalImage');
                
                // Set canvas size
                canvas.width = originalImage.naturalWidth;
                canvas.height = originalImage.naturalHeight;
                
                // Draw original image
                ctx.drawImage(originalImage, 0, 0);
                
                // Simple background removal simulation (just for demo)
                const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
                const data = imageData.data;
                
                // Make background transparent (very basic simulation)
                for (let i = 0; i < data.length; i += 4) {
                    const r = data[i];
                    const g = data[i + 1];
                    const b = data[i + 2];
                    
                    // Simple background detection (remove very light colors)
                    if (r > 200 && g > 200 && b > 200) {
                        data[i + 3] = 0; // Make transparent
                    }
                }
                
                ctx.putImageData(imageData, 0, 0);
                
                // Display processed image
                const processedImage = document.getElementById('processedImage');
                processedImage.src = canvas.toDataURL('image/png');
                processedImage.style.display = 'block';
                processedImage.parentNode.querySelector('.step-loading').style.display = 'none';
                
                processedImageData = canvas.toDataURL('image/png');
                
                progressBar.style.width = '100%';
                step3.querySelector('.step-loading').textContent = 'Background removed successfully!';
                
                // Show resolution options
                document.getElementById('resolutionContainer').classList.remove('hidden');
            }, 4000);
        }

        // Resolution selection
        document.addEventListener('DOMContentLoaded', function() {
            const resolutionCards = document.querySelectorAll('.resolution-card');
            resolutionCards.forEach(card => {
                card.addEventListener('click', function() {
                    resolutionCards.forEach(c => c.classList.remove('selected'));
                    this.classList.add('selected');
                    selectedResolution = this.dataset.resolution;
                });
            });
        });

        function downloadImage() {
            if (!processedImageData) {
                alert('No processed image available for download.');
                return;
            }

            // Create download link
            const link = document.createElement('a');
            link.download = `bg-removed-${selectedResolution}-${Date.now()}.png`;
            link.href = processedImageData;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            
            alert('Image downloaded successfully!');
        }

        function resetProcessor() {
            // Reset all states
            document.getElementById('processingContainer').classList.add('hidden');
            document.getElementById('resolutionContainer').classList.add('hidden');
            
            // Reset steps
            document.querySelectorAll('.process-step').forEach(step => {
                step.classList.remove('active', 'completed');
            });
            
            // Reset progress
            document.getElementById('progressBar').style.width = '0%';
            
            // Reset images
            document.getElementById('originalImage').style.display = 'none';
            document.getElementById('processedImage').style.display = 'none';
            document.getElementById('analysisSpinner').style.display = 'none';
            
            // Reset loading text
            document.querySelectorAll('.step-loading').forEach((loading, index) => {
                const texts = ['Waiting for upload...', 'Ready for analysis...', 'Awaiting processing...'];
                loading.textContent = texts[index];
                loading.style.display = 'block';
            });
            
            // Reset file input
            document.getElementById('fileInput').value = '';
            
            processedImageData = null;
        }

        // Admin dashboard updates (simulate real-time data)
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
                if (element) {
                    element.textContent = stats[key];
                }
            });
        }

        // Initialize the application
        document.addEventListener('DOMContentLoaded', function() {
            updateAuthUI();
            
            // Update admin stats every 30 seconds
            setInterval(updateAdminStats, 30000);
        });
    
        