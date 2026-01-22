<?php
/**
 * Formulario para registrar nuevo instalador
 */
require_once 'conexion.php';

$message = '';
$message_class = '';

// Verifica si se ha enviado el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre_instalador = $_POST['nombre_instalador'];
    $telefono = $_POST['telefono'];
    $activo = intval($_POST['activo']);

    $stmt = $conn->prepare("INSERT INTO instaladores (nombre_instalador, telefono, activo) VALUES (?, ?, ?)");
    $stmt->bind_param("ssi", $nombre_instalador, $telefono, $activo);

    if ($stmt->execute()) {
        $message = "¡Instalador registrado con éxito!";
        $message_class = 'success';
    } else {
        $message = "Error al registrar el instalador: " . $stmt->error;
        $message_class = 'error';
    }

    $stmt->close();
    $conn->close();
}

$path_to_root = "../";
$page_title = "Registro de Instaladores";
require_once 'includes/layout_head.php';
require_once 'includes/sidebar.php';
?>

<main class="main-content">
    <?php include 'includes/header.php'; ?>

    <div class="page-content">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center bg-white border-bottom-0 pt-4 px-4">
                <div>
                    <h5 class="fw-bold text-primary mb-1">Nuevo Instalador</h5>
                    <p class="text-muted small mb-0">Registrar técnico instalador</p>
                </div>
            </div>

            <div class="card-body px-4">
                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $message_class === 'success' ? 'success' : 'danger'; ?> alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <form action="registro_instaladores.php" method="POST" class="row g-3">
                    <div class="col-md-6">
                        <label for="nombre_instalador" class="form-label">Nombre Completo</label>
                        <input type="text" class="form-control" id="nombre_instalador" name="nombre_instalador" required autofocus>
                    </div>

                    <div class="col-md-6">
                        <label for="telefono" class="form-label">Teléfono</label>
                        <input type="text" class="form-control" id="telefono" name="telefono">
                    </div>

                    <div class="col-md-6">
                        <label for="activo" class="form-label">Estado</label>
                        <select class="form-select" id="activo" name="activo" required>
                            <option value="1" selected>Activo</option>
                            <option value="0">Inactivo</option>
                        </select>
                    </div>

                    <div class="col-12">
                        <a href="gestion_instaladores.php" class="btn btn-secondary">Regresar</a>
                        <button type="submit" class="btn btn-success">Guardar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</main>

<?php require_once 'includes/layout_foot.php'; ?>
