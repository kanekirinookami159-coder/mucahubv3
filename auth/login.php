<?php
include "../config/config.php";
$errorType = isset($_GET['error']) ? trim($_GET['error']) : '';
$errorMessage = '';
if ($errorType === 'empty') {
    $errorMessage = 'Please fill in all fields.';
} elseif ($errorType === 'invalid') {
    $errorMessage = 'Invalid email or password. Please try again.';
} elseif ($errorType === 'expired') {
    $errorMessage = 'Your verification code expired. Please sign in again.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MUCAHUB - Login</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { min-height: 100vh; display:flex; align-items:center; justify-content:center; background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%); overflow:hidden; }
        .bg-shapes { position:fixed; inset:0; z-index:0; overflow:hidden; }
        .shape { position:absolute; border-radius:50%; opacity:.12; animation:float 22s infinite ease-in-out; }
        .shape:nth-child(1){width:420px;height:420px;background:#556b2f;top:-120px;left:-100px;}
        .shape:nth-child(2){width:280px;height:280px;background:#6b8e23;bottom:-70px;right:-70px;animation-delay:-4s;}
        .shape:nth-child(3){width:180px;height:180px;background:#8fbc8f;top:45%;left:52%;animation-delay:-9s;}
        @keyframes float{0%,100%{transform:translate(0,0) rotate(0deg);}25%{transform:translate(45px,55px) rotate(90deg);}50%{transform:translate(0,105px) rotate(180deg);}75%{transform:translate(-45px,55px) rotate(270deg);}}
        .container { position:relative; z-index:1; width:min(520px,95%); padding:36px; background:rgba(255,255,255,.05); border:1px solid rgba(255,255,255,.12); border-radius:24px; box-shadow:0 30px 80px rgba(0,0,0,.25); backdrop-filter:blur(18px); }
        .logo-section { text-align:center; margin-bottom:28px; }
        .logo-section img { width:96px; animation:glow 2.5s ease-in-out infinite alternate; }
        @keyframes glow{from{filter:drop-shadow(0 0 20px rgba(85,107,47,.9));}to{filter:drop-shadow(0 0 45px rgba(143,188,143,1));}}
        .logo-section h1{font-size:38px;color:#fff;letter-spacing:4px;margin-bottom:6px;}
        .logo-section p{color:rgba(255,255,255,.72);letter-spacing:1px;}
        .login-card{display:grid;gap:18px;padding:30px;background:rgba(17,27,59,.9);border-radius:22px;border:1px solid rgba(255,255,255,.1);}
        .login-card h2{font-size:30px;color:#fff;}
        .login-card p{color:rgba(255,255,255,.75);}
        .error-box{min-height:24px;color:#ffb3b3;background:rgba(255,66,66,.12);border:1px solid rgba(255,66,66,.22);border-radius:12px;padding:12px 14px;display:<?= $errorMessage ? 'block' : 'none' ?>;}
        .input-group{display:flex;align-items:center;gap:14px;background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.12);border-radius:14px;padding:14px 16px;}
        .input-group input{width:100%;border:none;outline:none;background:transparent;color:#f8f8f8;font-size:15px;}
        .input-group i{color:rgba(255,255,255,.6);font-size:18px;min-width:24px;text-align:center;}
        .submit-btn{width:100%;border:none;outline:none;border-radius:14px;padding:16px;font-size:17px;color:#fff;background:linear-gradient(135deg,#556b2f,#78a74b);cursor:pointer;transition:.25s;}
        .submit-btn:hover{transform:translateY(-2px);box-shadow:0 18px 35px rgba(85,107,47,.28);}
        .help-text{color:rgba(255,255,255,.65);font-size:13px;text-align:center;}
        @media(max-width:640px){.container{padding:24px;}.logo-section h1{font-size:32px;}}    
    </style>
</head>
<body>
    <div class="bg-shapes">
        <div class="shape"></div>
        <div class="shape"></div>
        <div class="shape"></div>
    </div>
    <div class="container">
        <div class="logo-section">
            <img src="../assets/images/mucalogo.png" alt="MUCAHUB Logo">
            <h1>MUCAHUB</h1>
            <p>MUCAHUB Portal</p>
        </div>
        <div class="login-card">
            <h2>Sign In</h2>
            <p>Enter your email and password to continue.</p>
            <div class="error-box"><?= htmlspecialchars($errorMessage) ?></div>
            <form method="POST" action="login_handler.php">
                <div class="input-group">
                    <i class="fas fa-envelope"></i>
                    <input type="email" name="email" placeholder="Email" required autocomplete="username">
                </div>
                <div class="input-group">
                    <i class="fas fa-lock"></i>
                    <input type="password" name="password" placeholder="Password" required autocomplete="current-password">
                </div>
                <button type="submit" class="submit-btn">Log In</button>
            </form>
            <p class="help-text">A verification QR code will be generated after your credentials are validated.</p>
        </div>
    </div>
</body>
</html>
