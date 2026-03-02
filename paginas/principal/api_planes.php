<?php
/**
 * API para Gestión de Planes 
 * Acciones: get, add, update, delete, migrate
 */
require_once '../conexion.php';

header('Content-Type: application/json; charset=utf-8');

$action = isset($_GET['action']) ? $_GET['action'] : 'get';

if ($action === 'get') {
    $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $offset = ($page - 1) * $limit;

    $where = "";
    $params = [];
    $types = "";

    if (!empty($search)) {
        $where = " WHERE nombre_plan LIKE ?";
        $params[] = "%$search%";
        $types .= "s";
    }

    // Contar total
    $countSql = "SELECT COUNT(*) as total FROM planes $where";
    $stmtCount = $conn->prepare($countSql);
    if (!empty($params)) {
        $stmtCount->bind_param($types, ...$params);
    }
    $stmtCount->execute();
    $total = $stmtCount->get_result()->fetch_assoc()['total'];
    $stmtCount->close();

    // Obtener datos con conteo de clientes
    $sql = "SELECT p.*, 
            (SELECT COUNT(*) FROM contratos c WHERE c.id_plan = p.id_plan) as clientes_activos
            FROM planes p
            $where
            ORDER BY p.nombre_plan ASC
            LIMIT ?, ?";

    $stmt = $conn->prepare($sql);
    $allParams = array_merge($params, [$offset, $limit]);
    $allTypes = $types . "ii";
    $stmt->bind_param($allTypes, ...$allParams);
    $stmt->execute();
    $result = $stmt->get_result();

    $planes = [];
    while ($row = $result->fetch_assoc()) {
        $planes[] = $row;
    }
    $stmt->close();

    echo json_encode([
        'total' => $total,
        'page' => $page,
        'limit' => $limit,
        'pages' => ceil($total / $limit),
        'data' => $planes
    ]);
    exit;
}

if ($action === 'add') {
    $nombre = isset($_POST['nombre_plan']) ? trim($_POST['nombre_plan']) : '';
    $monto = isset($_POST['monto']) ? floatval($_POST['monto']) : 0;
    $descripcion = isset($_POST['descripcion']) ? trim($_POST['descripcion']) : '';

    if (empty($nombre) || $monto < 0) {
        echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
        exit;
    }

    $stmt = $conn->prepare("INSERT INTO planes (nombre_plan, monto, descripcion) VALUES (?, ?, ?)");
    $stmt->bind_param("sds", $nombre, $monto, $descripcion);

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => $conn->error]);
    }
    $stmt->close();
    exit;
}

if ($action === 'update') {
    $id = isset($_POST['id_plan']) ? intval($_POST['id_plan']) : 0;
    $nombre = isset($_POST['nombre_plan']) ? trim($_POST['nombre_plan']) : '';
    $monto = isset($_POST['monto']) ? floatval($_POST['monto']) : 0;
    $descripcion = isset($_POST['descripcion']) ? trim($_POST['descripcion']) : '';

    if ($id <= 0 || empty($nombre) || $monto < 0) {
        echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
        exit;
    }

    // Iniciar transacción para actualización en cascada
    $conn->begin_transaction();

    try {
        // 1. Actualizar plan
        $stmt = $conn->prepare("UPDATE planes SET nombre_plan = ?, monto = ?, descripcion = ? WHERE id_plan = ?");
        $stmt->bind_param("sdsi", $nombre, $monto, $descripcion, $id);
        $stmt->execute();

        // 2. Actualizar contratos (monto_pagar) de clientes vinculados
        $stmtC = $conn->prepare("UPDATE contratos SET monto_pagar = ? WHERE id_plan = ?");
        $stmtC->bind_param("di", $monto, $id);
        $stmtC->execute();

        $conn->commit();
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

if ($action === 'delete') {
    $id = isset($_POST['id_plan']) ? intval($_POST['id_plan']) : 0;

    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID inválido']);
        exit;
    }

    // Verificar si hay clientes
    $stmtCheck = $conn->prepare("SELECT COUNT(*) as total FROM contratos WHERE id_plan = ?");
    $stmtCheck->bind_param("i", $id);
    $stmtCheck->execute();
    $total = $stmtCheck->get_result()->fetch_assoc()['total'];
    $stmtCheck->close();

    if ($total > 0) {
        echo json_encode(['success' => false, 'has_clients' => true, 'count' => $total, 'message' => "No se puede eliminar: hay $total clientes vinculados."]);
        exit;
    }

    $stmt = $conn->prepare("DELETE FROM planes WHERE id_plan = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => $conn->error]);
    }
    $stmt->close();
    exit;
}

if ($action === 'migrate') {
    $id_old = isset($_POST['id_old']) ? intval($_POST['id_old']) : 0;
    $id_new = isset($_POST['id_new']) ? intval($_POST['id_new']) : 0;

    if ($id_old <= 0 || $id_new <= 0 || $id_old === $id_new) {
        echo json_encode(['success' => false, 'message' => 'IDs inválidos para migración']);
        exit;
    }

    // Obtener monto del nuevo plan
    $stmtM = $conn->prepare("SELECT monto FROM planes WHERE id_plan = ?");
    $stmtM->bind_param("i", $id_new);
    $stmtM->execute();
    $monto_new = $stmtM->get_result()->fetch_assoc()['monto'];
    $stmtM->close();

    // Actualizar todos los contratos
    $stmt = $conn->prepare("UPDATE contratos SET id_plan = ?, monto_pagar = ? WHERE id_plan = ?");
    $stmt->bind_param("idi", $id_new, $monto_new, $id_old);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'migrated' => $stmt->affected_rows]);
    } else {
        echo json_encode(['success' => false, 'message' => $conn->error]);
    }
    $stmt->close();
    exit;
}

$conn->close();
?>