<?php
require_once 'conexion.php';
$res = $conn->query("DESCRIBE mensualidades");
if (!$res) {
    echo json_encode(["error" => $conn->error]);
} else {
    $cols = [];
    while ($row = $res->fetch_assoc()) {
        $cols[] = $row;
    }
    echo json_encode($cols);
}
?>