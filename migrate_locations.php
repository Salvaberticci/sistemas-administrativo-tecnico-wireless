<?php
require 'paginas/conexion.php';
// Add columns if they don't exist
$conn->query("ALTER TABLE contratos ADD COLUMN municipio_texto VARCHAR(200) DEFAULT NULL AFTER id_parroquia");
$conn->query("ALTER TABLE contratos ADD COLUMN parroquia_texto VARCHAR(200) DEFAULT NULL AFTER municipio_texto");

// Optional: Migrate existing data (joining with municipio/parroquia tables)
$sql_mig = "UPDATE contratos c 
            LEFT JOIN municipio m ON c.id_municipio = m.id_municipio 
            LEFT JOIN parroquia p ON c.id_parroquia = p.id_parroquia 
            SET c.municipio_texto = m.nombre_municipio, 
                c.parroquia_texto = p.nombre_parroquia";
$conn->query($sql_mig);

echo "Columns added and data migrated.";
?>