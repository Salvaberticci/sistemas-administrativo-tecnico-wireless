<?php
/**
 * API Geográfica - Municipios, Parroquias y Comunidades
 */
header('Content-Type: application/json');
require_once '../conexion.php';

$action = isset($_GET['action']) ? $_GET['action'] : '';

if ($action === 'get_municipios') {
    $res = $conn->query("SELECT id_municipio, nombre_municipio FROM municipio ORDER BY nombre_municipio ASC");
    $data = $res->fetch_all(MYSQLI_ASSOC);
    echo json_encode(['success' => true, 'data' => $data]);
    exit;
}

if ($action === 'get_parroquias') {
    $id_municipio = isset($_GET['id_municipio']) ? intval($_GET['id_municipio']) : 0;
    $where = $id_municipio > 0 ? "WHERE id_municipio = $id_municipio" : "";
    $res = $conn->query("SELECT id_parroquia, nombre_parroquia, id_municipio FROM parroquia $where ORDER BY nombre_parroquia ASC");
    $data = $res->fetch_all(MYSQLI_ASSOC);
    echo json_encode(['success' => true, 'data' => $data]);
    exit;
}



if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'add_municipio') {
        $nombre = isset($_POST['nombre_municipio']) ? trim($_POST['nombre_municipio']) : '';
        if (empty($nombre)) {
            echo json_encode(['success' => false, 'message' => 'El nombre es obligatorio']);
            exit;
        }
        $stmt = $conn->prepare("INSERT INTO municipio (nombre_municipio) VALUES (?)");
        $stmt->bind_param("s", $nombre);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'id' => $stmt->insert_id]);
        } else {
            echo json_encode(['success' => false, 'message' => $stmt->error]);
        }
        exit;
    }

    if ($action === 'add_parroquia') {
        $nombre = isset($_POST['nombre_parroquia']) ? trim($_POST['nombre_parroquia']) : '';
        $id_municipio = isset($_POST['id_municipio']) ? intval($_POST['id_municipio']) : 0;
        if (empty($nombre) || $id_municipio <= 0) {
            echo json_encode(['success' => false, 'message' => 'Faltan datos obligatorios']);
            exit;
        }
        $stmt = $conn->prepare("INSERT INTO parroquia (nombre_parroquia, id_municipio) VALUES (?, ?)");
        $stmt->bind_param("si", $nombre, $id_municipio);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'id' => $stmt->insert_id]);
        } else {
            echo json_encode(['success' => false, 'message' => $stmt->error]);
        }
        exit;
    }


}

echo json_encode(['success' => false, 'message' => 'Acción no válida']);
