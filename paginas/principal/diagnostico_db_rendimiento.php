<?php
require '../conexion.php';

echo "<pre>";
echo "<h2>Diagnóstico de Rendimiento MySQL</h2>";

// 1. Verificar Tablas y Filas Aproximadas
echo "<h3>1. Tamaño de Tablas</h3>";
$tables = ['cuentas_por_cobrar', 'contratos', 'cobros_manuales_historial'];
foreach ($tables as $t) {
    $res = $conn->query("SHOW TABLE STATUS LIKE '$t'");
    if ($res && $row = $res->fetch_assoc()) {
        echo "- Tabla <b>$t</b>: ~" . number_format($row['Rows']) . " filas.<br>";
    }
}

// 2. Comprobar Índices en cuentas_por_cobrar
echo "<h3>2. Índices en cuentas_por_cobrar</h3>";
$indexes = $conn->query("SHOW INDEX FROM cuentas_por_cobrar");
$myIndexes = [];
if ($indexes) {
    while ($row = $indexes->fetch_assoc()) {
        $myIndexes[] = $row['Column_name'];
        echo "- Índice en columna: <b>" . $row['Column_name'] . "</b> (Nombre: " . $row['Key_name'] . ")<br>";
    }
}

$expected_indexes = ['fecha_pago', 'fecha_emision', 'id_banco', 'estado', 'estado_sae_plus', 'referencia_pago', 'id_contrato'];
foreach ($expected_indexes as $idx) {
    if (!in_array($idx, $myIndexes)) {
        echo "<span style='color:red;'>⚠️ FATA: Índice en <b>$idx</b> NO existe. Esto causa lentitud extrema.</span><br>";
    }
}

// 3. Prueba de Consulta Simplificada (simulando la carga de pestaña SAE)
echo "<h3>3. Análisis de Consulta (EXPLAIN)</h3>";

$query = "
    EXPLAIN 
    SELECT cxc.id_cobro
    FROM cuentas_por_cobrar cxc
    INNER JOIN contratos co ON cxc.id_contrato = co.id
    LEFT JOIN planes pl ON co.id_plan = pl.id_plan
    LEFT JOIN cobros_manuales_historial h ON cxc.id_cobro = h.id_cobro_cxc
    WHERE cxc.estado_sae_plus = 'NO CARGADO'
";

$res = $conn->query($query);
if ($res) {
    echo "<table border='1' cellpadding='5' style='border-collapse:collapse; margin-top: 10px;'>";
    echo "<tr style='background:#f4f4f4;'><th>table</th><th>type</th><th>possible_keys</th><th>key</th><th>rows</th><th>Extra</th></tr>";
    while ($row = $res->fetch_assoc()) {
        $color = ($row['type'] == 'ALL') ? 'background:#ffcccc;' : '';
        echo "<tr style='$color'>";
        echo "<td>" . $row['table'] . "</td>";
        echo "<td>" . $row['type'] . "</td>";
        echo "<td>" . $row['possible_keys'] . "</td>";
        echo "<td>" . $row['key'] . "</td>";
        echo "<td>" . $row['rows'] . "</td>";
        echo "<td>" . $row['Extra'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    echo "<br><small>* Si ves 'ALL' en la columna 'type' y miles de filas, MySQL está haciendo escaneos completos (lentitud asegurada).</small>";
} else {
    echo "Error ejecutando EXPLAIN: " . $conn->error;
}

echo "<h3>4. Verificando si setup_enhanced_system fue ejecutado...</h3>";
$query = "SHOW COLUMNS FROM contratos LIKE 'monto_instalacion'";
$res = $conn->query($query);
if ($res && $res->num_rows > 0) {
    echo "<span style='color:green;'>✅ El setup_enhanced_system parece haber sido aplicado (las columnas extra existen).</span><br>";
} else {
    echo "<span style='color:red;'>❌ FATA: setup_enhanced_system NO ha sido aplicado completamente en este servidor. Por favor ejecútalo.</span><br>";
}

echo "</pre>";
?>
