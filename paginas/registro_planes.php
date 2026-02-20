<?php
// Incluye el archivo de conexión.
require_once 'conexion.php';

$message = '';
$message_class = '';

// Verifica si se ha enviado el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Captura los datos del formulario
    $nombre_plan = $_POST['nombre_plan'];
    $monto = $_POST['monto'];
    $descripcion = $_POST['descripcion'];

    // Prepara la consulta SQL para insertar los datos
    $stmt = $conn->prepare("INSERT INTO planes (nombre_plan, monto, descripcion) VALUES (?, ?, ?)");
    $stmt->bind_param("sds", $nombre_plan, $monto, $descripcion);

    // Ejecuta la consulta
    if ($stmt->execute()) {
        $message = "¡Plan registrado con éxito!";
        $message_class = 'success';
    } else {
        $message = "Error al registrar el plan: " . $stmt->error;
        $message_class = 'error';
    }

    // Cierra la declaración y la conexión
    $stmt->close();
    $conn->close();
}

$path_to_root = "../";
$page_title = "Registro de Planes";
$breadcrumb = ["Admin", "Gestión de Planes"];
$back_url = "gestion_planes.php";
require_once 'includes/layout_head.php';
require_once 'includes/sidebar.php';
?>

<main class="main-content">
    <?php include 'includes/header.php'; ?>

    <div class="page-content">
        <div class="card">
            <div
                class="card-header d-flex justify-content-between align-items-center bg-white border-bottom-0 pt-4 px-4">
                <div>
                    <h5 class="fw-bold text-primary mb-1">Registro de Plan</h5>
                    <p class="text-muted small mb-0">Crear nuevo plan de servicio</p>
                </div>
            </div>

            <div class="card-body px-4">
                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $message_class === 'success' ? 'success' : 'danger'; ?> alert-dismissible fade show"
                        role="alert">
                        <?php echo htmlspecialchars($message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <form action="registro_planes.php" method="POST" class="row g-3">
                    <div class="col-md-6">
                        <label for="nombre_plan" class="form-label">Nombre del Plan</label>
                        <input type="text" class="form-control" id="nombre_plan" name="nombre_plan" required autofocus>
                    </div>

                    <div class="col-md-6">
                        <label for="monto" class="form-label">Monto (USD)</label>
                        <input type="number" class="form-control" id="monto" name="monto" step="0.01" required>
                    </div>

                    <div class="col-12">
                        <label for="descripcion" class="form-label">Descripción</label>
                        <textarea class="form-control" id="descripcion" name="descripcion" rows="4"></textarea>
                    </div>

                    <div class="col-12">
                        <a href="gestion_planes.php" class="btn btn-secondary">Volver</a>
                        <button type="submit" class="btn btn-success">Registrar Plan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</main>

<?php require_once 'includes/layout_foot.php'; ?>