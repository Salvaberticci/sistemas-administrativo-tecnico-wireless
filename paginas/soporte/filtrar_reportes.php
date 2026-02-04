<?php
/**
 * Filtrar Reportes - Endpoint para DataTables con Server-Side Processing
 * Soporta filtrado por fecha, tipo de falla, técnico y estado de pago
 */
require_once '../conexion.php';

// Parámetros de DataTables
$aColumns = array('s.id_soporte', 'fecha_soporte', 'c.nombre_completo', 'tipo_falla', 'tecnico_asignado', 'monto_total', 'monto_pagado', 'estado_pago');

$sIndexColumn = "s.id_soporte";
$sTable = "soportes s INNER JOIN contratos c ON s.id_contrato = c.id";

// Entrada de búsqueda y paginación
$sLimit = "";
if (isset($_GET['iDisplayStart']) && $_GET['iDisplayLength'] != '-1') {
    $sLimit = "LIMIT " . intval($_GET['iDisplayStart']) . ", " . intval($_GET['iDisplayLength']);
}

// Ordenamiento
$sOrder = "";
if (isset($_GET['iSortCol_0'])) {
    $sOrder = "ORDER BY  ";
    for ($i = 0; $i < intval($_GET['iSortingCols']); $i++) {
        if ($_GET['bSortable_' . intval($_GET['iSortCol_' . $i])] == "true") {
            $sOrder .= $aColumns[intval($_GET['iSortCol_' . $i])] . " " .
                ($_GET['sSortDir_' . $i] === 'asc' ? 'asc' : 'desc') . ", ";
        }
    }
    $sOrder = substr_replace($sOrder, "", -2);
    if ($sOrder == "ORDER BY") {
        $sOrder = "";
    }
}

// Filtros personalizados
$aWhere = array();

// Filtro de fecha
if (isset($_GET['fecha_desde']) && !empty($_GET['fecha_desde'])) {
    $fecha_desde = $conn->real_escape_string($_GET['fecha_desde']);
    $aWhere[] = "s.fecha_soporte >= '$fecha_desde'";
}

if (isset($_GET['fecha_hasta']) && !empty($_GET['fecha_hasta'])) {
    $fecha_hasta = $conn->real_escape_string($_GET['fecha_hasta']);
    $aWhere[] = "s.fecha_soporte <= '$fecha_hasta'";
}

// Filtro de tipo de falla
if (isset($_GET['tipo_falla']) && !empty($_GET['tipo_falla'])) {
    $tipo_falla = $conn->real_escape_string($_GET['tipo_falla']);
    $aWhere[] = "s.tipo_falla = '$tipo_falla'";
}

// Filtro de técnico
if (isset($_GET['tecnico']) && !empty($_GET['tecnico'])) {
    $tecnico = $conn->real_escape_string($_GET['tecnico']);
    $aWhere[] = "s.tecnico_asignado = '$tecnico'";
}

// Filtro de estado de pago
if (isset($_GET['estado_pago']) && !empty($_GET['estado_pago'])) {
    $estado = $conn->real_escape_string($_GET['estado_pago']);
    if ($estado == 'PAGADO') {
        $aWhere[] = "(s.monto_total - s.monto_pagado) <= 0.01";
    } elseif ($estado == 'PENDIENTE') {
        $aWhere[] = "(s.monto_total - s.monto_pagado) > 0.01";
    }
}

// Búsqueda general de DataTables
$sWhere = "";
if (isset($_GET['sSearch']) && $_GET['sSearch'] != "") {
    $sSearch = $conn->real_escape_string($_GET['sSearch']);
    $aWhere[] = "(
        s.id_soporte LIKE '%{$sSearch}%' OR
        c.nombre_completo LIKE '%{$sSearch}%' OR
        s.tipo_falla LIKE '%{$sSearch}%' OR
        s.tecnico_asignado LIKE '%{$sSearch}%' OR
        s.descripcion LIKE '%{$sSearch}%'
    )";
}

if (count($aWhere) > 0) {
    $sWhere = "WHERE " . implode(" AND ", $aWhere);
}

// Consulta SQL para datos
$sQuery = "
    SELECT SQL_CALC_FOUND_ROWS 
        s.id_soporte,
        DATE_FORMAT(s.fecha_soporte, '%d/%m/%Y') as fecha_formateada,
        c.nombre_completo,
        COALESCE(s.tipo_falla, 'No especificado') as tipo_falla,
        COALESCE(s.tecnico_asignado, 'Sin asignar') as tecnico,
        s.monto_total,
        s.monto_pagado,
        (s.monto_total - s.monto_pagado) as saldo_pendiente
    FROM $sTable
    $sWhere
    $sOrder
    $sLimit
";

$rResult = $conn->query($sQuery);

// Total de registros después del filtrado
$sQueryCnt = "SELECT FOUND_ROWS()";
$rResultCnt = $conn->query($sQueryCnt);
$aResultCnt = $rResultCnt->fetch_array();
$iFilteredTotal = $aResultCnt[0];

// Total de registros sin filtro
$sQueryTotal = "SELECT COUNT(s.id_soporte) FROM $sTable";
$rResultTotal = $conn->query($sQueryTotal);
$aResultTotal = $rResultTotal->fetch_array();
$iTotal = $aResultTotal[0];

// Construir salida JSON
$output = array(
    "sEcho" => intval($_GET['sEcho']),
    "iTotalRecords" => $iTotal,
    "iTotalDisplayRecords" => $iFilteredTotal,
    "aaData" => array()
);

while ($aRow = $rResult->fetch_assoc()) {
    $row = array();
    $row[] = $aRow['id_soporte'];
    $row[] = $aRow['fecha_formateada'];
    $row[] = $aRow['nombre_completo'];
    $row[] = $aRow['tipo_falla'];
    $row[] = $aRow['tecnico'];
    $row[] = $aRow['monto_total'];
    $row[] = $aRow['monto_pagado'];
    $row[] = $aRow['saldo_pendiente']; // Para el badge de estado

    $output['aaData'][] = $row;
}

echo json_encode($output);
$conn->close();
?>