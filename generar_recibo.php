<?php
require_once 'check_auth.php';
require_once 'config.php';

// Obtener el cobro si se pasa un ID
$cobro_id = isset($_GET['id']) ? $_GET['id'] : null;
$cobro_data = null;

if ($cobro_id) {
    $stmt = $pdo->prepare("SELECT cob.*, CONCAT(i.nombre, ' ', i.apellido) as nombre_completo, i.dni, p.direccion, p.departamento, p.localidad 
                           FROM cobros cob 
                           JOIN inquilinos i ON cob.inquilino_id = i.id 
                           JOIN propiedades p ON cob.propiedad_id = p.id 
                           WHERE cob.id = ?");
    $stmt->execute([$cobro_id]);
    $cobro_data = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Obtener todos los cobros pagados para el selector
$cobros_pagados = $pdo->query("SELECT cob.id, cob.periodo, CONCAT(i.apellido, ', ', i.nombre) as nombre_completo, p.direccion, p.departamento, cob.total 
                               FROM cobros cob 
                               JOIN inquilinos i ON cob.inquilino_id = i.id 
                               JOIN propiedades p ON cob.propiedad_id = p.id 
                               WHERE cob.status = 'PAGADO' 
                               ORDER BY cob.anio DESC, cob.mes DESC")->fetchAll(PDO::FETCH_ASSOC);

// Función para convertir número a letras
function numeroALetras($numero) {
    $entero = floor($numero);
    $decimales = round(($numero - $entero) * 100, 2);
    if ($decimales == 0) {
        $decimales_texto = '00/100';
    } else {
        $decimales_texto = str_pad($decimales, 2, '0', STR_PAD_LEFT) . '/100';
    }

    $letras = '';
    if ($entero == 0) {
        $letras = 'CERO';
    } else {
        $letras = convertirGrupo($entero);
    }

    return "$letras CON $decimales_texto";
}

function convertirGrupo($n) {
    $unidades = ['', 'UNO', 'DOS', 'TRES', 'CUATRO', 'CINCO', 'SEIS', 'SIETE', 'OCHO', 'NUEVE'];
    $decenas = ['', 'DIEZ', 'VEINTE', 'TREINTA', 'CUARENTA', 'CINCUENTA', 'SESENTA', 'SETENTA', 'OCHENTA', 'NOVENTA'];
    $centenas = ['', 'CIENTO', 'DOSCIENTOS', 'TRESCIENTOS', 'CUATROCIENTOS', 'QUINIENTOS', 'SEISCIENTOS', 'SETECIENTOS', 'OCHOCIENTOS', 'NOVECIENTOS'];
    $especiales = [
        11 => 'ONCE', 12 => 'DOCE', 13 => 'TRECE', 14 => 'CATORCE', 15 => 'QUINCE',
        16 => 'DIECISEIS', 17 => 'DIECISIETE', 18 => 'DIECIOCHO', 19 => 'DIECINUEVE',
        21 => 'VEINTIUNO', 22 => 'VEINTIDOS', 23 => 'VEINTITRES', 24 => 'VEINTICUATRO',
        25 => 'VEINTICINCO', 26 => 'VEINTISEIS', 27 => 'VEINTISIETE', 28 => 'VEINTIOCHO', 29 => 'VEINTINUEVE'
    ];

    $output = '';

    if ($n >= 1000000000) {
        $billones = floor($n / 1000000000);
        $n %= 1000000000;
        $output .= ($billones > 1 ? convertirGrupo($billones) . ' MIL' : 'MIL') . ' ';
        if ($n == 0) $output .= 'MILLONES';
    }

    if ($n >= 1000000) {
        $millones = floor($n / 1000000);
        $n %= 1000000;
        $output .= ($millones > 1 ? convertirGrupo($millones) . ' MILLONES' : 'UN MILLON') . ' ';
    }

    if ($n >= 1000) {
        $miles = floor($n / 1000);
        $n %= 1000;
        $output .= ($miles > 1 ? convertirGrupo($miles) . ' MIL' : 'MIL') . ' ';
    }

    if ($n >= 100) {
        $c = floor($n / 100);
        $n %= 100;
        if ($c == 1 && $n > 0) {
            $output .= 'CIENTO ';
        } else {
            $output .= $centenas[$c] . ' ';
        }
    }

    if ($n > 0) {
        if ($n < 10) {
            $output .= $unidades[$n];
        } elseif ($n < 30) {
            if (array_key_exists($n, $especiales)) {
                $output .= $especiales[$n];
            } else {
                $d = floor($n / 10);
                $u = $n % 10;
                $output .= $decenas[$d] . ($u > 0 ? ' Y ' . $unidades[$u] : '');
            }
        } else {
            $d = floor($n / 10);
            $u = $n % 10;
            $output .= $decenas[$d] . ($u > 0 ? ' Y ' . $unidades[$u] : '');
        }
    }

    return trim(str_replace('UNO ', 'UN ', $output));
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generador de Recibos</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f4f4; padding: 20px; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .selector-container { margin-bottom: 30px; padding: 20px; background: #f8f9fa; border-radius: 8px; }
        h1 { color: #333; margin-bottom: 20px; }
        label { font-weight: bold; margin-right: 10px; }
        select, button { padding: 10px; font-size: 16px; border-radius: 4px; border: 1px solid #ddd; }
        button { background: #007bff; color: white; cursor: pointer; }
        button:hover { background: #0056b3; }
        .recibo { border: 2px solid #333; padding: 20px; margin-top: 20px; }
        .recibo-header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #333; padding-bottom: 10px; margin-bottom: 20px; }
        .recibo-header h2 { margin: 0; color: #333; }
        .recibo-header .info { text-align: right; }
        .recibo-body p { margin: 5px 0; line-height: 1.6; }
        .recibo-body .monto-letras { font-weight: bold; text-transform: uppercase; }
        .recibo-detalles { width: 100%; border-collapse: collapse; margin: 20px 0; }
        .recibo-detalles th, .recibo-detalles td { border: 1px solid #ccc; padding: 8px; text-align: left; }
        .recibo-detalles th { background: #f2f2f2; }
        .recibo-detalles .total { font-weight: bold; }
        .recibo-footer { margin-top: 40px; padding-top: 20px; border-top: 1px solid #ccc; text-align: center; }
        @media print {
            body { background: white; padding: 0; }
            .container { box-shadow: none; border-radius: 0; padding: 0; }
            .selector-container, .print-button { display: none; }
            .recibo { border: none; margin-top: 0; }
        }
    </style>
    <style>
        .btn-secondary { background: #6c757d; color: white; text-decoration: none; display: inline-block; }
        .btn-secondary:hover { background: #5a6268; }
    </style>
</head>
<body>
    <div class="container">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
            <h1>🧾 Generador de Recibos</h1>
            <a href="index.php" class="btn btn-secondary" style="padding: 10px 15px; font-size: 16px; border-radius: 4px;">↩️ Volver al Menú</a>
        </div>
        <div class="selector-container">
            <form method="GET" action="">
                <label for="id">Seleccione un cobro pagado:</label>
                <select name="id" id="id" onchange="this.form.submit()">
                    <option value="">-- Seleccionar Recibo --</option>
                    <?php foreach ($cobros_pagados as $cobro): ?>
                        <option value="<?php echo $cobro['id']; ?>" <?php echo ($cobro_id == $cobro['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cobro['periodo'] . ' - ' . $cobro['nombre_completo'] . ' (' . $cobro['direccion'] . ') - ' . formatearMoneda($cobro['total'])); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>

        <?php if ($cobro_data): ?>
        <div class="recibo" id="recibo-a-imprimir">
            <div class="recibo-header">
                <div>
                    <h2>RECIBO DE PAGO</h2>
                    <p><strong>Propietario:</strong> [Nombre del Propietario/Inmobiliaria]</p>
                    <p><strong>Dirección:</strong> [Dirección del Propietario]</p>
                    <p><strong>Teléfono:</strong> [Teléfono del Propietario]</p>
                </div>
                <div class="info">
                    <p><strong>Recibo N°:</strong> <?php echo str_pad($cobro_data['id'], 8, '0', STR_PAD_LEFT); ?></p>
                    <p><strong>Fecha:</strong> <?php echo formatearFecha($cobro_data['fecha_cobro']); ?></p>
                    <p><strong>Total:</strong> <span style="font-size: 1.2em; font-weight: bold;"><?php echo formatearMoneda($cobro_data['total']); ?></span></p>
                </div>
            </div>

            <div class="recibo-body">
                <p>
                    Recibí de <strong><?php echo htmlspecialchars($cobro_data['nombre_completo']); ?></strong>, 
                    DNI <strong><?php echo htmlspecialchars($cobro_data['dni']); ?></strong>,
                    la suma de <strong class="monto-letras">PESOS <?php echo numeroALetras($cobro_data['total']); ?></strong>.
                </p>
                <p>
                    En concepto de pago del alquiler y otros servicios correspondientes al período 
                    <strong><?php echo strtoupper($cobro_data['periodo']); ?></strong>, 
                    por el inmueble sito en 
                    <strong><?php echo htmlspecialchars($cobro_data['direccion'] . ' ' . $cobro_data['departamento'] . ', ' . $cobro_data['localidad']); ?></strong>.
                </p>
            </div>

            <table class="recibo-detalles">
                <thead>
                    <tr>
                        <th>Concepto</th>
                        <th>Monto</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($cobro_data['alquiler'] > 0): ?><tr><td>Alquiler</td><td><?php echo formatearMoneda($cobro_data['alquiler']); ?></td></tr><?php endif; ?>
                    <?php if($cobro_data['luz'] > 0): ?><tr><td>Luz (EDENOR)</td><td><?php echo formatearMoneda($cobro_data['luz']); ?></td></tr><?php endif; ?>
                    <?php if($cobro_data['agua'] > 0): ?><tr><td>Agua (ABSA)</td><td><?php echo formatearMoneda($cobro_data['agua']); ?></td></tr><?php endif; ?>
                    <?php if($cobro_data['mantenimiento'] > 0): ?><tr><td>Mantenimiento</td><td><?php echo formatearMoneda($cobro_data['mantenimiento']); ?></td></tr><?php endif; ?>
                    <?php if($cobro_data['abl'] > 0): ?><tr><td>ABL</td><td><?php echo formatearMoneda($cobro_data['abl']); ?></td></tr><?php endif; ?>
                    <?php if($cobro_data['otros_conceptos'] > 0): ?><tr><td>Otros Conceptos</td><td><?php echo formatearMoneda($cobro_data['otros_conceptos']); ?></td></tr><?php endif; ?>
                    <tr class="total">
                        <td>TOTAL</td>
                        <td><?php echo formatearMoneda($cobro_data['total']); ?></td>
                    </tr>
                </tbody>
            </table>

            <?php if(!empty($cobro_data['observaciones'])): ?>
                <p><strong>Observaciones:</strong> <?php echo htmlspecialchars($cobro_data['observaciones']); ?></p>
            <?php endif; ?>

            <div class="recibo-footer">
                <p>Firma y Aclaración</p>
            </div>
        </div>
        <button class="print-button" onclick="window.print()" style="margin-top: 20px;">🖨️ Imprimir Recibo</button>
        <?php endif; ?>
    </div>
</body>
</html>