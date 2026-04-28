<?php
$c = new mysqli('localhost', 'root', '', 'wirelessdb');
if ($c->connect_error) {
    echo "Connection to wirelessdb failed\n";
} else {
    echo "--- Banks in wirelessdb ---\n";
    $res = $c->query("SELECT * FROM bancos");
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            echo "ID: {$row['id_banco']} | Name: {$row['nombre_banco']}\n";
        }
    } else {
        echo "No bancos table in wirelessdb\n";
    }
}
