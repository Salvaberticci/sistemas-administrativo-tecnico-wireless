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
    require '../conexion.php'; // Necesario para cascade

    // GUARDAR DATOS
    $input = file_get_contents('php://input');
    $newData = json_decode($input, true);

    if (json_last_error() === JSON_ERROR_NONE) {
        // --- LÓGICA DE CASCADA (NUEVO) ---
        $oldData = [];
        if (file_exists($jsonFile)) {
            $oldData = json_decode(file_get_contents($jsonFile), true) ?: [];
        }

        // 1. Detectar cambios en nombres de Municipios
        foreach ($newData as $mIndex => $mNuevo) {
            if (isset($oldData[$mIndex])) {
                $nombreViejo = $oldData[$mIndex]['municipio'];
                $nombreNuevo = $mNuevo['municipio'];

                if ($nombreViejo !== $nombreNuevo && !empty($nombreViejo)) {
                    $stmt = $conn->prepare("UPDATE contratos SET municipio_texto = ? WHERE municipio_texto = ?");
                    $stmt->bind_param("ss", $nombreNuevo, $nombreViejo);
                    $stmt->execute();
                    $stmt->close();
                }

                // 2. Detectar cambios en nombres de Parroquias dentro de este municipio
                $parroquiasViejas = $oldData[$mIndex]['parroquias'] ?? [];
                $parroquiasNuevas = $mNuevo['parroquias'] ?? [];

                foreach ($parroquiasNuevas as $pIndex => $pDataNuevo) {
                    if (isset($parroquiasViejas[$pIndex])) {
                        // Extraer nombres (pueden ser strings o arrays asociativos con 'nombre')
                        $pNombreNuevo = is_array($pDataNuevo) ? $pDataNuevo['nombre'] : $pDataNuevo;
                        $pDataViejo = $parroquiasViejas[$pIndex];
                        $pNombreViejo = is_array($pDataViejo) ? $pDataViejo['nombre'] : $pDataViejo;

                        if ($pNombreViejo !== $pNombreNuevo && !empty($pNombreViejo)) {
                            // Importante: filtrar por municipio_texto para evitar pisar parroquias con mismo nombre en otros municipios
                            $stmt = $conn->prepare("UPDATE contratos SET parroquia_texto = ? WHERE parroquia_texto = ? AND municipio_texto = ?");
                            $stmt->bind_param("sss", $pNombreNuevo, $pNombreViejo, $nombreNuevo);
                            $stmt->execute();
                            $stmt->close();
                        }
                    }
                }
            }
        }

        if (file_put_contents($jsonFile, json_encode($newData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
            echo json_encode(['status' => 'success', 'message' => 'Ubicaciones guardadas y actualizadas en cascada']);
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