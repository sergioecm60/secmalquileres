<?php
require_once 'check_auth.php';
require_once 'config.php';

// Procesar formulario de cobro
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'guardar_cobro') {
    try {
        $inquilino_id = $_POST['inquilino_id'];
        
        // Obtener el contrato activo del inquilino
        $stmt = $pdo->prepare("SELECT c.id as contrato_id, c.propiedad_id 
                               FROM contratos c 
                               WHERE c.inquilino_id = ? AND c.activo = 1 
                               LIMIT 1");
        $stmt->execute([$inquilino_id]);
        $contrato = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$contrato) {
            throw new Exception("El inquilino no tiene un contrato activo");
        }
        
        // Calcular total
        $total = floatval($_POST['alquiler']) + floatval($_POST['luz']) + floatval($_POST['agua']) +
                 floatval($_POST['mantenimiento']) + floatval($_POST['abl']) + floatval($_POST['otros_conceptos']);

        // Insertar cobro
        $stmt = $pdo->prepare("INSERT INTO cobros (contrato_id, inquilino_id, propiedad_id, periodo, mes, anio,
                               alquiler, luz, agua, mantenimiento, abl, otros_conceptos, total, status,
                               fecha_cobro, fecha_vencimiento, observaciones)
                               VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $contrato['contrato_id'],
            $inquilino_id,
            $contrato['propiedad_id'],
            $_POST['periodo'],
            $_POST['mes'],
            $_POST['anio'],
            floatval($_POST['alquiler']),
            floatval($_POST['luz']),
            floatval($_POST['agua']),
            floatval($_POST['mantenimiento']),
            floatval($_POST['abl']),
            floatval($_POST['otros_conceptos']),
            $total,
            $_POST['status'],
            $_POST['fecha_cobro'] ?: null,
            $_POST['fecha_vencimiento'] ?: null,
            $_POST['observaciones']
        ]);

        $mensaje = "Cobro registrado exitosamente con ID: " . $pdo->lastInsertId();
        $tipo_mensaje = "success";
    } catch(Exception $e) {
        $mensaje = "Error: " . $e->getMessage();
        $tipo_mensaje = "error";
    }
}

// Obtener inquilinos con contratos activos
$inquilinos_con_contrato = $pdo->query("SELECT DISTINCT i.id, CONCAT(i.apellido, ', ', i.nombre) as nombre_completo, i.dni, i.apellido, i.nombre,
                                         c.id as contrato_id, p.direccion, p.departamento, p.localidad
                                         FROM inquilinos i
                                         JOIN contratos c ON i.id = c.inquilino_id AND c.activo = 1
                                         JOIN propiedades p ON c.propiedad_id = p.id
                                         WHERE i.estado = 'activo'
                                         ORDER BY i.apellido, i.nombre")->fetchAll(PDO::FETCH_ASSOC);

// Filtros
$filtro = $_GET['filtro'] ?? 'todos';
$sql = "SELECT cob.*, CONCAT(i.nombre, ' ', i.apellido) as nombre_apellido, p.direccion, p.departamento, p.localidad
        FROM cobros cob 
        JOIN inquilinos i ON cob.inquilino_id = i.id 
        JOIN propiedades p ON cob.propiedad_id = p.id 
        WHERE 1=1";

if ($filtro === 'pendientes') {
    $sql .= " AND cob.status = 'PENDIENTE'";
} elseif ($filtro === 'pagados') {
    $sql .= " AND cob.status = 'PAGADO'";
} elseif ($filtro === 'vencidos') {
    $sql .= " AND cob.status = 'VENCIDO'";
}

$sql .= " ORDER BY cob.anio DESC, cob.mes DESC, cob.id DESC";
$cobros = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

$meses = $meses_es;
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Cobros</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f4f4f4; padding: 20px; }
        .container { max-width: 1800px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #333; margin-bottom: 30px; border-bottom: 3px solid #dc3545; padding-bottom: 10px; }
        h2 { color: #555; margin: 30px 0 20px; font-size: 20px; }
        h3 { color: #666; margin: 20px 0 15px; font-size: 16px; background: #f8f9fa; padding: 10px; border-left: 4px solid #dc3545; }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; color: #555; font-size: 14px; }
        input, select, textarea { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px; }
        textarea { min-height: 80px; resize: vertical; }
        input:focus, select:focus, textarea:focus { outline: none; border-color: #dc3545; }
        .btn { padding: 8px 15px; border: none; border-radius: 4px; cursor: pointer; font-size: 13px; transition: all 0.3s; margin: 2px; }
        .btn-danger { background: #dc3545; color: white; }
        .btn-success { background: #28a745; color: white; font-size: 12px; padding: 6px 12px; }
        .btn-primary { background: #007bff; color: white; }
        .btn-editar { background: #007bff; color: white; }
        .btn-pagado { background: #28a745; color: white; }
        .btn-pendiente { background: #ffc107; color: black; }
        .btn-anular { background: #6c757d; color: white; }
        .btn-secondary { background: #6c757d; color: white; text-decoration: none; display: inline-block; }
        .mensaje { padding: 15px; margin-bottom: 20px; border-radius: 4px; }
        .mensaje.success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .mensaje.error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; font-size: 11px; }
        th, td { padding: 8px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #dc3545; color: white; font-weight: bold; position: sticky; top: 0; }
        tr:hover { background: #f5f5f5; }
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; }
        .grid-4 { display: grid; grid-template-columns: 1fr 1fr 1fr 1fr; gap: 20px; }
        .badge { display: inline-block; padding: 5px 10px; border-radius: 3px; font-size: 10px; font-weight: bold; }
        .badge-success { background: #28a745; color: white; }
        .badge-warning { background: #ffc107; color: #000; }
        .badge-danger { background: #dc3545; color: white; }
        .badge-secondary { background: #6c757d; color: white; }
        .card { background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #dee2e6; }
        .money { text-align: right; font-family: 'Courier New', monospace; font-weight: bold; }
        .table-container { max-height: 600px; overflow-y: auto; }
        .filtros {
            margin: 20px 0;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 5px;
            display: flex;
            gap: 15px;
            align-items: center;
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
            overflow-y: auto;
        }
        .modal-content {
            background: white;
            margin: 30px auto;
            padding: 0;
            width: 90%;
            max-width: 900px;
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
        }
        .modal-header {
            background: #dc3545;
            color: white;
            padding: 15px 20px;
            border-radius: 8px 8px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .modal-header h2 { margin: 0; color: white; border: none; padding: 0; }
        .modal-body { padding: 20px; max-height: 70vh; overflow-y: auto; }
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
    </style>
</head>
<body>
    <div class="container">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h1>💰 Gestión de Cobros y Pagos</h1>
            <a href="index.php" class="btn btn-secondary">← Volver al Menú</a>
        </div>
        
        <?php if (isset($mensaje)): ?>
            <div class="mensaje <?php echo $tipo_mensaje; ?>">
                <?php echo $mensaje; ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <h3>Registrar Nuevo Cobro</h3>
            <form method="POST" action="">
                <input type="hidden" name="accion" value="guardar_cobro">
                
                <div class="grid-3">
                    <div class="form-group">
                        <label for="inquilino_id">Inquilino *</label>
                        <select id="inquilino_id" name="inquilino_id" required onchange="cargarDatosInquilino()">
                            <option value="">Seleccione un inquilino</option>
                            <?php foreach ($inquilinos_con_contrato as $inq): ?>
                                <option value="<?php echo $inq['id']; ?>" 
                                        data-contrato="<?php echo $inq['contrato_id']; ?>"
                                        data-propiedad="<?php echo htmlspecialchars($inq['direccion'] . ' ' . $inq['departamento'] . ', ' . $inq['localidad']); ?>">
                                    <?php echo htmlspecialchars($inq['nombre_completo'] . ' - DNI: ' . $inq['dni']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Propiedad Asociada</label>
                        <input type="text" id="info_propiedad" readonly style="background: #e9ecef;" placeholder="Seleccione un inquilino primero">
                    </div>
                    
                    <div class="form-group">
                        <label>Contrato #</label>
                        <input type="text" id="info_contrato" readonly style="background: #e9ecef;" placeholder="Auto">
                    </div>
                </div>

                <div class="grid-3">
                    <div class="form-group">
                        <label for="mes">Mes del Cobro *</label>
                        <select id="mes" name="mes" required onchange="actualizarPeriodo()">
                            <?php for ($i = 1; $i <= 12; $i++): ?>
                                <option value="<?php echo $i; ?>" <?php echo $i == date('n') ? 'selected' : '' ?>><?php echo ucfirst($meses[$i]); ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="anio">Año *</label>
                        <input type="number" id="anio" name="anio" value="<?php echo date('Y'); ?>" required onchange="actualizarPeriodo()">
                    </div>
                    
                    <div class="form-group">
                        <label for="periodo">Período</label>
                        <input type="text" id="periodo" name="periodo" readonly style="background: #e9ecef; font-weight: bold;">
                    </div>
                </div>

                <div class="grid-4">
                    <div class="form-group">
                        <label for="alquiler">Alquiler *</label>
                        <input type="number" id="alquiler" name="alquiler" step="0.01" required onchange="calcularTotal()">
                    </div>

                    <div class="form-group">
                        <label for="luz">Luz (EDENOR)</label>
                        <input type="number" id="luz" name="luz" step="0.01" value="0" onchange="calcularTotal()">
                    </div>

                    <div class="form-group">
                        <label for="agua">Agua (ABSA)</label>
                        <input type="number" id="agua" name="agua" step="0.01" value="0" onchange="calcularTotal()">
                    </div>

                    <div class="form-group">
                        <label for="mantenimiento">Mantenimiento</label>
                        <input type="number" id="mantenimiento" name="mantenimiento" step="0.01" value="0" onchange="calcularTotal()">
                    </div>
                </div>

                <div class="grid-3">
                    <div class="form-group">
                        <label for="abl">ABL</label>
                        <input type="number" id="abl" name="abl" step="0.01" value="0" onchange="calcularTotal()">
                    </div>

                    <div class="form-group">
                        <label for="otros_conceptos">Otros Conceptos</label>
                        <input type="number" id="otros_conceptos" name="otros_conceptos" step="0.01" value="0" onchange="calcularTotal()">
                    </div>

                    <div class="form-group">
                        <label for="total_calc">Total</label>
                        <input type="text" id="total_calc" readonly style="background: #e9ecef; font-weight: bold; font-size: 18px;">
                    </div>
                </div>

                <div class="grid-3">
                    <div class="form-group">
                        <label for="status">Estado *</label>
                        <select id="status" name="status" required>
                            <option value="PENDIENTE">PENDIENTE</option>
                            <option value="PAGADO">PAGADO</option>
                            <option value="VENCIDO">VENCIDO</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="fecha_vencimiento">Fecha Vencimiento</label>
                        <input type="date" id="fecha_vencimiento" name="fecha_vencimiento">
                    </div>

                    <div class="form-group">
                        <label for="fecha_cobro">Fecha de Cobro</label>
                        <input type="date" id="fecha_cobro" name="fecha_cobro">
                    </div>
                </div>

                <div class="form-group">
                    <label for="observaciones">Observaciones</label>
                    <textarea id="observaciones" name="observaciones"></textarea>
                </div>

                <button type="submit" class="btn btn-danger">💾 Registrar Cobro</button>
            </form>
        </div>

        <!-- Filtros -->
        <div class="filtros">
            <form method="GET" style="display: flex; gap: 15px; align-items: center;">
                <label>Estado:</label>
                <select name="filtro" onchange="this.form.submit()">
                    <option value="todos" <?php echo $filtro === 'todos' ? 'selected' : '' ?>>Todos</option>
                    <option value="pendientes" <?php echo $filtro === 'pendientes' ? 'selected' : '' ?>>Pendientes</option>
                    <option value="pagados" <?php echo $filtro === 'pagados' ? 'selected' : '' ?>>Pagados</option>
                    <option value="vencidos" <?php echo $filtro === 'vencidos' ? 'selected' : '' ?>>Vencidos</option>
                </select>
                <a href="form_cobros.php" class="btn btn-secondary">Limpiar</a>
            </form>
        </div>

        <h2>Historial de Cobros</h2>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Inquilino</th>
                        <th>Propiedad</th>
                        <th>Período</th>
                        <th>Alquiler</th>
                        <th>Luz</th>
                        <th>Agua</th>
                        <th>Mant.</th>
                        <th>ABL</th>
                        <th>Otros</th>
                        <th>TOTAL</th>
                        <th>Estado</th>
                        <th>F. Cobro</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cobros as $cob): ?>
                    <tr>
                        <td><?php echo $cob['id']; ?></td>
                        <td><?php echo htmlspecialchars($cob['nombre_apellido']); ?></td>
                        <td><?php echo htmlspecialchars($cob['direccion'] . ' ' . $cob['departamento']); ?></td>
                        <td><?php echo $cob['periodo']; ?></td>
                        <td class="money">$<?php echo number_format($cob['alquiler'], 2, ',', '.'); ?></td>
                        <td class="money">$<?php echo number_format($cob['luz'], 2, ',', '.'); ?></td>
                        <td class="money">$<?php echo number_format($cob['agua'], 2, ',', '.'); ?></td>
                        <td class="money">$<?php echo number_format($cob['mantenimiento'], 2, ',', '.'); ?></td>
                        <td class="money">$<?php echo number_format($cob['abl'], 2, ',', '.'); ?></td>
                        <td class="money">$<?php echo number_format($cob['otros_conceptos'], 2, ',', '.'); ?></td>
                        <td class="money" style="font-size: 13px;"><strong>$<?php echo number_format($cob['total'], 2, ',', '.'); ?></strong></td>
                        <td>
                            <?php if ($cob['status'] == 'PAGADO'): ?>
                                <span class="badge badge-success">PAGADO</span>
                            <?php elseif ($cob['status'] == 'PENDIENTE'): ?>
                                <span class="badge badge-warning">PENDIENTE</span>
                            <?php elseif ($cob['status'] == 'VENCIDO'): ?>
                                <span class="badge badge-danger">VENCIDO</span>
                            <?php else: ?>
                                <span class="badge badge-secondary">ANULADO</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo $cob['fecha_cobro'] ? date('d/m/Y', strtotime($cob['fecha_cobro'])) : '-'; ?></td>
                        <td>
                            <?php if ($cob['status'] != 'ANULADO'): ?>
                                <button onclick="editarCobro(<?php echo $cob['id'] ?>)" class="btn btn-editar" title="Editar">✏️</button>
                                
                                <?php if ($cob['status'] != 'PAGADO'): ?>
                                    <button onclick="marcarPagado(<?php echo $cob['id'] ?>)" class="btn btn-success" title="Marcar Pagado">✓</button>
                                <?php else: ?>
                                    <button onclick="marcarPendiente(<?php echo $cob['id'] ?>)" class="btn btn-pendiente" title="Marcar Pendiente">⏸️</button>
                                <?php endif; ?>
                                
                                <button onclick="anularCobro(<?php echo $cob['id'] ?>)" class="btn btn-anular" title="Anular">🗑️</button>
                            <?php else: ?>
                                <span style="color: #999;">Anulado</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal para editar -->
    <div id="modalCobro" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Editar Cobro</h2>
                <span class="close" onclick="cerrarModal()">&times;</span>
            </div>
            <form id="formEditarCobro" onsubmit="guardarEdicion(event)">
                <div class="modal-body">
                    <input type="hidden" id="edit_id" name="id">
                    <input type="hidden" name="action" value="editar">
                    
                    <div class="grid-3">
                        <div class="form-group">
                            <label>Período *</label>
                            <input type="text" id="edit_periodo" name="periodo" required>
                        </div>
                        <div class="form-group">
                            <label>Mes *</label>
                            <input type="number" id="edit_mes" name="mes" min="1" max="12" required>
                        </div>
                        <div class="form-group">
                            <label>Año *</label>
                            <input type="number" id="edit_anio" name="anio" required>
                        </div>
                    </div>
                    
                    <h3>Conceptos</h3>
                    <div class="grid-4">
                        <div class="form-group">
                            <label>Alquiler</label>
                            <input type="number" id="edit_alquiler" name="alquiler" step="0.01" oninput="calcularTotalEdit()">
                        </div>
                        <div class="form-group">
                            <label>Luz</label>
                            <input type="number" id="edit_luz" name="luz" step="0.01" oninput="calcularTotalEdit()">
                        </div>
                        <div class="form-group">
                            <label>Agua</label>
                            <input type="number" id="edit_agua" name="agua" step="0.01" oninput="calcularTotalEdit()">
                        </div>
                        <div class="form-group">
                            <label>Mantenimiento</label>
                            <input type="number" id="edit_mantenimiento" name="mantenimiento" step="0.01" oninput="calcularTotalEdit()">
                        </div>
                    </div>
                    
                    <div class="grid-3">
                        <div class="form-group">
                            <label>ABL</label>
                            <input type="number" id="edit_abl" name="abl" step="0.01" oninput="calcularTotalEdit()">
                        </div>
                        <div class="form-group">
                            <label>Otros Conceptos</label>
                            <input type="number" id="edit_otros_conceptos" name="otros_conceptos" step="0.01" oninput="calcularTotalEdit()">
                        </div>
                        <div class="form-group">
                            <label>Total</label>
                            <input type="text" id="edit_total" readonly style="background: #e9ecef; font-weight: bold;">
                        </div>
                    </div>
                    
                    <div class="grid-3">
                        <div class="form-group">
                            <label>Estado</label>
                            <select id="edit_status" name="status">
                                <option value="PENDIENTE">PENDIENTE</option>
                                <option value="PAGADO">PAGADO</option>
                                <option value="VENCIDO">VENCIDO</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Fecha Vencimiento</label>
                            <input type="date" id="edit_fecha_vencimiento" name="fecha_vencimiento">
                        </div>
                        <div class="form-group">
                            <label>Fecha Cobro</label>
                            <input type="date" id="edit_fecha_cobro" name="fecha_cobro">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Observaciones</label>
                        <textarea id="edit_observaciones" name="observaciones" rows="3"></textarea>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" onclick="cerrarModal()" class="btn btn-secondary">Cancelar</button>
                    <button type="submit" class="btn btn-danger">Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function cargarDatosInquilino() {
            const select = document.getElementById('inquilino_id');
            const option = select.options[select.selectedIndex];
            
            if (option.value) {
                const contrato = option.getAttribute('data-contrato');
                const propiedad = option.getAttribute('data-propiedad');
                
                document.getElementById('info_contrato').value = 'Contrato #' + contrato;
                document.getElementById('info_propiedad').value = propiedad;
            } else {
                document.getElementById('info_contrato').value = '';
                document.getElementById('info_propiedad').value = '';
            }
        }
        
        function actualizarPeriodo() {
            const meses = [null, 'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
            const mes = document.getElementById('mes').value;
            const anio = document.getElementById('anio').value;
            
            if (mes && anio) {
                const periodo = meses[parseInt(mes)] + '-' + anio.substring(2);
                document.getElementById('periodo').value = periodo;
            }
        }

        function calcularTotal(formPrefix = '') {
            const alquiler = parseFloat(document.getElementById(formPrefix + 'alquiler').value) || 0;
            const luz = parseFloat(document.getElementById(formPrefix + 'luz').value) || 0;
            const agua = parseFloat(document.getElementById(formPrefix + 'agua').value) || 0;
            const mantenimiento = parseFloat(document.getElementById(formPrefix + 'mantenimiento').value) || 0;
            const abl = parseFloat(document.getElementById(formPrefix + 'abl').value) || 0;
            const otros = parseFloat(document.getElementById(formPrefix + 'otros_conceptos').value) || 0;
            
            const total = alquiler + luz + agua + mantenimiento + abl + otros;
            const totalInput = document.getElementById(formPrefix + (formPrefix ? 'total' : 'total_calc'));
            totalInput.value = '$' + total.toLocaleString('es-AR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }

        // Inicializar
        actualizarPeriodo();
        calcularTotal();

        function cerrarModal() {
            document.getElementById('modalCobro').style.display = 'none';
        }

        function editarCobro(id) {
            fetch(`api/api_cobros.php?action=obtener&id=${id}`)
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        const cobro = data.data;
                        document.getElementById('edit_id').value = cobro.id;
                        document.getElementById('edit_periodo').value = cobro.periodo;
                        document.getElementById('edit_mes').value = cobro.mes;
                        document.getElementById('edit_anio').value = cobro.anio;
                        document.getElementById('edit_alquiler').value = cobro.alquiler;
                        document.getElementById('edit_luz').value = cobro.luz;
                        document.getElementById('edit_agua').value = cobro.agua;
                        document.getElementById('edit_mantenimiento').value = cobro.mantenimiento;
                        document.getElementById('edit_abl').value = cobro.abl;
                        document.getElementById('edit_otros_conceptos').value = cobro.otros_conceptos;
                        document.getElementById('edit_status').value = cobro.status;
                        document.getElementById('edit_fecha_vencimiento').value = cobro.fecha_vencimiento;
                        document.getElementById('edit_fecha_cobro').value = cobro.fecha_cobro;
                        document.getElementById('edit_observaciones').value = cobro.observaciones;
                        calcularTotalEdit();
                        document.getElementById('modalCobro').style.display = 'block';
                    } else {
                        alert('Error: ' + data.message);
                    }
                });
        }

        function guardarEdicion(event) {
            event.preventDefault();
            const formData = new FormData(document.getElementById('formEditarCobro'));
            fetch('api/api_cobros.php', { method: 'POST', body: formData })
                .then(res => res.json())
                .then(data => {
                    alert(data.message);
                    if (data.success) {
                        cerrarModal();
                        location.reload();
                    }
                });
        }

        function cambiarEstadoCobro(id, accion) {
            const msj = accion === 'marcar_pagado' ? '¿Marcar como PAGADO?' : '¿Marcar como PENDIENTE?';
            if (!confirm(msj)) return;
            const formData = new FormData();
            formData.append('action', accion);
            formData.append('id', id);
            fetch('api/api_cobros.php', { method: 'POST', body: formData })
                .then(res => res.json())
                .then(data => {
                    alert(data.message);
                    if (data.success) location.reload();
                });
        }

        function marcarPagado(id) {
            cambiarEstadoCobro(id, 'marcar_pagado');
        }

        function marcarPendiente(id) {
            cambiarEstadoCobro(id, 'marcar_pendiente');
        }

        function anularCobro(id) {
            if (!confirm('¿Está seguro de que desea ANULAR este cobro? Esta acción no se puede deshacer.')) return;
            const formData = new FormData();
            formData.append('action', 'anular');
            formData.append('id', id);
            fetch('api/api_cobros.php', { method: 'POST', body: formData })
                .then(res => res.json())
                .then(data => {
                    alert(data.message);
                    if (data.success) location.reload();
                });
        }

        function calcularTotalEdit() {
            calcularTotal('edit_');
        }

        window.onclick = function(event) {
            if (event.target == document.getElementById('modalCobro')) {
                cerrarModal();
            }
        }
    </script>
</body>
</html>