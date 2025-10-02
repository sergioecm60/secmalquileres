<?php
// Verificar autenticación
require 'check_auth.php';
require_once 'config.php';

// Obtener estadísticas
$total_inquilinos = $pdo->query("SELECT COUNT(*) FROM inquilinos WHERE estado = 'activo'")->fetchColumn();
$total_propiedades = $pdo->query("SELECT COUNT(*) FROM propiedades WHERE activo = 1")->fetchColumn();
$total_contratos = $pdo->query("SELECT COUNT(*) FROM contratos WHERE activo = 1")->fetchColumn();
$cobros_pendientes = $pdo->query("SELECT COUNT(*) FROM cobros WHERE status = 'PENDIENTE'")->fetchColumn();
$cobros_vencidos = $pdo->query("SELECT COUNT(*) FROM cobros WHERE status = 'VENCIDO'")->fetchColumn();
$total_cobrado_mes = $pdo->query("SELECT COALESCE(SUM(total), 0) FROM cobros WHERE status = 'PAGADO' AND MONTH(fecha_cobro) = MONTH(CURDATE()) AND YEAR(fecha_cobro) = YEAR(CURDATE())")->fetchColumn();

// Después de las estadísticas existentes, agregar:
$contratos_vencimiento = $pdo->query("SELECT COUNT(*) FROM contratos WHERE activo = 1 AND fecha_fin BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)")->fetchColumn();
$contratos_vencimiento_critico = $pdo->query("SELECT COUNT(*) FROM contratos WHERE activo = 1 AND fecha_fin BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)")->fetchColumn();

// Obtener datos para gráficos
$cobros_por_mes = $pdo->query("SELECT mes, SUM(total) as total FROM cobros WHERE status = 'PAGADO' AND anio = YEAR(CURDATE()) GROUP BY mes ORDER BY mes")->fetchAll(PDO::FETCH_ASSOC);

$propiedades_por_estado = $pdo->query("SELECT 
    SUM(CASE WHEN activo = 1 THEN 1 ELSE 0 END) as activas,
    SUM(CASE WHEN activo = 0 THEN 1 ELSE 0 END) as inactivas
    FROM propiedades")->fetch(PDO::FETCH_ASSOC);

$cobros_por_estado = $pdo->query("SELECT status, COUNT(*) as cantidad FROM cobros WHERE anio = YEAR(CURDATE()) GROUP BY status")->fetchAll(PDO::FETCH_ASSOC);

// Próximos vencimientos
$proximos_vencimientos = $pdo->query("SELECT cob.*, CONCAT(i.nombre, ' ', i.apellido) as nombre_completo, p.direccion, p.departamento 
                                       FROM cobros cob 
                                       JOIN inquilinos i ON cob.inquilino_id = i.id 
                                       JOIN propiedades p ON cob.propiedad_id = p.id 
                                       WHERE cob.status = 'PENDIENTE' 
                                       ORDER BY cob.fecha_vencimiento ASC 
                                       LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);

// Obtener nombre del usuario
$nombre_usuario = obtenerNombreUsuario();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Gestión de Alquileres</title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="dashboard-body">
    <div class="container">
        <header>
            <div class="header-left">
                <h1>🏠 Sistema de Gestión de Alquileres</h1>
                <p>Panel de Control y Administración</p>
            </div>
            <div class="user-info">
                <div class="user-avatar">
                    <?php echo strtoupper(substr($nombre_usuario, 0, 1)); ?>
                </div>
                <div class="user-details">
                    <div class="user-name"><?php echo htmlspecialchars($nombre_usuario); ?></div>
                    <div class="user-role">Administrador</div>
                </div>
                <a href="logout.php" class="btn btn-logout" onclick="return confirm('¿Está seguro que desea cerrar sesión?')">🚪 Salir</a>
            </div>
        </header>

        <div class="stats-grid">
            <div class="stat-card bg-blue">
                <div class="icon">👥</div>
                <div class="value"><?php echo $total_inquilinos; ?></div>
                <div class="label">Inquilinos Activos</div>
            </div>
            
            <div class="stat-card bg-green">
                <div class="icon">🏢</div>
                <div class="value"><?php echo $total_propiedades; ?></div>
                <div class="label">Propiedades</div>
            </div>
            
            <div class="stat-card bg-purple">
                <div class="icon">📄</div>
                <div class="value"><?php echo $total_contratos; ?></div>
                <div class="label">Contratos Activos</div>
            </div>
            
            <div class="stat-card bg-orange">
                <div class="icon">⏰</div>
                <div class="value"><?php echo $cobros_pendientes; ?></div>
                <div class="label">Cobros Pendientes</div>
            </div>
            
            <div class="stat-card bg-red">
                <div class="icon">⚠️</div>
                <div class="value"><?php echo $cobros_vencidos; ?></div>
                <div class="label">Cobros Vencidos</div>
            </div>
            
            <div class="stat-card bg-teal">
                <div class="icon">💰</div>
                <div class="value"><?php echo formatearMoneda($total_cobrado_mes); ?></div>
                <div class="label">Cobrado Este Mes</div>
            </div>
            
            <!-- Agregar tarjeta en el dashboard -->
            <?php if ($contratos_vencimiento > 0): ?>
            <div class="stat-card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white;">
                <div class="icon">⚠️</div>
                <div class="value"><?= $contratos_vencimiento ?></div>
                <div class="label">Contratos por Vencer</div>
                <a href="alertas.php" style="color: white; text-decoration: underline; font-size: 12px;">Ver detalles</a>
            </div>
            <?php endif; ?>
        </div>

        <div class="menu-grid">
            <a href="form_inquilinos.php" class="menu-card">
                <div class="icon">👤</div>
                <h3>Inquilinos</h3>
                <p>Gestionar inquilinos y sus datos personales</p>
            </a>
            
            <a href="form_propiedades.php" class="menu-card">
                <div class="icon">🏠</div>
                <h3>Propiedades</h3>
                <p>Administrar propiedades disponibles</p>
            </a>
            
            <a href="form_contratos.php" class="menu-card">
                <div class="icon">📋</div>
                <h3>Contratos</h3>
                <p>Crear y gestionar contratos de alquiler</p>
            </a>
            
            <a href="form_cobros.php" class="menu-card">
                <div class="icon">💵</div>
                <h3>Cobros</h3>
                <p>Registrar pagos y cobros mensuales</p>
            </a>
            
            <a href="generar_recibo.php" class="menu-card">
                <div class="icon">🧾</div>
                <h3>Recibos</h3>
                <p>Generar e imprimir recibos de pago</p>
            </a>

            <?php if (esAdmin()): ?>
            <a href="form_users.php" class="menu-card">
                <div class="icon">⚙️</div>
                <h3>Usuarios</h3>
                <p>Administrar usuarios y permisos del sistema</p>
            </a>
            <?php endif; ?>

        </div>

        <!-- Agregar sección de gráficos después de las tarjetas de estadísticas -->
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 30px;">
            <!-- Gráfico de ingresos mensuales -->
            <div style="background: white; padding: 20px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                <h3 style="margin-bottom: 15px;">📊 Ingresos Mensuales <?= date('Y') ?></h3>
                <canvas id="graficoIngresos"></canvas>
            </div>
            
            <!-- Gráfico de estados de cobros -->
            <div style="background: white; padding: 20px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                <h3 style="margin-bottom: 15px;">📈 Estados de Cobros</h3>
                <canvas id="graficoCobros"></canvas>
            </div>
        </div>

        <script>
        // Gráfico de ingresos mensuales
        const ctxIngresos = document.getElementById('graficoIngresos').getContext('2d');
        new Chart(ctxIngresos, {
            type: 'bar',
            data: {
                labels: ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'],
                datasets: [{
                    label: 'Ingresos ($)',
                    data: [
                        <?php 
                        $datos_meses = array_fill(1, 12, 0);
                        foreach ($cobros_por_mes as $dato) {
                            $datos_meses[$dato['mes']] = $dato['total'];
                        }
                        echo implode(',', array_values($datos_meses));
                        ?>
                    ],
                    backgroundColor: 'rgba(102, 126, 234, 0.8)',
                    borderColor: 'rgba(102, 126, 234, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '$' + value.toLocaleString();
                            }
                        }
                    }
                }
            }
        });

        // Gráfico de estados de cobros
        const ctxCobros = document.getElementById('graficoCobros').getContext('2d');
        new Chart(ctxCobros, {
            type: 'doughnut',
            data: {
                labels: ['Pendientes', 'Pagados', 'Vencidos'],
                datasets: [{
                    data: [<?php $estados = ['PENDIENTE' => 0, 'PAGADO' => 0, 'VENCIDO' => 0]; foreach ($cobros_por_estado as $dato) { if(isset($estados[$dato['status']])) $estados[$dato['status']] = $dato['cantidad']; } echo $estados['PENDIENTE'] . "," . $estados['PAGADO'] . "," . $estados['VENCIDO']; ?>],
                    backgroundColor: ['#ffc107', '#28a745', '#dc3545']
                }]
            }
        });
        </script>
        <?php if (count($proximos_vencimientos) > 0): ?>
        <div class="recent-section">
            <h2>⏰ Próximos Vencimientos</h2>
            <table>
                <thead>
                    <tr>
                        <th>Inquilino</th>
                        <th>Propiedad</th>
                        <th>Período</th>
                        <th>Monto</th>
                        <th>Vencimiento</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($proximos_vencimientos as $venc): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($venc['nombre_completo']); ?></td>
                        <td><?php echo htmlspecialchars($venc['direccion'] . ' ' . $venc['departamento']); ?></td>
                        <td><?php echo $venc['periodo']; ?></td>
                        <td><?php echo formatearMoneda($venc['total']); ?></td>
                        <td><?php echo formatearFecha($venc['fecha_vencimiento']); ?></td>
                        <td>
                            <?php if ($venc['status'] == 'VENCIDO'): ?>
                                <span class="badge badge-danger">VENCIDO</span>
                            <?php else: ?>
                                <span class="badge badge-warning">PENDIENTE</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

    <!-- Footer con licencia -->
    <footer style="background: white; margin-top: 30px; padding: 20px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); text-align: center; max-width: 1400px; margin-left: auto; margin-right: auto;">
        <div style="border-top: 2px solid #667eea; padding-top: 15px;">
            <p style="margin: 8px 0; color: #333; font-size: 13px;">
                <strong>SECM Gestión de Alquileres</strong> | 
                By <strong>Sergio Cabrera</strong> | 
                Copyleft © 2025 | 
                <a href="licence.php" style="color: #667eea; text-decoration: none; font-weight: bold;">Licencia GNU GPL v3</a>
            </p>
            <p style="margin: 8px 0; color: #666; font-size: 12px;">
                ¿Necesitas ayuda? 
                <a href="mailto:sergiomiers@gmail.com" style="color: #667eea; text-decoration: none;">📧 sergiomiers@gmail.com</a> | 
                <a href="https://wa.me/541167598452" target="_blank" style="color: #667eea; text-decoration: none;">💬 WhatsApp +54 11 6759-8452</a>
            </p>
        </div>
    </footer>

</body>
</html>