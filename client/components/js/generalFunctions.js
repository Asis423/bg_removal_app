function scrollToUpload() {
    showPage('home'); // if using page sections
    setTimeout(() => {
        document.querySelector('.upload-section').scrollIntoView({ behavior: 'smooth' });
    }, 100);
}

function scrollToPreview() {
    showPage('home'); // if using page sections
    setTimeout(() => {
        document.querySelector('.process-steps').scrollIntoView({ behavior: 'smooth' });
    }, 100);
}

function showMessage(message, type) {
    // Create or find message element
    let messageDiv = document.getElementById('message');
    if (!messageDiv) {
        messageDiv = document.createElement('div');
        messageDiv.id = 'message';
        document.body.appendChild(messageDiv);
    }

    messageDiv.textContent = message;
    messageDiv.className = `message ${type}`;
    messageDiv.style.display = 'block';

    // Auto hide after 5 seconds
    setTimeout(() => {
        messageDiv.style.display = 'none';
    }, 5000);
}