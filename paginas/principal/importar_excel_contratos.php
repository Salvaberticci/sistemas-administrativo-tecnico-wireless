<?php
require_once '../../vendor/autoload.php';
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
    $colMap = [
        'ID' => 'id',
        'SAR' => 'fecha_registro',
        'Cédula' => 'cedula',
        'Cliente' => 'nombre_completo',
        'Municipio' => 'id_municipio',
        'Parroquia' => 'id_parroquia',
        'Dirección' => 'direccion',
        'Telf. 1' => 'telefono',
        'Telf. 2' => 'telefono_secundario',
        'Correo' => 'correo',
        'Correo (Alt)' => 'correo_adicional',
        'F. Instalación' => 'fecha_instalacion',
        'Medio Pago' => 'medio_pago',
        'Monto Pagar' => 'monto_instalacion',
        'Monto Pagado' => 'monto_pagado',
        'Días Prorrateo' => 'dias_prorrateo',
        'Monto Prorr. ($)' => 'monto_prorrateo_usd',
        'Observ.' => 'observaciones',
        'Tipo Conex.' => 'tipo_conexion',
        'Num. ONU' => 'numero_onu',
        'MAC/Serial' => 'mac_onu',
        'IP ONU' => 'ip_onu',
        'Caja NAP' => 'ident_caja_nap',
        'Puerto NAP' => 'puerto_nap',
        'NAP TX (dBm)' => 'nap_tx_power',
        'ONU RX (dBm)' => 'onu_rx_power',
        'Dist. Drop (m)' => 'distancia_drop',
        'Instalador' => 'instalador',
        'Evidencia Fibra' => 'evidencia_fibra',
        'IP Servicio' => 'ip',
        'Punto Acceso' => 'punto_acceso',
        'Val. Conex. (dBm)' => 'valor_conexion_dbm',
        'Precinto ODN' => 'num_presinto_odn',
        'Vendedor (Edit)' => 'vendedor_texto',
        'SAE Plus (Edit)' => 'sae_plus',
        'Plan' => 'id_plan',
        'OLT' => 'id_olt',
        'PON' => 'id_pon',
        'Estado' => 'estado'
    ];

    // Obtener mapas de FK
    $municipiosMap = [];
    $res = $conn->query("SELECT id_municipio, nombre_municipio FROM municipio");
    if ($res)
        while ($r = $res->fetch_assoc())
            $municipiosMap[strtoupper(trim($r['nombre_municipio']))] = $r['id_municipio'];

    $parroquiasMap = [];
    $res = $conn->query("SELECT id_parroquia, nombre_parroquia FROM parroquia");
    if ($res)
        while ($r = $res->fetch_assoc())
            $parroquiasMap[strtoupper(trim($r['nombre_parroquia']))] = $r['id_parroquia'];

    $planesMap = [];
    $res = $conn->query("SELECT id_plan, nombre_plan FROM planes");
    if ($res)
        while ($r = $res->fetch_assoc())
            $planesMap[strtoupper(trim($r['nombre_plan']))] = $r['id_plan'];

    $oltsMap = [];
    $res = $conn->query("SELECT id_olt, nombre_olt FROM olt");
    if ($res)
        while ($r = $res->fetch_assoc())
            $oltsMap[strtoupper(trim($r['nombre_olt']))] = $r['id_olt'];

    $stats = ['updated' => 0, 'inserted' => 0, 'errors' => 0, 'skipped' => 0];

    // Recorrer filas
    foreach ($rows as $index => $row) {
        // Mapear Row
        $rowData = [];
        foreach ($headers as $colIndex => $headerName) {
            $columnName = trim($headerName);
            if (isset($colMap[$columnName]) && isset($row[$colIndex])) {
                $rowData[$colMap[$columnName]] = trim($row[$colIndex]);
            }
        }

        // Validar datos mínimos obligatorios (Cédula)
        if (empty($rowData['cedula'])) {
            $stats['skipped']++;
            continue;
        }

        // Resolver FKs Simples
        $munID = isset($rowData['id_municipio']) ? ($municipiosMap[strtoupper($rowData['id_municipio'])] ?? null) : null;
        $parID = isset($rowData['id_parroquia']) ? ($parroquiasMap[strtoupper($rowData['id_parroquia'])] ?? null) : null;
        $planID = isset($rowData['id_plan']) ? ($planesMap[strtoupper($rowData['id_plan'])] ?? null) : null;
        $oltID = isset($rowData['id_olt']) ? ($oltsMap[strtoupper($rowData['id_olt'])] ?? null) : null;

        // Componer valores para SQL
        $cedula = $rowData['cedula'];
        $nombre = $rowData['nombre_completo'] ?? '';
        $direccion = $rowData['direccion'] ?? '';
        $telefono = $rowData['telefono'] ?? '';
        $id_contrato = isset($rowData['id']) ? (int) $rowData['id'] : 0;

        if ($id_contrato > 0) {
            // INTENTAR UPDATE
            $stmt = $conn->prepare("UPDATE contratos SET
                cedula=?, nombre_completo=?, direccion=?, telefono=?,
                id_municipio=?, id_parroquia=?, id_plan=?, id_olt=?
                WHERE id=?");
            $stmt->bind_param("ssssiiiii", $cedula, $nombre, $direccion, $telefono, $munID, $parID, $planID, $oltID, $id_contrato);
            if ($stmt->execute()) {
                $stats['updated']++;
            } else {
                $stats['errors']++;
            }
        } else {
            // INTENTAR INSERT
            $stmt = $conn->prepare("INSERT INTO contratos (
                cedula, nombre_completo, direccion, telefono,
                id_municipio, id_parroquia, id_plan, id_olt
             ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssiiii", $cedula, $nombre, $direccion, $telefono, $munID, $parID, $planID, $oltID);
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