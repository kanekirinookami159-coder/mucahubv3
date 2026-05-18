<?php header('Location: ../auth/login.php'); exit; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MUCAHUB - Instructor Login</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
            overflow: hidden;
        }

        /* Animated Background */
        .bg-shapes {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            z-index: 0;
        }

        .shape {
            position: absolute;
            border-radius: 50%;
            opacity: 0.1;
            animation: float 20s infinite ease-in-out;
        }

        .shape:nth-child(1) {
            width: 400px;
            height: 400px;
            background: #556b2f;
            top: -100px;
            left: -100px;
            animation-delay: 0s;
        }

        .shape:nth-child(2) {
            width: 300px;
            height: 300px;
            background: #6b8e23;
            bottom: -50px;
            right: -50px;
            animation-delay: -5s;
        }

        .shape:nth-child(3) {
            width: 200px;
            height: 200px;
            background: #8fbc8f;
            top: 50%;
            left: 50%;
            animation-delay: -10s;
        }

        @keyframes float {
            0%, 100% { transform: translate(0, 0) rotate(0deg); }
            25% { transform: translate(50px, 50px) rotate(90deg); }
            50% { transform: translate(0, 100px) rotate(180deg); }
            75% { transform: translate(-50px, 50px) rotate(270deg); }
        }

        /* Main Container */
        .container {
            position: relative;
            z-index: 1;
            width: 900px;
            max-width: 95%;
            min-height: 500px;
            display: flex;
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(20px);
            border-radius: 24px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.3);
            overflow: hidden;
        }

        /* Logo Panel */
        .logo-panel {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 40px;
        }

        .logo-section {
            text-align: center;
        }

        .logo-section img {
            width: 150px;
            height: auto;
            margin-bottom: 20px;
            filter: drop-shadow(0 0 20px rgba(85, 107, 47, 1)) drop-shadow(0 0 40px rgba(107, 142, 35, 0.9)) drop-shadow(0 0 60px rgba(143, 188, 143, 0.7)) drop-shadow(0 0 80px rgba(107, 142, 35, 0.5));
            animation: glow 2s ease-in-out infinite alternate;
        }

        @keyframes glow {
            from {
                filter: drop-shadow(0 0 20px rgba(85, 107, 47, 1)) drop-shadow(0 0 40px rgba(107, 142, 35, 0.9)) drop-shadow(0 0 60px rgba(143, 188, 143, 0.6));
            }
            to {
                filter: drop-shadow(0 0 35px rgba(107, 142, 35, 1)) drop-shadow(0 0 70px rgba(143, 188, 143, 0.9)) drop-shadow(0 0 100px rgba(85, 107, 47, 0.7)) drop-shadow(0 0 130px rgba(107, 142, 35, 0.5));
            }
        }

        .logo-section h1 {
            font-size: 48px;
            font-weight: 800;
            color: white;
            text-shadow: 0 0 30px rgba(85, 107, 47, 0.5);
            letter-spacing: 4px;
        }

        .logo-section p {
            color: white;
            margin-top: 10px;
            font-size: 14px;
            letter-spacing: 2px;
        }

        .decorative-line {
            width: 100px;
            height: 3px;
            background: linear-gradient(90deg, transparent, #556b2f, transparent);
            margin: 20px auto;
        }

        /* Login Form Panel */
        .login-panel {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 40px;
            background: rgba(255, 255, 255, 0.03);
        }

        .login-form {
            width: 100%;
            max-width: 320px;
        }

        .login-form h2 {
            color: white;
            font-size: 28px;
            margin-bottom: 10px;
            text-align: center;
        }

        .login-form .role-label {
            color: #556b2f;
            font-size: 14px;
            text-align: center;
            margin-bottom: 30px;
            display: block;
        }

        .input-group {
            position: relative;
            margin-bottom: 25px;
        }

        .input-group input {
            width: 100%;
            padding: 16px 20px 16px 50px;
            background: rgba(255, 255, 255, 0.08);
            border: 2px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            color: white;
            font-size: 16px;
            transition: all 0.3s;
            outline: none;
        }

        .input-group.password-group input {
            padding-right: 50px;
        }

        .input-group.password-group .toggle-password {
            position: absolute;
            right: 18px;
            left: auto;
            top: 50%;
            transform: translateY(-50%);
            color: rgba(255, 255, 255, 0.6);
            font-size: 18px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .input-group input::placeholder {
            color: rgba(255, 255, 255, 0.4);
        }

        .input-group input:focus {
            border-color: #556b2f;
            background: rgba(255, 255, 255, 0.12);
            box-shadow: 0 0 20px rgba(85, 107, 47, 0.2);
        }

        .input-group i {
            position: absolute;
            left: 18px;
            top: 50%;
            transform: translateY(-50%);
            color: rgba(255, 255, 255, 0.4);
            font-size: 18px;
            transition: all 0.3s;
        }

        .input-group input:focus + i {
            color: #556b2f;
        }

        .submit-btn {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #556b2f, #6b8e23);
            border: none;
            border-radius: 12px;
            color: white;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
        }

        .submit-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            transition: left 0.5s;
        }

        .submit-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(85, 107, 47, 0.4);
        }

        .submit-btn:hover::before {
            left: 100%;
        }

        /* Error Message */
        .error-msg {
            background: rgba(255, 82, 82, 0.2);
            border: 1px solid rgba(255, 82, 82, 0.3);
            color: #ff6b6b;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            display: none;
            animation: shake 0.5s;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-10px); }
            75% { transform: translateX(10px); }
        }

        .error-msg.show {
            display: block;
        }

        /* Back Link */
        .back-link {
            text-align: center;
            margin-top: 20px;
        }

        .back-link a {
            color: #556b2f;
            text-decoration: none;
            font-size: 14px;
            transition: all 0.3s;
        }

        .back-link a:hover {
            color: #6b8e23;
            text-decoration: underline;
        }

        /* Forgot Password */
        .forgot-password {
            text-align: center;
            margin-top: 15px;
        }

        .forgot-password a {
            color: #8fbc8f;
            text-decoration: none;
            font-size: 12px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .forgot-password a:hover {
            color: #6b8e23;
            text-decoration: underline;
        }

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            z-index: 3000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.6);
            animation: fadeIn 0.3s;
        }

        .modal.show {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .modal-content {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 16px;
            padding: 30px;
            width: 90%;
            max-width: 350px;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.3);
            animation: slideIn 0.3s;
        }

        @keyframes slideIn {
            from {
                transform: translateY(-50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .modal-header h3 {
            color: white;
            font-size: 20px;
            margin: 0;
        }

        .modal-close {
            background: none;
            border: none;
            color: rgba(255, 255, 255, 0.6);
            font-size: 24px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .modal-close:hover {
            color: #556b2f;
        }

        .modal-body {
            margin-bottom: 20px;
        }

        .modal-body p {
            color: rgba(255, 255, 255, 0.7);
            font-size: 14px;
            margin-bottom: 15px;
        }

        .modal-body input {
            width: 100%;
            padding: 12px 15px;
            background: rgba(255, 255, 255, 0.08);
            border: 2px solid rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            color: white;
            font-size: 14px;
            transition: all 0.3s;
            box-sizing: border-box;
        }

        .modal-body input::placeholder {
            color: rgba(255, 255, 255, 0.4);
        }

        .modal-body input:focus {
            border-color: #556b2f;
            background: rgba(255, 255, 255, 0.12);
            box-shadow: 0 0 20px rgba(85, 107, 47, 0.2);
            outline: none;
        }

        .modal-footer {
            display: flex;
            gap: 10px;
        }

        .modal-footer button {
            flex: 1;
            padding: 12px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .modal-footer .btn-cancel {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .modal-footer .btn-cancel:hover {
            background: rgba(255, 255, 255, 0.15);
        }

        .modal-footer .btn-submit {
            background: linear-gradient(135deg, #556b2f, #6b8e23);
            color: white;
        }

        .modal-footer .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(85, 107, 47, 0.4);
        }

        /* Lockout Message */
        .lockout-msg {
            background: rgba(255, 82, 82, 0.2);
            border: 1px solid rgba(255, 82, 82, 0.3);
            color: #ff6b6b;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 13px;
            text-align: center;
            display: none;
        }

        .lockout-msg.show {
            display: block;
        }

        /* Particle Animation */
        .particles {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 0;
        }

        .particle {
            position: absolute;
            width: 4px;
            height: 4px;
            background: #556b2f;
            border-radius: 50%;
            opacity: 0.3;
            animation: particle-float 15s infinite;
        }

        @keyframes particle-float {
            0% { transform: translateY(100vh) rotate(0deg); opacity: 0; }
            10% { opacity: 0.3; }
            90% { opacity: 0.3; }
            100% { transform: translateY(-100vh) rotate(720deg); opacity: 0; }
        }

        /* Responsive */
        @media (max-width: 768px) {
            .container {
                flex-direction: column;
                min-height: auto;
                height: auto;
            }

            .logo-panel {
                padding: 30px 20px;
            }

            .login-panel {
                padding: 30px 20px;
            }

            .logo-section h1 {
                font-size: 36px;
            }
        }
    </style>
</head>
<body>
    <!-- Background Shapes -->
    <div class="bg-shapes">
        <div class="shape"></div>
        <div class="shape"></div>
        <div class="shape"></div>
    </div>

    <!-- Particles -->
    <div class="particles" id="particles"></div>

    <div class="container">
        <!-- Logo Panel -->
        <div class="logo-panel">
            <div class="logo-section">
                <img src="../assets/images/mucalogo.png" alt="MUCAHUB Logo">
                <h1>MUCAHUB</h1>
                <div class="decorative-line"></div>
                <p>Learning Management System</p>
            </div>
        </div>

        <!-- Login Panel -->
        <div class="login-panel">
            <form class="login-form" id="loginForm" method="POST" action="login_handler.php">
                <h2>Welcome Back</h2>
                <span class="role-label">Instructor Login</span>
                
                <div class="lockout-msg" id="lockoutMsg">
                    Too many login attempts. Please try again in <span id="lockoutTimer">5:00</span>
                </div>

                <div class="error-msg" id="errorMsg">
                    Invalid credentials. Please try again.
                </div>
                
                <div class="input-group">
                    <input type="email" name="email" id="emailInput" placeholder="Email" required>
                    <i class="fas fa-user"></i>
                </div>
                
                <div class="input-group password-group">
                    <input type="password" name="password" id="passwordInput" placeholder="Password" required autocomplete="current-password">
                    <i class="fas fa-lock"></i>
                    <i class="fas fa-eye toggle-password" id="togglePassword" title="Show password"></i>
                </div>
                
                <button type="submit" class="submit-btn" id="submitBtn">
                    <i class="fas fa-sign-in-alt"></i> Sign In
                </button>

                <div class="forgot-password">
                    <a onclick="openForgotPasswordModal()" id="forgotPasswordLink">Forgot Password?</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Forgot Password Modal -->
    <div class="modal" id="forgotPasswordModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Reset Password</h3>
                <button type="button" class="modal-close" onclick="closeForgotPasswordModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <p>Enter your email address and we'll send you a new password.</p>
                <input type="email" id="resetEmail" placeholder="Enter your email">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeForgotPasswordModal()">Cancel</button>
                <button type="button" class="btn-submit" onclick="sendPasswordReset()">Send Reset</button>
            </div>
        </div>
    </div>

    <script>
        const LOCKOUT_KEY = 'instructor_login_lockout';
        const ATTEMPTS_KEY = 'instructor_login_attempts';
        const LOCKOUT_TIME = 5 * 60 * 1000; // 5 minutes
        const MAX_ATTEMPTS = 5;

        // Create particles
        function createParticles() {
            const container = document.getElementById('particles');
            for (let i = 0; i < 20; i++) {
                const particle = document.createElement('div');
                particle.className = 'particle';
                particle.style.left = Math.random() * 100 + '%';
                particle.style.animationDelay = Math.random() * 15 + 's';
                particle.style.animationDuration = (15 + Math.random() * 10) + 's';
                container.appendChild(particle);
            }
        }
        
        createParticles();

        // Check if account is locked
        function checkLockout() {
            const lockoutTime = localStorage.getItem(LOCKOUT_KEY);
            if (lockoutTime) {
                const now = Date.now();
                if (now < parseInt(lockoutTime)) {
                    applyLockout();
                    return true;
                } else {
                    localStorage.removeItem(LOCKOUT_KEY);
                    localStorage.removeItem(ATTEMPTS_KEY);
                }
            }
            return false;
        }

        // Apply lockout state to UI
        function applyLockout() {
            const emailInput = document.getElementById('emailInput');
            const passwordInput = document.getElementById('passwordInput');
            const submitBtn = document.getElementById('submitBtn');
            const lockoutMsg = document.getElementById('lockoutMsg');
            const forgotLink = document.getElementById('forgotPasswordLink');

            emailInput.disabled = true;
            passwordInput.disabled = true;
            submitBtn.disabled = true;
            lockoutMsg.classList.add('show');
            forgotLink.style.pointerEvents = 'none';
            forgotLink.style.opacity = '0.5';

            startLockoutTimer();
        }

        // Remove lockout state from UI
        function removeLockout() {
            const emailInput = document.getElementById('emailInput');
            const passwordInput = document.getElementById('passwordInput');
            const submitBtn = document.getElementById('submitBtn');
            const lockoutMsg = document.getElementById('lockoutMsg');
            const forgotLink = document.getElementById('forgotPasswordLink');

            emailInput.disabled = false;
            passwordInput.disabled = false;
            submitBtn.disabled = false;
            lockoutMsg.classList.remove('show');
            forgotLink.style.pointerEvents = 'auto';
            forgotLink.style.opacity = '1';
        }

        // Start countdown timer
        function startLockoutTimer() {
            const lockoutTime = parseInt(localStorage.getItem(LOCKOUT_KEY));
            const timerElement = document.getElementById('lockoutTimer');

            const updateTimer = () => {
                const now = Date.now();
                const remaining = lockoutTime - now;

                if (remaining <= 0) {
                    localStorage.removeItem(LOCKOUT_KEY);
                    localStorage.removeItem(ATTEMPTS_KEY);
                    removeLockout();
                    timerElement.textContent = '5:00';
                    clearInterval(timerInterval);
                    return;
                }

                const minutes = Math.floor(remaining / 60000);
                const seconds = Math.floor((remaining % 60000) / 1000);
                timerElement.textContent = `${minutes}:${seconds.toString().padStart(2, '0')}`;
            };

            updateTimer();
            const timerInterval = setInterval(updateTimer, 1000);
        }

        // Increment login attempts
        function incrementAttempts() {
            let attempts = parseInt(localStorage.getItem(ATTEMPTS_KEY) || '0');
            attempts++;
            localStorage.setItem(ATTEMPTS_KEY, attempts);

            if (attempts >= MAX_ATTEMPTS) {
                const lockoutUntil = Date.now() + LOCKOUT_TIME;
                localStorage.setItem(LOCKOUT_KEY, lockoutUntil);
                applyLockout();
                return true;
            }
            return false;
        }

        // Handle form submission
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            e.preventDefault();

            if (checkLockout()) {
                return;
            }

            const email = document.getElementById('emailInput').value;
            const password = document.getElementById('passwordInput').value;

            if (email && password) {
                this.submit();
            } else {
                document.getElementById('errorMsg').textContent = 'Please fill in all fields.';
                document.getElementById('errorMsg').classList.add('show');
                setTimeout(() => {
                    document.getElementById('errorMsg').classList.remove('show');
                }, 3000);
            }
        });

        // Toggle password visibility
        document.getElementById('togglePassword').addEventListener('click', function() {
            const passwordInput = document.getElementById('passwordInput');
            const isPassword = passwordInput.type === 'password';
            passwordInput.type = isPassword ? 'text' : 'password';
            this.classList.toggle('fa-eye-slash', isPassword);
            this.classList.toggle('fa-eye', !isPassword);
            this.setAttribute('title', isPassword ? 'Hide password' : 'Show password');
        });

        // Forgot Password Modal Functions
        function openForgotPasswordModal() {
            if (!checkLockout()) {
                document.getElementById('forgotPasswordModal').classList.add('show');
                document.getElementById('resetEmail').value = document.getElementById('emailInput').value;
                document.getElementById('resetEmail').focus();
            }
        }

        function closeForgotPasswordModal() {
            document.getElementById('forgotPasswordModal').classList.remove('show');
        }

        function sendPasswordReset() {
            const email = document.getElementById('resetEmail').value.trim();
            if (!email) {
                alert('Please enter your email address');
                return;
            }

            const btn = event.target;
            btn.disabled = true;
            btn.textContent = 'Sending...';

            fetch('../api/password_reset_handler.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'role=instructor&email=' + encodeURIComponent(email)
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('HTTP Error: ' + response.status);
                }
                return response.json();
            })
            .then(data => {
                btn.disabled = false;
                btn.textContent = 'Send Reset';
                
                if (data.success) {
                    let message = data.message;
                    
                    // If password is in response (email not configured), show it
                    if (data.password) {
                        message += '\n\nTemporary Password: ' + data.password;
                        if (data.warning) {
                            message += '\n\n' + data.warning;
                        }
                    }
                    
                    alert(message);
                    closeForgotPasswordModal();
                    document.getElementById('emailInput').value = email;
                    document.getElementById('passwordInput').value = '';
                } else {
                    alert('Error: ' + (data.message || 'Failed to reset password'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                btn.disabled = false;
                btn.textContent = 'Send Reset';
                alert('Error sending reset request. Please try again later.');
            });
        }

        // Close modal when clicking outside
        window.addEventListener('click', function(event) {
            const modal = document.getElementById('forgotPasswordModal');
            if (event.target === modal) {
                closeForgotPasswordModal();
            }
        });

        // Check lockout on page load
        document.addEventListener('DOMContentLoaded', function() {
            checkLockout();
        });

        // Check for error in URL
        const urlParams = new URLSearchParams(window.location.search);

        if (urlParams.get('error') === 'invalid') {
            const isLocked = incrementAttempts();
            if (isLocked) {
                document.getElementById('lockoutMsg').classList.add('show');
            } else {
                const attempts = parseInt(localStorage.getItem(ATTEMPTS_KEY) || '0');
                const remaining = MAX_ATTEMPTS - attempts;
                document.getElementById('errorMsg').textContent = `Invalid email or password. ${remaining} attempt${remaining !== 1 ? 's' : ''} remaining.`;
                document.getElementById('errorMsg').classList.add('show');
                setTimeout(() => {
                    document.getElementById('errorMsg').classList.remove('show');
                }, 5000);
            }
        } else if (urlParams.get('error') === 'empty') {
            document.getElementById('errorMsg').textContent = 'Please fill in all fields.';
            document.getElementById('errorMsg').classList.add('show');
            setTimeout(() => {
                document.getElementById('errorMsg').classList.remove('show');
            }, 5000);
        }

        // Reset attempts on successful login (cleared in session)
        if (!urlParams.get('error')) {
            localStorage.removeItem(ATTEMPTS_KEY);
        }
    </script>
</body>
</html>
