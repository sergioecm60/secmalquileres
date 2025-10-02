<?php
// Verificación de autenticación mejorada con seguridad adicional

// Configuración de seguridad de sesión
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // Cambiar a 1 si usas HTTPS

session_start();
// Verificar si existe la sesión del usuario
if (!isset($_SESSION['user_id'])) {
    // Guardar la URL actual para redirigir después del login
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    
    header('Location: login.php');
    exit();
}

// Verificar timeout de sesión (30 minutos de inactividad)
$timeout_duration = 1800; // 30 minutos en segundos

if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout_duration) {
    // Sesión expirada
    session_unset();
    session_destroy();
    header('Location: login.php?timeout=1');
    exit();
}

// Actualizar timestamp de última actividad
$_SESSION['last_activity'] = time();

// Función para actualizar último acceso en la base de datos (opcional)
function actualizarUltimoAcceso($pdo, $user_id) {
    try {
        $stmt = $pdo->prepare("UPDATE users SET ultimo_acceso = NOW() WHERE id = ?");
        $stmt->execute([$user_id]);
    } catch(PDOException $e) {
        error_log("Error actualizando último acceso: " . $e->getMessage());
    }
}

// Función helper para obtener el nombre del usuario
function obtenerNombreUsuario() {
    return isset($_SESSION['nombre_completo']) ? $_SESSION['nombre_completo'] : $_SESSION['username'];
}
