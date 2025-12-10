<?php
require_once '../config.php';
require_once '../includes/auth.php';

// Jika sudah login, redirect ke admin
requireGuest();

$error = isset($_SESSION['error']) ? $_SESSION['error'] : '';
$username = isset($_SESSION['login_username']) ? $_SESSION['login_username'] : '';

// Clear error session setelah ditampilkan
unset($_SESSION['error']);
unset($_SESSION['login_username']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin - Apotek Sehat</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* ===== VARIABLES ===== */
        :root {
            --primary: #10b981;
            --primary-dark: #059669;
            --primary-light: #d1fae5;
            --secondary: #3b82f6;
            --accent: #f59e0b;
            --danger: #ef4444;
            --success: #10b981;
            --warning: #f59e0b;
            --dark: #1f2937;
            --light: #f9fafb;
            --gray: #6b7280;
            --gray-light: #e5e7eb;
        }

        /* ===== RESET & BASE ===== */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', sans-serif;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: #f8fafc;
            color: var(--dark);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
        }

        /* ===== LOGIN CONTAINER ===== */
        .login-container {
            display: flex;
            width: 100%;
            max-width: 1000px;
            min-height: 600px;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            background: white;
        }

        /* ===== LEFT SIDE - ILLUSTRATION ===== */
        .login-left {
            flex: 1;
            background: linear-gradient(135deg, #10b981 0%, #0d9488 100%);
            padding: 50px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            color: white;
            position: relative;
            overflow: hidden;
        }

        .login-left::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -20%;
            width: 300px;
            height: 300px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
        }

        .login-left::after {
            content: '';
            position: absolute;
            bottom: -30%;
            left: -10%;
            width: 200px;
            height: 200px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 50%;
        }

        .login-logo {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 40px;
            position: relative;
            z-index: 1;
        }

        .login-logo i {
            font-size: 2.5rem;
            color: #ffd700;
        }

        .login-logo h1 {
            font-size: 1.8rem;
            font-weight: 700;
        }

        .login-logo span {
            color: #ffd700;
        }

        .login-illustration {
            position: relative;
            z-index: 1;
            margin-top: 30px;
        }

        .login-illustration h2 {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 15px;
            line-height: 1.3;
        }

        .login-illustration p {
            font-size: 1rem;
            opacity: 0.9;
            margin-bottom: 30px;
            line-height: 1.6;
        }

        .features-list {
            list-style: none;
            margin-top: 30px;
        }

        .features-list li {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 15px;
            font-size: 0.95rem;
        }

        .features-list i {
            color: #ffd700;
            font-size: 1.1rem;
        }

        /* ===== RIGHT SIDE - FORM ===== */
        .login-right {
            flex: 1;
            padding: 60px 50px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .login-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .login-header i {
            font-size: 3.5rem;
            color: var(--primary);
            margin-bottom: 15px;
        }

        .login-header h2 {
            font-size: 2rem;
            color: var(--dark);
            margin-bottom: 10px;
            font-weight: 700;
        }

        .login-header p {
            color: var(--gray);
            font-size: 1rem;
        }

        /* ===== FORM STYLES ===== */
        .login-form {
            width: 100%;
            max-width: 400px;
            margin: 0 auto;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            color: var(--dark);
            font-weight: 500;
            font-size: 0.95rem;
        }

        .input-group {
            position: relative;
            border-radius: 12px;
            overflow: hidden;
            border: 2px solid var(--gray-light);
            transition: border-color 0.3s;
        }

        .input-group:focus-within {
            border-color: var(--primary);
        }

        .input-icon {
            position: absolute;
            left: 0;
            top: 0;
            height: 100%;
            width: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--gray);
            background: var(--light);
            border-right: 2px solid var(--gray-light);
        }

        .input-group input {
            width: 100%;
            padding: 15px 15px 15px 65px;
            border: none;
            outline: none;
            font-size: 1rem;
            color: var(--dark);
            background: white;
        }

        .input-group input::placeholder {
            color: #9ca3af;
        }

        .password-toggle {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--gray);
            cursor: pointer;
            font-size: 1.1rem;
            transition: color 0.3s;
        }

        .password-toggle:hover {
            color: var(--primary);
        }

        /* ===== ERROR MESSAGE ===== */
        .alert {
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            font-size: 0.95rem;
            border-left: 4px solid;
            animation: slideIn 0.3s ease;
        }

        .alert-danger {
            background: #fee2e2;
            color: #991b1b;
            border-color: var(--danger);
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* ===== BUTTON ===== */
        .btn-login {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-top: 10px;
        }

        .btn-login:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(16, 185, 129, 0.3);
        }

        .btn-login:active {
            transform: translateY(-1px);
        }

        /* ===== BACK LINK ===== */
        .back-link {
            text-align: center;
            margin-top: 30px;
        }

        .back-link a {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
            transition: gap 0.3s;
            padding: 10px 20px;
            border-radius: 8px;
            background: var(--primary-light);
        }

        .back-link a:hover {
            gap: 12px;
            background: #a7f3d0;
        }

        /* ===== FOOTER ===== */
        .login-footer {
            text-align: center;
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid var(--gray-light);
            color: var(--gray);
            font-size: 0.85rem;
        }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 768px) {
            .login-container {
                flex-direction: column;
                min-height: auto;
            }
            
            .login-left {
                padding: 30px;
                min-height: 300px;
            }
            
            .login-right {
                padding: 40px 30px;
            }
            
            .login-header h2 {
                font-size: 1.8rem;
            }
        }

        @media (max-width: 480px) {
            body {
                padding: 10px;
            }
            
            .login-left,
            .login-right {
                padding: 25px;
            }
            
            .login-header i {
                font-size: 2.5rem;
            }
            
            .login-header h2 {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <!-- Left Side - Illustration & Info -->
        <div class="login-left">
            <div class="login-logo">
                <i class="fas fa-heartbeat"></i>
                <h1>Apotek<span>Sehat</span></h1>
            </div>
            
            <div class="login-illustration">
                <h2>Selamat Datang Admin</h2>
                <p>Masuk ke sistem admin untuk mengelola data apotek, obat, transaksi, dan pegawai.</p>
                
                <ul class="features-list">
                    <li>
                        <i class="fas fa-check-circle"></i>
                        <span>Kelola data obat & stok</span>
                    </li>
                    <li>
                        <i class="fas fa-check-circle"></i>
                        <span>Monitor transaksi penjualan</span>
                    </li>
                    <li>
                        <i class="fas fa-check-circle"></i>
                        <span>Kelola data dokter konsultasi</span>
                    </li>
                    <li>
                        <i class="fas fa-check-circle"></i>
                        <span>Kelola data pegawai admin</span>
                    </li>
                </ul>
            </div>
        </div>
        
        <!-- Right Side - Login Form -->
        <div class="login-right">
            <div class="login-header">
                <i class="fas fa-user-shield"></i>
                <h2>Login Admin</h2>
                <p>Masukkan kredensial Anda untuk melanjutkan</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="process_login.php" class="login-form">
                <div class="form-group">
                    <label for="username" class="form-label">Username</label>
                    <div class="input-group">
                        <div class="input-icon">
                            <i class="fas fa-user"></i>
                        </div>
                        <input type="text" id="username" name="username" 
                               value="<?php echo htmlspecialchars($username); ?>" 
                               required placeholder="Masukkan username Anda">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="password" class="form-label">Password</label>
                    <div class="input-group">
                        <div class="input-icon">
                            <i class="fas fa-lock"></i>
                        </div>
                        <input type="password" id="password" name="password" 
                               required placeholder="Masukkan password Anda">
                        <button type="button" class="password-toggle" id="togglePassword">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                
                <button type="submit" class="btn-login">
                    <i class="fas fa-sign-in-alt"></i>
                    Masuk ke Sistem
                </button>
            </form>
            
            <div class="back-link">
                <a href="<?php echo $base_url; ?>/index.php">
                    <i class="fas fa-arrow-left"></i>
                    Kembali ke Beranda
                </a>
            </div>
            
            <div class="login-footer">
                <p>© 2024 Apotek Sehat. Hak Cipta Dilindungi.</p>
            </div>
        </div>
    </div>

    <script>
        // Toggle Password Visibility
        document.getElementById('togglePassword').addEventListener('click', function() {
            const passwordInput = document.getElementById('password');
            const icon = this.querySelector('i');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });

        // Enter key submits form
        document.getElementById('password').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                document.querySelector('.login-form').submit();
            }
        });
    </script>
</body>
</html>