function scrollToUpload() {
    showPage('home'); // if using page sections
    setTimeout(() => {
        document.querySelector('.upload-section').scrollIntoView({ behavior: 'smooth' });
    }, 100);
}
