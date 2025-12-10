<?php
require_once '../config.php';

// Jika sudah login user, redirect ke dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: " . $base_url . "/screen/dashboard/");
    exit();
}

$error = isset($_SESSION['error']) ? $_SESSION['error'] : '';
unset($_SESSION['error']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Akun - Apotek Sehat</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* ===== VARIABLES (Using the same logic as Login) ===== */
        :root {
            --primary: #10b981;
            --secondary: #3b82f6;
            --danger: #ef4444;
            --success: #10b981;
            --dark: #1f2937;
            --light: #f9fafb;
            --gray: #6b7280;
            --gray-light: #e5e7eb;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }

        body {
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .login-container {
            display: flex;
            width: 100%;
            max-width: 1100px; /* Slightly wider for register */
            min-height: 700px;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            background: white;
        }

        .login-left {
            flex: 1;
            background: linear-gradient(135deg, #8b5cf6 0%, #6d28d9 100%); /* Purple for Register */
            padding: 50px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            color: white;
            position: relative;
        }
        
        .login-left::before {
            content: ''; position: absolute; top: -50%; right: -20%;
            width: 300px; height: 300px; background: rgba(255,255,255,0.1); border-radius: 50%;
        }

        .login-logo { display: flex; align-items: center; gap: 15px; margin-bottom: 40px; z-index: 1; }
        .login-logo i { font-size: 2.5rem; color: #ffd700; }
        .login-logo h1 { font-size: 1.8rem; font-weight: 700; color: white; }
        .login-logo span { color: #ffd700; }

        .login-illustration { position: relative; z-index: 1; }
        .login-illustration h2 { font-size: 2rem; font-weight: 700; margin-bottom: 15px; }
        .login-illustration p { margin-bottom: 30px; opacity: 0.9; line-height: 1.6; }

        .login-right {
            flex: 1.2; /* Give more space to form */
            padding: 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .login-header { text-align: center; margin-bottom: 30px; }
        .login-header i { font-size: 2.5rem; color: #8b5cf6; margin-bottom: 10px; }
        .login-header h2 { font-size: 1.8rem; font-weight: 700; color: var(--dark); }

        .form-group { margin-bottom: 15px; }
        .form-label { display: block; margin-bottom: 8px; font-weight: 500; font-size: 0.9rem; color: var(--dark); }
        .input-group { position: relative; border: 2px solid var(--gray-light); border-radius: 12px; overflow: hidden; }
        .input-group:focus-within { border-color: #8b5cf6; }
        .input-icon { position: absolute; left: 0; top: 0; height: 100%; width: 45px; display: flex; align-items: center; justify-content: center; color: var(--gray); background: var(--light); border-right: 2px solid var(--gray-light); }
        .input-group input { width: 100%; padding: 12px 15px 12px 60px; border: none; outline: none; font-size: 0.95rem; }

        .btn-login {
            width: 100%; padding: 14px; background: linear-gradient(135deg, #8b5cf6 0%, #6d28d9 100%);
            color: white; border: none; border-radius: 12px; font-weight: 600; cursor: pointer;
            margin-top: 10px; transition: transform 0.2s;
        }
        .btn-login:hover { transform: translateY(-3px); }

        .auth-links { text-align: center; margin-top: 20px; font-size: 0.9rem; }
        .auth-links a { color: #8b5cf6; text-decoration: none; font-weight: 600; }

        .alert-danger { padding: 12px; background: #fee2e2; color: #991b1b; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid #ef4444; }

        @media (max-width: 768px) {
            .login-container { flex-direction: column; }
            .login-left { min-height: 200px; padding: 30px; }
            .login-right { padding: 30px; }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-left">
            <div class="login-logo">
                <i class="fas fa-heartbeat"></i>
                <h1>Apotek<span>Sehat</span></h1>
            </div>
            <div class="login-illustration">
                <h2>Buat Akun Baru</h2>
                <p>Bergabunglah dengan ribuan pelanggan lainnya dan nikmati layanan kesehatan terbaik.</p>
            </div>
        </div>

        <div class="login-right">
            <div class="login-header">
                <i class="fas fa-user-plus"></i>
                <h2>Registrasi</h2>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="process_user_register.php">
                <div class="form-group">
                    <label class="form-label">Nama Lengkap</label>
                    <div class="input-group">
                        <div class="input-icon"><i class="fas fa-user"></i></div>
                        <input type="text" name="nama_lengkap" required placeholder="Masukan nama lengkap">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Email</label>
                    <div class="input-group">
                        <div class="input-icon"><i class="fas fa-envelope"></i></div>
                        <input type="email" name="email" required placeholder="Masukan email aktif">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">No. Handphone</label>
                    <div class="input-group">
                        <div class="input-icon"><i class="fas fa-phone"></i></div>
                        <input type="tel" name="no_hp" required placeholder="Contoh: 08123456789">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Password</label>
                    <div class="input-group">
                        <div class="input-icon"><i class="fas fa-lock"></i></div>
                        <input type="password" name="password" required placeholder="Minimal 6 karakter" minlength="6">
                    </div>
                </div>

                <button type="submit" class="btn-login"><i class="fas fa-check-circle"></i> Daftar Sekarang</button>
            </form>

            <div class="auth-links">
                Sudah punya akun? <a href="user_login.php">Login disini</a>
            </div>
            
            <div style="text-align: center; margin-top: 15px;">
                <a href="<?php echo $base_url; ?>/index.php" style="color: #6b7280; text-decoration: none; font-size: 0.9rem;">
                    <i class="fas fa-arrow-left"></i> Kembali
                </a>
            </div>
        </div>
    </div>
</body>
</html>
