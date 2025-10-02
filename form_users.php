<?php
require_once 'check_auth.php';
require_once 'config.php';

// Solo los administradores pueden acceder a esta página
if (!esAdmin()) {
    header('Location: index.php');
    exit();
}

$mensaje = '';
$tipo_mensaje = '';

// Procesar formulario para crear o editar usuario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre_completo = trim($_POST['nombre_completo']);
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $rol = $_POST['rol'];
    $id = $_POST['id'] ?? null;

    try {
        if ($id) {
            // Editar usuario
            if (!empty($password)) {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET nombre_completo = ?, username = ?, email = ?, password = ?, rol = ? WHERE id = ?");
                $stmt->execute([$nombre_completo, $username, $email, $hashed_password, $rol, $id]);
            } else {
                $stmt = $pdo->prepare("UPDATE users SET nombre_completo = ?, username = ?, email = ?, rol = ? WHERE id = ?");
                $stmt->execute([$nombre_completo, $username, $email, $rol, $id]);
            }
            $mensaje = "Usuario actualizado exitosamente.";
        } else {
            // Crear nuevo usuario
            if (empty($password)) {
                throw new Exception("La contraseña es obligatoria para nuevos usuarios.");
            }
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (nombre_completo, username, email, password, rol) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$nombre_completo, $username, $email, $hashed_password, $rol]);
            $mensaje = "Usuario creado exitosamente.";
        }
        $tipo_mensaje = "success";
    } catch (Exception $e) {
        $mensaje = "Error: " . $e->getMessage();
        $tipo_mensaje = "error";
    }
}

// Eliminar usuario
if (isset($_GET['eliminar'])) {
    $id_eliminar = $_GET['eliminar'];
    // Prevenir que el admin se elimine a sí mismo
    if ($id_eliminar == $_SESSION['user_id']) {
        $mensaje = "Error: No puedes eliminar tu propia cuenta.";
        $tipo_mensaje = "error";
    } else {
        try {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$id_eliminar]);
            $mensaje = "Usuario eliminado exitosamente.";
            $tipo_mensaje = "success";
        } catch (PDOException $e) {
            $mensaje = "Error al eliminar el usuario: " . $e->getMessage();
            $tipo_mensaje = "error";
        }
    }
}

// Obtener datos para editar
$usuario_a_editar = null;
if (isset($_GET['editar'])) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_GET['editar']]);
    $usuario_a_editar = $stmt->fetch();
}

// Obtener lista de usuarios
$usuarios = $pdo->query("SELECT * FROM users ORDER BY id DESC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Usuarios</title>
    <link rel="stylesheet" href="assets/css/styles.css"> <!-- Asumiendo que tendrás un CSS central -->
    <style>
        body { font-family: Arial, sans-serif; background: #f4f4f4; padding: 20px; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1, h2 { color: #333; margin-bottom: 20px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input, select { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; }
        .btn { padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; display: inline-block; }
        .btn-primary { background: #007bff; color: white; }
        .btn-secondary { background: #6c757d; color: white; }
        .btn-danger { background: #dc3545; color: white; font-size: 12px; padding: 5px 10px; }
        .btn-warning { background: #ffc107; color: black; font-size: 12px; padding: 5px 10px; }
        .mensaje { padding: 15px; margin-bottom: 20px; border-radius: 4px; }
        .mensaje.success { background: #d4edda; color: #155724; }
        .mensaje.error { background: #f8d7da; color: #721c24; }
        table { width: 100%; border-collapse: collapse; margin-top: 30px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f2f2f2; }
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <h1>⚙️ Gestión de Usuarios</h1>
            <a href="index.php" class="btn btn-secondary">↩️ Volver al Menú</a>
        </div>

        <?php if ($mensaje): ?>
            <div class="mensaje <?php echo $tipo_mensaje; ?>"><?php echo htmlspecialchars($mensaje); ?></div>
        <?php endif; ?>

        <h2><?php echo $usuario_a_editar ? 'Editar Usuario' : 'Crear Nuevo Usuario'; ?></h2>
        <form method="POST" action="form_users.php">
            <input type="hidden" name="id" value="<?php echo $usuario_a_editar['id'] ?? ''; ?>">
            <div class="grid-2">
                <div class="form-group">
                    <label for="nombre_completo">Nombre Completo</label>
                    <input type="text" name="nombre_completo" required value="<?php echo htmlspecialchars($usuario_a_editar['nombre_completo'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label for="username">Nombre de Usuario</label>
                    <input type="text" name="username" required value="<?php echo htmlspecialchars($usuario_a_editar['username'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" name="email" required value="<?php echo htmlspecialchars($usuario_a_editar['email'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label for="password">Contraseña</label>
                    <input type="password" name="password" <?php echo $usuario_a_editar ? '' : 'required'; ?> placeholder="<?php echo $usuario_a_editar ? 'Dejar en blanco para no cambiar' : ''; ?>">
                </div>
                <div class="form-group">
                    <label for="rol">Rol</label>
                    <select name="rol" required>
                        <option value="usuario" <?php echo (isset($usuario_a_editar) && $usuario_a_editar['rol'] == 'usuario') ? 'selected' : ''; ?>>Usuario</option>
                        <option value="admin" <?php echo (isset($usuario_a_editar) && $usuario_a_editar['rol'] == 'admin') ? 'selected' : ''; ?>>Administrador</option>
                    </select>
                </div>
            </div>
            <button type="submit" class="btn btn-primary"><?php echo $usuario_a_editar ? 'Actualizar Usuario' : 'Crear Usuario'; ?></button>
            <?php if ($usuario_a_editar): ?>
                <a href="form_users.php" class="btn btn-secondary">Cancelar Edición</a>
            <?php endif; ?>
        </form>

        <h2>Lista de Usuarios</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre Completo</th>
                    <th>Usuario</th>
                    <th>Email</th>
                    <th>Rol</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($usuarios as $usuario): ?>
                <tr>
                    <td><?php echo $usuario['id']; ?></td>
                    <td><?php echo htmlspecialchars($usuario['nombre_completo']); ?></td>
                    <td><?php echo htmlspecialchars($usuario['username']); ?></td>
                    <td><?php echo htmlspecialchars($usuario['email']); ?></td>
                    <td><?php echo ucfirst($usuario['rol']); ?></td>
                    <td>
                        <a href="?editar=<?php echo $usuario['id']; ?>" class="btn btn-warning">Editar</a>
                        <a href="?eliminar=<?php echo $usuario['id']; ?>" class="btn btn-danger" onclick="return confirm('¿Está seguro de que desea eliminar a este usuario?');">Eliminar</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>