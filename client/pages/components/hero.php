<section class="hero-section">
    <div class="hero-content">
        <h1 class="hero-title">Remove Backgrounds With Style In Seconds</h1>
        <p class="hero-subtitle">
            Transform your images instantly with our advanced AI-powered background removal tool. Professional results in seconds.
        </p>
        <div class="hero-cta">
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="#" class="btn-primary" onclick="scrollToUpload()">
                    <i class="fas fa-upload"></i> Start Removing Backgrounds
                </a>
            <?php else: ?>
                <a href="login.php" class="btn-primary">
                    <i class="fas fa-upload"></i> Start Removing Backgrounds
                </a>
            <?php endif; ?>
            <a href="about.php" class="btn-secondary">
                <i class="fas fa-play"></i> Learn More
            </a>
        </div>
    </div>
</section>
