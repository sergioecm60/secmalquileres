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
    
    try {
        $fecha_inicio = $_POST['fecha_inicio'];
        $duracion = $_POST['duracion_meses'];
        $fecha_fin = date('Y-m-d', strtotime($fecha_inicio . " + $duracion months -1 day"));
        
        // Procesar valores de alquiler
        $valores_alquiler = [];
        if (isset($_POST['periodo_desde']) && is_array($_POST['periodo_desde'])) {
            for ($i = 0; $i < count($_POST['periodo_desde']); $i++) {
                $valores_alquiler[] = [
                    'desde' => (int)$_POST['periodo_desde'][$i],
                    'hasta' => (int)$_POST['periodo_hasta'][$i],
                    'valor' => (float)$_POST['periodo_valor'][$i]
                ];
            }
        }
        
        $stmt = $pdo->prepare("UPDATE contratos SET 
                              codigo=?, inquilino_id=?, propiedad_id=?, garante_id=?,
                              fecha_inicio=?, duracion_meses=?, fecha_fin=?,
                              deposito_ingreso=?, valores_alquiler=?
                              WHERE id=?");
        $stmt->execute([
            $_POST['codigo'],
            $_POST['inquilino_id'],
            $_POST['propiedad_id'],
            $_POST['garante_id'] ?: null,
            $fecha_inicio,
            $duracion,
            $fecha_fin,
            $_POST['deposito_ingreso'] ?: 0,
            json_encode($valores_alquiler),
            $id
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