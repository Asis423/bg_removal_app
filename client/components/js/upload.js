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
  formData.append("image", file);

  fetch("upload.php", {
    method: "POST",
    body: formData
  })
  .then(res => res.json())
  .catch(err => {
    console.error("Error:", err);
    alert("Server error.");
  });
}

});


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
        
        container.style.display = 'block';
    };
    
    // If image is already loaded (cached)
    if (img.complete) {
        img.onload();
    }
}