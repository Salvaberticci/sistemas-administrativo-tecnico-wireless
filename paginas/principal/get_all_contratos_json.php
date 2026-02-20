<?php
require_once '../conexion.php';

header('Content-Type: application/json');

// Obtener todos los contratos con sus relaciones
$sql = "SELECT 
            c.id AS id_contrato,
            c.fecha_registro,
            c.cedula AS cedula_cliente,
            c.nombre_completo AS nombre_cliente,
            m.nombre_municipio,
            p.nombre_parroquia,
            c.direccion AS direccion_instalacion,
            c.telefono AS telefono_cliente,
            c.telefono_secundario AS telefono_extra,
            c.correo AS email_cliente,
            c.correo_adicional AS email_extra,
            c.fecha_instalacion,
            c.medio_pago,
            c.monto_instalacion AS costo_instalacion,
            c.monto_pagado,
            c.dias_prorrateo,
            c.monto_prorrateo_usd AS monto_prorrateo,
            c.observaciones,
            c.tipo_conexion,
            c.numero_onu,
            c.mac_onu AS mac_serial,
            c.ip_onu,
            c.ident_caja_nap AS caja_nap,
            c.puerto_nap,
            c.nap_tx_power AS potencia_nap_tx,
            c.onu_rx_power AS potencia_onu_rx,
            c.distancia_drop,
            c.instalador AS id_instalador, 
            c.evidencia_fibra AS evidencia_foto_fibra,
            c.ip AS ip_servicio,
            c.punto_acceso,
            c.valor_conexion_dbm AS valor_conexion,
            c.num_presinto_odn AS precinto_odn,
            c.vendedor_texto AS id_vendedor, 
            c.sae_plus AS codigo_sae_plus,
            pl.nombre_plan,
            o.nombre_olt,
            pn.nombre_pon,
            c.estado AS status
        FROM contratos c
        LEFT JOIN municipio m ON c.id_municipio = m.id_municipio
        LEFT JOIN parroquia p ON c.id_parroquia = p.id_parroquia
        LEFT JOIN planes pl ON c.id_plan = pl.id_plan
        LEFT JOIN olt o ON c.id_olt = o.id_olt
        LEFT JOIN pon pn ON c.id_pon = pn.id_pon
        ORDER BY c.id DESC";

$result = $conn->query($sql);

if (!$result) {
    http_response_code(500);
    echo json_encode(['error' => $conn->error]);
    exit;
}

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

echo json_encode($data);
?>