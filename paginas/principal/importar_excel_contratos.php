<?php
require_once '../../vendor/autoload.php';
require_once '../conexion.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

// Optimización de recursos para archivos grandes
ini_set('memory_limit', '512M');
set_time_limit(600);

header('Content-Type: application/json');

// Función para normalizar encabezados
function normalizeHeader($h) {
    if (!$h) return "";
    $h = trim($h);
    $h = mb_strtoupper($h, 'UTF-8');
    // Eliminar acentos para robustez
    $search = ['Á','É','Í','Ó','Ú','Ñ'];
    $replace = ['A','E','I','O','U','N'];
    return str_replace($search, $replace, $h);
}

// Función para formatear IP (12 dígitos a 4 octetos)
function formatIP($ip) {
    if (!$ip) return "";
    $ip = trim($ip);
    if (preg_match('/^\d{12}$/', $ip)) {
        return (int)substr($ip, 0, 3) . "." . (int)substr($ip, 3, 3) . "." . (int)substr($ip, 6, 3) . "." . (int)substr($ip, 9, 3);
    }
    return $ip;
}

// Verificar archivo
if (!isset($_FILES['archivo_excel']) || $_FILES['archivo_excel']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'Error al subir el archivo.']);
    exit;
}

$inputFileName = $_FILES['archivo_excel']['tmp_name'];

try {
    $spreadsheet = IOFactory::load($inputFileName);
    $worksheet = $spreadsheet->getActiveSheet();
    $rows = $worksheet->toArray(null, true, true, true);

    if (empty($rows)) {
        throw new Exception("El archivo está vacío.");
    }

    $raw_headers = array_shift($rows);
    $headers = [];
    foreach ($raw_headers as $colKey => $h) {
        $headers[$colKey] = normalizeHeader($h);
    }

    // Mapa de Columnas (Nombre Normalizado => Columna BD)
    $colMap = [
        'CEDULA' => 'cedula',
        'NOMBRE' => 'nombre_completo',
        'MUNICIPIO' => 'municipio_texto',
        'PARROQUIA' => 'parroquia_texto',
        'DIRECCION' => 'direccion',
        'TELEFONO DE CONTACTO 1' => 'telefono',
        'TELEFONO DE CONTACTO 2' => 'telefono_secundario',
        'CORREO ELECTRONICO' => 'correo',
        'CORREO ELECTRONICO(ADICIONAL)' => 'correo_adicional',
        'FECHA DE INSTALACION' => 'fecha_instalacion',
        'MEDIO DE PAGO' => 'medio_pago',
        'MONTO A PAGAR(BS O $)' => 'monto_pagar',
        'MONTO PAGADO(BS O $)' => 'monto_pagado',
        'DIAS DE PRORRATEO' => 'dias_prorrateo',
        'MONTO PRORRATEO $' => 'monto_prorrateo_usd',
        'OBSERVACIONES' => 'observaciones',
        'TIPO DE CONEXION' => 'tipo_conexion',
        'MAC O SERIAL DE ONU' => 'mac_onu',
        'IDENTIFICACION CAJA NAP' => 'ident_caja_nap',
        'PUERTO DE NAP' => 'puerto_nap',
        'NAP TX POWER -DBM' => 'nap_tx_power',
        'ONU RX POWER -DBM' => 'onu_rx_power',
        'DISTANCIA DROPP (M)' => 'distancia_drop',
        'INSTALADOR' => 'instalador',
        'DIRECCION IP' => 'ip_onu',
        'PUNTO DE ACCESO' => 'punto_acceso',
        'VALOR DE CONEXION DBM' => 'valor_conexion_dbm',
        'INSTALADOR C' => 'instalador_c',
        'NUMERO DE PRECINTO DE IDENTIFICACION ODN' => 'num_presinto_odn',
        'PLAN' => 'plan_raw'
    ];

    // Obtener Mapas de FK
    $municipiosMap = [];
    $res = $conn->query("SELECT id_municipio, nombre_municipio FROM municipio");
    while ($r = $res->fetch_assoc()) $municipiosMap[normalizeHeader($r['nombre_municipio'])] = $r['id_municipio'];

    $parroquiasMap = [];
    $res = $conn->query("SELECT id_parroquia, nombre_parroquia FROM parroquia");
    while ($r = $res->fetch_assoc()) $parroquiasMap[normalizeHeader($r['nombre_parroquia'])] = $r['id_parroquia'];

    $planesPrices = [];
    $res = $conn->query("SELECT id_plan, monto FROM planes");
    while ($r = $res->fetch_assoc()) $planesPrices[] = ['id' => $r['id_plan'], 'monto' => (float)$r['monto']];

    $stats = ['updated' => 0, 'inserted' => 0, 'errors' => 0, 'skipped' => 0];

    foreach ($rows as $row) {
        // Inicializar todos los campos con string vacío para evitar NULL
        $rowData = array_fill_keys(array_values($colMap), '');
        
        foreach ($headers as $colKey => $normHeader) {
            if (isset($colMap[$normHeader])) {
                $val = $row[$colKey] ?? '';
                $rowData[$colMap[$normHeader]] = ($val === null) ? '' : trim($val);
            }
        }

        // Validar datos mínimos obligatorios (Cédula y Nombre)
        if (empty($rowData['cedula']) || empty($rowData['nombre_completo'])) {
            $stats['skipped']++;
            continue;
        }

        // Resolver Plan por Precio
        $id_plan = 8; // Default Exonerado
        if (isset($rowData['plan_raw'])) {
            $price = (float)$rowData['plan_raw'];
            foreach ($planesPrices as $p) {
                if (abs($p['monto'] - $price) < 0.05) {
                    $id_plan = $p['id'];
                    // Heurística para $23.20 (2=20MB, 3=250MB)
                    if (abs($price - 23.20) < 0.05) {
                        $tipo = strtoupper($rowData['tipo_conexion'] ?? '');
                        $id_plan = ($tipo == 'RADIO') ? 2 : 3;
                    }
                    break;
                }
            }
        }

        $id_mun = $municipiosMap[normalizeHeader($rowData['municipio_texto'] ?? '')] ?? null;
        $id_par = $parroquiasMap[normalizeHeader($rowData['parroquia_texto'] ?? '')] ?? null;
        $ip_onu = formatIP($rowData['ip_onu'] ?? '');

        // SQL Dinámico para Insert/Update
        $cedula = trim($rowData['cedula']);
        $sql = "INSERT INTO contratos (
            cedula, nombre_completo, direccion, telefono, id_municipio, municipio_texto, id_parroquia, parroquia_texto,
            id_plan, monto_plan, tipo_conexion, mac_onu, ip_onu, ident_caja_nap, puerto_nap, nap_tx_power, onu_rx_power,
            distancia_drop, instalador, instalador_c, punto_acceso, valor_conexion_dbm, num_presinto_odn, observaciones,
            estado, fecha_registro
        ) VALUES (
            ?, ?, ?, ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?, ?, ?,
            'ACTIVO', NOW()
        ) ON DUPLICATE KEY UPDATE
            nombre_completo = VALUES(nombre_completo),
            direccion = VALUES(direccion),
            telefono = VALUES(telefono),
            id_municipio = VALUES(id_municipio),
            municipio_texto = VALUES(municipio_texto),
            id_parroquia = VALUES(id_parroquia),
            parroquia_texto = VALUES(parroquia_texto),
            id_plan = VALUES(id_plan),
            monto_plan = VALUES(monto_plan),
            tipo_conexion = VALUES(tipo_conexion),
            mac_onu = VALUES(mac_onu),
            ip_onu = VALUES(ip_onu),
            ident_caja_nap = VALUES(ident_caja_nap),
            puerto_nap = VALUES(puerto_nap),
            nap_tx_power = VALUES(nap_tx_power),
            onu_rx_power = VALUES(onu_rx_power),
            distancia_drop = VALUES(distancia_drop),
            instalador = VALUES(instalador),
            instalador_c = VALUES(instalador_c),
            punto_acceso = VALUES(punto_acceso),
            valor_conexion_dbm = VALUES(valor_conexion_dbm),
            num_presinto_odn = VALUES(num_presinto_odn),
            observaciones = VALUES(observaciones)";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssisisidssssssssssssss", 
            $cedula, $rowData['nombre_completo'], $rowData['direccion'], $rowData['telefono'], $id_mun, $rowData['municipio_texto'], $id_par, $rowData['parroquia_texto'],
            $id_plan, $rowData['plan_raw'], $rowData['tipo_conexion'], $rowData['mac_onu'], $ip_onu, $rowData['ident_caja_nap'], $rowData['puerto_nap'], $rowData['nap_tx_power'], $rowData['onu_rx_power'],
            $rowData['distancia_drop'], $rowData['instalador'], $rowData['instalador_c'], $rowData['punto_acceso'], $rowData['valor_conexion_dbm'], $rowData['num_presinto_odn'], $rowData['observaciones']
        );

        if ($stmt->execute()) {
            if ($stmt->affected_rows == 1) $stats['inserted']++;
            elseif ($stmt->affected_rows == 2) $stats['updated']++;
            else $stats['skipped']++;
        } else {
            $stats['errors']++;
        }
    }

    echo json_encode([
        'success' => true,
        'message' => "Proceso completado. Insertados: {$stats['inserted']}, Actualizados: {$stats['updated']}, Errores: {$stats['errors']}, Ignorados: {$stats['skipped']}",
        'stats' => $stats
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>