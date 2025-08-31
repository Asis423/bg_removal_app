<?php
session_start();
require_once 'db.php'; // Create this file to connect to DB
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BG Remover Pro - AI Background Removal</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="./css/style.css">
    <script src="./js/main.js" defer></script>
</head>
<body>
    <!-- Modern Navigation -->
    <nav class="navbar">
        <div class="nav-container">
            <a href="#" class="brand" onclick="showPage('home')">
                <div class="brand-icon">
                    <i class="fas fa-magic"></i>
                </div>
                BG Remover Pro
            </a>
            <ul class="nav-menu">
                <li><a class="nav-link" onclick="showPage('home')">Home</a></li>
                <li><a class="nav-link" onclick="showPage('about')">About</a></li>
                <li><a class="nav-link" onclick="showPage('login')" id="auth-link">Login</a></li>
                <li><a class="nav-link" onclick="showPage('register')" id="register-link">Sign Up</a></li>
                <li><a class="nav-link" onclick="showPage('admin')" id="admin-link" style="display:none;">Admin</a></li>
                <li><a class="btn-nav" onclick="logout()" id="logout-link" style="display:none;">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a></li>
            </ul>
        </div>
    </nav>

    <!-- Home Section -->
    <div id="home" class="page-section active">
        <!-- Hero Section -->
        <section class="hero-section">
            <div class="hero-content">
                <h1 class="hero-title">Remove Backgrounds with AI Magic</h1>
                <p class="hero-subtitle">Transform your images instantly with our advanced AI-powered background removal tool. Professional results in seconds.</p>
                <div class="hero-cta">
                    <a href="#" class="btn-primary" onclick="scrollToUpload()">
                        <i class="fas fa-upload"></i> Start Removing Backgrounds
                    </a>
                    <a href="#" class="btn-secondary" onclick="showPage('about')">
                        <i class="fas fa-play"></i> Learn More
                    </a>
                </div>
            </div>
        </section>

        <!-- Upload Section -->
        <section class="upload-section">
            <div class="upload-container">
                <div class="upload-area" onclick="document.getElementById('fileInput').click()" 
                     ondrop="handleDrop(event)" ondragover="handleDragOver(event)" ondragleave="handleDragLeave(event)">
                    <div class="upload-icon">
                        <i class="fas fa-cloud-upload-alt"></i>
                    </div>
                    <div class="upload-text">Drop your image here or click to browse</div>
                    <div class="upload-subtext">Supports JPG, PNG, WEBP • Max 10MB</div>
                </div>
                <input type="file" id="fileInput" class="file-input" accept="image/*" onchange="handleFileSelect(event)">
            </div>
        </section>

        <!-- Processing Section (Hidden initially) -->
        <div id="processingContainer" class="container hidden">
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
                            <img id="originalImage" alt="Original Image" style="display: none;">
                            <div class="step-loading">Waiting for upload...</div>
                        </div>
                    </div>

                    <div class="process-step" id="step2">
                        <div class="step-number">2</div>
                        <h3 class="step-title">AI Analysis</h3>
                        <p class="step-description">Analyzing image content and detecting subjects</p>
                        <div class="step-image">
                            <div class="loading-spinner" id="analysisSpinner" style="display: none;"></div>
                            <div class="step-loading">Ready for analysis...</div>
                        </div>
                    </div>

                    <div class="process-step" id="step3">
                        <div class="step-number">3</div>
                        <h3 class="step-title">Background Removal</h3>
                        <p class="step-description">Removing background with precision AI algorithms</p>
                        <div class="step-image">
                            <img id="processedImage" alt="Processed Image" style="display: none;">
                            <div class="step-loading">Awaiting processing...</div>
                        </div>
                    </div>
                </div>

                <!-- Resolution Selection -->
                <div id="resolutionContainer" class="resolution-section hidden">
                    <h3 class="resolution-title">Choose Output Resolution</h3>
                    <div class="resolution-options">
                        <div class="resolution-card selected" data-resolution="original">
                            <div class="resolution-label">Original</div>
                            <div class="resolution-size">Keep original size</div>
                        </div>
                        <div class="resolution-card" data-resolution="hd">
                            <div class="resolution-label">HD Quality</div>
                            <div class="resolution-size">1920x1080</div>
                        </div>
                        <div class="resolution-card" data-resolution="4k">
                            <div class="resolution-label">4K Quality</div>
                            <div class="resolution-size">3840x2160</div>
                        </div>
                    </div>

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

        <!-- Features Section -->
        <div class="container section">
            <div class="text-center">
                <h2 style="font-size: 2.5rem; font-weight: 700; margin-bottom: 1rem; color: var(--text-primary);">Why Choose BG Remover Pro?</h2>
                <p style="font-size: 1.2rem; color: var(--text-secondary); margin-bottom: 4rem;">Experience the power of AI-driven background removal</p>
            </div>

            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-bolt"></i>
                    </div>
                    <h3 class="feature-title">Lightning Fast</h3>
                    <p class="feature-description">Remove backgrounds in seconds with our optimized AI algorithms. No waiting, just instant results that save you time.</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-brain"></i>
                    </div>
                    <h3 class="feature-title">AI Precision</h3>
                    <p class="feature-description">Advanced machine learning ensures perfect edge detection and natural-looking results every time.</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-mobile-alt"></i>
                    </div>
                    <h3 class="feature-title">Works Everywhere</h3>
                    <p class="feature-description">Use on any device - desktop, tablet, or mobile. No app downloads required, just open and start editing.</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <h3 class="feature-title">Privacy First</h3>
                    <p class="feature-description">Your images are processed securely and never stored on our servers. Complete privacy guaranteed.</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-file-image"></i>
                    </div>
                    <h3 class="feature-title">High Quality Output</h3>
                    <p class="feature-description">Get professional results with support for high-resolution images up to 4K quality.</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                    <h3 class="feature-title">Free to Use</h3>
                    <p class="feature-description">Remove backgrounds from unlimited images completely free. No hidden costs or subscriptions.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- About Section -->
    <div id="about" class="page-section">
        <div class="container section">
            <div class="text-center">
                <h1 style="font-size: 3rem; font-weight: 800; margin-bottom: 2rem; color: var(--text-primary);">About BG Remover Pro</h1>
                <p style="font-size: 1.3rem; color: var(--text-secondary); margin-bottom: 4rem; max-width: 800px; margin-left: auto; margin-right: auto;">
                    We're revolutionizing image editing with cutting-edge AI technology that makes professional background removal accessible to everyone.
                </p>
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 4rem; margin: 4rem 0;">
                <div>
                    <h2 style="font-size: 2rem; font-weight: 700; margin-bottom: 1.5rem; color: var(--primary-blue);">Our Mission</h2>
                    <p style="color: var(--text-secondary); line-height: 1.8; margin-bottom: 1.5rem;">
                        We believe that powerful image editing tools shouldn't require expensive software or technical expertise. Our mission is to democratize professional-grade background removal using state-of-the-art artificial intelligence.
                    </p>
                    <p style="color: var(--text-secondary); line-height: 1.8;">
                        Whether you're a content creator, e-commerce seller, photographer, or just someone who wants to perfect their photos, BG Remover Pro gives you studio-quality results in seconds.
                    </p>
                </div>

                <div>
                    <h2 style="font-size: 2rem; font-weight: 700; margin-bottom: 1.5rem; color: var(--primary-blue);">How It Works</h2>
                    <div style="space-y: 1rem;">
                        <div style="margin-bottom: 1rem;">
                            <h4 style="font-weight: 600; color: var(--text-primary); margin-bottom: 0.5rem;">
                                <i class="fas fa-upload" style="color: var(--primary-orange); margin-right: 0.5rem;"></i>
                                Upload Your Image
                            </h4>
                            <p style="color: var(--text-secondary); margin-left: 1.5rem;">Simply drag and drop or click to select your image file.</p>
                        </div>
                        <div style="margin-bottom: 1rem;">
                            <h4 style="font-weight: 600; color: var(--text-primary); margin-bottom: 0.5rem;">
                                <i class="fas fa-brain" style="color: var(--primary-orange); margin-right: 0.5rem;"></i>
                                AI Processing
                            </h4>
                            <p style="color: var(--text-secondary); margin-left: 1.5rem;">Our advanced neural networks analyze and precisely remove the background.</p>
                        </div>
                        <div style="margin-bottom: 1rem;">
                            <h4 style="font-weight: 600; color: var(--text-primary); margin-bottom: 0.5rem;">
                                <i class="fas fa-download" style="color: var(--primary-orange); margin-right: 0.5rem;"></i>
                                Download Result
                            </h4>
                            <p style="color: var(--text-secondary); margin-left: 1.5rem;">Get your professionally edited image in seconds, ready to use.</p>
                        </div>
                    </div>
                </div>
            </div>

            <div style="background: var(--surface); padding: 4rem; border-radius: 24px; text-center; margin: 4rem 0; border: 1px solid var(--border-light);">
                <h2 style="font-size: 2rem; font-weight: 700; margin-bottom: 2rem; color: var(--text-primary);">Ready to Get Started?</h2>
                <p style="color: var(--text-secondary); margin-bottom: 2rem; font-size: 1.1rem;">
                    Join thousands of users who trust BG Remover Pro for their image editing needs.
                </p>
                <a href="#" class="btn-primary" onclick="showPage('home')">
                    <i class="fas fa-rocket"></i> Start Removing Backgrounds
                </a>
            </div>
        </div>
    </div>

<!-- Login Section -->
<div id="login" class="page-section">
    <div class="container">
        <div class="auth-container">
            <div class="auth-header">
                <h1 class="auth-title">Welcome Back</h1>
                <p class="auth-subtitle">Sign in to access premium features</p>
            </div>

            <form action="login.php" method="POST">
                <div class="form-group">
                    <label class="form-label" for="email">Email Address</label>
                    <input type="email" id="email" name="email" class="form-input" required placeholder="Enter your email">
                </div>

                <div class="form-group">
                    <label class="form-label" for="password">Password</label>
                    <input type="password" id="password" name="password" class="form-input" required placeholder="Enter your password">
                </div>

                <button type="submit" class="form-submit">
                    <i class="fas fa-sign-in-alt"></i> Sign In
                </button>
            </form>

            <div class="auth-link">
                Don't have an account? <a href="#" onclick="showRegister()">Sign up here</a>
            </div>
        </div>
    </div>
</div>


<!-- Register Section -->
<div id="register" class="page-section">
    <div class="container">
        <div class="auth-container">
            <div class="auth-header">
                <h1 class="auth-title">Create Account</h1>
                <p class="auth-subtitle">Join thousands of satisfied users</p>
            </div>

            <form action="signup.php" method="POST">
                <div class="form-group">
                    <label class="form-label" for="regName">Full Name</label>
                    <input type="text" id="regName" name="name" class="form-input" required placeholder="Enter your full name">
                </div>

                <div class="form-group">
                    <label class="form-label" for="regEmail">Email Address</label>
                    <input type="email" id="regEmail" name="email" class="form-input" required placeholder="Enter your email">
                </div>

                <div class="form-group">
                    <label class="form-label" for="regPassword">Password</label>
                    <input type="password" id="regPassword" name="password" class="form-input" required placeholder="Create a password">
                </div>

                <button type="submit" class="form-submit">
                    <i class="fas fa-user-plus"></i> Create Account
                </button>
            </form>

            <div class="auth-link">
                Already have an account? <a href="#" onclick="showLogin()">Sign in here</a>
            </div>
        </div>
    </div>
</div>

    <!-- Admin Section -->
    <div id="admin" class="page-section">
        <div class="container section">
            <div class="admin-container">
                <div class="admin-header">
                    <h1 class="admin-title">Admin Dashboard</h1>
                    <p class="admin-subtitle">Monitor system performance and user activity</p>
                </div>

                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-number" id="totalUsers">1,247</div>
                        <div class="stat-label">Total Users</div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-number" id="imagesProcessed">15,834</div>
                        <div class="stat-label">Images Processed</div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-number" id="activeToday">89</div>
                        <div class="stat-label">Active Today</div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-number" id="successRate">99.2%</div>
                        <div class="stat-label">Success Rate</div>
                    </div>
                </div>

                <h3 style="font-size: 1.5rem; font-weight: 600; margin: 3rem 0 1.5rem; color: var(--text-primary);">Recent Activity</h3>
                
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
                            <td>john.doe@email.com</td>
                            <td>Background Removal</td>
                            <td>2 minutes ago</td>
                            <td style="color: var(--success); font-weight: 600;">Success</td>
                        </tr>
                        <tr>
                            <td>sarah.smith@email.com</td>
                            <td>Account Registration</td>
                            <td>5 minutes ago</td>
                            <td style="color: var(--success); font-weight: 600;">Success</td>
                        </tr>
                        <tr>
                            <td>mike.wilson@email.com</td>
                            <td>Background Removal</td>
                            <td>8 minutes ago</td>
                            <td style="color: var(--success); font-weight: 600;">Success</td>
                        </tr>
                        <tr>
                            <td>anna.taylor@email.com</td>
                            <td>File Upload</td>
                            <td>12 minutes ago</td>
                            <td style="color: var(--warning); font-weight: 600;">Processing</td>
                        </tr>
                        <tr>
                            <td>david.brown@email.com</td>
                            <td>Background Removal</td>
                            <td>15 minutes ago</td>
                            <td style="color: var(--success); font-weight: 600;">Success</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>