<?php
// generar_sql_prueba_v2.php
$num_registros = 30;

echo "SET NAMES utf8mb4;\n";
echo "USE `tecnico-administrativo-wirelessdb`;\n\n";

$tipos_falla = [
    'Sin Señal / LOS',
    'Internet Lento',
    'Cortes Intermitentes',
    'Router Dañado',
    'ONU Apagada/Dañada',
    'Antena Desalineada',
    'Cable Dañado',
    'Fibra Cortada',
    'Problema Eléctrico',
    'Configuración Incorrecta'
];

$tecnicos = [
    'Juan Pérez',
    'Carlos Gómez',
    'Luis Rodríguez',
    'Ana Martínez',
    'Miguel Ángel'
];

$prioridades = ['NIVEL 1', 'NIVEL 2', 'NIVEL 3'];
$zonas = ['Zona Centro', 'Zona Norte', 'Zona Sur', 'Sector Industrial', 'Residencial Los Sauces'];

echo "INSERT INTO soportes (id_contrato, fecha_soporte, fecha_reporte, fecha_atencion, fecha_resolucion, descripcion, tipo_falla, prioridad, es_caida_critica, clientes_afectados, zona_afectada, tecnico_asignado, solucion_completada, monto_total, monto_pagado) VALUES\n";

$values = [];
for ($i = 0; $i < $num_registros; $i++) {
    $id_contrato = rand(1, 50);
    $tipo = $tipos_falla[array_rand($tipos_falla)];
    $tecnico = $tecnicos[array_rand($tecnicos)];
    $prioridad = $prioridades[array_rand($prioridades)];
    $zona = $zonas[array_rand($zonas)];

    // Fechas
    $dias_atras = rand(0, 90);
    $fecha_reporte = date('Y-m-d H:i:s', strtotime("-$dias_atras days"));
    $fecha_soporte = date('Y-m-d', strtotime("-$dias_atras days")); // Solo fecha

    $horas_atencion = rand(1, 4);
    $fecha_atencion = date('Y-m-d H:i:s', strtotime("$fecha_reporte + $horas_atencion hours"));

    $resuelta = (rand(1, 10) > 2);
    $horas_resolucion = rand(5, 48);
    $fecha_resolucion = $resuelta ? date('Y-m-d H:i:s', strtotime("$fecha_reporte + $horas_resolucion hours")) : "NULL";
    $solucion_completada = $resuelta ? 1 : 0;

    $es_critica = (rand(1, 100) <= 15) ? 1 : 0;
    $clientes_afectados = $es_critica ? rand(5, 50) : 1;

    $monto_total = rand(10, 100);
    $monto_pagado = (rand(1, 100) <= 70) ? $monto_total : rand(0, $monto_total - 1);

    $descripcion = "Cliente reporta falla de prueba: " . $tipo;
    $tipo_escapado = addslashes($tipo);
    $tecnico_escapado = addslashes($tecnico);
    $descripcion_escapado = addslashes($descripcion);

    $fecha_resol_sql = $resuelta ? "'$fecha_resolucion'" : "NULL";

    $values[] = "($id_contrato, '$fecha_soporte', '$fecha_reporte', '$fecha_atencion', $fecha_resol_sql, '$descripcion_escapado', '$tipo_escapado', '$prioridad', $es_critica, $clientes_afectados, '$zona', '$tecnico_escapado', $solucion_completada, $monto_total, $monto_pagado)";
}

echo implode(",\n", $values) . ";\n";
?>