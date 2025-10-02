<?php
require_once 'check_auth.php';
require_once 'config.php';
 
// Filtros
$filtro = $_GET['filtro'] ?? 'activos';
$busqueda = $_GET['busqueda'] ?? '';
 
// Construir consulta
$sql = "SELECT * FROM inquilinos WHERE 1=1";
$params = [];
 
// Filtro por estado
if ($filtro === 'activos') {
    $sql .= " AND estado = 'activo'";
} elseif ($filtro === 'inactivos') {
    $sql .= " AND estado = 'inactivo'";
} elseif ($filtro === 'eliminados') {
    $sql .= " AND estado = 'eliminado'";
}
 
// Búsqueda
if (!empty($busqueda)) {
    $sql .= " AND (apellido LIKE :busqueda OR nombre LIKE :busqueda OR dni LIKE :busqueda)";
    $params[':busqueda'] = "%$busqueda%";
}
 
$sql .= " ORDER BY apellido, nombre";
 
// Ejecutar consulta
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$inquilinos = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Inquilinos</title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <style>
        .filtros { margin: 20px 0; padding: 15px; background: #f8f9fa; border-radius: 5px; display: flex; gap: 15px; align-items: center; flex-wrap: wrap; }
        .filtros label { font-weight: bold; margin-right: 5px; }
        .filtros input, .filtros select { padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th { background: #343a40; color: white; padding: 12px; text-align: left; font-weight: bold; }
        td { padding: 10px; border-bottom: 1px solid #ddd; }
        tr:hover { background: #f8f9fa; }
        .estado-badge { padding: 4px 10px; border-radius: 12px; font-size: 12px; font-weight: bold; display: inline-block; }
        .estado-activo { background: #d4edda; color: #155724; }
        .estado-inactivo { background: #fff3cd; color: #856404; }
        .estado-eliminado { background: #f8d7da; color: #721c24; }
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); }
        .modal-content { background: white; margin: 30px auto; padding: 0; width: 90%; max-width: 700px; border-radius: 8px; box-shadow: 0 5px 15px rgba(0,0,0,0.3); max-height: 90vh; overflow-y: auto; }
        .modal-header { background: #007bff; color: white; padding: 15px 20px; border-radius: 8px 8px 0 0; display: flex; justify-content: space-between; align-items: center; }
        .modal-header h2 { margin: 0; color: white; border: none; padding: 0; }
        .modal-body { padding: 20px; }
        .close { color: white; font-size: 28px; font-weight: bold; cursor: pointer; line-height: 20px; }
        .close:hover { color: #f8d7da; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; color: #333; }
        .form-group input, .form-group textarea, .form-group select { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; font-size: 14px; }
        .form-group input:focus, .form-group textarea:focus, .form-group select:focus { border-color: #007bff; outline: none; }
        .modal-footer { padding: 15px 20px; background: #f8f9fa; text-align: right; border-radius: 0 0 8px 8px; }
        .alert { padding: 12px; margin-bottom: 15px; border-radius: 4px; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-danger { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .no-data { text-align: center; padding: 40px; color: #6c757d; font-style: italic; }
        .top-actions { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>📋 Gestión de Inquilinos</h1>
        
        <div class="top-actions">
            <div>
                <a href="index.php" class="btn btn-secondary">← Volver al Menú</a>
            </div>
            <div>
                <button onclick="nuevoInquilino()" class="btn btn-primary">
                    ➕ Nuevo Inquilino
                </button>
            </div>
        </div>
        
        <div class="filtros">
            <form method="GET" style="display: flex; gap: 15px; align-items: center; flex-wrap: wrap; width: 100%;">
                <div>
                    <label>Estado:</label>
                    <select name="filtro" onchange="this.form.submit()">
                        <option value="activos" <?php echo $filtro === 'activos' ? 'selected' : '' ?>>✅ Activos</option>
                        <option value="inactivos" <?php echo $filtro === 'inactivos' ? 'selected' : '' ?>>⏸️ Inactivos</option>
                        <option value="todos" <?php echo $filtro === 'todos' ? 'selected' : '' ?>>📊 Todos</option>
                        <option value="eliminados" <?php echo $filtro === 'eliminados' ? 'selected' : '' ?>>🗑️ Eliminados</option>
                    </select>
                </div>
                
                <div style="flex-grow: 1;">
                    <label>Buscar:</label>
                    <input type="text" name="busqueda" value="<?php echo htmlspecialchars($busqueda) ?>" 
                           placeholder="Apellido, nombre o DNI..." style="min-width: 300px;">
                </div>
                
                <div>
                    <button type="submit" class="btn btn-primary">🔍 Buscar</button>
                    <a href="form_inquilinos.php" class="btn btn-secondary">🔄 Limpiar</a>
                </div>
            </form>
        </div>
        
        <table>
            <thead>
                <tr>
                    <th style="width: 50px;">ID</th>
                    <th>Apellido y Nombre</th>
                    <th style="width: 120px;">DNI</th>
                    <th style="width: 130px;">Teléfono</th>
                    <th>Email</th>
                    <th style="width: 100px;">Estado</th>
                    <th style="width: 200px;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($inquilinos) > 0): ?>
                    <?php foreach ($inquilinos as $inquilino): ?>
                        <tr id="row-<?php echo $inquilino['id'] ?>">
                            <td><?php echo $inquilino['id'] ?></td>
                            <td>
                                <strong><?php echo htmlspecialchars($inquilino['apellido']) ?></strong>, 
                                <?php echo htmlspecialchars($inquilino['nombre']) ?>
                            </td>
                            <td><?php echo htmlspecialchars($inquilino['dni']) ?></td>
                            <td><?php echo htmlspecialchars($inquilino['telefono'] ?? '-') ?></td>
                            <td><?php echo htmlspecialchars($inquilino['email'] ?? '-') ?></td>
                            <td>
                                <span class="estado-badge estado-<?php echo $inquilino['estado'] ?>">
                                    <?php
                                    $estados = [
                                        'activo' => '✅ Activo',
                                        'inactivo' => '⏸️ Inactivo',
                                        'eliminado' => '🗑️ Eliminado'
                                    ];
                                    echo $estados[$inquilino['estado']] ?? $inquilino['estado'];
                                    ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($inquilino['estado'] !== 'eliminado'): ?>
                                    <button onclick="editarInquilino(<?php echo $inquilino['id'] ?>)" 
                                            class="btn btn-editar" title="Editar">
                                        ✏️
                                    </button>
                                    
                                    <?php if ($inquilino['estado'] === 'activo'): ?>
                                        <button onclick="desactivarInquilino(<?php echo $inquilino['id'] ?>)" 
                                                class="btn btn-desactivar" title="Desactivar">
                                            ⏸️
                                        </button>
                                    <?php else: ?>
                                        <button onclick="activarInquilino(<?php echo $inquilino['id'] ?>)" 
                                                class="btn btn-activar" title="Activar">
                                            ▶️
                                        </button>
                                    <?php endif; ?>
                                    
                                    <button onclick="eliminarInquilino(<?php echo $inquilino['id'] ?>)" 
                                            class="btn btn-eliminar" title="Eliminar">
                                        🗑️
                                    </button>
                                <?php else: ?>
                                    <span style="color: #999; font-style: italic;">Eliminado</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="no-data">
                            😕 No se encontraron inquilinos con los filtros seleccionados
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        
        <div style="margin-top: 20px; color: #6c757d; font-size: 14px;">
            Total: <strong><?php echo count($inquilinos) ?></strong> inquilino(s)
        </div>
    </div>
    
    <div id="modalInquilino" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitulo">Editar Inquilino</h2>
                <span class="close" onclick="cerrarModal()">&times;</span>
            </div>
            <form id="formInquilino" onsubmit="guardarInquilino(event)">
                <div class="modal-body">
                    <input type="hidden" id="id_inquilino" name="id_inquilino">
                    <input type="hidden" id="accion" name="action" value="editar">
                    
                    <div class="form-group">
                        <label>Apellido *</label>
                        <input type="text" id="apellido" name="apellido" required placeholder="Ingrese apellido">
                    </div>
                    
                    <div class="form-group">
                        <label>Nombre *</label>
                        <input type="text" id="nombre" name="nombre" required placeholder="Ingrese nombre">
                    </div>
                    
                    <div class="form-group">
                        <label>DNI *</label>
                        <input type="text" id="dni" name="dni" required placeholder="Ej: 12345678">
                    </div>
                    
                    <div class="form-group">
                        <label>Teléfono</label>
                        <input type="text" id="telefono" name="telefono" placeholder="Ej: +54 9 11 1234-5678">
                    </div>
                    
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" id="email" name="email" placeholder="ejemplo@correo.com">
                    </div>
                    
                    <div class="form-group">
                        <label>Dirección</label>
                        <input type="text" id="direccion" name="direccion" placeholder="Calle y número">
                    </div>
                    
                    <div class="form-group">
                        <label>Localidad</label>
                        <input type="text" id="localidad" name="localidad" placeholder="Ciudad">
                    </div>
                    
                    <div class="form-group">
                        <label>Provincia</label>
                        <input type="text" id="provincia" name="provincia" placeholder="Provincia">
                    </div>
                    
                    <div class="form-group">
                        <label>Observaciones</label>
                        <textarea id="observaciones" name="observaciones" rows="3" placeholder="Notas adicionales sobre el inquilino..."></textarea>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" onclick="cerrarModal()" class="btn btn-secondary">
                        ✖️ Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary">
                        💾 Guardar
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function nuevoInquilino() {
            document.getElementById('modalTitulo').textContent = 'Nuevo Inquilino';
            document.getElementById('formInquilino').reset();
            document.getElementById('id_inquilino').value = '';
            document.getElementById('accion').value = 'crear';
            document.getElementById('modalInquilino').style.display = 'block';
        }
        
        function editarInquilino(id) {
            fetch(`api/api_inquilinos.php?action=obtener&id=${id}`)
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        const inquilino = data.data;
                        document.getElementById('modalTitulo').textContent = 'Editar Inquilino';
                        document.getElementById('id_inquilino').value = inquilino.id;
                        document.getElementById('accion').value = 'editar';
                        document.getElementById('apellido').value = inquilino.apellido;
                        document.getElementById('nombre').value = inquilino.nombre;
                        document.getElementById('dni').value = inquilino.dni;
                        document.getElementById('telefono').value = inquilino.telefono || '';
                        document.getElementById('email').value = inquilino.email || '';
                        document.getElementById('direccion').value = inquilino.direccion || '';
                        document.getElementById('localidad').value = inquilino.localidad || '';
                        document.getElementById('provincia').value = inquilino.provincia || '';
                        document.getElementById('observaciones').value = inquilino.observaciones || '';
                        
                        document.getElementById('modalInquilino').style.display = 'block';
                    } else {
                        alert('❌ ' + data.message);
                    }
                })
                .catch(err => {
                    console.error(err);
                    alert('❌ Error al cargar los datos del inquilino');
                });
        }
        
        function cerrarModal() {
            document.getElementById('modalInquilino').style.display = 'none';
        }
        
        function guardarInquilino(event) {
            event.preventDefault();
            const formData = new FormData(document.getElementById('formInquilino'));
            
            fetch('api/api_inquilinos.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    alert('✅ ' + data.message);
                    cerrarModal();
                    location.reload();
                } else {
                    alert('❌ ' + data.message);
                }
            })
            .catch(err => {
                console.error(err);
                alert('❌ Error al guardar los cambios');
            });
        }
        
        function cambiarEstado(id, accion) {
            const msj = accion === 'desactivar' ? '¿Desea desactivar este inquilino?' : '¿Desea activar este inquilino?';
            if (!confirm(msj)) return;
            
            const formData = new FormData();
            formData.append('action', accion);
            formData.append('id', id);
            
            fetch('api/api_inquilinos.php', { method: 'POST', body: formData })
                .then(res => res.json())
                .then(data => {
                    alert(data.success ? '✅ ' + data.message : '❌ ' + data.message);
                    if (data.success) location.reload();
                })
                .catch(err => {
                    console.error(err);
                    alert(`❌ Error al ${accion}`);
                });
        }

        function desactivarInquilino(id) {
            cambiarEstado(id, 'desactivar');
        }

        function activarInquilino(id) {
            cambiarEstado(id, 'activar');
        }
        
        function eliminarInquilino(id) {
            if (!confirm('⚠️ ¿ELIMINAR este inquilino?\n\nEsta acción lo marcará como eliminado y no podrá usarse en nuevos contratos.')) return;
            
            const formData = new FormData();
            formData.append('action', 'eliminar');
            formData.append('id', id);
            
            fetch('api/api_inquilinos.php', { method: 'POST', body: formData })
                .then(res => res.json())
                .then(data => {
                    alert(data.success ? '✅ ' + data.message : '❌ ' + data.message);
                    if (data.success) location.reload();
                })
                .catch(err => {
                    console.error(err);
                    alert('❌ Error al eliminar');
                });
        }
        
        window.onclick = function(event) {
            const modal = document.getElementById('modalInquilino');
            if (event.target == modal) {
                cerrarModal();
            }
        }
        
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                cerrarModal();
            }
        });
    </script>
</body>
</html>