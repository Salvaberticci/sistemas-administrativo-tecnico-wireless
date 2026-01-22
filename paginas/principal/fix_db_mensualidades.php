<?php
require_once '../conexion.php';

echo "<h3>Actualizando base de datos para Mensualidades</h3>";

// 1. Agregar columna id_banco a cuentas_por_cobrar
$sql_add_banco = "ALTER TABLE cuentas_por_cobrar ADD COLUMN id_banco INT NULL AFTER referencia_pago";

if ($conn->query($sql_add_banco)) {
    echo "✅ Columna 'id_banco' agregada a 'cuentas_por_cobrar'.<br>";
} else {
    echo "❌ Error al agregar columna or ya existe: " . $conn->error . "<br>";
}

echo "<br><a href='gestion_mensualidades.php'>Volver a Mensualidades</a>";
$conn->close();
?>
