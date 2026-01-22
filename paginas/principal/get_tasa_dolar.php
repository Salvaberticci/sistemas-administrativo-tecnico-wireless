<?php
/**
 * Obtiene la tasa del dólar oficial promedio desde ve.dolarapi.com
 * Retorna JSON: { "promedio": 123.45, "fecha": "..." }
 */
header('Content-Type: application/json; charset=utf-8');

// URL de la API
$url = "https://ve.dolarapi.com/v1/dolares/oficial";

// Inicializar CURL
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 
curl_setopt($ch, CURLOPT_TIMEOUT, 5); // 5 segundos máximo
$response = curl_exec($ch);

if (curl_errno($ch)) {
    echo json_encode(['error' => 'Error CURL: ' . curl_error($ch)]);
    curl_close($ch);
    exit;
}
curl_close($ch);

$data = json_decode($response, true);

// La API retorna "promedio" en el objeto raíz para "oficial"
// Ojo: la respuesta de esta API suele ser un objeto directo con campos como "promedio", "fechaActualizacion", etc.
if ($data && isset($data['promedio'])) {
    echo json_encode([
        'success' => true,
        'promedio' => floatval($data['promedio']),
        'fecha' => $data['fechaActualizacion'] ?? date('Y-m-d H:i:s')
    ]);
} else {
    // Fallback o Error
    echo json_encode([
        'success' => false,
        'promedio' => 0,
        'message' => 'No se pudo obtener la tasa'
    ]);
}
?>
