<?php
// api/api_contratos.php
require_once '../check_auth.php';
require_once '../config.php';

header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'obtener':
        obtenerContrato($pdo);
        break;
    case 'editar':
        editarContrato($pdo);
        break;
    case 'eliminar':
        eliminarContrato($pdo);
        break;
    case 'desactivar':
        cambiarEstado($pdo, 0);
        break;
    case 'activar':
        cambiarEstado($pdo, 1);
        break;
    case 'finalizar':
        finalizarContrato($pdo);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Acción no válida']);
}

function obtenerContrato($pdo) {
    $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    
    if (!$id) {
        echo json_encode(['success' => false, 'message' => 'ID inválido']);
        return;
    }
    
    try {
        $stmt = $pdo->prepare("SELECT c.*, i.nombre, i.apellido, i.dni as inquilino_dni, 
                               p.direccion, p.departamento, p.localidad,
                               g.nombre_apellido as garante_nombre, g.dni as garante_dni
                               FROM contratos c
                               JOIN inquilinos i ON c.inquilino_id = i.id
                               JOIN propiedades p ON c.propiedad_id = p.id
                               LEFT JOIN garantes g ON c.garante_id = g.id
                               WHERE c.id = ?");
        $stmt->execute([$id]);
        $contrato = $stmt->fetch();
        
        if ($contrato) {
            echo json_encode(['success' => true, 'data' => $contrato]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Contrato no encontrado']);
        }
    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}

function editarContrato($pdo) {
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    
    if (!$id) {
        echo json_encode(['success' => false, 'message' => 'ID inválido']);
        return;
    }
    
    $datos = [
        'codigo' => trim($_POST['codigo'] ?? ''),
        'inquilino_id' => filter_var($_POST['inquilino_id'], FILTER_VALIDATE_INT),
        'propiedad_id' => filter_var($_POST['propiedad_id'], FILTER_VALIDATE_INT),
        'garante_id' => filter_var($_POST['garante_id'], FILTER_VALIDATE_INT) ?: null,
        'fecha_inicio' => $_POST['fecha_inicio'] ?? '',
        'duracion_meses' => filter_var($_POST['duracion_meses'], FILTER_VALIDATE_INT),
        'deposito_ingreso' => filter_var($_POST['deposito_ingreso'], FILTER_VALIDATE_FLOAT) ?: 0,
        'mes_1_3' => filter_var($_POST['mes_1_3'], FILTER_VALIDATE_FLOAT) ?: 0,
        'mes_4_6' => filter_var($_POST['mes_4_6'], FILTER_VALIDATE_FLOAT) ?: 0,
        'mes_7_9' => filter_var($_POST['mes_7_9'], FILTER_VALIDATE_FLOAT) ?: 0,
        'mes_10_12' => filter_var($_POST['mes_10_12'], FILTER_VALIDATE_FLOAT) ?: 0
    ];
    
    if (empty($datos['inquilino_id']) || empty($datos['propiedad_id']) || 
        empty($datos['fecha_inicio']) || empty($datos['duracion_meses'])) {
        echo json_encode(['success' => false, 'message' => 'Faltan datos obligatorios']);
        return;
    }
    
    try {
        $fecha_fin = date('Y-m-d', strtotime($datos['fecha_inicio'] . " +{$datos['duracion_meses']} months -1 day"));
        
        if (!empty($datos['codigo'])) {
            $stmt = $pdo->prepare("SELECT id FROM contratos WHERE codigo = ? AND id != ?");
            $stmt->execute([$datos['codigo'], $id]);
            if ($stmt->fetch()) {
                echo json_encode(['success' => false, 'message' => 'Ya existe otro contrato con ese código']);
                return;
            }
        }
        
        $stmt = $pdo->prepare("UPDATE contratos SET 
                              codigo=?, inquilino_id=?, propiedad_id=?, garante_id=?,
                              fecha_inicio=?, duracion_meses=?, fecha_fin=?,
                              deposito_ingreso=?, mes_1_3=?, mes_4_6=?, mes_7_9=?, mes_10_12=?
                              WHERE id=?");
        $stmt->execute([
            $datos['codigo'], $datos['inquilino_id'], $datos['propiedad_id'], $datos['garante_id'],
            $datos['fecha_inicio'], $datos['duracion_meses'], $fecha_fin,
            $datos['deposito_ingreso'], $datos['mes_1_3'], $datos['mes_4_6'], 
            $datos['mes_7_9'], $datos['mes_10_12'], $id
        ]);
        
        echo json_encode(['success' => true, 'message' => 'Contrato actualizado correctamente']);
    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}

function eliminarContrato($pdo) {
    // La eliminación de contratos se maneja desactivándolos.
    cambiarEstado($pdo, 0);
}

function finalizarContrato($pdo) {
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    if (!$id) {
        echo json_encode(['success' => false, 'message' => 'ID inválido']);
        return;
    }
    
    try {
        $stmt = $pdo->prepare("UPDATE contratos SET activo = 0, fecha_fin = CURDATE() WHERE id = ?");
        $stmt->execute([$id]);
        echo json_encode(['success' => true, 'message' => 'Contrato finalizado correctamente']);
    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}

function cambiarEstado($pdo, $nuevoEstado) {
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    if (!$id) {
        echo json_encode(['success' => false, 'message' => 'ID inválido']);
        return;
    }
    
    try {
        $stmt = $pdo->prepare("UPDATE contratos SET activo = ? WHERE id = ?");
        $stmt->execute([$nuevoEstado, $id]);
        $mensaje = $nuevoEstado === 1 ? 'activado' : 'desactivado';
        echo json_encode(['success' => true, 'message' => "Contrato $mensaje correctamente"]);
    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}
?>