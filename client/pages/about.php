<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>About | BG Remover Pro</title>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet"/>
  <link rel="stylesheet" href="../components/css/style.css"/>
  <link rel="stylesheet" href="../components/css/upload.css"/>
  <style>
    .about-container {
      max-width: 900px;
      margin: 50px auto;
      padding: 20px;
      background: #fff;
      border-radius: 12px;
      box-shadow: 0 4px 10px rgba(0,0,0,0.1);
    }

    .about-header {
      text-align: center;
      margin-bottom: 30px;
    }

    .about-header h1 {
      font-size: 2rem;
      margin-bottom: 10px;
    }

    .about-content {
      display: flex;
      align-items: flex-start;
      gap: 20px;
      flex-wrap: wrap;
    }

    .about-image {
      flex: 1 1 250px;
      display: flex;
      justify-content: center;
    }

    .about-image img {
      width: 250px;
      height: 250px;
      border-radius: 50%;
      object-fit: cover;
      background: #f1f1f1;
    }

    .about-text {
      flex: 2 1 500px;
    }

    .about-text h2 {
      margin-bottom: 10px;
      font-size: 1.5rem;
      color: var(--primary-orange);
    }

    .about-text p {
      margin-bottom: 15px;
      line-height: 1.6;
    }

    .about-links a {
      display: inline-block;
      margin-right: 15px;
      text-decoration: none;
      color: var(--primary-orange);
      font-weight: bold;
    }

    .about-links a:hover {
      text-decoration: underline;
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

  <main class="about-container">
    <div class="about-header">
      <h1>About BG Remover Pro</h1>
      <p>A smart tool to analyze and remove image backgrounds with ease.</p>
    </div>

    <div class="about-content">
      <!-- Placeholder image -->
      <div class="about-image">
        <img src="https://via.placeholder.com/250" alt="Ashish Subedi - Developer"/>
      </div>

      <div class="about-text">
        <h2>Developed by Ashish Subedi</h2>
        <p><strong>BG Remover Pro</strong> is a project built for the <strong>8th Semester Final Project of BCA</strong>.  
        It combines multiple technologies to deliver a smooth and efficient background removal experience.</p>

        <p>The backend uses <strong>Python</strong> for analyzing images, creating upload masks, and removing backgrounds.  
        <strong>PHP & MySQL</strong> handle database operations and server hosting, while  
        <strong>HTML, CSS, and JavaScript</strong> are used to build an interactive and user-friendly UI.</p>

         <h3>Features</h3>
        <ul>
          <li>AI Background Removal: Advanced algorithms for professional results</li>
          <li>User Authentication: Secure login/signup with session management</li>
          <li>User Dashboard: Track image conversions and progress</li>
          <li>Admin Panel: Monitor system usage and manage uploads/users</li>
          <li>Image Management: Upload, process, and download images in multiple resolutions</li>
          <li>Responsive Design: Mobile-friendly modern interface</li>
        </ul>
        <br>
        <p>This project demonstrates practical integration of advanced image processing with modern web development.</p>

       
        <div class="about-links">
          <a href="https://github.com/Asis423/bg_removal_app" target="_blank"><i class="fab fa-github"></i> GitHub Repo</a>
          <a href="https://github.com/Asis423" target="_blank"><i class="fab fa-github"></i> @Asis24</a>
        </div>
      </div>
    </div>
  </main>
</body>
</html>
