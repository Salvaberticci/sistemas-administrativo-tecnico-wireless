<?php
require_once 'paginas/conexion.php';

echo "<h3>Iniciando Migración: Sistema de Reporte de Pagos</h3>";

// 1. Crear tabla de pagos reportados por clientes
$sql_pagos_reportados = "
CREATE TABLE IF NOT EXISTS pagos_reportados (
    id_reporte INT AUTO_INCREMENT PRIMARY KEY,
    cedula_titular VARCHAR(20) NOT NULL,
    nombre_titular VARCHAR(255) NOT NULL,
    telefono_titular VARCHAR(20) NOT NULL,
    fecha_pago DATE NOT NULL,
    metodo_pago VARCHAR(50) NOT NULL,
    id_banco_destino INT NULL,
    referencia VARCHAR(100) NULL,
    meses_pagados TEXT NOT NULL,
    concepto TEXT NULL,
    capture_path VARCHAR(255) NOT NULL,
    estado ENUM('PENDIENTE', 'APROBADO', 'RECHAZADO') DEFAULT 'PENDIENTE',
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    id_contrato_asociado INT NULL -- Opcional, si logramos vincularlo por la cédula
);";

if ($conn->query($sql_pagos_reportados)) {
    echo "✅ Tabla 'pagos_reportados' lista.<br>";
} else {
    echo "❌ Error al crear tabla 'pagos_reportados': " . $conn->error . "<br>";
}

// 2. Agregar columna 'origen' a cuentas_por_cobrar
$check_col = $conn->query("SHOW COLUMNS FROM cuentas_por_cobrar LIKE 'origen'");
if ($check_col->num_rows == 0) {
    $sql_alter = "ALTER TABLE cuentas_por_cobrar ADD COLUMN origen ENUM('SISTEMA', 'LINK') DEFAULT 'SISTEMA' AFTER estado";
    if ($conn->query($sql_alter)) {
        echo "✅ Columna 'origen' agregada a 'cuentas_por_cobrar'.<br>";
    } else {
        echo "❌ Error al agregar columna 'origen': " . $conn->error . "<br>";
    }
} else {
    echo "ℹ️ La columna 'origen' ya existe en 'cuentas_por_cobrar'.<br>";
}

$conn->close();
echo "<h3>Migración Finalizada</h3>";
?>