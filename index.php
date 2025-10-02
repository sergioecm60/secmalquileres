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