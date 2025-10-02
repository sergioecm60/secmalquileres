<?php
session_start();
require 'config.php';

$error = '';

$success = '';

// Mensajes de logout o timeout
if (isset($_GET['logout'])) {
    $success = '✓ Has cerrado sesión correctamente.';
}
if (isset($_GET['timeout'])) {
    $error = 'Tu sesión ha expirado por inactividad. Por favor, inicia sesión nuevamente.';
}

// Si el usuario ya está logueado, redirigir al index
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_POST['username']) || empty($_POST['password']) || empty($_POST['captcha'])) {
        $error = 'Todos los campos son obligatorios.';
    } elseif (strtoupper($_POST['captcha']) !== $_SESSION['captcha_answer']) {
        $error = 'El código CAPTCHA es incorrecto.';
    } else {
        $username = trim($_POST['username']);
        $password = $_POST['password'];

        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND activo = 1");
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                session_regenerate_id(true);
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['nombre_completo'] = $user['nombre_completo'];
                $_SESSION['rol'] = $user['rol'];
                $_SESSION['last_activity'] = time();
                header('Location: index.php');
                exit();
            } else {
                $error = 'Nombre de usuario o contraseña incorrectos.';
            }
        } catch(PDOException $e) {
            $error = 'Error en el sistema. Intente nuevamente.';
            error_log("Error de login: " . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - Gestión de Alquileres</title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="assets/css/login.css">
</head>
<body class="login-page">
    <div class="login-wrapper">
        <div class="login-container">
            <div class="logo-section">
                <img src="assets/image/logo.png" alt="Logo SECM" class="logo-image">
                <h1>Bienvenido</h1>
                <p class="subtitle">SECM Gestión de Alquileres</p>
            </div>

            <?php if ($success): ?>
                <div class="success">
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="error">
                    ⚠️ <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="login.php">
                <div class="form-group">
                    <label for="username">Usuario</label>
                    <div class="input-wrapper">
                        <span class="input-icon">👤</span>
                        <input type="text" id="username" name="username" required autocomplete="username" placeholder="Ingrese su usuario">
                    </div>
                </div>

                <div class="form-group">
                    <label for="password">Contraseña</label>
                    <div class="input-wrapper">
                        <span class="input-icon">🔒</span>
                        <input type="password" id="password" name="password" required autocomplete="current-password" placeholder="Ingrese su contraseña">
                        <span class="password-toggle" onclick="togglePassword()">👁️</span>
                    </div>
                </div>

                <div class="form-group">
                    <label for="captcha">Código de Seguridad</label>
                    <div class="captcha-group">
                        <input type="text" id="captcha" name="captcha" required autocomplete="off" placeholder="Ingrese el código">
                        <img src="captcha.php?<?php echo time(); ?>" alt="CAPTCHA" id="captcha-image" onclick="refreshCaptcha()">
                        <span class="captcha-refresh" onclick="refreshCaptcha()" title="Recargar CAPTCHA">🔄</span>
                    </div>
                </div>

                <button type="submit" class="btn">🚀 Ingresar al Sistema</button>
            </form>

            <!-- El registro público ha sido deshabilitado -->
        </div>
    </div>

    <footer class="page-footer-fixed">
        <p>
            <strong>SECM Gestión de Alquileres</strong> | 
            By Sergio Cabrera | 
            Copyleft © 2025 | 
            <a href="licence.php">Licencia GNU GPL v3</a>
        </p>
        <p class="footer-contact">
            ¿Necesitas ayuda? 
            <a href="mailto:sergiomiers@gmail.com">📧 sergiomiers@gmail.com</a> | 
            <a href="https://wa.me/541167598452" target="_blank">💬 WhatsApp +54 11 6759-8452</a>
        </p>
    </footer>

    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggle = document.querySelector('.password-toggle');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggle.textContent = '🙈';
            } else {
                passwordInput.type = 'password';
                toggle.textContent = '👁️';
            }
        }

        function refreshCaptcha() {
            const img = document.getElementById('captcha-image');
            img.src = 'captcha.php?' + new Date().getTime();
        }

        // Limpiar campo de CAPTCHA cuando hay error
        <?php if ($error): ?>
            document.getElementById('captcha').value = '';
            document.getElementById('captcha').focus();
        <?php endif; ?>
    </script>
</body>
</html>
