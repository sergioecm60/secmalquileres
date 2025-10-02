<?php
require_once '../check_auth.php';
require_once '../config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'crear':
            crearInquilino($pdo);
            break;
        case 'obtener':
            obtenerInquilino($pdo);
            break;
        case 'editar':
            editarInquilino($pdo);
            break;
        case 'eliminar':
            eliminarInquilino($pdo);
            break;
        case 'desactivar':
            cambiarEstadoInquilino($pdo, 'inactivo');
            break;
        case 'activar':
            cambiarEstadoInquilino($pdo, 'activo');
            break;
        default:
            echo json_encode(['success' => false, 'message' => 'Acción no válida']);
    }
} catch (PDOException $e) {
    error_log("API Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error de base de datos: ' . $e->getMessage()]);
}

function obtenerInquilino($pdo) {
    $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    if (!$id) {
        echo json_encode(['success' => false, 'message' => 'ID inválido']);
        return;
    }
    
    $stmt = $pdo->prepare("SELECT * FROM inquilinos WHERE id = ?");
    $stmt->execute([$id]);
    $inquilino = $stmt->fetch();
    
    if ($inquilino) {
        echo json_encode(['success' => true, 'data' => $inquilino]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Inquilino no encontrado']);
    }
}

function crearInquilino($pdo) {
    $datos = prepararDatosPost();
    if (empty($datos['apellido']) || empty($datos['nombre']) || empty($datos['dni'])) {
        echo json_encode(['success' => false, 'message' => 'Apellido, nombre y DNI son obligatorios']);
        return;
    }

    $stmt = $pdo->prepare("SELECT id FROM inquilinos WHERE dni = ?");
    $stmt->execute([$datos['dni']]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Ya existe un inquilino con ese DNI']);
        return;
    }

    $sql = "INSERT INTO inquilinos (nombre, apellido, dni, telefono, email, direccion, localidad, provincia, observaciones) 
            VALUES (:nombre, :apellido, :dni, :telefono, :email, :direccion, :localidad, :provincia, :observaciones)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($datos);

    echo json_encode(['success' => true, 'message' => 'Inquilino creado correctamente']);
}

function editarInquilino($pdo) {
    $id = filter_input(INPUT_POST, 'id_inquilino', FILTER_VALIDATE_INT);
    if (!$id) {
        echo json_encode(['success' => false, 'message' => 'ID inválido']);
        return;
    }

    $datos = prepararDatosPost();
    if (empty($datos['apellido']) || empty($datos['nombre']) || empty($datos['dni'])) {
        echo json_encode(['success' => false, 'message' => 'Apellido, nombre y DNI son obligatorios']);
        return;
    }

    $stmt = $pdo->prepare("SELECT id FROM inquilinos WHERE dni = ? AND id != ?");
    $stmt->execute([$datos['dni'], $id]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Ya existe otro inquilino con ese DNI']);
        return;
    }

    $datos['id'] = $id;
    $sql = "UPDATE inquilinos SET
                nombre = :nombre, apellido = :apellido, dni = :dni, telefono = :telefono,
                email = :email, direccion = :direccion, localidad = :localidad,
                provincia = :provincia, observaciones = :observaciones
            WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($datos);

    echo json_encode(['success' => true, 'message' => 'Inquilino actualizado correctamente']);
}

function eliminarInquilino($pdo) {
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    if (!$id) {
        echo json_encode(['success' => false, 'message' => 'ID inválido']);
        return;
    }

    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM contratos WHERE inquilino_id = ? AND activo = 1");
    $stmt->execute([$id]);
    if ($stmt->fetchColumn() > 0) {
        echo json_encode(['success' => false, 'message' => 'No se puede eliminar. El inquilino tiene contratos activos.']);
        return;
    }

    $stmt = $pdo->prepare("UPDATE inquilinos SET estado = 'eliminado', deleted_at = NOW() WHERE id = ?");
    $stmt->execute([$id]);

    echo json_encode(['success' => true, 'message' => 'Inquilino eliminado correctamente']);
}

function cambiarEstadoInquilino($pdo, $nuevoEstado) {
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    if (!$id) {
        echo json_encode(['success' => false, 'message' => 'ID inválido']);
        return;
    }

    $stmt = $pdo->prepare("UPDATE inquilinos SET estado = ? WHERE id = ?");
    $stmt->execute([$nuevoEstado, $id]);

    $mensaje = $nuevoEstado === 'activo' ? 'Inquilino activado correctamente' : 'Inquilino desactivado correctamente';
    echo json_encode(['success' => true, 'message' => $mensaje]);
}

function prepararDatosPost() {
    return [
        'nombre' => trim($_POST['nombre'] ?? ''),
        'apellido' => trim($_POST['apellido'] ?? ''),
        'dni' => trim($_POST['dni'] ?? ''),
        'telefono' => trim($_POST['telefono'] ?? ''),
        'email' => trim($_POST['email'] ?? ''),
        'direccion' => trim($_POST['direccion'] ?? ''),
        'localidad' => trim($_POST['localidad'] ?? ''),
        'provincia' => trim($_POST['provincia'] ?? ''),
        'observaciones' => trim($_POST['observaciones'] ?? '')
    ];
}
?>