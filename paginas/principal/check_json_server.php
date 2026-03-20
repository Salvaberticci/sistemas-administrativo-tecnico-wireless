<?php
$jsonFile = 'data/tipos_instalacion.json';
echo "Filepath: " . realpath($jsonFile) . "\n";
if (file_exists($jsonFile)) {
    echo "Content:\n";
    echo file_get_contents($jsonFile);
} else {
    echo "File not found at $jsonFile";
}
echo "\n\n---\n\n";
$jsonSoporte = '../soporte/data/opciones_soporte.json';
if (file_exists($jsonSoporte)) {
    echo "Soporte Content:\n";
    echo file_get_contents($jsonSoporte);
}
?>
