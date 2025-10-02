<?php
// api/api_cobros.php
require_once '../check_auth.php';
require_once '../config.php';

header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'obtener':
        obtenerCobro($pdo);
        break;
    case 'editar':
        editarCobro($pdo);
        break;
    case 'anular':
        anularCobro($pdo);
        break;
    case 'marcar_pagado':
        marcarPagado($pdo);
        break;
    case 'marcar_pendiente':
        marcarPendiente($pdo);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Acción no válida']);
}

function obtenerCobro($pdo) {
    $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    
    if (!$id) {
        echo json_encode(['success' => false, 'message' => 'ID inválido']);
        return;
    }
    
    try {
        $stmt = $pdo->prepare("SELECT cob.*, CONCAT(i.nombre, ' ', i.apellido) as nombre_completo, p.direccion, p.departamento
                               FROM cobros cob
                               JOIN inquilinos i ON cob.inquilino_id = i.id
                               JOIN propiedades p ON cob.propiedad_id = p.id
                               WHERE cob.id = ?");
        $stmt->execute([$id]);
        $cobro = $stmt->fetch();
        
        if ($cobro) {
            echo json_encode(['success' => true, 'data' => $cobro]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Cobro no encontrado']);
        }
    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}

function editarCobro($pdo) {
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    
    if (!$id) {
        echo json_encode(['success' => false, 'message' => 'ID inválido']);
        return;
    }
    
    $datos = [
        'periodo' => trim($_POST['periodo'] ?? ''),
        'mes' => filter_var($_POST['mes'], FILTER_VALIDATE_INT),
        'anio' => filter_var($_POST['anio'], FILTER_VALIDATE_INT),
        'alquiler' => filter_var($_POST['alquiler'], FILTER_VALIDATE_FLOAT) ?: 0,
        'luz' => filter_var($_POST['luz'], FILTER_VALIDATE_FLOAT) ?: 0,
        'agua' => filter_var($_POST['agua'], FILTER_VALIDATE_FLOAT) ?: 0,
        'mantenimiento' => filter_var($_POST['mantenimiento'], FILTER_VALIDATE_FLOAT) ?: 0,
        'abl' => filter_var($_POST['abl'], FILTER_VALIDATE_FLOAT) ?: 0,
        'otros_conceptos' => filter_var($_POST['otros_conceptos'], FILTER_VALIDATE_FLOAT) ?: 0,
        'status' => $_POST['status'] ?? 'PENDIENTE',
        'fecha_cobro' => $_POST['fecha_cobro'] ?: null,
        'fecha_vencimiento' => $_POST['fecha_vencimiento'] ?: null,
        'observaciones' => trim($_POST['observaciones'] ?? '')
    ];
    
    $total = $datos['alquiler'] + $datos['luz'] + $datos['agua'] + 
             $datos['mantenimiento'] + $datos['abl'] + $datos['otros_conceptos'];
    
    if (empty($datos['periodo']) || empty($datos['mes']) || empty($datos['anio'])) {
        echo json_encode(['success' => false, 'message' => 'Período, mes y año son obligatorios']);
        return;
    }
    
    try {
        $stmt = $pdo->prepare("UPDATE cobros SET 
                              periodo=?, mes=?, anio=?, alquiler=?, luz=?, agua=?,
                              mantenimiento=?, abl=?, otros_conceptos=?, total=?,
                              status=?, fecha_cobro=?, fecha_vencimiento=?, observaciones=?
                              WHERE id=?");
        $stmt->execute([
            $datos['periodo'], $datos['mes'], $datos['anio'],
            $datos['alquiler'], $datos['luz'], $datos['agua'],
            $datos['mantenimiento'], $datos['abl'], $datos['otros_conceptos'], $total,
            $datos['status'], $datos['fecha_cobro'], $datos['fecha_vencimiento'],
            $datos['observaciones'], $id
        ]);
        
        echo json_encode(['success' => true, 'message' => 'Cobro actualizado correctamente', 'total' => $total]);
    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}

function anularCobro($pdo) {
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    if (!$id) {
        echo json_encode(['success' => false, 'message' => 'ID inválido']);
        return;
    }
    try {
        $stmt = $pdo->prepare("SELECT status FROM cobros WHERE id = ?");
        $stmt->execute([$id]);
        $cobro = $stmt->fetch();
        
        if ($cobro && $cobro['status'] === 'PAGADO') {
            echo json_encode(['success' => false, 'message' => 'No se puede anular un cobro que ya está pagado. Primero debe marcarlo como pendiente.']);
            return;
        }

        $stmt = $pdo->prepare("UPDATE cobros SET status = 'ANULADO', observaciones = CONCAT(IFNULL(observaciones, ''), '\n[ANULADO el ', NOW(), ']') WHERE id = ?");
        $stmt->execute([$id]);
        echo json_encode(['success' => true, 'message' => 'Cobro anulado correctamente']);
    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}

function marcarPagado($pdo) {
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    if (!$id) { echo json_encode(['success' => false, 'message' => 'ID inválido']); return; }
    $stmt = $pdo->prepare("UPDATE cobros SET status = 'PAGADO', fecha_cobro = CURDATE() WHERE id = ?");
    $stmt->execute([$id]);
    echo json_encode(['success' => true, 'message' => 'Cobro marcado como PAGADO']);
}

function marcarPendiente($pdo) {
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    if (!$id) { echo json_encode(['success' => false, 'message' => 'ID inválido']); return; }
    $stmt = $pdo->prepare("UPDATE cobros SET status = 'PENDIENTE', fecha_cobro = NULL WHERE id = ?");
    $stmt->execute([$id]);
    echo json_encode(['success' => true, 'message' => 'Cobro marcado como PENDIENTE']);
}
?>