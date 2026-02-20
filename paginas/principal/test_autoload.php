<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../../vendor/autoload.php';
require_once '../conexion.php';

if (class_exists('PhpOffice\PhpSpreadsheet\IOFactory')) {
    echo "OK: SpreadSheet Loaded\n";
} else {
    echo "FAIL: SpreadSheet NOT Loaded\n";
}

if ($conn) {
    echo "OK: DB Connected\n";
} else {
    echo "FAIL: DB Not Connected\n";
}
?>