<?php
// api/api_propiedades.php
require_once '../check_auth.php';
require_once '../config.php';

header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'obtener':
        obtenerPropiedad($pdo);
        break;
    case 'editar':
        editarPropiedad($pdo);
        break;
    case 'eliminar':
        eliminarPropiedad($pdo);
        break;
    case 'desactivar':
        cambiarEstado($pdo, 0);
        break;
    case 'activar':
        cambiarEstado($pdo, 1);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Acción no válida']);
}

function obtenerPropiedad($pdo) {
    $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    
    if (!$id) {
        echo json_encode(['success' => false, 'message' => 'ID inválido']);
        return;
    }
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM propiedades WHERE id = ?");
        $stmt->execute([$id]);
        $propiedad = $stmt->fetch();
        
        if ($propiedad) {
            echo json_encode(['success' => true, 'data' => $propiedad]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Propiedad no encontrada']);
        }
    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}

function editarPropiedad($pdo) {
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    
    if (!$id) {
        echo json_encode(['success' => false, 'message' => 'ID inválido']);
        return;
    }
    
    $datos = [
        'codigo' => trim($_POST['codigo'] ?? ''),
        'direccion' => trim($_POST['direccion'] ?? ''),
        'departamento' => trim($_POST['departamento'] ?? ''),
        'localidad' => trim($_POST['localidad'] ?? ''),
        'descripcion' => trim($_POST['descripcion'] ?? '')
    ];
    
    if (empty($datos['direccion']) || empty($datos['localidad'])) {
        echo json_encode(['success' => false, 'message' => 'Dirección y localidad son obligatorios']);
        return;
    }
    
    try {
        if (!empty($datos['codigo'])) {
            $stmt = $pdo->prepare("SELECT id FROM propiedades WHERE codigo = ? AND id != ?");
            $stmt->execute([$datos['codigo'], $id]);
            if ($stmt->fetch()) {
                echo json_encode(['success' => false, 'message' => 'Ya existe otra propiedad con ese código']);
                return;
            }
        }
        
        $stmt = $pdo->prepare("UPDATE propiedades SET codigo=?, direccion=?, departamento=?, localidad=?, descripcion=? WHERE id=?");
        $stmt->execute([
            $datos['codigo'], $datos['direccion'], $datos['departamento'],
            $datos['localidad'], $datos['descripcion'], $id
        ]);
        
        echo json_encode(['success' => true, 'message' => 'Propiedad actualizada correctamente']);
    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}

function eliminarPropiedad($pdo) {
    // Esta función en realidad desactiva la propiedad, es una eliminación lógica.
    cambiarEstado($pdo, 0);
}

function cambiarEstado($pdo, $nuevoEstado) {
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    
    if (!$id) {
        echo json_encode(['success' => false, 'message' => 'ID inválido']);
        return;
    }
    
    try {
        $stmt = $pdo->prepare("UPDATE propiedades SET activo = ? WHERE id = ?");
        $stmt->execute([$nuevoEstado, $id]);
        
        $mensaje = $nuevoEstado === 1 ? 'activada' : 'desactivada';
        echo json_encode(['success' => true, 'message' => "Propiedad $mensaje correctamente"]);
    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}
?>