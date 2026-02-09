<?php
/**
 * Backend para gestionar opciones de soporte (Fallas y Servicios)
 * CRUD sobre archivo JSON
 */

header('Content-Type: application/json');

// Ruta al archivo JSON
$json_file = 'data/opciones_soporte.json';

// Verificar permisos de escritura en el directorio si el archivo no existe
if (!file_exists($json_file)) {
    if (!is_writable(dirname($json_file))) {
        echo json_encode(['success' => false, 'message' => 'No hay permisos de escritura en el directorio data/']);
        exit;
    }
    // Crear archivo por defecto si no existe
    $defaults = [
        'tipos_falla' => ['Sin Señal', 'Internet Lento', 'Otro'],
        'tipos_servicio' => ['Fibra Óptica', 'Radio Enlace']
    ];
    file_put_contents($json_file, json_encode($defaults, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

// Leer datos actuales
$json_content = file_get_contents($json_file);
$data = json_decode($json_content, true);

if (!$data) {
    $data = ['tipos_falla' => [], 'tipos_servicio' => []];
}

// Acción solicitada
$action = $_POST['action'] ?? $_GET['action'] ?? 'read';

try {
    switch ($action) {
        case 'read':
            echo json_encode(['success' => true, 'data' => $data]);
            break;

        case 'add':
            $type = $_POST['type'] ?? ''; // 'tipos_falla' o 'tipos_servicio'
            $value = trim($_POST['value'] ?? '');

            if (!in_array($type, ['tipos_falla', 'tipos_servicio'])) {
                throw new Exception("Tipo de opción inválido");
            }
            if (empty($value)) {
                throw new Exception("El valor no puede estar vacío");
            }
            if (in_array($value, $data[$type])) {
                throw new Exception("Esta opción ya existe");
            }

            $data[$type][] = $value;
            saveData($json_file, $data);
            echo json_encode(['success' => true, 'data' => $data]);
            break;

        case 'delete':
            $type = $_POST['type'] ?? '';
            $value = $_POST['value'] ?? '';

            if (!in_array($type, ['tipos_falla', 'tipos_servicio'])) {
                throw new Exception("Tipo de opción inválido");
            }

            $key = array_search($value, $data[$type]);
            if ($key !== false) {
                array_splice($data[$type], $key, 1);
                saveData($json_file, $data);
                echo json_encode(['success' => true, 'data' => $data]);
            } else {
                throw new Exception("Opción no encontrada");
            }
            break;

        default:
            throw new Exception("Acción no reconocida");
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

function saveData($file, $data)
{
    if (file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) === false) {
        throw new Exception("Error al guardar archivo");
    }
}
?>