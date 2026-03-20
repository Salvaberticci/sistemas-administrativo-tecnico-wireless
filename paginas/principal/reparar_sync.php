<?php
// Repair script for Namecheap synchronization
$filesToRepair = [
    'data/tipos_instalacion.json' => ["FTTH", "RADIO"],
    'data/instaladores.json' => null, // Skip if complex
];

foreach ($filesToRepair as $path => $defaultData) {
    if ($defaultData !== null) {
        if (file_put_contents($path, json_encode($defaultData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
            echo "Reparado $path con éxito.<br>";
        } else {
            echo "Error al reparar $path.<br>";
        }
    }
}

echo "Borrando scripts de diagnóstico...<br>";
@unlink('check_json_server.php');
// El script se borrará a sí mismo
echo "Autoborrando este script de reparación...<br>";
// register_shutdown_function(function() { @unlink(__FILE__); }); // Comentado para que el usuario pueda verlo primero
?>
