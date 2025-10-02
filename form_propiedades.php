<?php
// Incluir configuración de base de datos
require_once 'check_auth.php';
require_once 'config.php';

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion'])) {
    if ($_POST['accion'] === 'guardar') {
        try {
            $stmt = $pdo->prepare("INSERT INTO propiedades (codigo, direccion, departamento, localidad, descripcion) 
                                   VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([
                $_POST['codigo'],
                $_POST['direccion'],
                $_POST['departamento'],
                $_POST['localidad'],
                $_POST['descripcion']
            ]);
            $mensaje = "Propiedad registrada exitosamente con ID: " . $pdo->lastInsertId();
            $tipo_mensaje = "success";
        } catch(PDOException $e) {
            $mensaje = "Error: " . $e->getMessage();
            $tipo_mensaje = "error";
        }
    }
}

// Obtener propiedades
$propiedades = $pdo->query("SELECT * FROM propiedades WHERE activo = 1 ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Propiedades</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f4f4f4; padding: 20px; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #333; margin-bottom: 30px; border-bottom: 3px solid #28a745; padding-bottom: 10px; }
        h2 { color: #555; margin: 30px 0 20px; }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; color: #555; }
        input, select, textarea { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px; }
        textarea { min-height: 100px; resize: vertical; }
        input:focus, select:focus, textarea:focus { outline: none; border-color: #28a745; }
        .btn { padding: 12px 30px; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; transition: all 0.3s; }
        .btn-success { background: #28a745; color: white; }
        .btn-success:hover { background: #218838; }
        .mensaje { padding: 15px; margin-bottom: 20px; border-radius: 4px; }
        .mensaje.success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .mensaje.error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #28a745; color: white; font-weight: bold; }
        tr:hover { background: #f5f5f5; }
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; }
        .badge { display: inline-block; padding: 5px 10px; border-radius: 3px; font-size: 12px; }
        .badge-success { background: #28a745; color: white; }
        .btn-secondary { background: #6c757d; color: white; text-decoration: none; display: inline-block; }
        .btn-secondary:hover { background: #5a6268; }
    </style>
</head>
<body>
    <div class="container">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
            <h1>🏠 Gestión de Propiedades</h1>
            <a href="index.php" class="btn btn-secondary">↩️ Volver al Menú</a>
        </div>


        <?php if (isset($mensaje)): ?>
            <div class="mensaje <?php echo $tipo_mensaje; ?>">
                <?php echo $mensaje; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <input type="hidden" name="accion" value="guardar">
            
            <div class="grid-3">
                <div class="form-group">
                    <label for="codigo">Código de Propiedad</label>
                    <input type="text" id="codigo" name="codigo" placeholder="Ej: PROP-001">
                </div>

                <div class="form-group">
                    <label for="direccion">Dirección *</label>
                    <input type="text" id="direccion" name="direccion" required placeholder="Ej: Artigas 1159">
                </div>

                <div class="form-group">
                    <label for="departamento">Departamento</label>
                    <input type="text" id="departamento" name="departamento" placeholder="Ej: A, B, 1A">
                </div>
            </div>

            <div class="form-group">
                <label for="localidad">Localidad *</label>
                <input type="text" id="localidad" name="localidad" required placeholder="Ej: General Rodriguez">
            </div>

            <div class="form-group">
                <label for="descripcion">Descripción</label>
                <textarea id="descripcion" name="descripcion" placeholder="Características de la propiedad..."></textarea>
            </div>

            <button type="submit" class="btn btn-success">💾 Guardar Propiedad</button>
        </form>

        <h2>Lista de Propiedades Registradas</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Código</th>
                    <th>Dirección</th>
                    <th>Depto</th>
                    <th>Localidad</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($propiedades as $prop): ?>
                <tr>
                    <td><?php echo $prop['id']; ?></td>
                    <td><?php echo htmlspecialchars($prop['codigo']); ?></td>
                    <td><?php echo htmlspecialchars($prop['direccion']); ?></td>
                    <td><?php echo htmlspecialchars($prop['departamento']); ?></td>
                    <td><?php echo htmlspecialchars($prop['localidad']); ?></td>
                    <td><span class="badge badge-success">Activa</span></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>