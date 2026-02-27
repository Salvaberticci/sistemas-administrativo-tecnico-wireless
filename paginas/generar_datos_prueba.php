<?php
require_once 'conexion.php';

// Obtener algunos IDs de contratos válidos
$sql_contratos = "SELECT id FROM contratos LIMIT 10";
$result_contratos = $conn->query($sql_contratos);
$contratos = [];
if ($result_contratos) {
    while ($row = $result_contratos->fetch_assoc()) {
        $contratos[] = $row['id'];
    }
}

if (empty($contratos)) {
    die("No hay contratos en la base de datos para asignar soportes.");
}

$tipos_falla = [
    'Sin Señal / LOS',
    'Internet Lento',
    'Cortes Intermitentes',
    'Router Dañado',
    'ONU Apagada/Dañada',
    'Antena Desalineada',
    'Cable Dañado',
    'Fibra Cortada'
];

$tecnicos = ['Juan Perez', 'Carlos Martinez', 'Luis Gomez', 'Pedro Sanchez'];
$prioridades = ['NIVEL 1', 'NIVEL 2', 'NIVEL 3'];
$zonas = ['Zona Centro', 'Zona Norte', 'Zona Sur', 'Sector Industrial', 'Residencial Los Sauces'];

echo "Generando 30 registros de prueba...\n";

for ($i = 1; $i <= 30; $i++) {
    $id_contrato = $contratos[array_rand($contratos)];

    // Distribuir fechas en los últimos 3 meses
    $dias_atras = rand(0, 90);
    $fecha_reporte = date('Y-m-d H:i:s', strtotime("-$dias_atras days"));

    // 80% resueltas, 20% pendientes
    $resuelta = (rand(1, 10) > 2);
    $horas_resolucion = rand(1, 48);
    $fecha_resolucion = $resuelta ? date('Y-m-d H:i:s', strtotime("$fecha_reporte + $horas_resolucion hours")) : 'NULL';

    // Atención suele ser rápida (1 a 4 horas después del reporte)
    $horas_atencion = rand(1, 4);
    $fecha_atencion = date('Y-m-d H:i:s', strtotime("$fecha_reporte + $horas_atencion hours"));

    $tipo = $tipos_falla[array_rand($tipos_falla)];
    $tecnico = $tecnicos[array_rand($tecnicos)];
    $prioridad = $prioridades[array_rand($prioridades)];

    // 15% de caídas críticas
    $es_critica = (rand(1, 100) <= 15) ? 1 : 0;

    // Clientes afectados (si es crítica > 1, si no 1)
    $clientes_afectados = $es_critica ? rand(5, 50) : 1;
    $zona = $zonas[array_rand($zonas)];

    $monto_total = rand(0, 50) + (rand(0, 99) / 100); // 0 a 50 dolares
    if ($monto_total < 10)
        $monto_total = 0; // Múltiples gratis
    $monto_pagado = (rand(0, 1) == 1) ? $monto_total : 0; // O pagado completo o 0

    $prioridad_sql = "'$prioridad'";
    $fecha_resol_sql = $resuelta ? "'$fecha_resolucion'" : "NULL";
    $solucion_completada = $resuelta ? 1 : 0;

    $sql = "INSERT INTO soportes (
                id_contrato, fecha_soporte, fecha_reporte, fecha_atencion, fecha_resolucion, 
                descripcion, tipo_falla, prioridad, es_caida_critica, clientes_afectados, 
                zona_afectada, tecnico_asignado, solucion_completada, monto_total, monto_pagado
            ) VALUES (
                $id_contrato, 
                DATE('$fecha_reporte'), 
                '$fecha_reporte', 
                '$fecha_atencion', 
                $fecha_resol_sql, 
                'Falla de prueba generada automáticamente', 
                '$tipo', 
                $prioridad_sql, 
                $es_critica, 
                $clientes_afectados, 
                '$zona', 
                '$tecnico', 
                $solucion_completada, 
                $monto_total, 
                $monto_pagado
            )";

    if ($conn->query($sql) !== TRUE) {
        echo "Error insertando registro $i: " . $conn->error . "\n";
    }
}

echo "Proceso completado.\n";
$conn->close();
?>