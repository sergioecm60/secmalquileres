<?php
require_once 'check_auth.php';
require_once 'config.php';

// Obtener contratos próximos a vencer
function obtenerContratosProximosVencer($pdo, $dias = 30) {
    $sql = "SELECT c.*, 
            CONCAT(i.apellido, ', ', i.nombre) as inquilino,
            p.direccion, p.departamento,
            DATEDIFF(c.fecha_fin, CURDATE()) as dias_restantes
            FROM contratos c
            JOIN inquilinos i ON c.inquilino_id = i.id
            JOIN propiedades p ON c.propiedad_id = p.id
            WHERE c.activo = 1 
            AND c.fecha_fin BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ? DAY)
            ORDER BY c.fecha_fin ASC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$dias]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$contratos_30_dias = obtenerContratosProximosVencer($pdo, 30);
$contratos_7_dias = obtenerContratosProximosVencer($pdo, 7);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alertas de Vencimientos</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f4f4f4; padding: 20px; }
        .container { max-width: 1400px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #333; margin-bottom: 30px; border-bottom: 3px solid #dc3545; padding-bottom: 10px; }
        .alert-section { margin-bottom: 30px; }
        .alert-section h2 { color: #555; margin-bottom: 15px; font-size: 20px; }
        .alert-card {
            background: white;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin-bottom: 10px;
            border-radius: 4px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .alert-card.critico { border-left-color: #dc3545; background: #fff5f5; }
        .alert-info { flex: 1; }
        .alert-info h3 { color: #333; font-size: 16px; margin-bottom: 5px; }
        .alert-info p { color: #666; font-size: 14px; margin: 3px 0; }
        .alert-dias {
            background: #ffc107;
            color: #000;
            padding: 10px 20px;
            border-radius: 50px;
            font-weight: bold;
            font-size: 16px;
            text-align: center;
            min-width: 120px;
        }
        .alert-dias.critico { background: #dc3545; color: white; }
        .btn { padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; display: inline-block; }
        .btn-secondary { background: #6c757d; color: white; }
        .no-alerts {
            text-align: center;
            padding: 40px;
            color: #28a745;
            font-size: 18px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
            <h1>⚠️ Alertas de Vencimiento de Contratos</h1>
            <a href="index.php" class="btn btn-secondary">← Volver al Dashboard</a>
        </div>

        <!-- Alertas críticas (7 días) -->
        <div class="alert-section">
            <h2>🚨 Vencimientos Críticos (próximos 7 días)</h2>
            <?php if (count($contratos_7_dias) > 0): ?>
                <?php foreach ($contratos_7_dias as $contrato): ?>
                    <div class="alert-card critico">
                        <div class="alert-info">
                            <h3><?= htmlspecialchars($contrato['inquilino']) ?></h3>
                            <p><strong>Propiedad:</strong> <?= htmlspecialchars($contrato['direccion'] . ' ' . $contrato['departamento']) ?></p>
                            <p><strong>Fecha de vencimiento:</strong> <?= formatearFecha($contrato['fecha_fin']) ?></p>
                            <p><strong>Contrato:</strong> <?= htmlspecialchars($contrato['codigo']) ?></p>
                        </div>
                        <div class="alert-dias critico">
                            <?= $contrato['dias_restantes'] ?> días
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="no-alerts">✅ No hay contratos por vencer en los próximos 7 días</div>
            <?php endif; ?>
        </div>

        <!-- Alertas normales (30 días) -->
        <div class="alert-section">
            <h2>⚠️ Vencimientos Próximos (próximos 30 días)</h2>
            <?php if (count($contratos_30_dias) > 0): ?>
                <?php foreach ($contratos_30_dias as $contrato): ?>
                    <div class="alert-card">
                        <div class="alert-info">
                            <h3><?= htmlspecialchars($contrato['inquilino']) ?></h3>
                            <p><strong>Propiedad:</strong> <?= htmlspecialchars($contrato['direccion'] . ' ' . $contrato['departamento']) ?></p>
                            <p><strong>Fecha de vencimiento:</strong> <?= formatearFecha($contrato['fecha_fin']) ?></p>
                            <p><strong>Contrato:</strong> <?= htmlspecialchars($contrato['codigo']) ?></p>
                        </div>
                        <div class="alert-dias">
                            <?= $contrato['dias_restantes'] ?> días
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="no-alerts">✅ No hay contratos por vencer en los próximos 30 días</div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>