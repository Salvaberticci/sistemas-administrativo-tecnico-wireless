<?php
/**
 * API para Gestión de Bancos (JSON)
 * Acciones: get, add, delete
 */

header('Content-Type: application/json; charset=utf-8');

$file_path = 'bancos.json';

// Cargar Datos
function loadPartidas($path) {
    if (!file_exists($path)) return [];
    $content = file_get_contents($path);
    return json_decode($content, true) ?: [];
}

// Guardar Datos
function savePartidas($path, $data) {
    return file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

$action = isset($_GET['action']) ? $_GET['action'] : 'get';

$bancos = loadPartidas($file_path);

if ($action === 'get') {
    echo json_encode($bancos);
    exit;
}

if ($action === 'add') {
    $nombre = isset($_POST['nombre_banco']) ? trim($_POST['nombre_banco']) : '';
    // Podrías agregar más campos si es necesario (titular, cuenta, etc)
    // Por ahora el usuario solo pidió nombre o "editar", asumimos nombre básico para la lista
    // Pero para ser compatible con lo anterior, quizás quiera todos los datos?
    // User request: "quiero que las cuentas que aparecen se guarden en un json y que se puedan editar"
    // "modal para poder agregar o liminar cuientas bancarias"
    
    // Vamos a aceptar todos los campos posibles para ser flexibles
    $numero = isset($_POST['numero_cuenta']) ? trim($_POST['numero_cuenta']) : '';
    $titular = isset($_POST['titular_cuenta']) ? trim($_POST['titular_cuenta']) : '';
    $cedula = isset($_POST['cedula']) ? trim($_POST['cedula']) : '';

    if (empty($nombre)) {
        echo json_encode(['success' => false, 'message' => 'Nombre requerido']);
        exit;
    }

    // Generar ID
    $maxId = 0;
    foreach ($bancos as $b) {
        if (isset($b['id_banco']) && intval($b['id_banco']) > $maxId) {
            $maxId = intval($b['id_banco']);
        }
    }
    $newId = $maxId + 1;

    $nuevo = [
        'id_banco' => (string)$newId, // Mantener string si venía de DB
        'nombre_banco' => $nombre,
        'numero_cuenta' => $numero,
        'titular_cuenta' => $titular,
        'cedula_titular' => $cedula // Ajustar si el campo era diferente en DB, el error anterior era cedula_titular vs cedula
        // Al hacer select * en migrate, los nombres de campos en JSON son los de la DB.
        // Asumiremos que el JS enviará los nombres correctos.
    ];

    $bancos[] = $nuevo;
    savePartidas($file_path, $bancos);
    echo json_encode(['success' => true, 'data' => $nuevo]);
    exit;
}

if ($action === 'delete') {
    $id = isset($_POST['id']) ? $_POST['id'] : '';
    
    if (empty($id)) {
        echo json_encode(['success' => false, 'message' => 'ID requerido']);
        exit;
    }

    $bancos = array_filter($bancos, function($b) use ($id) {
        return $b['id_banco'] != $id;
    });
    
    savePartidas($file_path, array_values($bancos)); // Re-index array
    echo json_encode(['success' => true]);
    exit;
}
?>
