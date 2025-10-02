<?php
// Incluir configuración de base de datos
require_once 'check_auth.php';
require_once 'config.php';

// Procesar formulario de creación
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'guardar') {
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

// Filtros
$filtro = $_GET['filtro'] ?? 'activas';
$busqueda = $_GET['busqueda'] ?? '';

$sql = "SELECT * FROM propiedades WHERE 1=1";

if ($filtro === 'activas') {
    $sql .= " AND activo = 1";
} elseif ($filtro === 'inactivas') {
    $sql .= " AND activo = 0";
}

if (!empty($busqueda)) {
    $sql .= " AND (direccion LIKE ? OR departamento LIKE ? OR localidad LIKE ? OR codigo LIKE ?)";
}

$sql .= " ORDER BY id DESC";

$stmt = $pdo->prepare($sql);

if (!empty($busqueda)) {
    $busquedaParam = "%$busqueda%";
    $stmt->execute([$busquedaParam, $busquedaParam, $busquedaParam, $busquedaParam]);
} else {
    $stmt->execute();
}

$propiedades = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
        .container { max-width: 1400px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #333; margin-bottom: 30px; border-bottom: 3px solid #28a745; padding-bottom: 10px; }
        h2 { color: #555; margin: 30px 0 20px; }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; color: #555; }
        input, select, textarea { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px; }
        textarea { min-height: 100px; resize: vertical; }
        input:focus, select:focus, textarea:focus { outline: none; border-color: #28a745; }
        .btn { padding: 8px 15px; margin: 3px; border: none; border-radius: 4px; cursor: pointer; font-size: 14px; transition: all 0.3s; }
        .btn:hover { opacity: 0.8; }
        .btn-success { background: #28a745; color: white; }
        .btn-primary { background: #007bff; color: white; }
        .btn-editar { background: #007bff; color: white; }
        .btn-desactivar { background: #ffc107; color: black; }
        .btn-activar { background: #28a745; color: white; }
        .btn-eliminar { background: #dc3545; color: white; }
        .btn-secondary { background: #6c757d; color: white; text-decoration: none; display: inline-block; }
        .mensaje { padding: 15px; margin-bottom: 20px; border-radius: 4px; }
        .mensaje.success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .mensaje.error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #28a745; color: white; font-weight: bold; }
        tr:hover { background: #f5f5f5; }
        .grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; }
        .badge { display: inline-block; padding: 5px 10px; border-radius: 3px; font-size: 12px; }
        .badge-success { background: #28a745; color: white; }
        .badge-danger { background: #dc3545; color: white; }
        .filtros {
            margin: 20px 0;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 5px;
            display: flex;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap;
        }
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.6);
        }
        .modal-content {
            background: white;
            margin: 50px auto;
            padding: 0;
            width: 90%;
            max-width: 700px;
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
        }
        .modal-header {
            background: #28a745;
            color: white;
            padding: 15px 20px;
            border-radius: 8px 8px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .modal-header h2 { margin: 0; color: white; border: none; padding: 0; }
        .modal-body { padding: 20px; }
        .modal-footer {
            padding: 15px 20px;
            background: #f8f9fa;
            text-align: right;
            border-radius: 0 0 8px 8px;
        }
        .close {
            color: white;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        .close:hover { color: #f8d7da; }
        .top-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🏠 Gestión de Propiedades</h1>
        
        <?php if (isset($mensaje)): ?>
            <div class="mensaje <?php echo $tipo_mensaje; ?>">
                <?php echo $mensaje; ?>
            </div>
        <?php endif; ?>

        <div class="top-actions">
            <a href="index.php" class="btn btn-secondary">← Volver al inicio</a>
            <button onclick="nuevaPropiedad()" class="btn btn-primary">➕ Nueva Propiedad</button>
        </div>

        <!-- Filtros y búsqueda -->
        <div class="filtros">
            <form method="GET" style="display: flex; gap: 15px; align-items: center; flex-wrap: wrap; width: 100%;">
                <div>
                    <label>Estado:</label>
                    <select name="filtro" onchange="this.form.submit()">
                        <option value="activas" <?php echo $filtro === 'activas' ? 'selected' : '' ?>>✅ Activas</option>
                        <option value="inactivas" <?php echo $filtro === 'inactivas' ? 'selected' : '' ?>>⏸️ Inactivas</option>
                        <option value="todas" <?php echo $filtro === 'todas' ? 'selected' : '' ?>>📊 Todas</option>
                    </select>
                </div>
                
                <div style="flex-grow: 1;">
                    <label>Buscar:</label>
                    <input type="text" name="busqueda" value="<?php echo htmlspecialchars($busqueda) ?>" 
                           placeholder="Dirección, departamento o localidad...">
                </div>
                
                <div>
                    <button type="submit" class="btn btn-primary">🔍 Buscar</button>
                    <a href="form_propiedades.php" class="btn btn-secondary">🔄 Limpiar</a>
                </div>
            </form>
        </div>

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
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($propiedades) > 0): ?>
                    <?php foreach ($propiedades as $prop): ?>
                    <tr>
                        <td><?php echo $prop['id'] ?></td>
                        <td><?php echo htmlspecialchars($prop['codigo']) ?></td>
                        <td><?php echo htmlspecialchars($prop['direccion']) ?></td>
                        <td><?php echo htmlspecialchars($prop['departamento']) ?></td>
                        <td><?php echo htmlspecialchars($prop['localidad']) ?></td>
                        <td>
                            <?php if ($prop['activo']): ?>
                                <span class="badge badge-success">Activa</span>
                            <?php else: ?>
                                <span class="badge badge-danger">Inactiva</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <button onclick="editarPropiedad(<?php echo $prop['id'] ?>)" 
                                    class="btn btn-editar" title="Editar">✏️</button>
                            
                            <?php if ($prop['activo']): ?>
                                <button onclick="desactivarPropiedad(<?php echo $prop['id'] ?>)" 
                                        class="btn btn-desactivar" title="Desactivar">⏸️</button>
                            <?php else: ?>
                                <button onclick="activarPropiedad(<?php echo $prop['id'] ?>)" 
                                        class="btn btn-activar" title="Activar">▶️</button>
                            <?php endif; ?>
                            
                            <button onclick="eliminarPropiedad(<?php echo $prop['id'] ?>)" 
                                    class="btn btn-eliminar" title="Eliminar">🗑️</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 20px;">
                            No se encontraron propiedades
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        
        <div style="margin-top: 20px; color: #6c757d;">
            Total: <strong><?php echo count($propiedades) ?></strong> propiedad(es)
        </div>
    </div>

    <!-- Modal para editar/crear -->
    <div id="modalPropiedad" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitulo">Editar Propiedad</h2>
                <span class="close" onclick="cerrarModal()">&times;</span>
            </div>
            <form id="formPropiedad" onsubmit="guardarPropiedad(event)">
                <div class="modal-body">
                    <input type="hidden" id="id" name="id">
                    <input type="hidden" id="accion" name="action" value="editar">
                    
                    <div class="grid-3">
                        <div class="form-group">
                            <label>Código</label>
                            <input type="text" id="codigo" name="codigo" placeholder="PROP-001">
                        </div>
                        
                        <div class="form-group">
                            <label>Dirección *</label>
                            <input type="text" id="direccion" name="direccion" required placeholder="Artigas 1159">
                        </div>
                        
                        <div class="form-group">
                            <label>Departamento</label>
                            <input type="text" id="departamento" name="departamento" placeholder="A, B, 1A">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Localidad *</label>
                        <input type="text" id="localidad" name="localidad" required placeholder="General Rodriguez">
                    </div>
                    
                    <div class="form-group">
                        <label>Descripción</label>
                        <textarea id="descripcion" name="descripcion" rows="3" placeholder="Características de la propiedad..."></textarea>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" onclick="cerrarModal()" class="btn btn-secondary">Cancelar</button>
                    <button type="submit" class="btn btn-success">💾 Guardar</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function nuevaPropiedad() {
            document.getElementById('modalTitulo').textContent = 'Nueva Propiedad';
            document.getElementById('formPropiedad').reset();
            document.getElementById('id').value = '';
            document.getElementById('accion').value = 'crear';
            document.getElementById('modalPropiedad').style.display = 'block';
        }
        
        function editarPropiedad(id) {
            fetch(`api/api_propiedades.php?action=obtener&id=${id}`)
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        const prop = data.data;
                        document.getElementById('modalTitulo').textContent = 'Editar Propiedad';
                        document.getElementById('id').value = prop.id;
                        document.getElementById('accion').value = 'editar';
                        document.getElementById('codigo').value = prop.codigo || '';
                        document.getElementById('direccion').value = prop.direccion;
                        document.getElementById('departamento').value = prop.departamento || '';
                        document.getElementById('localidad').value = prop.localidad;
                        document.getElementById('descripcion').value = prop.descripcion || '';
                        
                        document.getElementById('modalPropiedad').style.display = 'block';
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(err => alert('Error al cargar datos'));
        }
        
        function cerrarModal() {
            document.getElementById('modalPropiedad').style.display = 'none';
        }
        
        function guardarPropiedad(event) {
            event.preventDefault();
            
            const formData = new FormData(document.getElementById('formPropiedad'));
            
            fetch('api/api_propiedades.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                alert(data.message);
                if (data.success) {
                    cerrarModal();
                    location.reload();
                }
            })
            .catch(err => alert('Error al guardar'));
        }
        
        function cambiarEstadoPropiedad(id, accion) {
            const msj = accion === 'desactivar' ? '¿Desactivar esta propiedad?' : '¿Activar esta propiedad?';
            if (!confirm(msj)) return;

            const formData = new FormData();
            formData.append('action', accion);
            formData.append('id', id);

            fetch('api/api_propiedades.php', { method: 'POST', body: formData })
                .then(res => res.json())
                .then(data => {
                    alert(data.message);
                    if (data.success) location.reload();
                });
        }

        function desactivarPropiedad(id) {
            cambiarEstadoPropiedad(id, 'desactivar');
        }

        function activarPropiedad(id) {
            cambiarEstadoPropiedad(id, 'activar');
        }
        
        function eliminarPropiedad(id) {
            if (!confirm('¿ELIMINAR esta propiedad? Esto la marcará como inactiva.')) return;
            
            const formData = new FormData();
            formData.append('action', 'eliminar');
            formData.append('id', id);
            
            fetch('api/api_propiedades.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                alert(data.message);
                if (data.success) location.reload();
            });
        }
        
        window.onclick = function(event) {
            const modal = document.getElementById('modalPropiedad');
            if (event.target == modal) {
                cerrarModal();
            }
        }
    </script>
</body>
</html>