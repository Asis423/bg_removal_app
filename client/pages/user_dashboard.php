<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>User Dashboard</title>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet"/>
  <link rel="stylesheet" href="../components/css/style.css"/>
  <link rel="stylesheet" href="../components/css/upload.css"/>
  <style>
    body { font-family: "Segoe UI", sans-serif; background: #f5f7fa; margin: 0; }
    .navbar { background: var(--primary-blue); padding: 1rem; color: white; }
    .nav-container { display: flex; justify-content: space-between; align-items: center; max-width: 1200px; margin: auto; }
    .brand { font-size: 1.5rem; font-weight: bold; color: white; text-decoration: none; display: flex; align-items: center; gap: 0.5rem; }
    .profile-circle {
        background: var(--primary-orange); 
        color: white; 
        border-radius: 50%; 
        width: 40px; 
        height: 40px; 
        display: flex; 
        align-items: center; 
        justify-content: center; 
        font-weight: bold; 
        cursor: pointer;
        position: relative;
    }
    .dropdown {
        display: none;
        position: absolute;
        right: 0;
        top: 50px;
        background: white;
        color: black;
        border-radius: 8px;
        box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        min-width: 150px;
        z-index: 999;
    }
    .dropdown a {
        display: block;
        padding: 10px;
        color: black;
        text-decoration: none;
    }
    .dropdown a:hover {
        background: #eee;
    }
    .profile-wrapper { position: relative; }
    .upload-section {
        max-width: 800px;
        margin: 2rem auto;
        padding: 2rem;
        background: white;
        border-radius: 12px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        text-align: center;
    }
    .upload-section h2 { margin-bottom: 1rem; }
    .message { margin-top: 1rem; color: green; font-weight: bold; }
    .error { color: red; }
    .nav-link {
        color: white;
    }
    .nav-link:hover{
        color:black;
        background: var(--light-blue);
    }
    .nav-menu{
        flex: content;
        justify-content: end;
        margin-right: 10px;
    }
  </style>
</head>
<body>
  <nav class="navbar">
    <div class="nav-container">
        
      <a href="user_dashboard.php" class="brand"><i class="fas fa-magic"></i> BG Remover Pro</a>
        <ul class="nav-menu">
            <li><a class="nav-link" href="index.php">Home</a></li>
            <li><a class="nav-link" href="about.php">About</a></li>
        </ul>
      <div class="profile-wrapper">
        <div class="profile-circle" onclick="toggleDropdown()"><?= htmlspecialchars($initials) ?></div>
        <div class="dropdown" id="profileDropdown">
          <a href="user_uploads_dashboard.php">Dashboard</a>
          <a href="settings.php">Settings</a>
          <a href="./server/logout.php">Logout</a>
        </div>
      </div>
    </div>
  </nav>

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

          <div class="preview-container" id="previewContainer">
              <div class="preview-title">Image Preview</div>
              <img id="imagePreview" class="image-preview" src="" alt="Preview">
          </div>

          <button id="uploadButton" class="btn-upload" disabled>
              <i class="fas fa-upload"></i> Upload Image
          </button>

          <div id="message" class="message"></div>
      </div>
  </section>

  <script>
    // Profile dropdown functionality
    function toggleDropdown() {
        document.getElementById("profileDropdown").style.display =
            document.getElementById("profileDropdown").style.display === "block" ? "none" : "block";
    }
    window.onclick = function(event) {
        if (!event.target.closest('.profile-wrapper')) {
            document.getElementById("profileDropdown").style.display = "none";
        }
    }

    // Upload functionality (copied from your working example)
    document.addEventListener('DOMContentLoaded', function () {
        const uploadArea = document.getElementById('uploadArea');
        const fileInput = document.getElementById('fileInput');
        const uploadButton = document.getElementById('uploadButton');
        const previewContainer = document.getElementById('previewContainer');
        const imagePreview = document.getElementById('imagePreview');
        const messageDiv = document.getElementById('message');

        // Click on upload area to trigger file input
        uploadArea.addEventListener('click', () => {
            fileInput.click();
        });

        // Drag and drop functionality
        uploadArea.addEventListener('dragover', (e) => {
            e.preventDefault();
            uploadArea.classList.add('dragover');
        });

        uploadArea.addEventListener('dragleave', () => {
            uploadArea.classList.remove('dragover');
        });

        uploadArea.addEventListener('drop', (e) => {
            e.preventDefault();
            uploadArea.classList.remove('dragover');

            if (e.dataTransfer.files.length) {
                fileInput.files = e.dataTransfer.files;
                handleFileSelection();
            }
        });

        // File input change event
        fileInput.addEventListener('change', handleFileSelection);

        // Upload button click event
        uploadButton.addEventListener('click', uploadImage);

        function handleFileSelection() {
            const file = fileInput.files[0];

            if (file) {
                // Validate file type
                if (!file.type.match('image.*')) {
                    showMessage('Please select a valid image file (JPG, PNG, WEBP).', 'error');
                    resetForm();
                    return;
                }

                // Validate file size (10MB max)
                if (file.size > 10 * 1024 * 1024) {
                    showMessage('File size must be less than 10MB.', 'error');
                    resetForm();
                    return;
                }

                // Show preview
                const reader = new FileReader();
                reader.onload = function (e) {
                    imagePreview.src = e.target.result;
                    previewContainer.style.display = 'block';
                    uploadButton.disabled = false;
                };
                reader.readAsDataURL(file);
            }
        }

        function uploadImage() {
            const file = fileInput.files[0];
            if (!file) return;

            const formData = new FormData();
            formData.append('image', file);

            // Disable upload button during upload
            uploadButton.disabled = true;
            uploadButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Uploading...';

            // Send to server
            fetch('upload.php', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showMessage(data.message, 'success');
                        resetForm();
                    } else {
                        showMessage(data.message, 'error');
                        uploadButton.disabled = false;
                        uploadButton.innerHTML = '<i class="fas fa-upload"></i> Upload Image';
                    }
                })
                .catch(error => {
                    showMessage('Upload failed: ' + error, 'error');
                    uploadButton.disabled = false;
                    uploadButton.innerHTML = '<i class="fas fa-upload"></i> Upload Image';
                });
        }

        function showMessage(message, type) {
            messageDiv.textContent = message;
            messageDiv.className = `message ${type}`;
            messageDiv.style.display = 'block';

            // Auto hide after 5 seconds
            setTimeout(() => {
                messageDiv.style.display = 'none';
            }, 5000);
        }

        function resetForm() {
            fileInput.value = '';
            previewContainer.style.display = 'none';
            uploadButton.disabled = true;
            uploadButton.innerHTML = '<i class="fas fa-upload"></i> Upload Image';
        }
    });
  </script>
</body>
</html>