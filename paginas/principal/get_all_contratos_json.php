<?php
require_once '../conexion.php';

header('Content-Type: application/json');

// Obtener todos los contratos con sus relaciones
$sql = "SELECT 
            c.id_contrato,
            c.fecha_registro,
            c.cedula_cliente,
            c.nombre_cliente,
            m.nombre_municipio,
            p.nombre_parroquia,
            c.direccion_instalacion,
            c.telefono_cliente,
            c.telefono_extra,
            c.email_cliente,
            c.email_extra,
            c.fecha_instalacion,
            c.metodo_pago,
            c.costo_instalacion,
            c.monto_pagado,
            c.dias_prorrateo,
            c.monto_prorrateo,
            c.observaciones,
            c.tipo_conexion,
            c.numero_onu,
            c.mac_serial,
            c.ip_onu,
            c.caja_nap,
            c.puerto_nap,
            c.potencia_nap_tx,
            c.potencia_onu_rx,
            c.distancia_drop,
            c.id_instalador, -- OJO: Instalador es TEXTO en algunas versiones o ID en otras, verificar
            c.evidencia_foto_fibra,
            c.ip_servicio,
            c.punto_acceso,
            c.valor_conexion,
            c.precinto_odn,
            c.id_vendedor, -- Igual, verificar si es ID o Texto
            c.codigo_sae_plus,
            pl.nombre_plan,
            o.nombre_olt,
            pn.nombre_pon,
            c.status
        FROM contratos c
        LEFT JOIN municipio m ON c.id_municipio = m.id_municipio
        LEFT JOIN parroquia p ON c.id_parroquia = p.id_parroquia
        LEFT JOIN planes pl ON c.id_plan = pl.id_plan
        LEFT JOIN olts o ON c.id_olt = o.id_olt
        LEFT JOIN pons pn ON c.id_pon = pn.id_pon -- Asumiendo tabla pons
        ORDER BY c.id_contrato DESC";

$result = $conn->query($sql);

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

echo json_encode($data);
?>