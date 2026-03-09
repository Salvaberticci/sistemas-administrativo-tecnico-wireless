<?php
// Archivo: exportar_contratos_filtrado.php
// Exporta contratos a Excel aplicando filtros dinámicos

require_once '../conexion.php';

// Configurar encabezados para forzar la descarga del archivo Excel
header("Content-Type: application/vnd.ms-excel; charset=utf-8");
header("Content-Disposition: attachment; filename=reporte_contratos_filtrado_" . date('Ymd_His') . ".xls");
header("Pragma: no-cache");
header("Expires: 0");

// Imprimir BOM para UTF-8 en Excel
echo "\xEF\xBB\xBF";

// 1. Captura de parámetros POST/GET del modal
$cedula = isset($_GET['cedula']) ? trim($_GET['cedula']) : '';
$cliente = isset($_GET['cliente']) ? trim($_GET['cliente']) : '';
$fecha_desde = isset($_GET['fecha_desde']) ? $_GET['fecha_desde'] : '';
$fecha_hasta = isset($_GET['fecha_hasta']) ? $_GET['fecha_hasta'] : '';
$municipio = isset($_GET['municipio']) ? $_GET['municipio'] : '';
$estado = isset($_GET['estado']) ? $_GET['estado'] : '';
$id_plan = isset($_GET['id_plan']) ? $_GET['id_plan'] : '';
$tipo_conexion = isset($_GET['tipo_conexion']) ? $_GET['tipo_conexion'] : '';
$id_olt = isset($_GET['id_olt']) ? $_GET['id_olt'] : '';

// 2. Construcción de la consulta dinámica
$where_clauses = ["1=1"];

if ($cedula !== '') {
    $where_clauses[] = "c.cedula LIKE '%" . $conn->real_escape_string($cedula) . "%'";
}
if ($cliente !== '') {
    $where_clauses[] = "c.nombre_completo LIKE '%" . $conn->real_escape_string($cliente) . "%'";
}
if ($fecha_desde !== '') {
    $where_clauses[] = "c.fecha_instalacion >= '" . $conn->real_escape_string($fecha_desde) . "'";
}
if ($fecha_hasta !== '') {
    $where_clauses[] = "c.fecha_instalacion <= '" . $conn->real_escape_string($fecha_hasta) . "'";
}
if ($municipio !== '') {
    $where_clauses[] = "m.nombre_municipio = '" . $conn->real_escape_string($municipio) . "'";
}
if ($estado !== '') {
    $where_clauses[] = "c.estado = '" . $conn->real_escape_string($estado) . "'";
}
if ($id_plan !== '') {
    $where_clauses[] = "c.id_plan = " . intval($id_plan);
}
if ($tipo_conexion !== '') {
    $where_clauses[] = "c.tipo_conexion = '" . $conn->real_escape_string($tipo_conexion) . "'";
}
if ($id_olt !== '') {
    $where_clauses[] = "c.id_olt = " . intval($id_olt);
}

$where_sql = implode(" AND ", $where_clauses);

// Consulta principal
$query = "
    SELECT 
        c.*, 
        m.nombre_municipio, 
        p.nombre_parroquia, 
        pl.nombre_plan,
        ol.nombre_olt,
        po.nombre_pon
    FROM contratos c
    LEFT JOIN municipio m ON c.id_municipio = m.id_municipio
    LEFT JOIN parroquia p ON c.id_parroquia = p.id_parroquia
    LEFT JOIN planes pl ON c.id_plan = pl.id_plan
    LEFT JOIN olt ol ON c.id_olt = ol.id_olt
    LEFT JOIN pon po ON c.id_pon = po.id_pon
    WHERE $where_sql
    ORDER BY c.id DESC
";

$result = $conn->query($query);

if (!$result) {
    die("Error en la consulta: " . $conn->error);
}

// 3. Generación del HTML para Excel
?>
<table border="1" style="font-family: Arial, sans-serif; font-size: 12px; border-collapse: collapse;">
    <thead>
        <tr style="background-color: #007bff; color: #ffffff; font-weight: bold; text-align: center;">
            <th>ID</th>
            <th>Cédula</th>
            <th>Cliente</th>
            <th>Teléfono</th>
            <th>Correo</th>
            <th>Dirección Completa</th>
            <th>Fecha Instalación</th>
            <th>Plan</th>
            <th>Estado</th>
            <th>Tipo Conexión</th>
            <th>OLT</th>
            <th>PON</th>
            <th>Caja NAP</th>
            <th>IP ONU</th>
            <th>MAC ONU</th>
            <th>Instalador</th>
            <th>Observaciones</th>
        </tr>
    </thead>
    <tbody>
        <?php
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                // Formatear dirección
                $dir_completa = $row['nombre_municipio'] . " - " . $row['nombre_parroquia'] . " - " . $row['direccion'];

                // Color por estado
                $color_estado = '#000000';
                if ($row['estado'] === 'Activo')
                    $color_estado = '#28a745';
                else if ($row['estado'] === 'Suspendido')
                    $color_estado = '#dc3545';
                else if ($row['estado'] === 'Retirado')
                    $color_estado = '#6c757d';

                echo "<tr>";
                echo "<td>{$row['id']}</td>";
                echo "<td>{$row['cedula']}</td>";
                echo "<td>" . htmlspecialchars($row['nombre_completo'] ?? '') . "</td>";
                echo "<td>{$row['telefono']}</td>";
                echo "<td>" . htmlspecialchars($row['correo'] ?? '') . "</td>";
                echo "<td>" . htmlspecialchars($dir_completa) . "</td>";
                echo "<td>{$row['fecha_instalacion']}</td>";
                echo "<td>" . htmlspecialchars($row['nombre_plan'] ?? 'N/A') . "</td>";
                echo "<td style='color: {$color_estado}; font-weight: bold;'>{$row['estado']}</td>";
                echo "<td>{$row['tipo_conexion']}</td>";
                echo "<td>" . htmlspecialchars($row['nombre_olt'] ?? 'N/A') . "</td>";
                echo "<td>" . htmlspecialchars($row['nombre_pon'] ?? 'N/A') . "</td>";
                echo "<td>{$row['ident_caja_nap']}</td>";
                echo "<td>{$row['ip_onu']}</td>";
                echo "<td>{$row['mac_onu']}</td>";
                echo "<td>" . htmlspecialchars($row['instalador'] ?? '') . "</td>";
                echo "<td>" . htmlspecialchars($row['observaciones'] ?? '') . "</td>";
                echo "</tr>";
            }
        } else {
            echo "<tr><td colspan='17' style='text-align: center; color: red;'>No se encontraron registros con los filtros aplicados.</td></tr>";
        }
        ?>
    </tbody>
</table>
<?php $conn->close(); ?>