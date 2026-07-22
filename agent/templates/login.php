<?php // templates/login.php ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Kadad AI Agent - Authentication Terminal</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="login-body-layout">
    <div class="login-card-container">
        <h2>Kadad AI Agent</h2>
        <p class="subtitle-desc-text">Secure Cloud Agent Environment Access Framework</p>
        <div id="loginError" class="error-banner hidden-element" style="color:var(--accent-danger); margin-bottom:15px; font-size:0.9rem;"></div>
        <form id="loginForm">
            <div class="form-group-item">
                <label>Admin Username</label>
                <input type="text" id="username" required autocomplete="off" autofocus>
            </div>
            <div class="form-group-item">
                <label>Secure Password</label>
                <input type="password" id="password" required>
            </div>
            <button type="submit" class="btn-primary-action" style="width:100%; border-radius:4px;">Authenticate Identity Token</button>
        </form>
    </div>
    <script>
        document.getElementById('loginForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const errBox = document.getElementById('loginError');
            errBox.classList.add('hidden-element');

            const payload = {
                username: document.getElementById('username').value,
                password: document.getElementById('password').value
            };

            try {
                const res = await fetch('api/auth.php?action=login', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const data = await res.json();
                if (res.ok && data.success) {
                    window.location.href = 'index.php?route=dashboard';
                } else {
                    errBox.textContent = data.error || 'Authentication layer validation failure.';
                    errBox.classList.remove('hidden-element');
                }
            } catch (err) {
                errBox.textContent = 'Network communication pathway transmission failure.';
                errBox.classList.remove('hidden-element');
            }
        });
    </script>
</body>
</html>
