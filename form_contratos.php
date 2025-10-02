<?php
require_once 'check_auth.php';
require_once 'config.php';

// Procesar formulario de garante
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'guardar_garante') {
    try {
        $stmt = $pdo->prepare("INSERT INTO garantes (nombre_apellido, dni, telefono, direccion, email) 
                               VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([
            $_POST['garante_nombre'],
            $_POST['garante_dni'],
            $_POST['garante_telefono'],
            $_POST['garante_direccion'],
            $_POST['garante_email']
        ]);
        $mensaje = "Garante registrado exitosamente";
        $tipo_mensaje = "success";
    } catch(PDOException $e) {
        $mensaje = "Error: " . $e->getMessage();
        $tipo_mensaje = "error";
    }
}

// Procesar formulario de contrato
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'guardar_contrato') {
    try {
        $fecha_inicio = $_POST['fecha_inicio'];
        $duracion = $_POST['duracion_meses'];
        $fecha_fin = date('Y-m-d', strtotime($fecha_inicio . " + $duracion months -1 day"));

        // Procesar valores de alquiler dinámicos
        $valores_alquiler = [];
        if (isset($_POST['periodo_desde']) && is_array($_POST['periodo_desde'])) {
            for ($i = 0; $i < count($_POST['periodo_desde']); $i++) {
                $valores_alquiler[] = [
                    'desde' => (int)$_POST['periodo_desde'][$i],
                    'hasta' => (int)$_POST['periodo_hasta'][$i],
                    'valor' => (float)$_POST['periodo_valor'][$i]
                ];
            }
        }
        
        $stmt = $pdo->prepare("INSERT INTO contratos (codigo, inquilino_id, propiedad_id, garante_id, 
                               fecha_inicio, duracion_meses, fecha_fin, deposito_ingreso, 
                               valores_alquiler) 
                               VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $_POST['codigo'],
            $_POST['inquilino_id'],
            $_POST['propiedad_id'],
            $_POST['garante_id'] ?: null, // Corregido para incluir garante_id
            $fecha_inicio,
            $duracion,
            $fecha_fin,
            $_POST['deposito_ingreso'] ?: 0, // Corregido para incluir deposito_ingreso
            json_encode($valores_alquiler)
        ]);
        $mensaje = "Contrato registrado exitosamente con ID: " . $pdo->lastInsertId();
        $tipo_mensaje = "success";
    } catch(PDOException $e) {
        $mensaje = "Error: " . $e->getMessage();
        $tipo_mensaje = "error";
    }
}

// Obtener datos para los selectores
$inquilinos = $pdo->query("SELECT id, nombre, apellido, dni FROM inquilinos WHERE estado = 'activo' ORDER BY apellido, nombre")->fetchAll(PDO::FETCH_ASSOC);
$propiedades = $pdo->query("SELECT id, codigo, direccion, departamento, localidad FROM propiedades WHERE activo = 1 ORDER BY direccion")->fetchAll(PDO::FETCH_ASSOC);
$garantes = $pdo->query("SELECT id, nombre_apellido, dni FROM garantes ORDER BY nombre_apellido")->fetchAll(PDO::FETCH_ASSOC);

// Filtros
$filtro_estado = $_GET['filtro_estado'] ?? 'activos';
$busqueda = $_GET['busqueda'] ?? '';

// Construir consulta
$sql = "SELECT c.*, CONCAT(i.nombre, ' ', i.apellido) as inquilino, p.direccion, p.departamento, p.localidad 
        FROM contratos c 
        JOIN inquilinos i ON c.inquilino_id = i.id 
        JOIN propiedades p ON c.propiedad_id = p.id 
        WHERE 1=1";
$params = [];

// Filtro por estado
if ($filtro_estado === 'activos') {
    $sql .= " AND c.activo = 1";
} elseif ($filtro_estado === 'inactivos') {
    $sql .= " AND c.activo = 0";
}

// Búsqueda
if (!empty($busqueda)) {
    $sql .= " AND (c.codigo LIKE :busqueda OR i.nombre LIKE :busqueda OR i.apellido LIKE :busqueda OR p.direccion LIKE :busqueda)";
    $params[':busqueda'] = "%$busqueda%";
}

$sql .= " ORDER BY c.id DESC";

// Ejecutar consulta
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$contratos = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Contratos</title>
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body class="form-contratos">
    <div class="container">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
            <h1>📄 Gestión de Contratos</h1>
            <a href="index.php" class="btn btn-secondary">↩️ Volver al Menú</a>
        </div>

        <?php if (isset($mensaje)): ?>
            <div class="mensaje <?php echo $tipo_mensaje; ?>">
                <?php echo $mensaje; ?>
            </div>
        <?php endif; ?>

        <div class="tabs">
            <button class="tab active" onclick="showTab('contrato')">Nuevo Contrato</button>
            <button class="tab" onclick="showTab('garante')">Agregar Garante</button>
        </div>

        <!-- Tab Garante -->
        <div id="garante" class="tab-content">
            <div class="card">
                <h3>Datos del Garante</h3>
                <form method="POST" action="">
                    <input type="hidden" name="accion" value="guardar_garante">
                    
                    <div class="grid-2">
                        <div class="form-group">
                            <label for="garante_nombre">Nombre y Apellido *</label>
                            <input type="text" id="garante_nombre" name="garante_nombre" required>
                        </div>

                        <div class="form-group">
                            <label for="garante_dni">DNI *</label>
                            <input type="text" id="garante_dni" name="garante_dni" required>
                        </div>

                        <div class="form-group">
                            <label for="garante_telefono">Teléfono</label>
                            <input type="text" id="garante_telefono" name="garante_telefono">
                        </div>

                        <div class="form-group">
                            <label for="garante_email">Email</label>
                            <input type="email" id="garante_email" name="garante_email">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="garante_direccion">Dirección</label>
                        <input type="text" id="garante_direccion" name="garante_direccion">
                    </div>

                    <button type="submit" class="btn btn-secondary">💾 Guardar Garante</button>
                </form>
            </div>
        </div>

        <!-- Tab Contrato -->
        <div id="contrato" class="tab-content active">
            <form method="POST" action="">
                <input type="hidden" name="accion" value="guardar_contrato">
                
                <div class="card">
                    <h3>Información del Contrato</h3>
                    
                    <div class="grid-3">
                        <div class="form-group">
                            <label for="codigo">Código de Contrato</label>
                            <input type="text" id="codigo" name="codigo" placeholder="Ej: CONT-2024-001">
                        </div>

                        <div class="form-group">
                            <label for="inquilino_id">Inquilino *</label>
                            <select id="inquilino_id" name="inquilino_id" required>
                                <option value="">Seleccione un inquilino</option>
                                <?php foreach ($inquilinos as $inq): ?>
                                    <option value="<?php echo $inq['id']; ?>">
                                        <?php echo htmlspecialchars($inq['apellido'] . ', ' . $inq['nombre'] . ' - DNI: ' . $inq['dni']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="propiedad_id">Propiedad *</label>
                            <select id="propiedad_id" name="propiedad_id" required>
                                <option value="">Seleccione una propiedad</option>
                                <?php foreach ($propiedades as $prop): ?>
                                    <option value="<?php echo $prop['id']; ?>">
                                        <?php echo htmlspecialchars($prop['direccion'] . ' ' . $prop['departamento'] . ' - ' . $prop['localidad']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="garante_id">Garante (Opcional)</label>
                        <select id="garante_id" name="garante_id">
                            <option value="">Sin garante</option>
                            <?php foreach ($garantes as $gar): ?>
                                <option value="<?php echo $gar['id']; ?>">
                                    <?php echo htmlspecialchars($gar['nombre_apellido'] . ' - DNI: ' . $gar['dni']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="card">
                    <h3>Fechas y Duración</h3>
                    
                    <div class="grid-3">
                        <div class="form-group">
                            <label for="fecha_inicio">Fecha Inicio *</label>
                            <input type="date" id="fecha_inicio" name="fecha_inicio" required>
                        </div>

                        <div class="form-group">
                            <label for="duracion_meses">Duración (Meses) *</label>
                            <input type="number" id="duracion_meses" name="duracion_meses" min="1" max="60" required>
                        </div>

                        <div class="form-group">
                            <label for="deposito_ingreso">Depósito de Ingreso</label>
                            <input type="number" id="deposito_ingreso" name="deposito_ingreso" step="0.01" placeholder="0.00">
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                        <h3>Valores de Alquiler por Período</h3>
                        <button type="button" class="btn btn-secondary" onclick="agregarPeriodo()">➕ Agregar Período</button>
                    </div>
                    <div id="periodos-container">
                        <!-- Los períodos se agregarán aquí dinámicamente -->
                        <div class="periodo-item" data-periodo="1">
                            <div class="form-group"><label>Mes Desde</label><input type="number" name="periodo_desde[]" min="1" max="999" value="1" required></div>
                            <div class="form-group"><label>Mes Hasta</label><input type="number" name="periodo_hasta[]" min="1" max="999" value="12" required></div>
                            <div class="form-group"><label>Valor del Alquiler</label><input type="number" name="periodo_valor[]" step="0.01" min="0" placeholder="0.00" required></div>
                            <button type="button" class="btn btn-eliminar" onclick="eliminarPeriodo(this)" title="Eliminar período">🗑️</button>
                        </div>
                
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">💾 Crear Contrato</button>
            </form>
        </div>

        <h2>Listado de Contratos</h2>

        <!-- Filtros y búsqueda -->
        <div class="filtros">
            <form method="GET" style="display: flex; gap: 15px; align-items: center; flex-wrap: wrap; width: 100%;">
                <div>
                    <label>Estado:</label>
                    <select name="filtro_estado" onchange="this.form.submit()">
                        <option value="activos" <?php echo $filtro_estado === 'activos' ? 'selected' : '' ?>>✅ Activos</option>
                        <option value="inactivos" <?php echo $filtro_estado === 'inactivos' ? 'selected' : '' ?>>⏸️ Inactivos</option>
                        <option value="todos" <?php echo $filtro_estado === 'todos' ? 'selected' : '' ?>>📊 Todos</option>
                    </select>
                </div>
                <div style="flex-grow: 1;">
                    <label>Buscar:</label>
                    <input type="text" name="busqueda" value="<?php echo htmlspecialchars($busqueda) ?>" placeholder="Código, inquilino, dirección...">
                </div>
                <div>
                    <button type="submit" class="btn btn-primary">🔍 Buscar</button>
                    <a href="form_contratos.php" class="btn btn-secondary">🔄 Limpiar</a>
                </div>
            </form>
        </div>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Código</th>
                    <th>Inquilino</th>
                    <th>Propiedad</th>
                    <th>Fecha Inicio</th>
                    <th>Duración</th>
                    <th>Fecha Fin</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($contratos as $cont): ?>
                <tr>
                    <td><?php echo $cont['id']; ?></td>
                    <td><?php echo htmlspecialchars($cont['codigo']); ?></td>
                    <td><?php echo htmlspecialchars($cont['inquilino']); ?></td>
                    <td><?php echo htmlspecialchars($cont['direccion'] . ' ' . $cont['departamento']); ?></td>
                    <td><?php echo date('d/m/Y', strtotime($cont['fecha_inicio'])); ?></td>
                    <td><?php echo $cont['duracion_meses']; ?> meses</td>
                    <td><?php echo date('d/m/Y', strtotime($cont['fecha_fin'])); ?></td>
                    <td>
                        <?php if ($cont['activo']): ?>
                            <span class="badge badge-success">Activo</span>
                        <?php else: ?>
                            <span class="badge badge-danger">Inactivo</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <button onclick="editarContrato(<?php echo $cont['id']; ?>)" class="btn btn-warning" title="Editar">✏️</button>
                        <?php if ($cont['activo']): ?>
                            <button onclick="desactivarContrato(<?php echo $cont['id']; ?>)" class="btn btn-danger" title="Desactivar">🗑️</button>
                        <?php else: ?>
                            <button onclick="activarContrato(<?php echo $cont['id']; ?>)" class="btn btn-success" title="Activar">▶️</button>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Modal para editar -->
    <div id="modalContrato" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Editar Contrato</h2>
                <span class="close" onclick="cerrarModal()">&times;</span>
            </div>
            <form id="formEditarContrato" onsubmit="guardarEdicion(event)">
                <div class="modal-body">
                    <input type="hidden" id="edit_id" name="id">
                    <input type="hidden" name="action" value="editar">
                    
                    <div class="form-group">
                        <label>Código</label>
                        <input type="text" id="edit_codigo" name="codigo">
                    </div>
                    
                    <div class="grid-2">
                        <div class="form-group">
                            <label>Inquilino *</label>
                            <select id="edit_inquilino_id" name="inquilino_id" required>
                                <?php foreach ($inquilinos as $inq): ?>
                                    <option value="<?= $inq['id'] ?>">
                                        <?= htmlspecialchars($inq['apellido'] . ', ' . $inq['nombre']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Propiedad *</label>
                            <select id="edit_propiedad_id" name="propiedad_id" required>
                                <?php foreach ($propiedades as $prop): ?>
                                    <option value="<?= $prop['id'] ?>">
                                        <?= htmlspecialchars($prop['direccion'] . ' ' . $prop['departamento']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Garante</label>
                        <select id="edit_garante_id" name="garante_id">
                            <option value="">Sin garante</option>
                            <?php foreach ($garantes as $gar): ?>
                                <option value="<?= $gar['id'] ?>">
                                    <?= htmlspecialchars($gar['nombre_apellido']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="grid-3">
                        <div class="form-group">
                            <label>Fecha Inicio *</label>
                            <input type="date" id="edit_fecha_inicio" name="fecha_inicio" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Duración (Meses) *</label>
                            <input type="number" id="edit_duracion_meses" name="duracion_meses" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Depósito</label>
                            <input type="number" id="edit_deposito_ingreso" name="deposito_ingreso" step="0.01">
                        </div>
                    </div>
                    
                    <div style="margin-top: 20px;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                            <h3 style="margin: 0;">Valores de Alquiler</h3>
                            <button type="button" class="btn btn-secondary" onclick="agregarPeriodoEdit()">+ Agregar</button>
                        </div>
                        <div id="edit_periodos_container">
                            <!-- Los períodos se cargarán aquí dinámicamente -->
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" onclick="cerrarModal()" class="btn btn-secondary">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>

    <script src="assets/js/main.js"></script>
    <script>
        let contadorPeriodos = 1;

        function agregarPeriodo() {
            contadorPeriodos++;
            const container = document.getElementById('periodos-container');
            const ultimoPeriodo = container.lastElementChild;
            
            // Sugerir valores basados en el último período
            let desdesugerido = 1;
            if (ultimoPeriodo) {
                const ultimoHasta = parseInt(ultimoPeriodo.querySelector('input[name="periodo_hasta[]"]').value) || 0;
                desdesugerido = ultimoHasta + 1;
            }
            
            const item = document.createElement('div');
            item.classList.add('periodo-item');
            item.setAttribute('data-periodo', contadorPeriodos);
            item.innerHTML = `
                <div class="form-group"><label>Mes Desde</label><input type="number" name="periodo_desde[]" min="1" max="999" value="${desdesugerido}" required></div>
                <div class="form-group"><label>Mes Hasta</label><input type="number" name="periodo_hasta[]" min="1" max="999" value="${desdesugerido + 11}" required></div>
                <div class="form-group"><label>Valor del Alquiler</label><input type="number" name="periodo_valor[]" step="0.01" min="0" placeholder="0.00" required></div>
                <button type="button" class="btn btn-eliminar" onclick="eliminarPeriodo(this)" title="Eliminar período">🗑️</button>
            `;
            container.appendChild(item);
        }

        function eliminarPeriodo(btn) {
            const container = document.getElementById('periodos-container');
            if (container.children.length > 1) {
                btn.parentElement.remove();
            } else {
                alert('Debe haber al menos un período de alquiler');
            }
        }

        function validarPeriodos() {
            const container = document.getElementById('periodos-container');
            const periodos = Array.from(container.querySelectorAll('.periodo-item'));
            
            for (let i = 0; i < periodos.length; i++) {
                const desde = parseInt(periodos[i].querySelector('input[name="periodo_desde[]"]').value);
                const hasta = parseInt(periodos[i].querySelector('input[name="periodo_hasta[]"]').value);
                
                if (desde > hasta) {
                    alert(`Período ${i+1}: "Mes Desde" no puede ser mayor que "Mes Hasta"`);
                    return false;
                }
            }
            
            return true;
        }

        // Validar antes de enviar el formulario de contrato
        document.querySelector('form[action=""][method="POST"]').addEventListener('submit', function(e) {
            if (document.querySelector('input[name="accion"]').value === 'guardar_contrato') {
                if (!validarPeriodos()) {
                    e.preventDefault();
                }
            }
        });
    </script>
    <script>
        function editarContrato(id) {
            fetch(`api/api_contratos.php?action=obtener&id=${id}`)
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        const c = data.data;
                        
                        // Llenar campos básicos
                        document.getElementById('edit_id').value = c.id;
                        document.getElementById('edit_codigo').value = c.codigo || '';
                        document.getElementById('edit_inquilino_id').value = c.inquilino_id;
                        document.getElementById('edit_propiedad_id').value = c.propiedad_id;
                        document.getElementById('edit_garante_id').value = c.garante_id || '';
                        document.getElementById('edit_fecha_inicio').value = c.fecha_inicio;
                        document.getElementById('edit_duracion_meses').value = c.duracion_meses;
                        document.getElementById('edit_deposito_ingreso').value = c.deposito_ingreso || 0;
                        
                        // Manejar valores de alquiler (JSON o campos antiguos)
                        const container = document.getElementById('edit_periodos_container');
                        container.innerHTML = ''; // Limpiar períodos existentes
                        
                        let periodos = [];
                        
                        // Intentar parsear valores_alquiler si existe
                        if (c.valores_alquiler) {
                            try {
                                periodos = JSON.parse(c.valores_alquiler);
                            } catch(e) {
                                console.error('Error parseando valores_alquiler:', e);
                            }
                        }
                        
                        // Si no hay valores_alquiler JSON, usar campos antiguos
                        if (periodos.length === 0) {
                            if (c.mes_1_3 && c.mes_1_3 > 0) periodos.push({desde: 1, hasta: 3, valor: c.mes_1_3});
                            if (c.mes_4_6 && c.mes_4_6 > 0) periodos.push({desde: 4, hasta: 6, valor: c.mes_4_6});
                            if (c.mes_7_9 && c.mes_7_9 > 0) periodos.push({desde: 7, hasta: 9, valor: c.mes_7_9});
                            if (c.mes_10_12 && c.mes_10_12 > 0) periodos.push({desde: 10, hasta: 12, valor: c.mes_10_12});
                        }
                        
                        // Si aún no hay períodos, agregar uno vacío
                        if (periodos.length === 0) {
                            periodos.push({desde: 1, hasta: 12, valor: 0});
                        }
                        
                        // Crear campos para cada período
                        periodos.forEach((periodo, index) => {
                            const item = document.createElement('div');
                            item.className = 'periodo-item';
                            item.innerHTML = `
                                <div class="form-group"><label>Mes Desde</label><input type="number" name="periodo_desde[]" min="1" value="${periodo.desde}" required></div>
                                <div class="form-group"><label>Mes Hasta</label><input type="number" name="periodo_hasta[]" min="1" value="${periodo.hasta}" required></div>
                                <div class="form-group"><label>Valor</label><input type="number" name="periodo_valor[]" step="0.01" value="${periodo.valor}" required></div>
                                <button type="button" class="btn btn-eliminar" onclick="eliminarPeriodoEdit(this)">🗑️</button>
                            `;
                            container.appendChild(item);
                        });
                        
                        document.getElementById('modalContrato').style.display = 'block';
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(err => {
                    console.error('Error:', err);
                    alert('Error al cargar los datos del contrato');
                });
        }

        function agregarPeriodoEdit() {
            const container = document.getElementById('edit_periodos_container');
            const item = document.createElement('div');
            item.className = 'periodo-item';
            item.innerHTML = `
                <div class="form-group"><label>Mes Desde</label><input type="number" name="periodo_desde[]" min="1" value="1" required></div>
                <div class="form-group"><label>Mes Hasta</label><input type="number" name="periodo_hasta[]" min="1" value="12" required></div>
                <div class="form-group"><label>Valor</label><input type="number" name="periodo_valor[]" step="0.01" value="0" required></div>
                <button type="button" class="btn btn-eliminar" onclick="eliminarPeriodoEdit(this)">🗑️</button>
            `;
            container.appendChild(item);
        }

        function eliminarPeriodoEdit(btn) {
            const container = document.getElementById('edit_periodos_container');
            if (container.children.length > 1) {
                btn.parentElement.remove();
            } else {
                alert('Debe haber al menos un período');
            }
        }

        function cerrarModal() {
            document.getElementById('modalContrato').style.display = 'none';
        }

        function cambiarEstadoContrato(id, accion) {
            const msj = accion === 'desactivar' ? '¿Está seguro de que desea desactivar este contrato?' : '¿Está seguro de que desea activar este contrato?';
            if (!confirm(msj)) return;
            
            const formData = new FormData();
            formData.append('action', accion);
            formData.append('id', id);

            fetch('api/api_contratos.php', { method: 'POST', body: formData })
                .then(res => res.json())
                .then(data => {
                    alert(data.message);
                    if (data.success) location.reload();
                });
        }

        function desactivarContrato(id) {
            cambiarEstadoContrato(id, 'desactivar');
        }

        function activarContrato(id) {
            cambiarEstadoContrato(id, 'activar');
        }

        function guardarEdicion(event) {
            event.preventDefault();
            const formData = new FormData(document.getElementById('formEditarContrato'));
            fetch('api/api_contratos.php', { method: 'POST', body: formData })
                .then(res => res.json())
                .then(data => {
                    alert(data.message);
                    if (data.success) location.reload();
                });
        }
    </script>
</body>
</html>