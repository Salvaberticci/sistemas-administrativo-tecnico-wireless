<?php
require_once 'paginas/conexion.php';

$sql = "CREATE TABLE IF NOT EXISTS prorrogas (
    id_prorroga INT AUTO_INCREMENT PRIMARY KEY,
    tipo_solicitud ENUM('PRORROGA', 'VENTA') NOT NULL,
    
    -- Datos Comunes
    cedula_titular VARCHAR(20) NOT NULL,
    nombre_titular VARCHAR(100) NOT NULL,
    fecha_corte DATE,
    
    -- Datos Específicos Prórroga (Interno)
    existe_saeplus ENUM('SI', 'NO') DEFAULT 'NO',
    prorroga_regular ENUM('SI', 'NO') DEFAULT 'SI',
    
    -- Datos Específicos Ventas
    telefono VARCHAR(20),
    telefono_extra VARCHAR(20),
    email VARCHAR(100),
    id_municipio INT,
    id_parroquia INT,
    direccion TEXT,
    id_plan INT,
    fecha_firma DATE,
    path_contrato VARCHAR(255),
    prorateo VARCHAR(50),
    metodo_pago VARCHAR(50),
    fecha_instalacion DATE,
    estado_venta VARCHAR(50),
    
    -- Control
    estado ENUM('PENDIENTE', 'PROCESADO', 'RECHAZADO') DEFAULT 'PENDIENTE',
    id_contrato_asociado INT NULL,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    observaciones TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

if ($conn->query($sql)) {
    echo "Tabla 'prorrogas' creada o verificada con éxito.\n";
} else {
    echo "Error al crear tabla: " . $conn->error . "\n";
}

$conn->close();
?>