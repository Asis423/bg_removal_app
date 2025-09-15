        function handleLogin(event) {
            event.preventDefault();
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;

            fetch('login.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `email=${encodeURIComponent(email)}&password=${encodeURIComponent(password)}`
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        currentUser = data.user;
                        updateAuthUI();
                        if (data.user.role === 'admin') {
                            showPage('admin');
                            alert('Welcome back, Admin!');
                        } else {
                            showPage('home');
                            alert('Login successful!');
                        }
                    } else {
                        alert(data.message || 'Login failed');
                    }
                })
                .catch(err => {
                    console.error(err);
                    alert('Server error during login.');
                });
        }

        function handleRegister(event) {
            event.preventDefault();
            const name = document.getElementById('regName').value;
            const email = document.getElementById('regEmail').value;
            const password = document.getElementById('regPassword').value;

            fetch('signup.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `name=${encodeURIComponent(name)}&email=${encodeURIComponent(email)}&password=${encodeURIComponent(password)}`
            })
                .then(response => {
                    if (response.redirected) {
                        window.location.href = response.url;
                    } else {
                        return response.text();
                    }
                })
                .catch(err => {
                    console.error(err);
                    alert('Server error during registration.');
                });
        }

        