<?php
header('Content-Type: application/json');

// Archivo JSON
$jsonFile = 'data/tipos_instalacion.json';

// CREAR ARCHIVO SI NO EXISTE
if (!file_exists($jsonFile)) {
    // Valores por defecto si se crea de cero
    $defaults = ["FTTH", "RADIO"];
    file_put_contents($jsonFile, json_encode($defaults, JSON_PRETTY_PRINT));
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

    if (json_last_error() === JSON_ERROR_NONE && is_array($data)) {
        // --- LOGICA DE ACTUALIZACION EN CASCADA ---
        require_once '../conexion.php'; // Necesitamos conexión a la BD para la cascada

        // 1. Obtener lista anterior
        $old_data = [];
        if (file_exists($jsonFile)) {
            $old_data = json_decode(file_get_contents($jsonFile), true) ?: [];
        }

        // 2. Identificar cambios (Asumiendo que el orden se mantiene o que podemos mapear por índice si la UI lo permite)
        // Pero como es una lista de strings, la UI de gestión (al igual que instaladores/vendedores) 
        // suele editar por índice. Si se cambia el texto en un índice existente:
        foreach ($data as $index => $new_name) {
            if (isset($old_data[$index]) && $old_data[$index] !== $new_name) {
                $old_name = $conn->real_escape_string($old_data[$index]);
                $safe_new_name = $conn->real_escape_string($new_name);

                // Ejecutar UPDATE en cascada
                $sql = "UPDATE contratos SET tipo_conexion = '$safe_new_name' WHERE tipo_conexion = '$old_name'";
                $conn->query($sql);
            }
        }

        if (file_put_contents($jsonFile, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
            echo json_encode(['status' => 'success', 'message' => 'Tipos guardados correctamente y contratos actualizados']);
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