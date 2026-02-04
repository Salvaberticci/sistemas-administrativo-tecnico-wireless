<?php
header('Content-Type: application/json; charset=utf-8');

// Archivos JSON
$fileInstaladores = 'data/instaladores.json';
$fileVendedores = 'data/vendedores.json';

// Ensure data dir exists
if (!is_dir('data'))
    mkdir('data', 0755, true);

// Función helper para leer
function leerJson($file)
{
    if (!file_exists($file))
        return [];
    $content = file_get_contents($file);
    return json_decode($content, true) ?: [];
}

// Función helper para guardar
function guardarJson($file, $data)
{
    return file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

// REQUEST METHOD
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

if ($method === 'GET') {
    if ($action === 'get_instaladores') {
        echo json_encode(leerJson($fileInstaladores));
    } elseif ($action === 'get_vendedores') {
        echo json_encode(leerJson($fileVendedores));
    } elseif ($action === 'get_planes_prorrateo') {
        echo json_encode(leerJson('data/planes_prorrateo.json'));
    } else {
        echo json_encode(['error' => 'Invalid action']);
    }
} elseif ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    if ($action === 'save_instaladores') {
        if (is_array($input)) {
            guardarJson($fileInstaladores, $input);
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid data format']);
        }
    } elseif ($action === 'save_vendedores') {
        if (is_array($input)) {
            guardarJson($fileVendedores, $input);
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid data format']);
        }
    } elseif ($action === 'save_planes_prorrateo') {
        // Planes Prorrateo: Array de objetos {nombre, precio}
        if (is_array($input)) {
            guardarJson('data/planes_prorrateo.json', $input);
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid data format']);
        }
    } else {
        echo json_encode(['error' => 'Invalid action']);
    }
} else {
    echo json_encode(['error' => 'Method not allowed']);
}
