<?php
require_once '../vendor/autoload.php';
require_once '../conexion.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

header('Content-Type: application/json');

// Verificar archivo
if (!isset($_FILES['archivo_excel']) || $_FILES['archivo_excel']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'Error al subir el archivo.']);
    exit;
}

$inputFileName = $_FILES['archivo_excel']['tmp_name'];

try {
    $spreadsheet = IOFactory::load($inputFileName);
    $worksheet = $spreadsheet->getActiveSheet();
    $rows = $worksheet->toArray();

    // Asumimos que la primera fila son los encabezados
    $headers = array_shift($rows);

    // Mapa de columnas esperado (Nombre Header => Nombre Columna BD)
    // Ajustar según los nombres exactos que pusimos en el exportar de JS
    $colMap = [
        'ID' => 'id_contrato',
        'SAR' => 'fecha_registro', // Timestamp
        'Cédula' => 'cedula_cliente',
        'Cliente' => 'nombre_cliente', // OJO: La tabla contratos usa id_cliente, esto requerirá lógica extra si el cliente no existe
        'Municipio' => 'id_municipio', // Igual, FK
        'Parroquia' => 'id_parroquia', // FK
        'Dirección' => 'direccion_instalacion',
        'Telf. 1' => 'telefono_cliente',
        'Telf. 2' => 'telefono_extra',
        'Correo' => 'email_cliente',
        'Correo (Alt)' => 'email_extra',
        'F. Instalación' => 'fecha_instalacion',
        'Medio Pago' => 'metodo_pago',
        'Monto Pagar' => 'costo_instalacion',
        'Monto Pagado' => 'monto_pagado',
        'Días Prorrateo' => 'dias_prorrateo',
        'Monto Prorr. ($)' => 'monto_prorrateo',
        'Observ.' => 'observaciones',
        'Tipo Conex.' => 'tipo_conexion',
        'Num. ONU' => 'numero_onu',
        'MAC/Serial' => 'mac_serial',
        'IP ONU' => 'ip_onu',
        'Caja NAP' => 'caja_nap',
        'Puerto NAP' => 'puerto_nap',
        'NAP TX (dBm)' => 'potencia_nap_tx',
        'ONU RX (dBm)' => 'potencia_onu_rx',
        'Dist. Drop (m)' => 'distancia_drop',
        'Instalador' => 'id_instalador', // FK
        'Evidencia Fibra' => 'evidencia_foto_fibra',
        'IP Servicio' => 'ip_servicio',
        'Punto Acceso' => 'punto_acceso',
        'Val. Conex. (dBm)' => 'valor_conexion',
        'Precinto ODN' => 'precinto_odn',
        // 'Foto' => 'foto_cedula', // No se puede importar archivos binarios desde Excel fácilmente
        // 'Firma Cliente', 'Firma Técnico' tampoco
        'Vendedor (Edit)' => 'id_vendedor', // FK
        'SAE Plus (Edit)' => 'codigo_sae_plus',
        'Plan' => 'id_plan', // FK
        'OLT' => 'id_olt', // FK
        'PON' => 'id_pon', // FK
        'Estado' => 'status'
    ];

    // Obtener mapas de FK para evitar subconsultas en el loop
    $municipiosMap = [];
    $res = $conn->query("SELECT id_municipio, nombre_municipio FROM municipio");
    while ($r = $res->fetch_assoc())
        $municipiosMap[strtoupper(trim($r['nombre_municipio']))] = $r['id_municipio'];

    $parroquiasMap = [];
    $res = $conn->query("SELECT id_parroquia, nombre_parroquia FROM parroquia");
    while ($r = $res->fetch_assoc())
        $parroquiasMap[strtoupper(trim($r['nombre_parroquia']))] = $r['id_parroquia'];

    $planesMap = [];
    $res = $conn->query("SELECT id_plan, nombre_plan FROM planes");
    while ($r = $res->fetch_assoc())
        $planesMap[strtoupper(trim($r['nombre_plan']))] = $r['id_plan'];

    $oltsMap = [];
    $res = $conn->query("SELECT id_olt, nombre_olt FROM olts");
    while ($r = $res->fetch_assoc())
        $oltsMap[strtoupper(trim($r['nombre_olt']))] = $r['id_olt'];

    $ponsMap = [];
    $res = $conn->query("SELECT id_pon, nombre_pon FROM pons"); // Asumiendo tabla pons
    while ($r = $res->fetch_assoc())
        $ponsMap[strtoupper(trim($r['nombre_pon']))] = $r['id_pon'];

    $stats = ['updated' => 0, 'inserted' => 0, 'errors' => 0, 'skipped' => 0];

    // Recorrer filas (omitir header ya quitado)
    foreach ($rows as $index => $row) {
        // Mapear Row a Array Asociativo usando Headers
        $rowData = [];
        foreach ($headers as $colIndex => $headerName) {
            $columnName = trim($headerName); // Limpiar espacios
            if (isset($colMap[$columnName]) && isset($row[$colIndex])) {
                $rowData[$colMap[$columnName]] = trim($row[$colIndex]);
            }
        }

        // Validar datos mínimos obligatorios (Cédula)
        if (empty($rowData['cedula_cliente'])) {
            $stats['skipped']++;
            continue;
        }

        // Resolver FKs Simples (Municipio, Parroquia, Plan, OLT)
        // Nota: Esto es básico. Si el nombre no coincide exactamente, quedará NULL.
        $munID = isset($rowData['id_municipio']) ? ($municipiosMap[strtoupper($rowData['id_municipio'])] ?? null) : null;
        $parID = isset($rowData['id_parroquia']) ? ($parroquiasMap[strtoupper($rowData['id_parroquia'])] ?? null) : null;
        $planID = isset($rowData['id_plan']) ? ($planesMap[strtoupper($rowData['id_plan'])] ?? null) : null;
        $oltID = isset($rowData['id_olt']) ? ($oltsMap[strtoupper($rowData['id_olt'])] ?? null) : null;

        // Componer valores para SQL
        $cedula = $rowData['cedula_cliente'];
        $nombre = $rowData['nombre_cliente'] ?? '';
        $direccion = $rowData['direccion_instalacion'] ?? '';
        $telf1 = $rowData['telefono_cliente'] ?? '';
        // ... (resto de campos) - Simplificado para este paso:
        // Si existe ID, actualizar. Si no, insertar.

        $id_contrato = isset($rowData['id_contrato']) ? (int) $rowData['id_contrato'] : 0;

        if ($id_contrato > 0) {
            // INTENTAR UPDATE
            $stmt = $conn->prepare("UPDATE contratos SET 
                cedula_cliente=?, nombre_cliente=?, direccion_instalacion=?, telefono_cliente=?,
                id_municipio=?, id_parroquia=?, id_plan=?, id_olt=?
                WHERE id_contrato=?");
            $stmt->bind_param("ssssiiiii", $cedula, $nombre, $direccion, $telf1, $munID, $parID, $planID, $oltID, $id_contrato);
            if ($stmt->execute()) {
                $stats['updated']++;
            } else {
                $stats['errors']++;
            }
        } else {
            // INTENTAR INSERT
            $stmt = $conn->prepare("INSERT INTO contratos (
                cedula_cliente, nombre_cliente, direccion_instalacion, telefono_cliente,
                id_municipio, id_parroquia, id_plan, id_olt
             ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssiiii", $cedula, $nombre, $direccion, $telf1, $munID, $parID, $planID, $oltID);
            if ($stmt->execute()) {
                $stats['inserted']++;
            } else {
                $stats['errors']++;
            }
        }
    }

    echo json_encode([
        'success' => true,
        'message' => "Proceso completado. Actualizados: {$stats['updated']}, Nuevos: {$stats['inserted']}, Errores: {$stats['errors']}",
        'stats' => $stats
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error procesando Excel: ' . $e->getMessage()]);
}
?>