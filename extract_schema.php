<?php
$lines = file('salvxkld_tecnico-administrativo-wirelessdb.sql');
$capture = false;
foreach($lines as $line) {
    if(strpos($line, 'CREATE TABLE `cuentas_por_cobrar`') !== false || strpos($line, 'CREATE TABLE `cobros_manuales_historial`') !== false) {
        $capture = true;
    }
    if($capture) {
        echo $line;
    }
    if($capture && strpos($line, 'ENGINE=') !== false) {
        $capture = false;
        echo "\n-------------------\n";
    }
}
?>
