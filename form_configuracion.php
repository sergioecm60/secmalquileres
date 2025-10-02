<?php
require_once 'check_auth.php';
require_once 'config.php';

// Solo los administradores pueden acceder
if (!esAdmin()) {
    header('Location: index.php');
    exit();
}

// Procesar el formulario de actualización
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $stmt = $pdo->prepare("UPDATE empresa_configuracion SET 
                                nombre = ?,
                                direccion = ?,
                                telefono = ?,
                                email = ?,
                                cuit = ?
                                WHERE id = 1");
        $stmt->execute([
            $_POST['nombre'],
            $_POST['direccion'],
            $_POST['telefono'],
            $_POST['email'],
            $_POST['cuit']
        ]);

        $mensaje = "Datos de la empresa actualizados correctamente.";
        $tipo_mensaje = "success";
        
        // Recargar los datos en la sesión o en las variables globales si es necesario
        // Por simplicidad, los cambios se verán en la próxima carga de página que incluya config.php
        header('Location: form_configuracion.php?success=1');
        exit();

    } catch (PDOException $e) {
        $mensaje = "Error al actualizar los datos: " . $e->getMessage();
        $tipo_mensaje = "error";
    }
}

// Obtener datos actuales de la empresa para mostrar en el formulario
$stmt = $pdo->query("SELECT * FROM empresa_configuracion WHERE id = 1");
$empresa = $stmt->fetch();

if (isset($_GET['success'])) {
    $mensaje = "Datos de la empresa actualizados correctamente.";
    $tipo_mensaje = "success";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuración de la Empresa</title>
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>
    <div class="container">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
            <h1>⚙️ Configuración de la Empresa</h1>
            <a href="index.php" class="btn btn-secondary">↩️ Volver al Menú</a>
        </div>

        <?php if (isset($mensaje)): ?>
            <div class="mensaje <?php echo $tipo_mensaje; ?>">
                <?php echo htmlspecialchars($mensaje); ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <form method="POST" action="">
                <div class="grid-2">
                    <div class="form-group">
                        <label for="nombre">Nombre de la Inmobiliaria / Propietario</label>
                        <input type="text" id="nombre" name="nombre" value="<?= htmlspecialchars($empresa['nombre'] ?? '') ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="cuit">CUIT / CUIL</label>
                        <input type="text" id="cuit" name="cuit" value="<?= htmlspecialchars($empresa['cuit'] ?? '') ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label for="direccion">Dirección</label>
                    <input type="text" id="direccion" name="direccion" value="<?= htmlspecialchars($empresa['direccion'] ?? '') ?>">
                </div>
                <div class="grid-2">
                    <div class="form-group">
                        <label for="telefono">Teléfono</label>
                        <input type="text" id="telefono" name="telefono" value="<?= htmlspecialchars($empresa['telefono'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label for="email">Email de Contacto</label>
                        <input type="email" id="email" name="email" value="<?= htmlspecialchars($empresa['email'] ?? '') ?>">
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">💾 Guardar Cambios</button>
            </form>
        </div>
    </div>
</body>
</html>