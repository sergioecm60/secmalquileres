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
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; padding: 20px; }
        .container { max-width: 1400px; margin: 0 auto; }
        
        header { background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.1); margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; }
        header .header-left { flex: 1; }
        header h1 { color: #333; font-size: 32px; margin-bottom: 10px; }
        header p { color: #666; font-size: 16px; }
        
        .user-info { display: flex; align-items: center; gap: 15px; }
        .user-avatar { width: 50px; height: 50px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 20px; font-weight: bold; }
        .user-details { text-align: right; }
        .user-name { font-weight: bold; color: #333; }
        .user-role { font-size: 12px; color: #999; }
        .btn-logout { padding: 8px 20px; background: #e74c3c; color: white; border: none; border-radius: 8px; cursor: pointer; font-size: 14px; transition: all 0.3s; text-decoration: none; display: inline-block; }
        .btn-logout:hover { background: #c0392b; transform: translateY(-2px); }
        
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: white; padding: 25px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); transition: transform 0.3s; }
        .stat-card:hover { transform: translateY(-5px); }
        .stat-card .icon { font-size: 40px; margin-bottom: 15px; }
        .stat-card .value { font-size: 36px; font-weight: bold; color: #333; margin-bottom: 5px; }
        .stat-card .label { color: #666; font-size: 14px; text-transform: uppercase; }
        
        .menu-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .menu-card { background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); text-align: center; transition: all 0.3s; text-decoration: none; display: block; }
        .menu-card:hover { transform: translateY(-5px); box-shadow: 0 6px 25px rgba(0,0,0,0.15); }
        .menu-card .icon { font-size: 50px; margin-bottom: 15px; }
        .menu-card h3 { color: #333; font-size: 20px; margin-bottom: 10px; }
        .menu-card p { color: #666; font-size: 14px; }
        
        .bg-blue { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
        .bg-green { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white; }
        .bg-purple { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); color: white; }
        .bg-red { background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); color: white; }
        .bg-orange { background: linear-gradient(135deg, #30cfd0 0%, #330867 100%); color: white; }
        .bg-teal { background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%); color: #333; }
        
        .recent-section { background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        .recent-section h2 { color: #333; margin-bottom: 20px; font-size: 24px; }
        
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #eee; }
        th { background: #f8f9fa; font-weight: bold; color: #555; }
        tr:hover { background: #f8f9fa; }
        
        .badge { display: inline-block; padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: bold; }
        .badge-warning { background: #ffc107; color: #000; }
        .badge-danger { background: #dc3545; color: white; }
    </style>
</head>
<body>
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
                <a href="logout.php" class="btn-logout" onclick="return confirm('¿Está seguro que desea cerrar sesión?')">🚪 Salir</a>
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
</body>
</html>