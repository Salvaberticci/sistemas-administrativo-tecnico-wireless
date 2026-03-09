<?php
require 'paginas/conexion.php';

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
    WHERE 1=1
    ORDER BY c.id DESC LIMIT 1
";

$result = $conn->query($query);
if (!$result) {
    echo "SQL ERROR: " . $conn->error . "\n";
} else {
    echo "ROWS RETURNED: " . $result->num_rows . "\n";
    var_dump($result->fetch_assoc());
}
?>