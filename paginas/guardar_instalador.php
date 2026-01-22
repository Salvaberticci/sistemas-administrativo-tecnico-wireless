<?php
/**
 * Guardar nuevo instalador
 */
require 'conexion.php';

$nombre = $conn->real_escape_string($_POST['nombre_instalador']);
$telefono = $conn->real_escape_string($_POST['telefono']);
$activo = intval($_POST['activo']);

$sql = "INSERT INTO instaladores (nombre_instalador, telefono, activo) VALUES (?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ssi", $nombre, $telefono, $activo);

$resultado = $stmt->execute();
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Guardar Instalador</title>
    <link href="../css/bootstrap.min.css" rel="stylesheet">
    <link href="../css/style4.css" rel="stylesheet">
</head>
<body>
    <main class="container">
        <?php if ($resultado) { ?>
            <h3 class="text-center text-success">✅ INSTALADOR GUARDADO</h3>
            <p class="text-center">El instalador ha sido registrado exitosamente.</p>
            <div class="col-12 text-center">
                <a href="gestion_instaladores.php" class="btn btn-primary">Volver a la lista</a>
            </div>
        <?php } else { ?>
            <h3 class="text-center text-danger">❌ ERROR AL GUARDAR</h3>
            <p class="text-center">Hubo un problema al registrar el instalador.</p>
            <div class="col-12 text-center">
                <a href="registro_instaladores.php" class="btn btn-danger">Regresar</a>
            </div>
        <?php } ?>
    </main>
</body>
</html>
