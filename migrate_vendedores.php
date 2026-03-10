<?php
require 'paginas/conexion.php';

echo "Iniciando migración de Vendedores...\n";

// 1. Exportar a JSON
$res = $conn->query("SELECT nombre_vendedor FROM vendedores ORDER BY nombre_vendedor ASC");
$sellers = [];
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $sellers[] = $row['nombre_vendedor'];
    }
}
$json_path = 'paginas/principal/data/vendedores.json';
if (!is_dir(dirname($json_path)))
    mkdir(dirname($json_path), 0755, true);
file_put_contents($json_path, json_encode($sellers, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo "1. Vendedores exportados a JSON.\n";

// 2. Mapear nombres en la tabla contratos
$sql = "UPDATE contratos c 
        JOIN vendedores v ON c.id_vendedor = v.id_vendedor 
        SET c.vendedor_texto = v.nombre_vendedor 
        WHERE c.id_vendedor IS NOT NULL AND c.id_vendedor > 0";
if ($conn->query($sql)) {
    echo "2. Contratos actualizados con vendedor_texto.\n";
} else {
    echo "Error actualizando contratos (quizás id_vendedor ya fue eliminado): " . $conn->error . "\n";
}

// 3. Eliminar Foreign Key
$conn->query("ALTER TABLE contratos DROP FOREIGN KEY fk_contrato_vendedor");
echo "3. Foreign Key 'fk_contrato_vendedor' eliminada (si existía).\n";

// 4. Eliminar columna id_vendedor
if ($conn->query("ALTER TABLE contratos DROP COLUMN id_vendedor")) {
    echo "4. Columna 'id_vendedor' eliminada.\n";
} else {
    echo "4. Columna 'id_vendedor' no se pudo eliminar o ya no existe: " . $conn->error . "\n";
}

echo "Migración completada.\n";
?>