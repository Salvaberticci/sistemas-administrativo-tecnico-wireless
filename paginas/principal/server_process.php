<?php
// server_process.php
ini_set('display_errors', 0); // Hide errors in production JSON
error_reporting(E_ALL);

require '../conexion.php';

// 1. Tables and Joins
$sTabla = "
    contratos c
   LEFT JOIN municipio m ON c.id_municipio = m.id_municipio 
   LEFT JOIN parroquia pa ON c.id_parroquia = pa.id_parroquia 
   LEFT JOIN comunidad com ON c.id_comunidad = com.id_comunidad 
   LEFT JOIN planes pl ON c.id_plan = pl.id_plan
   LEFT JOIN vendedores v ON c.id_vendedor = v.id_vendedor
   LEFT JOIN olt ol ON c.id_olt = ol.id_olt
   LEFT JOIN pon pn ON c.id_pon = pn.id_pon
";

// 2. Columns to FETCH and SEARCH
// Note: The order here dictates the index for searching/sorting/filtering from DataTables
$aColumnas = [
    'c.id',                     // 0
    'c.fecha_registro',         // 1
    'c.cedula',                 // 2
    'c.nombre_completo',        // 3
    'm.nombre_municipio',       // 4
    'pa.nombre_parroquia',      // 5
    'c.direccion',              // 6
    'c.telefono',               // 7
    'c.telefono_secundario',    // 8
    'c.correo',                 // 9
    'c.correo_adicional',       // 10
    'c.fecha_instalacion',      // 11
    'c.medio_pago',             // 12
    'c.monto_pagar',            // 13
    'c.monto_pagado',           // 14
    'c.dias_prorrateo',         // 15
    'c.monto_prorrateo_usd',    // 16
    'c.observaciones',          // 17
    'c.tipo_conexion',          // 18
    'c.numero_onu',             // 19
    'c.mac_onu',                // 20
    'c.ip_onu',                 // 21
    'c.ident_caja_nap',         // 22
    'c.puerto_nap',             // 23
    'c.nap_tx_power',           // 24
    'c.onu_rx_power',           // 25
    'c.distancia_drop',         // 26
    'c.instalador',             // 27
    'c.evidencia_fibra',        // 28
    'c.ip',                     // 29
    'c.punto_acceso',           // 30
    'c.valor_conexion_dbm',     // 31
    'c.num_presinto_odn',       // 32
    'c.evidencia_foto',         // 33
    'c.firma_cliente',          // 34
    'c.firma_tecnico',          // 35
    'c.vendedor_texto',         // 36
    'c.sae_plus',               // 37
    'pl.nombre_plan',           // 38
    'ol.nombre_olt',            // 39
    'pn.nombre_pon',            // 40
    'c.estado'                  // 41
];

$sIndexColumn = "c.id";

// --- Paginación ---
$sLimit = '';
if (isset($_GET['iDisplayStart']) && $_GET['iDisplayLength'] != '-1') {
    $sLimit = "LIMIT " . intval($_GET['iDisplayStart']) . ", " . intval($_GET['iDisplayLength']);
}

// --- Ordenamiento ---
$sOrder = '';
if (isset($_GET['iSortCol_0'])) {
    $sOrder = 'ORDER BY ';
    $sortingCols = intval($_GET['iSortingCols']);
    for ($i = 0; $i < $sortingCols; $i++) {
        $sortColIdx = intval($_GET["iSortCol_{$i}"]);
        if (isset($aColumnas[$sortColIdx])) {
            $sortDir = $_GET["sSortDir_{$i}"] === 'desc' ? 'desc' : 'asc'; // Sanitize direction
            $sOrder .= $aColumnas[$sortColIdx] . " " . $sortDir . ", ";
        }
    }
    $sOrder = rtrim($sOrder, ', ');
    if ($sOrder == 'ORDER BY ')
        $sOrder = '';
}

// --- Búsqueda ---
$sWhere = "";
$searchConditions = [];
if (isset($_GET['sSearch']) && $_GET['sSearch'] != "") {
    $searchValue = $conn->real_escape_string($_GET['sSearch']);
    foreach ($aColumnas as $col) {
        $searchConditions[] = "$col LIKE '%$searchValue%'";
    }
}

// --- Filtro por Vacíos ---
$emptyFilterConditions = [];
if (isset($_GET['empty_filter']) && $_GET['empty_filter'] !== "") {
    $colIdx = intval($_GET['empty_filter']);
    if (isset($aColumnas[$colIdx])) {
        $colName = $aColumnas[$colIdx];
        // TRIM handles strings with only spaces
        // Also check for '0000-00-00' (dates) and '-' (placeholder)
        $emptyFilterConditions[] = "($colName IS NULL OR TRIM($colName) = '' OR $colName = '0000-00-00' OR TRIM($colName) = '-')";
    }
}

if (!empty($searchConditions) || !empty($emptyFilterConditions)) {
    $sWhere = "WHERE ";
    if (!empty($searchConditions)) {
        $sWhere .= "(" . implode(' OR ', $searchConditions) . ")";
        if (!empty($emptyFilterConditions)) {
            $sWhere .= " AND ";
        }
    }
    if (!empty($emptyFilterConditions)) {
        $sWhere .= "(" . implode(' AND ', $emptyFilterConditions) . ")";
    }
}

// --- Query Principal ---
$sQuery = "
    SELECT SQL_CALC_FOUND_ROWS " . implode(', ', $aColumnas) . "
    FROM $sTabla
    $sWhere
    $sOrder
    $sLimit
";
$rResult = $conn->query($sQuery);
if (!$rResult) {
    die(json_encode(['error' => $conn->error]));
}

// --- Totales ---
$rResultFilterTotal = $conn->query("SELECT FOUND_ROWS()");
$iFilteredTotal = $rResultFilterTotal->fetch_array()[0];

$rResultTotal = $conn->query("SELECT COUNT({$sIndexColumn}) FROM $sTabla");
$iTotal = $rResultTotal->fetch_array()[0];

// --- Salida ---
$output = [
    "sEcho" => intval($_GET['sEcho']),
    "iTotalRecords" => $iTotal,
    "iTotalDisplayRecords" => $iFilteredTotal,
    "aaData" => []
];

// Helper para limpiar strings
function clean($str)
{
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

while ($aRow = $rResult->fetch_assoc()) {
    $row = [];
    $id = $aRow['id'];

    // 0. ID (Hidden in JS usually, but sent here)
    $row[] = $id;

    // 1. SAR (Fecha Registro)
    $row[] = !empty($aRow['fecha_registro']) ? date('d/m/Y H:i', strtotime($aRow['fecha_registro'])) : '-';

    // 2. CEDULA
    $row[] = clean($aRow['cedula']);

    // 3. NOMBRE
    $row[] = "<span class='fw-bold text-primary'>" . clean($aRow['nombre_completo']) . "</span>";

    // 4. MUNICIPIO
    $row[] = clean($aRow['nombre_municipio']);

    // 5. PARROQUIA
    $row[] = clean($aRow['nombre_parroquia']);

    // 6. DIRECCION (Boton + Texto)
    $direccion = clean(str_replace(["\r", "\n"], ' ', $aRow['direccion']));
    $row[] = "<div class='d-flex align-items-center gap-2'>
                <small class='text-muted d-inline-block' style='max-width: 150px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;' title='{$direccion}'>{$direccion}</small>
              </div>";

    // 7. TELEFONO 1
    $row[] = clean($aRow['telefono']);

    // 8. TELEFONO 2
    $row[] = clean($aRow['telefono_secundario']);

    // 9. CORREO
    $row[] = clean($aRow['correo']);

    // 10. CORREO ADICIONAL
    $row[] = clean($aRow['correo_adicional']);

    // 11. FECHA INSTALACION
    $row[] = (!empty($aRow['fecha_instalacion']) && $aRow['fecha_instalacion'] != '0000-00-00') ? date('d/m/Y', strtotime($aRow['fecha_instalacion'])) : '';

    // 12. MEDIO PAGO
    $row[] = clean($aRow['medio_pago']);

    // 13. MONTO PAGAR
    $row[] = clean($aRow['monto_pagar']);

    // 14. MONTO PAGADO
    $row[] = clean($aRow['monto_pagado']);

    // 15. DIAS PRORRATEO
    $row[] = clean($aRow['dias_prorrateo']);

    // 16. MONTO PRORRATEO $
    $row[] = clean($aRow['monto_prorrateo_usd']);

    // 17. OBSERVACIONES
    $row[] = clean($aRow['observaciones']);

    // 18. TIPO CONEXION
    $row[] = clean($aRow['tipo_conexion']);

    // 19. NUMERO ONU
    $row[] = clean($aRow['numero_onu']);

    // 20. MAC ONU
    $row[] = clean($aRow['mac_onu']);

    // 21. IP ONU
    $row[] = clean($aRow['ip_onu']);

    // 22. CAJA NAP
    $row[] = clean($aRow['ident_caja_nap']);

    // 23. PUERTO NAP
    $row[] = clean($aRow['puerto_nap']);

    // 24. NAP TX POWER
    $row[] = clean($aRow['nap_tx_power']);

    // 25. ONU RX POWER
    $row[] = clean($aRow['onu_rx_power']);

    // 26. DISTANCIA DROP
    $row[] = clean($aRow['distancia_drop']);

    // 27. INSTALADOR
    $row[] = clean($aRow['instalador']);

    // 28. IP SERVICIO
    $row[] = clean($aRow['ip']);

    // 29. PUNTO ACCESO
    $row[] = clean($aRow['punto_acceso']);

    // 30. VALOR CONEXION DBM
    $row[] = clean($aRow['valor_conexion_dbm']);

    // 31. INSTALADOR (Repetido)
    $row[] = clean($aRow['instalador']);

    // 32. SUGERENCIAS (Observaciones)
    $row[] = clean($aRow['observaciones']);

    // 33. PRECINTO ODN
    $row[] = clean($aRow['num_presinto_odn']);

    // 34. EVIDENCIA FOTO
    $link = $aRow['evidencia_foto'];
    if (!empty($link)) {
        $row[] = "<a href='{$link}' target='_blank' class='btn btn-sm btn-outline-primary'><i class='fa-solid fa-image'></i></a>";
    } else {
        $row[] = '-';
    }

    // 34B. FIRMA CLIENTE
    $firmaCliente = $aRow['firma_cliente'] ?? '';
    if (!empty($firmaCliente)) {
        $row[] = "<a href='../../uploads/firmas/{$firmaCliente}' target='_blank' class='btn btn-sm btn-outline-info'><i class='fa-solid fa-signature'></i></a>";
    } else {
        $row[] = '-';
    }

    // 34C. FIRMA TECNICO
    $firmaTecnico = $aRow['firma_tecnico'] ?? '';
    if (!empty($firmaTecnico)) {
        $row[] = "<a href='../../uploads/firmas/{$firmaTecnico}' target='_blank' class='btn btn-sm btn-outline-success'><i class='fa-solid fa-signature'></i></a>";
    } else {
        $row[] = '-';
    }

    // 34D. EVIDENCIA FIBRA  
    $row[] = clean($aRow['evidencia_fibra']);

    // --- EXTRAS ---

    // VENDEDOR (Editable)
    $v = clean($aRow['vendedor_texto']);
    $row[] = "<div contenteditable='true' class='editable-cell' data-id='{$id}' data-field='vendedor_texto'>{$v}</div>";

    // SAE PLUS (Editable)
    $s = clean($aRow['sae_plus']);
    $row[] = "<div contenteditable='true' class='editable-cell' data-id='{$id}' data-field='sae_plus'>{$s}</div>";

    // PLAN
    $row[] = clean($aRow['nombre_plan']);

    // OLT
    $row[] = clean($aRow['nombre_olt']);

    // PON
    $row[] = clean($aRow['nombre_pon']);

    // ESTADO
    $st = $aRow['estado'];
    $color = 'secondary';
    if ($st == 'ACTIVO')
        $color = 'success';
    if ($st == 'SUSPENDIDO')
        $color = 'danger';
    $row[] = "<span class='badge bg-{$color}'>{$st}</span>";

    // ACCIONES
    $row[] = "
        <div class='d-flex gap-1'>
            <a href='../reportes_pdf/generar_contrato_pdf.php?id_contrato={$id}' target='_blank' class='btn btn-sm btn-outline-danger' title='PDF'><i class='fa-solid fa-file-pdf'></i></a>
            <a href='modifica.php?id={$id}' class='btn btn-sm btn-outline-primary' title='Editar'><i class='fa-solid fa-pen'></i></a>
            <button class='btn btn-sm btn-outline-secondary' onclick='confirmarEliminar({$id})' title='Eliminar'><i class='fa-solid fa-trash'></i></button>
        </div>
    ";

    $output['aaData'][] = $row;
}

echo json_encode($output);
?>