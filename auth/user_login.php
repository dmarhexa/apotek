<?php
require_once '../config.php';

// Jika sudah login user, redirect ke dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: " . $base_url . "/screen/dashboard/");
    exit();
}

$error = isset($_SESSION['error']) ? $_SESSION['error'] : '';
$success = isset($_SESSION['success']) ? $_SESSION['success'] : '';
$email = isset($_SESSION['login_email']) ? $_SESSION['login_email'] : '';

unset($_SESSION['error']);
unset($_SESSION['success']);
unset($_SESSION['login_email']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Pelanggan - Apotek Sehat</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* ===== VARIABLES (Same as Admin) ===== */
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
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); /* Blue for User */
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
            padding: 60px 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .login-header i {
            font-size: 3rem;
            color: var(--secondary); /* Blue */
            margin-bottom: 15px;
        }

        .login-header h2 {
            font-size: 1.8rem;
            color: var(--dark);
            margin-bottom: 10px;
            font-weight: 700;
        }

        .login-header p {
            color: var(--gray);
            font-size: 0.95rem;
        }

        /* ===== FORM STYLES ===== */
        .login-form {
            width: 100%;
            max-width: 400px;
            margin: 0 auto;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            color: var(--dark);
            font-weight: 500;
            font-size: 0.9rem;
        }

        .input-group {
            position: relative;
            border-radius: 12px;
            overflow: hidden;
            border: 2px solid var(--gray-light);
            transition: border-color 0.3s;
        }

        .input-group:focus-within {
            border-color: var(--secondary);
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
            padding: 12px 15px 12px 65px;
            border: none;
            outline: none;
            font-size: 1rem;
            color: var(--dark);
            background: white;
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
            color: var(--secondary);
        }

        /* ===== MESSAGES ===== */
        .alert {
            padding: 12px 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 0.9rem;
            border-left: 4px solid;
            animation: slideIn 0.3s ease;
        }

        .alert-danger {
            background: #fee2e2;
            color: #991b1b;
            border-color: var(--danger);
        }

        .alert-success {
            background: #dcfce7;
            color: #166534;
            border-color: var(--success);
        }

        @keyframes slideIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* ===== BUTTON ===== */
        .btn-login {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
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
            box-shadow: 0 10px 25px rgba(59, 130, 246, 0.3);
        }

        /* ===== LINKS ===== */
        .auth-links {
            text-align: center;
            margin-top: 25px;
            font-size: 0.9rem;
        }
        
        .auth-links a {
            color: var(--secondary);
            text-decoration: none;
            font-weight: 600;
        }
        
        .auth-links a:hover {
            text-decoration: underline;
        }

        .back-link {
            text-align: center;
            margin-top: 15px;
        }

        .back-link a {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            color: var(--gray);
            text-decoration: none;
            font-size: 0.9rem;
            transition: color 0.3s;
        }

        .back-link a:hover {
            color: var(--secondary);
        }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 768px) {
            .login-container { flex-direction: column; min-height: auto; }
            .login-left { padding: 30px; min-height: 250px; }
            .login-right { padding: 40px 30px; }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <!-- Left Side - Illustration -->
        <div class="login-left">
            <div class="login-logo">
                <i class="fas fa-heartbeat"></i>
                <h1>Apotek<span>Sehat</span></h1>
            </div>
            
            <div class="login-illustration">
                <h2>Selamat Datang</h2>
                <p>Nikmati kemudahan layanan kesehatan dalam genggaman Anda.</p>
                
                <ul class="features-list">
                    <li><i class="fas fa-star"></i> <span>Beri ulasan layanan kami</span></li>
                    <li><i class="fas fa-shopping-cart"></i> <span>Riwayat transaksi lengkap</span></li>
                    <li><i class="fas fa-user-md"></i> <span>Konsultasi dokter online</span></li>
                </ul>
            </div>
        </div>
        
        <!-- Right Side - Login Form -->
        <div class="login-right">
            <div class="login-header">
                <i class="fas fa-user-circle"></i>
                <h2>Login Pelanggan</h2>
                <p>Masuk ke akun Anda</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="process_user_login.php" class="login-form">
                <div class="form-group">
                    <label for="email" class="form-label">Email</label>
                    <div class="input-group">
                        <div class="input-icon"><i class="fas fa-envelope"></i></div>
                        <input type="email" id="email" name="email" 
                               value="<?php echo htmlspecialchars($email); ?>" 
                               required placeholder="nama@email.com">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="password" class="form-label">Password</label>
                    <div class="input-group">
                        <div class="input-icon"><i class="fas fa-lock"></i></div>
                        <input type="password" id="password" name="password" 
                               required placeholder="Masukkan password Anda">
                        <button type="button" class="password-toggle" id="togglePassword">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                
                <button type="submit" class="btn-login">
                    <i class="fas fa-sign-in-alt"></i> Masuk Sekarang
                </button>
            </form>
            
            <div class="auth-links">
                Belum punya akun? <a href="user_register.php">Daftar disini</a>
            </div>
            
            <div class="back-link">
                <a href="<?php echo $base_url; ?>/index.php">
                    <i class="fas fa-arrow-left"></i> Kembali ke Beranda
                </a>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('togglePassword').addEventListener('click', function() {
            const passwordInput = document.getElementById('password');
            const icon = this.querySelector('i');
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                icon.classList.replace('fa-eye-slash', 'fa-eye');
            }
        });
    </script>
</body>
</html>
