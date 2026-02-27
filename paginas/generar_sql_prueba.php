<?php
// generar_sql_prueba.php
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

echo "INSERT INTO soportes (id_contrato, fecha_soporte, tipo_falla, tecnico_asignado, prioridad, problema, solucion, monto_total, monto_pagado, fecha_visita) VALUES\n";

$values = [];
for ($i = 0; $i < $num_registros; $i++) {
    // Generar datos aleatorios
    $id_contrato = rand(1, 50); // Asumiendo que hay contratos entre el id 1 y 50
    $tipo = $tipos_falla[array_rand($tipos_falla)];
    $tecnico = $tecnicos[array_rand($tecnicos)];
    $prioridad = $prioridades[array_rand($prioridades)];

    // Fecha aleatoria en los últimos 3 meses
    $timestamp_ini = strtotime("-3 months");
    $timestamp_fin = time();
    $fecha_timestamp = rand($timestamp_ini, $timestamp_fin);
    $fecha = date("Y-m-d", $fecha_timestamp);
    $fecha_visita = date("Y-m-d", $fecha_timestamp + rand(0, 86400 * 2)); // Visita 0-2 días después

    // Montos
    $monto_total = rand(10, 100);
    // Probabilidad de que esté pagado: 70%
    $monto_pagado = (rand(1, 100) <= 70) ? $monto_total : rand(0, $monto_total - 1);

    // Problema y solución
    $problema = "Cliente reporta: " . $tipo;
    $solucion = "Se realizó la corrección de: " . $tipo;

    // Escapar para SQL (manualmente para el string básico generado)
    $tipo_escapado = addslashes($tipo);
    $tecnico_escapado = addslashes($tecnico);
    $problema_escapado = addslashes($problema);
    $solucion_escapado = addslashes($solucion);

    $values[] = "($id_contrato, '$fecha', '$tipo_escapado', '$tecnico_escapado', '$prioridad', '$problema_escapado', '$solucion_escapado', $monto_total, $monto_pagado, '$fecha_visita')";
}

echo implode(",\n", $values) . ";\n";
?>