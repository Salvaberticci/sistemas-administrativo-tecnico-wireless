<?php
require_once '../conexion.php';

// Nombre del archivo
$filename = "Galanet-Prorroga_" . date('F_Y') . ".xls";

// Cabeceras para forzar descarga de Excel
header("Content-Type: application/vnd.ms-excel; charset=utf-8");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Pragma: no-cache");
header("Expires: 0");

$sql = "SELECT cedula_titular, nombre_titular, existe_saeplus, fecha_corte, dia_prorroga, prorroga_regular, estado 
        FROM prorrogas 
        WHERE tipo_solicitud = 'PRORROGA' 
        ORDER BY fecha_registro DESC";

$res = $conn->query($sql);

echo '<table border="1">';
echo '<tr><th colspan="7" style="background-color: #f8f9fa; color: #0d6efd; font-size: 16px;">Galanet-Prórroga ' . date('Y') . '</th></tr>';
echo '<tr><th colspan="7" style="text-align: right;">' . strtoupper(date('F')) . '</th></tr>';
echo '<tr style="background-color: #d1e7dd;">
        <th>CEDULA</th>
        <th>NOMBRE</th>
        <th>SAEPLUS</th>
        <th>CORTE</th>
        <th>PRORROGA</th>
        <th>REGULAR?</th>
        <th>CARGADO</th>
      </tr>';

if ($res && $res->num_rows > 0) {
    while ($row = $res->fetch_assoc()) {
        $corte = !empty($row['fecha_corte']) ? date('j', strtotime($row['fecha_corte'])) : '';
        echo '<tr>';
        echo '<td>' . htmlspecialchars($row['cedula_titular']) . '</td>';
        echo '<td>' . htmlspecialchars($row['nombre_titular']) . '</td>';
        echo '<td>' . htmlspecialchars($row['existe_saeplus']) . '</td>';
        echo '<td>' . $corte . '</td>';
        echo '<td>' . htmlspecialchars($row['dia_prorroga']) . '</td>';
        echo '<td>' . htmlspecialchars($row['prorroga_regular']) . '</td>';
        echo '<td>' . htmlspecialchars($row['estado']) . '</td>';
        echo '</tr>';
    }
} else {
    echo '<tr><td colspan="7">No hay datos disponibles</td></tr>';
}
echo '</table>';

$conn->close();
?>