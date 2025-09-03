<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Image Upload</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        
        .container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            width: 100%;
            max-width: 500px;
            overflow: hidden;
        }
        
        .header {
            background: #3498db;
            color: white;
            padding: 25px;
            text-align: center;
        }
        
        .header h1 {
            font-size: 24px;
            margin-bottom: 5px;
        }
        
        .header p {
            opacity: 0.9;
        }
        
        .upload-container {
            padding: 30px;
        }
        
        .upload-area {
            border: 2px dashed #3498db;
            border-radius: 12px;
            padding: 30px;
            text-align: center;
            margin-bottom: 20px;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .upload-area:hover, .upload-area.dragover {
            background-color: #e8f4ff;
            border-color: #2980b9;
        }
        
        .upload-icon {
            font-size: 50px;
            color: #3498db;
            margin-bottom: 15px;
        }
        
        .upload-text {
            font-size: 18px;
            margin-bottom: 10px;
            color: #2c3e50;
        }
        
        .upload-subtext {
            color: #7f8c8d;
            font-size: 14px;
        }
        
        .file-input {
            display: none;
        }
        
        .preview-container {
            margin-top: 25px;
            text-align: center;
            display: none;
        }
        
        .preview-title {
            font-size: 16px;
            color: #2c3e50;
            margin-bottom: 10px;
        }
        
        .image-preview {
            max-width: 100%;
            max-height: 200px;
            border-radius: 8px;
            margin-top: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        
        .btn-upload {
            background: #3498db;
            color: white;
            border: none;
            padding: 14px 20px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
            transition: background 0.3s ease;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
        }
        
        .btn-upload:hover {
            background: #2980b9;
        }
        
        .btn-upload:disabled {
            background: #bdc3c7;
            cursor: not-allowed;
        }
        
        .message {
            padding: 15px;
            border-radius: 8px;
            margin-top: 20px;
            text-align: center;
            display: none;
        }
        
        .success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .upload-info {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-top: 20px;
            font-size: 14px;
            color: #6c757d;
        }
        
        .upload-info ul {
            padding-left: 20px;
            margin-top: 10px;
        }
        
        .upload-info li {
            margin-bottom: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-cloud-upload-alt"></i> Image Upload</h1>
            <p>Upload your images to our database</p>
        </div>
        
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
            
            <div class="upload-info">
                <strong>How it works:</strong>
                <ul>
                    <li>Select an image or drag it to the upload area</li>
                    <li>Preview will be shown before uploading</li>
                    <li>Click the Upload button to save to database</li>
                    <li>Image info will be stored in MySQL "uploads" table</li>
                </ul>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
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
                    reader.onload = function(e) {
    document.querySelectorAll('.preview-target').forEach(img => {
        img.src = e.target.result;
    });
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