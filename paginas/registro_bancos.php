<?php
// Incluye el archivo de conexión
require_once 'conexion.php';

$message = '';
$message_class = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Captura los datos del formulario
    $nombre_banco = $_POST['nombre_banco'];
    $numero_cuenta = $_POST['numero_cuenta'];
    $cedula_propietario = $_POST['cedula_propietario'];
    $nombre_propietario = $_POST['nombre_propietario'];

    // Prepara la consulta SQL para insertar los datos
    $stmt = $conn->prepare("INSERT INTO bancos (nombre_banco, numero_cuenta, cedula_propietario, nombre_propietario) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $nombre_banco, $numero_cuenta, $cedula_propietario, $nombre_propietario);

    // Ejecuta la consulta
    if ($stmt->execute()) {
        $message = "¡Banco registrado con éxito!";
        $message_class = 'alert-success';
    } else {
        $message = "Error al registrar el banco: " . $stmt->error;
        $message_class = 'alert-danger';
    }

    $stmt->close();
    $conn->close();
}

$path_to_root = "../";
$page_title = "Registro de Bancos";
require_once 'includes/layout_head.php';
require_once 'includes/sidebar.php';
?>

<main class="main-content">
    <?php include 'includes/header.php'; ?>

    <div class="page-content">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center bg-white border-bottom-0 pt-4 px-4">
                <div>
                    <h5 class="fw-bold text-primary mb-1">Registro de Bancos</h5>
                    <p class="text-muted small mb-0">Wireless Supply, C.A.</p>
                </div>
            </div>

            <div class="card-body px-4">
                
                <?php if ($message): ?>
                    <div class="alert <?php echo $message_class; ?> alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <form action="registro_bancos.php" method="POST" class="row g-3">
                    <div class="col-md-6">
                        <label for="nombre_banco" class="form-label">Nombre del Banco</label>
                        <input type="text" class="form-control" id="nombre_banco" name="nombre_banco" required>
                    </div>
                    
                    <div class="col-md-6">
                        <label for="numero_cuenta" class="form-label">Número de Cuenta</label>
                        <input type="text" class="form-control" id="numero_cuenta" name="numero_cuenta" required>
                    </div>
                    
                    <div class="col-md-6">
                        <label for="cedula_propietario" class="form-label">Cédula del Propietario</label>
                        <input type="text" class="form-control" id="cedula_propietario" name="cedula_propietario" required>
                    </div>
                    
                    <div class="col-md-6">
                        <label for="nombre_propietario" class="form-label">Nombre del Propietario</label>
                        <input type="text" class="form-control" id="nombre_propietario" name="nombre_propietario" required>
                    </div>
                    
                    <div class="col-12 mt-4 text-end">
                        <a href="gestion_bancos.php" class="btn btn-secondary me-2">Volver</a>
                        <button type="submit" class="btn btn-primary">Registrar Banco</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</main>

<?php require_once 'includes/layout_foot.php'; ?>