<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>About | BG Remover Pro</title>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet"/>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <style>
    :root {
      --primary-blue: rgb(0, 64, 145);
      --primary-orange: rgb(255, 140, 0);
      --accent-blue: rgb(20, 84, 165);
      --light-blue: rgb(240, 248, 255);
      --dark-blue: rgb(0, 44, 105);
      --orange-hover: rgb(235, 120, 0);
      --success: rgb(34, 197, 94);
      --error: rgb(239, 68, 68);
      --warning: rgb(245, 158, 11);
      --text-primary: rgb(15, 23, 42);
      --text-secondary: rgb(71, 85, 105);
      --text-muted: rgb(148, 163, 184);
      --surface: rgb(255, 255, 255);
      --surface-alt: rgb(248, 250, 252);
      --border: rgb(226, 232, 240);
      --border-light: rgb(241, 245, 249);
      --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
      --shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);
      --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1);
      --shadow-xl: 0 20px 25px -5px rgb(0 0 0 / 0.1);
    }

    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: 'Poppins', sans-serif;
      background-color: var(--light-blue);
      color: var(--text-primary);
      line-height: 1.6;
    }

    .navbar {
      background: var(--surface);
      box-shadow: var(--shadow);
      padding: 0.8rem 0;
      position: sticky;
      top: 0;
      z-index: 100;
    }

    .nav-container {
      max-width: 1200px;
      margin: 0 auto;
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 0 1.5rem;
    }

    .brand {
      display: flex;
      align-items: center;
      text-decoration: none;
      color: var(--primary-blue);
      font-weight: 700;
      font-size: 1.4rem;
    }

    .brand-icon {
      background: var(--primary-blue);
      width: 40px;
      height: 40px;
      border-radius: 8px;
      display: flex;
      align-items: center;
      justify-content: center;
      margin-right: 10px;
      color: white;
    }

    .nav-menu {
      display: flex;
      list-style: none;
    }

    .nav-link {
      text-decoration: none;
      color: var(--text-secondary);
      font-weight: 500;
      padding: 0.5rem 1rem;
      margin: 0 0.2rem;
      border-radius: 6px;
      transition: all 0.3s ease;
    }

    .nav-link:hover, .nav-link.active {
      color: var(--primary-blue);
      background-color: rgba(0, 64, 145, 0.1);
    }

    .about-hero {
      background: linear-gradient(135deg, var(--primary-blue) 0%, var(--accent-blue) 100%);
      color: white;
      padding: 4rem 1rem;
      text-align: center;
    }

    .about-hero h1 {
      font-size: 2.8rem;
      margin-bottom: 1rem;
      font-weight: 700;
    }

    .about-hero p {
      font-size: 1.2rem;
      max-width: 700px;
      margin: 0 auto;
      opacity: 0.9;
    }

    .about-container {
      max-width: 1200px;
      margin: -50px auto 50px;
      padding: 0 1.5rem;
    }

    .about-card {
      background: var(--surface);
      border-radius: 16px;
      box-shadow: var(--shadow-lg);
      overflow: hidden;
      padding: 2.5rem;
      display: flex;
      flex-wrap: wrap;
      gap: 2.5rem;
      position: relative;
    }

    .about-image {
      flex: 1 1 300px;
      display: flex;
      flex-direction: column;
      align-items: center;
      position: sticky;
      top: 100px; /* Adjust based on your navbar height */
      align-self: flex-start;
      height: fit-content;
    }

    .profile-image {
      width: 280px;
      height: 280px;
      border-radius: 50%;
      object-fit: cover;
      border: 5px solid #fff;
      box-shadow: var(--shadow);
      background: var(--surface-alt);
    }

    .profile-title {
      margin-top: 1.5rem;
      text-align: center;
    }

    .profile-title h2 {
      color: var(--primary-blue);
      margin-bottom: 0.5rem;
    }

    .profile-title p {
      color: var(--text-secondary);
      font-style: italic;
    }

    .about-text {
      flex: 2 1 600px;
    }

    .about-section {
      margin-bottom: 2rem;
    }

    .about-section h2 {
      color: var(--primary-blue);
      margin-bottom: 1rem;
      font-size: 1.8rem;
      position: relative;
      padding-bottom: 0.5rem;
    }

    .about-section h2:after {
      content: '';
      position: absolute;
      bottom: 0;
      left: 0;
      width: 50px;
      height: 4px;
      background: var(--primary-orange);
      border-radius: 2px;
    }

    .about-section p {
      margin-bottom: 1rem;
      color: var(--text-primary);
    }

    .tech-stack {
      display: flex;
      flex-wrap: wrap;
      gap: 1rem;
      margin: 1.5rem 0;
    }

    .tech-item {
      background: rgba(0, 64, 145, 0.1);
      color: var(--primary-blue);
      padding: 0.5rem 1rem;
      border-radius: 50px;
      font-weight: 500;
      display: flex;
      align-items: center;
    }

    .tech-item i {
      margin-right: 0.5rem;
    }

    .features-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
      gap: 1.5rem;
      margin: 1.5rem 0;
    }

    .feature-card {
      background: var(--surface-alt);
      border-radius: 12px;
      padding: 1.5rem;
      box-shadow: var(--shadow-sm);
      border: 1px solid var(--border-light);
      transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .feature-card:hover {
      transform: translateY(-5px);
      box-shadow: var(--shadow);
    }

    .feature-icon {
      background: var(--primary-blue);
      width: 50px;
      height: 50px;
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
      font-size: 1.2rem;
      margin-bottom: 1rem;
    }

    .feature-card h4 {
      margin-bottom: 0.8rem;
      color: var(--primary-blue);
    }

    .feature-card p {
      color: var(--text-secondary);
      font-size: 0.95rem;
    }

    .about-links {
      display: flex;
      gap: 1.5rem;
      margin-top: 2rem;
    }

    .social-link {
      display: inline-flex;
      align-items: center;
      text-decoration: none;
      color: var(--primary-blue);
      font-weight: 600;
      padding: 0.8rem 1.5rem;
      border-radius: 50px;
      background: rgba(0, 64, 145, 0.1);
      transition: all 0.3s ease;
    }

    .social-link:hover {
      background: var(--primary-blue);
      color: white;
      transform: translateY(-2px);
      box-shadow: 0 5px 15px rgba(0, 64, 145, 0.3);
    }

    .social-link i {
      margin-right: 0.5rem;
    }

    @media (max-width: 900px) {
      .about-image {
        position: relative;
        top: 0;
      }
    }

    @media (max-width: 768px) {
      .about-content {
        flex-direction: column;
      }
      
      .about-hero h1 {
        font-size: 2.2rem;
      }
      
      .nav-menu {
        display: none; /* For simplicity, consider adding a mobile menu toggle */
      }
      
      .about-links {
        flex-direction: column;
        gap: 1rem;
      }
    }
  </style>
</head>
<body>
  <nav class="navbar">
    <div class="nav-container">
      <a href="index.php" class="brand">
        <div class="brand-icon"><i class="fas fa-magic"></i></div>
        BG Remover Pro
      </a>
      <ul class="nav-menu">
        <li><a class="nav-link" href="index.php">Home</a></li>
        <li><a class="nav-link active" href="about.php">About</a></li>
        <li><a class="nav-link" href="login.php">Login</a></li>
        <li><a class="nav-link" href="register.php">Sign Up</a></li>
      </ul>
    </div>
  </nav>

  <section class="about-hero">
    <h1>About BG Remover Pro</h1>
    <p>A smart tool to analyze and remove image backgrounds with precision and ease</p>
  </section>

  <main class="about-container">
    <div class="about-card">
      <div class="about-image">
        <img src="https://via.placeholder.com/280" alt="Ashish Subedi - Developer" class="profile-image" />
        <div class="profile-title">
          <h2>Ashish Subedi</h2>
          <p>Full Stack Developer</p>
        </div>
      </div>

      <div class="about-text">
        <div class="about-section">
          <h2>Project Overview</h2>
          <p><strong>BG Remover Pro</strong> is a project built for the <strong>8th Semester Final Project of BCA</strong>. It combines multiple technologies to deliver a smooth and efficient background removal experience.</p>
          
          <div class="tech-stack">
            <div class="tech-item"><i class="fab fa-python"></i> Python</div>
            <div class="tech-item"><i class="fab fa-php"></i> PHP</div>
            <div class="tech-item"><i class="fas fa-database"></i> MySQL</div>
            <div class="tech-item"><i class="fab fa-js"></i> JavaScript</div>
            <div class="tech-item"><i class="fab fa-html5"></i> HTML5</div>
            <div class="tech-item"><i class="fab fa-css3-alt"></i> CSS3</div>
          </div>
          
          <p>The backend uses <strong>Python</strong> for analyzing images, creating upload masks, and removing backgrounds. <strong>PHP & MySQL</strong> handle database operations and server hosting, while <strong>HTML, CSS, and JavaScript</strong> are used to build an interactive and user-friendly UI.</p>
        </div>

        <div class="about-section">
          <h2>Key Features</h2>
          <div class="features-grid">
            <div class="feature-card">
              <div class="feature-icon">
                <i class="fas fa-brain"></i>
              </div>
              <h4>AI Background Removal</h4>
              <p>Advanced algorithms for professional results with precision</p>
            </div>
            
            <div class="feature-card">
              <div class="feature-icon">
                <i class="fas fa-user-lock"></i>
              </div>
              <h4>User Authentication</h4>
              <p>Secure login/signup with session management</p>
            </div>
            
            <div class="feature-card">
              <div class="feature-icon">
                <i class="fas fa-tachometer-alt"></i>
              </div>
              <h4>User Dashboard</h4>
              <p>Track image conversions and progress</p>
            </div>
            
            <div class="feature-card">
              <div class="feature-icon">
                <i class="fas fa-cogs"></i>
              </div>
              <h4>Admin Panel</h4>
              <p>Monitor system usage and manage uploads/users</p>
            </div>
            
            <div class="feature-card">
              <div class="feature-icon">
                <i class="fas fa-images"></i>
              </div>
              <h4>Image Management</h4>
              <p>Upload, process, and download in multiple resolutions</p>
            </div>
            
            <div class="feature-card">
              <div class="feature-icon">
                <i class="fas fa-mobile-alt"></i>
              </div>
              <h4>Responsive Design</h4>
              <p>Mobile-friendly modern interface</p>
            </div>
          </div>
          
          <p>This project demonstrates practical integration of advanced image processing with modern web development.</p>
        </div>

        <div class="about-links">
          <a href="https://github.com/Asis423/bg_removal_app" target="_blank" class="social-link">
            <i class="fab fa-github"></i> GitHub Repository
          </a>
          <a href="https://github.com/Asis423" target="_blank" class="social-link">
            <i class="fab fa-github"></i> @Asis423
          </a>
        </div>
      </div>
    </div>
  </main>

  <script>
    // Add a small script to handle the top offset for the sticky element
    document.addEventListener('DOMContentLoaded', function() {
      const navbar = document.querySelector('.navbar');
      const stickyElement = document.querySelector('.about-image');
      
      function updateStickyPosition() {
        if (window.innerWidth > 900) {
          const navbarHeight = navbar.offsetHeight;
          stickyElement.style.top = `${navbarHeight + 20}px`;
        }
      }
      
      // Initial call
      updateStickyPosition();
      
      // Update on resize
      window.addEventListener('resize', updateStickyPosition);
    });
  </script>
</body>
</html>