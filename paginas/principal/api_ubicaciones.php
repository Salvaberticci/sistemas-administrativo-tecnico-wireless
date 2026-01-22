<?php
header('Content-Type: application/json');

// Archivo JSON
$jsonFile = 'data/ubicaciones.json';

// CREAR ARCHIVO SI NO EXISTE
if (!file_exists($jsonFile)) {
    file_put_contents($jsonFile, json_encode([], JSON_PRETTY_PRINT));
}

// MANEJO DE SOLICITUDES
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // LEER DATOS
    if (file_exists($jsonFile)) {
        echo file_get_contents($jsonFile);
    } else {
        echo json_encode([]);
    }
} elseif ($method === 'POST') {
    // GUARDAR DATOS
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (json_last_error() === JSON_ERROR_NONE) {
        if (file_put_contents($jsonFile, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
            echo json_encode(['status' => 'success', 'message' => 'Ubicaciones guardadas correctamente']);
        } else {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'No se pudo escribir en el archivo JSON']);
        }
    } else {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Datos JSON inválidos']);
    }
} else {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Método no permitido']);
}
?>
