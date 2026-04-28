<?php
require 'paginas/conexion.php';
$res = $conn->query("SELECT p.nombre_plan, COUNT(*) as count FROM contratos c JOIN planes p ON c.id_plan = p.id_plan WHERE c.sae_plus LIKE 'WR%' GROUP BY p.nombre_plan");
while ($row = $res->fetch_assoc()) {
    print_r($row);
}
