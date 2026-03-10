<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Mock the environment
$hostname = "localhost";
$username = "root";
$password = "";
$database = "tecnico-administrativo-wirelessdb";
$conn = mysqli_connect($hostname, $username, $password, $database);

$file = 'paginas/principal/data/planes_prorrateo.json';
if (file_exists($file)) {
    echo file_get_contents($file);
} else {
    echo "File not found: $file";
}
?>