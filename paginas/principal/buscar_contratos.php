<?php
// Script para buscar contratos por ID o nombre del cliente (usado por AJAX)

header('Content-Type: application/json');

// Incluye su archivo de conexión
require_once '../conexion.php';

$resultados = [];
$search_query = isset($_GET['q']) ? $conn->real_escape_string($_GET['q']) : '';

if (strlen($search_query) >= 3) {
    // Buscamos contratos activos con su respectivo plan
    $sql = "SELECT c.id, c.nombre_completo, c.cedula, c.telefono, c.direccion, c.ip_onu as ip, c.tipo_conexion as tipo_servicio, 
                   p.nombre_plan, p.monto as monto_plan,
                   (SELECT COUNT(*) FROM contratos WHERE cedula = c.cedula) AS total_contratos,
                   (SELECT COUNT(*) FROM contratos WHERE cedula = c.cedula AND id <= c.id) AS nro_orden,
                   (SELECT h.justificacion FROM cuentas_por_cobrar cxc LEFT JOIN cobros_manuales_historial h ON cxc.id_cobro = h.id_cobro_cxc WHERE cxc.id_contrato = c.id AND cxc.estado = 'PAGADO' AND h.justificacion REGEXP 'Enero|Febrero|Marzo|Abril|Mayo|Junio|Julio|Agosto|Septiembre|Octubre|Noviembre|Diciembre' ORDER BY cxc.fecha_pago DESC, cxc.id_cobro DESC LIMIT 1) as ultimo_justif,
                   (SELECT SUM(saldo_pendiente) FROM clientes_deudores WHERE id_contrato = c.id AND tipo_registro = 'DEUDA' AND estado = 'PENDIENTE') as saldo_deuda,
                   (SELECT SUM(saldo_pendiente) FROM clientes_deudores WHERE id_contrato = c.id AND tipo_registro = 'CREDITO' AND estado = 'PENDIENTE') as saldo_favor
            FROM contratos c
            LEFT JOIN planes p ON c.id_plan = p.id_plan
            WHERE c.nombre_completo LIKE '%" . $search_query . "%' 
               OR c.id LIKE '%" . $search_query . "%'
               OR c.cedula LIKE '%" . $search_query . "%'
            LIMIT 10"; // Limitar a 10 resultados para no sobrecargar

    $resultado = $conn->query($sql);

    if ($resultado && $resultado->num_rows > 0) {
        while ($fila = $resultado->fetch_assoc()) {
            $resultados[] = $fila;
        }
    }
}

echo json_encode($resultados);

$conn->close();
?>