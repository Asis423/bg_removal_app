document.addEventListener('DOMContentLoaded', () => {
  const uploadArea = document.getElementById('uploadArea');
  const fileInput = document.getElementById('fileInput');

  // Scroll to upload on clicking “Start Removing Backgrounds” button
  const startBtn = document.querySelector('.btn-primary');
  if (startBtn) {
    startBtn.addEventListener('click', (e) => {
      e.preventDefault();
      document.getElementById('upload').scrollIntoView({ behavior: 'smooth' });
    });
  }

  // Drag and drop listeners
  uploadArea.addEventListener('dragover', (e) => {
    e.preventDefault();
    uploadArea.classList.add('dragover');
  });

  uploadArea.addEventListener('dragleave', (e) => {
    e.preventDefault();
    uploadArea.classList.remove('dragover');
  });

  uploadArea.addEventListener('drop', (e) => {
    e.preventDefault();
    uploadArea.classList.remove('dragover');
    if (e.dataTransfer.files && e.dataTransfer.files.length > 0) {
      processFile(e.dataTransfer.files[0]);
      e.dataTransfer.clearData();
    }
  });

  // File input change listener
  fileInput.addEventListener('change', (e) => {
    if (e.target.files.length > 0) {
      processFile(e.target.files[0]);
    }
  });

  // File processing function
 function processFile(file) {
  if (!file.type.startsWith('image/')) {
    alert('Please select a valid image file.');
    return;
  }

  if (file.size > 10 * 1024 * 1024) {
    alert('File size must be less than 10MB.');
    return;
  }

  const formData = new FormData();
  formData.append("file", file);

  fetch("upload.php", {
    method: "POST",
    body: formData
  })
  .then(res => res.json())
  .then(data => {
    if (data.success) {
      alert("Upload successful!");
      console.log("Saved path:", data.saved_path);
    } else {
      alert("Upload failed: " + data.message);
    }
  })
  .catch(err => {
    console.error("Error:", err);
    alert("Server error.");
  });
}

});
