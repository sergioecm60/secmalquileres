<?php
require_once 'check_auth.php';
require_once 'config.php';

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion'])) {
    if ($_POST['accion'] === 'guardar') {
        try {
            $stmt = $pdo->prepare("INSERT INTO inquilinos (nombre_apellido, dni, telefono, email) 
                                   VALUES (?, ?, ?, ?)");
            $stmt->execute([
                $_POST['nombre_apellido'],
                $_POST['dni'],
                $_POST['telefono'],
                $_POST['email']
            ]);
            $mensaje = "Inquilino registrado exitosamente con ID: " . $pdo->lastInsertId();
            $tipo_mensaje = "success";
        } catch(PDOException $e) {
            $mensaje = "Error: " . $e->getMessage();
            $tipo_mensaje = "error";
        }
    }
}

// Obtener lista de inquilinos
$inquilinos = $pdo->query("SELECT * FROM inquilinos WHERE activo = 1 ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Inquilinos</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f4f4f4; padding: 20px; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #333; margin-bottom: 30px; border-bottom: 3px solid #007bff; padding-bottom: 10px; }
        h2 { color: #555; margin: 30px 0 20px; }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; color: #555; }
        input, select, textarea { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px; }
        input:focus, select:focus, textarea:focus { outline: none; border-color: #007bff; }
        .btn { padding: 12px 30px; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; transition: all 0.3s; }
        .btn-primary { background: #007bff; color: white; }
        .btn-primary:hover { background: #0056b3; }
        .mensaje { padding: 15px; margin-bottom: 20px; border-radius: 4px; }
        .mensaje.success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .mensaje.error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #007bff; color: white; font-weight: bold; }
        tr:hover { background: #f5f5f5; }
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .badge { display: inline-block; padding: 5px 10px; border-radius: 3px; font-size: 12px; }
        .badge-success { background: #28a745; color: white; }
    </style>
    <style>
        .btn-secondary { background: #6c757d; color: white; text-decoration: none; display: inline-block; }
        .btn-secondary:hover { background: #5a6268; }
    </style>
</head>
<body>
    <div class="container">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
            <h1>📋 Gestión de Inquilinos</h1>
            <a href="index.php" class="btn btn-secondary">↩️ Volver al Menú</a>
        </div>
        <?php if (isset($mensaje)): ?>
            <div class="mensaje <?php echo $tipo_mensaje; ?>">
                <?php echo $mensaje; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <input type="hidden" name="accion" value="guardar">
            
            <div class="grid-2">
                <div class="form-group">
                    <label for="nombre_apellido">Nombre y Apellido *</label>
                    <input type="text" id="nombre_apellido" name="nombre_apellido" required>
                </div>

                <div class="form-group">
                    <label for="dni">DNI *</label>
                    <input type="text" id="dni" name="dni" required>
                </div>

                <div class="form-group">
                    <label for="telefono">Teléfono</label>
                    <input type="text" id="telefono" name="telefono">
                </div>

                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email">
                </div>
            </div>

            <button type="submit" class="btn btn-primary">💾 Guardar Inquilino</button>
        </form>

        <h2>Lista de Inquilinos Registrados</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre y Apellido</th>
                    <th>DNI</th>
                    <th>Teléfono</th>
                    <th>Email</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($inquilinos as $inquilino): ?>
                <tr>
                    <td><?php echo $inquilino['id']; ?></td>
                    <td><?php echo htmlspecialchars($inquilino['nombre_apellido']); ?></td>
                    <td><?php echo htmlspecialchars($inquilino['dni']); ?></td>
                    <td><?php echo htmlspecialchars($inquilino['telefono']); ?></td>
                    <td><?php echo htmlspecialchars($inquilino['email']); ?></td>
                    <td><span class="badge badge-success">Activo</span></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>