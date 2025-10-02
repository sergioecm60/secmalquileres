<?php
// config.php - Archivo de configuración de la base de datos
// Configuración de la conexión a MySQL

$host = 'localhost';
$db = 'gestion_alquileres';
$user = 'root';  // Cambiar según tu configuración
$pass = '';      // Cambiar según tu configuración

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Error de conexión a la base de datos: " . $e->getMessage());
}

// Cargar configuración de la empresa desde la base de datos
$stmt = $pdo->query("SELECT * FROM empresa_configuracion WHERE id = 1");
$empresa_config = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$empresa_config) {
    // Fallback por si la tabla está vacía
    $empresa_config = ['nombre' => 'Mi Inmobiliaria', 'direccion' => 'Mi Dirección', 'telefono' => 'Mi Teléfono', 'cuit' => 'Mi CUIT', 'email' => 'mi@email.com'];
}

// Definir variables globales para los datos de la empresa que se usarán en los recibos
$propietario_nombre = $empresa_config['nombre'];
$propietario_direccion = $empresa_config['direccion'];
$propietario_telefono = $empresa_config['telefono'];
$propietario_cuit = $empresa_config['cuit'];
$propietario_email = $empresa_config['email'];

// Función auxiliar para formatear moneda
function formatearMoneda($monto) {
    return '$' . number_format($monto, 2, ',', '.');
}

// Función auxiliar para formatear fecha
function formatearFecha($fecha) {
    if (!$fecha) return '-';
    return date('d/m/Y', strtotime($fecha));
}

// Array de meses en español
$meses_es = [
    1 => 'enero', 2 => 'febrero', 3 => 'marzo', 4 => 'abril',
    5 => 'mayo', 6 => 'junio', 7 => 'julio', 8 => 'agosto',
    9 => 'septiembre', 10 => 'octubre', 11 => 'noviembre', 12 => 'diciembre'
];
?>