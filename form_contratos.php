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
                    'desde' => $_POST['periodo_desde'][$i],
                    'hasta' => $_POST['periodo_hasta'][$i],
                    'valor' => $_POST['periodo_valor'][$i]
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
            $_POST['garante_id'] ?: null,
            $fecha_inicio,
            $duracion,
            $fecha_fin,
            $_POST['deposito_ingreso'],
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

// Obtener contratos activos
$contratos = $pdo->query("SELECT c.*, CONCAT(i.nombre, ' ', i.apellido) as inquilino, p.direccion, p.departamento, p.localidad 
                          FROM contratos c 
                          JOIN inquilinos i ON c.inquilino_id = i.id 
                          JOIN propiedades p ON c.propiedad_id = p.id 
                          WHERE c.activo = 1 
                          ORDER BY c.id DESC")->fetchAll(PDO::FETCH_ASSOC);
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
                        <div class="periodo-item">
                            <div class="form-group"><label>Mes Desde</label><input type="number" name="periodo_desde[]" min="1" placeholder="Ej: 1" required></div>
                            <div class="form-group"><label>Mes Hasta</label><input type="number" name="periodo_hasta[]" min="1" placeholder="Ej: 6" required></div>
                            <div class="form-group"><label>Valor del Alquiler</label><input type="number" name="periodo_valor[]" step="0.01" placeholder="0.00" required></div>
                            <button type="button" class="btn btn-eliminar" onclick="this.parentElement.remove()">🗑️</button>
                        </div>

                    </div>
                </div>

                <button type="submit" class="btn btn-primary">💾 Crear Contrato</button>
            </form>
        </div>

        <h2>Contratos Activos</h2>
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
                    <td><span class="badge badge-success">Activo</span></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <script src="assets/js/main.js"></script>
    <script>
        function agregarPeriodo() {
            const container = document.getElementById('periodos-container');
            const item = document.createElement('div');
            item.classList.add('periodo-item');
            item.innerHTML = `
                <div class="form-group"><label>Mes Desde</label><input type="number" name="periodo_desde[]" min="1" placeholder="Ej: 7" required></div>
                <div class="form-group"><label>Mes Hasta</label><input type="number" name="periodo_hasta[]" min="1" placeholder="Ej: 12" required></div>
                <div class="form-group"><label>Valor del Alquiler</label><input type="number" name="periodo_valor[]" step="0.01" placeholder="0.00" required></div>
                <button type="button" class="btn btn-eliminar" onclick="this.parentElement.remove()">🗑️</button>
            `;
            container.appendChild(item);
        }

        // Sobrescribir la función showTab para que no dé error si no existe el elemento
        function showTab(tabName) {
            const tabs = document.querySelectorAll('.tab');
            const contents = document.querySelectorAll('.tab-content');
            const clickedTab = event.currentTarget;

            tabs.forEach(tab => tab.classList.remove('active'));
            contents.forEach(content => content.classList.remove('active'));
            
            document.getElementById(tabName).classList.add('active');
            clickedTab.classList.add('active');
        }
    </script>
</body>
</html>