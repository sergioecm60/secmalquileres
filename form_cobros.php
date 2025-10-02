<?php
include 'config.php';

// Procesar formulario de cobro
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'guardar_cobro') {
    try {
        // Calcular total
        $total = floatval($_POST['alquiler']) + floatval($_POST['luz']) + floatval($_POST['agua']) + 
                 floatval($_POST['mantenimiento']) + floatval($_POST['abl']) + floatval($_POST['otros_conceptos']);
        
        // Obtener datos del contrato
        $stmt = $pdo->prepare("SELECT inquilino_id, propiedad_id FROM contratos WHERE id = ?");
        $stmt->execute([$_POST['contrato_id']]);
        $contrato = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Insertar cobro
        $stmt = $pdo->prepare("INSERT INTO cobros (contrato_id, inquilino_id, propiedad_id, periodo, mes, anio, 
                               alquiler, luz, agua, mantenimiento, abl, otros_conceptos, total, status, 
                               fecha_cobro, fecha_vencimiento, observaciones) 
                               VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $_POST['contrato_id'],
            $contrato['inquilino_id'],
            $contrato['propiedad_id'],
            $_POST['periodo'],
            $_POST['mes'],
            $_POST['anio'],
            $_POST['alquiler'],
            $_POST['luz'],
            $_POST['agua'],
            $_POST['mantenimiento'],
            $_POST['abl'],
            $_POST['otros_conceptos'],
            $total,
            $_POST['status'],
            $_POST['fecha_cobro'] ?: null,
            $_POST['fecha_vencimiento'] ?: null,
            $_POST['observaciones']
        ]);
        
        $mensaje = "Cobro registrado exitosamente con ID: " . $pdo->lastInsertId();
        $tipo_mensaje = "success";
    } catch(PDOException $e) {
        $mensaje = "Error: " . $e->getMessage();
        $tipo_mensaje = "error";
    }
}

// Marcar como pagado
if (isset($_GET['marcar_pagado'])) {
    try {
        $stmt = $pdo->prepare("UPDATE cobros SET status = 'PAGADO', fecha_cobro = CURDATE() WHERE id = ?");
        $stmt->execute([$_GET['marcar_pagado']]);
        $mensaje = "Cobro marcado como PAGADO";
        $tipo_mensaje = "success";
    } catch(PDOException $e) {
        $mensaje = "Error: " . $e->getMessage();
        $tipo_mensaje = "error";
    }
}

// Obtener contratos activos para el selector
$contratos = $pdo->query("SELECT c.id, c.codigo, i.nombre_apellido, p.direccion, p.departamento 
                          FROM contratos c 
                          JOIN inquilinos i ON c.inquilino_id = i.id 
                          JOIN propiedades p ON c.propiedad_id = p.id 
                          WHERE c.activo = 1 
                          ORDER BY i.nombre_apellido")->fetchAll(PDO::FETCH_ASSOC);

// Obtener cobros registrados
$cobros = $pdo->query("SELECT cob.*, i.nombre_apellido, p.direccion, p.departamento, p.localidad 
                       FROM cobros cob 
                       JOIN inquilinos i ON cob.inquilino_id = i.id 
                       JOIN propiedades p ON cob.propiedad_id = p.id 
                       ORDER BY cob.anio DESC, cob.mes DESC, cob.id DESC")->fetchAll(PDO::FETCH_ASSOC);

$meses = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
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
        .container { max-width: 1600px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #333; margin-bottom: 30px; border-bottom: 3px solid #dc3545; padding-bottom: 10px; }
        h2 { color: #555; margin: 30px 0 20px; font-size: 20px; }
        h3 { color: #666; margin: 20px 0 15px; font-size: 16px; background: #f8f9fa; padding: 10px; border-left: 4px solid #dc3545; }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; color: #555; font-size: 14px; }
        input, select, textarea { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px; }
        textarea { min-height: 80px; resize: vertical; }
        input:focus, select:focus, textarea:focus { outline: none; border-color: #dc3545; }
        .btn { padding: 12px 30px; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; transition: all 0.3s; text-decoration: none; display: inline-block; }
        .btn-danger { background: #dc3545; color: white; }
        .btn-danger:hover { background: #c82333; }
        .btn-success { background: #28a745; color: white; font-size: 12px; padding: 6px 12px; }
        .btn-success:hover { background: #218838; }
        .mensaje { padding: 15px; margin-bottom: 20px; border-radius: 4px; }
        .mensaje.success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .mensaje.error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; font-size: 12px; }
        th, td { padding: 8px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #dc3545; color: white; font-weight: bold; position: sticky; top: 0; }
        tr:hover { background: #f5f5f5; }
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; }
        .grid-4 { display: grid; grid-template-columns: 1fr 1fr 1fr 1fr; gap: 20px; }
        .badge { display: inline-block; padding: 5px 10px; border-radius: 3px; font-size: 11px; font-weight: bold; }
        .badge-success { background: #28a745; color: white; }
        .badge-warning { background: #ffc107; color: #000; }
        .badge-danger { background: #dc3545; color: white; }
        .card { background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #dee2e6; }
        .money { text-align: right; font-family: 'Courier New', monospace; font-weight: bold; }
        .total-row { background: #fff3cd; font-weight: bold; }
        .table-container { max-height: 600px; overflow-y: auto; }
    </style>
</head>
<body>
    <div class="container">
        <h1>💰 Gestión de Cobros y Pagos</h1>
        
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
                        <label for="contrato_id">Contrato *</label>
                        <select id="contrato_id" name="contrato_id" required>
                            <option value="">Seleccione un contrato</option>
                            <?php foreach ($contratos as $cont): ?>
                                <option value="<?php echo $cont['id']; ?>">
                                    <?php echo htmlspecialchars($cont['nombre_apellido'] . ' - ' . $cont['direccion'] . ' ' . $cont['departamento']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="mes">Mes *</label>
                        <select id="mes" name="mes" required onchange="actualizarPeriodo()">
                            <?php for ($i = 1; $i <= 12; $i++): ?>
                                <option value="<?php echo $i; ?>"><?php echo $meses[$i-1]; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="anio">Año *</label>
                        <input type="number" id="anio" name="anio" value="<?php echo date('Y'); ?>" required onchange="actualizarPeriodo()">
                    </div>
                </div>

                <div class="form-group">
                    <label for="periodo">Período</label>
                    <input type="text" id="periodo" name="periodo" readonly>
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
                        <td class="money" style="font-size: 14px;"><strong>$<?php echo number_format($cob['total'], 2, ',', '.'); ?></strong></td>
                        <td>
                            <?php if ($cob['status'] == 'PAGADO'): ?>
                                <span class="badge badge-success">PAGADO</span>
                            <?php elseif ($cob['status'] == 'PENDIENTE'): ?>
                                <span class="badge badge-warning">PENDIENTE</span>
                            <?php else: ?>
                                <span class="badge badge-danger">VENCIDO</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo $cob['fecha_cobro'] ? date('d/m/Y', strtotime($cob['fecha_cobro'])) : '-'; ?></td>
                        <td>
                            <?php if ($cob['status'] != 'PAGADO'): ?>
                                <a href="?marcar_pagado=<?php echo $cob['id']; ?>" class="btn btn-success" onclick="return confirm('¿Marcar como PAGADO?')">✓ Pagado</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        function actualizarPeriodo() {
            const meses = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
            const mes = document.getElementById('mes').value;
            const anio = document.getElementById('anio').value;
            
            if (mes && anio) {
                const periodo = meses[mes - 1] + '-' + anio.substring(2);
                document.getElementById('periodo').value = periodo;
            }
        }

        function calcularTotal() {
            const alquiler = parseFloat(document.getElementById('alquiler').value) || 0;
            const luz = parseFloat(document.getElementById('luz').value) || 0;
            const agua = parseFloat(document.getElementById('agua').value) || 0;
            const mantenimiento = parseFloat(document.getElementById('mantenimiento').value) || 0;
            const abl = parseFloat(document.getElementById('abl').value) || 0;
            const otros = parseFloat(document.getElementById('otros_conceptos').value) || 0;
            
            const total = alquiler + luz + agua + mantenimiento + abl + otros;
            document.getElementById('total_calc').value = '$' + total.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
        }

        // Inicializar
        actualizarPeriodo();
        calcularTotal();
    </script>
</body>
</html>