<?php
require_once 'check_auth.php'; // Cambiar de 'auth.php' a 'check_auth.php'
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Licencia - SECM</title>
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body class="licence-page">
<div class="container">
    <h1>📜 Licencia del Software</h1>
    <p><strong>SECM Gestión de Alquileres</strong></p>
    <p>Copyleft © 2025 Sergio Cabrera Miers</p>
    <pre><?php
echo htmlspecialchars(file_get_contents('licence.txt'));
    ?></pre>
    <a href="index.php" class="btn">← Volver al Sistema</a>
</div>
</body>
</html>