<?php
/**
 * Exportar Mensualidades a Excel (.xls)
 * Columnas: Fecha, Ref, Cliente, Concepto, Monto, Cuenta
 */
require '../conexion.php';

$tipo = isset($_GET['tipo']) ? $_GET['tipo'] : 'todos';
$fecha_inicio = isset($_GET['fecha_inicio']) ? $_GET['fecha_inicio'] : '';
$fecha_fin = isset($_GET['fecha_fin']) ? $_GET['fecha_fin'] : '';
$id_banco = isset($_GET['id_banco']) ? $_GET['id_banco'] : '';

// Construir consulta
$sWhere = "WHERE 1=1";

if (!empty($fecha_inicio) && !empty($fecha_fin)) {
    $sWhere .= " AND (cxc.fecha_emision BETWEEN '" . $conn->real_escape_string($fecha_inicio) . "' AND '" . $conn->real_escape_string($fecha_fin) . "')";
}

if ($tipo === 'filtrado' && !empty($id_banco)) {
    $sWhere .= " AND cxc.id_banco = '" . $conn->real_escape_string($id_banco) . "'";
}
// Si es 'todos' (global), NO aplicamos el filtro de banco, pero sí el de fechas si existen.

// Cargar Bancos JSON
$json_bancos = file_get_contents('bancos.json');
$bancosArr = json_decode($json_bancos, true) ?: [];
$bancosMap = [];
foreach($bancosArr as $b) {
    if(isset($b['id_banco'])) $bancosMap[$b['id_banco']] = $b['nombre_banco'];
}

$sql = "
    SELECT 
        cxc.fecha_emision,
        cxc.fecha_pago,
        cxc.referencia_pago,
        c.nombre_completo,
        pl.nombre_plan,
        cxc.monto_total,
        cxc.id_banco,
        cxc.estado,
        h.justificacion
    FROM cuentas_por_cobrar cxc
    INNER JOIN contratos c ON cxc.id_contrato = c.id
    LEFT JOIN planes pl ON c.id_plan = pl.id_plan
    LEFT JOIN cobros_manuales_historial h ON cxc.id_cobro = h.id_cobro_cxc
    $sWhere
    ORDER BY cxc.fecha_emision DESC
";

$result = $conn->query($sql);

// Nombre del archivo
$suffix = ($tipo === 'filtrado' ? 'filtrado' : 'global');
if ($fecha_inicio) $suffix .= "_" . str_replace('-', '', $fecha_inicio);
$filename = "mensualidades_" . $suffix . "_" . date('Ymd') . ".xls";

// Headers para descarga en formato Excel
header("Content-Type: application/vnd.ms-excel; charset=utf-8");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Expires: 0");
header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
header("Cache-Control: private", false);

// BOM para UTF-8
echo "\xEF\xBB\xBF";

// Empezamos la tabla HTML que Excel interpretará como celdas
echo "<table border='1'>";
echo "<thead>
        <tr style='background-color: #0d6efd; color: white;'>
            <th>Fecha de registro</th>
            <th>Numero de Referencia</th>
            <th>Cliente</th>
            <th>Concepto</th>
            <th>Monto</th>
            <th>Cuenta</th>
            <th>Estado</th>
        </tr>
      </thead>";
echo "<tbody>";

// Datos
while ($row = $result->fetch_assoc()) {
    $fecha_display = $row['fecha_pago'] ? $row['fecha_pago'] : $row['fecha_emision'];
    $fecha_fmt = date('d/m/Y', strtotime($fecha_display));
    
    // Concepto
    $concepto = !empty($row['justificacion']) ? htmlspecialchars($row['justificacion']) : htmlspecialchars($row['nombre_plan']);
    
    $monto = number_format($row['monto_total'], 2, ',', '.');
    
    // Banco name from JSON
    $id_banco = $row['id_banco'];
    $nombre_banco = isset($bancosMap[$id_banco]) ? $bancosMap[$id_banco] : ($id_banco ? 'Desconocido' : '-');

    echo "<tr>";
    echo "<td>" . $fecha_fmt . "</td>";
    echo "<td>" . ($row['referencia_pago'] ?? '-') . "</td>";
    echo "<td>" . htmlspecialchars($row['nombre_completo']) . "</td>";
    echo "<td>" . $concepto . "</td>";
    echo "<td>" . $monto . "</td>";
    echo "<td>" . htmlspecialchars($nombre_banco) . "</td>";
    echo "<td>" . htmlspecialchars($row['estado']) . "</td>";
    echo "</tr>";
}

echo "</tbody>";
echo "</table>";

$conn->close();
exit();
?>
