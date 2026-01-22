<?php
// Incluye el archivo de conexión.
require_once 'conexion.php';

$message = '';
$message_class = '';

// Verifica si el formulario fue enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_vendedor = $_POST['id_vendedor'];
    $nombre_vendedor = $_POST['nombre_vendedor'];
    $telefono_vendedor = $_POST['telefono_vendedor'];

    // Validar que el ID de vendedor no exista
    $stmt = $conn->prepare("SELECT `id_vendedor` FROM `vendedores` WHERE `id_vendedor` = ?");
    $stmt->bind_param("s", $id_vendedor);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        // El ID ya existe
        $message = "El ID de vendedor '{$id_vendedor}' ya está registrado. Por favor, elige otro.";
        $message_class = 'error';
    } else {
        // El ID no existe, se puede registrar
        $stmt = $conn->prepare("INSERT INTO `vendedores` (`id_vendedor`, `nombre_vendedor`, `telefono_vendedor`) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $id_vendedor, $nombre_vendedor, $telefono_vendedor);

        if ($stmt->execute()) {
            $message = "¡Vendedor '{$nombre_vendedor}' registrado con éxito!";
            $message_class = 'success';
        } else {
            $message = "Error al registrar el vendedor: " . $stmt->error;
            $message_class = 'error';
        }
    }
    $stmt->close();
}
$conn->close();

$path_to_root = "../";
$page_title = "Registro de Vendedores";
require_once 'includes/layout_head.php';
require_once 'includes/sidebar.php';
?>

<main class="main-content">
    <?php include 'includes/header.php'; ?>

    <div class="page-content">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center bg-white border-bottom-0 pt-4 px-4">
                <div>
                    <h5 class="fw-bold text-primary mb-1">Registro de Vendedor</h5>
                    <p class="text-muted small mb-0">Crear nuevo vendedor</p>
                </div>
            </div>

            <div class="card-body px-4">
                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $message_class === 'success' ? 'success' : 'danger'; ?> alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <form action="registro_vendedores.php" method="POST" class="row g-3">
                    <div class="col-md-6">
                        <label for="id_vendedor" class="form-label">ID de Vendedor</label>
                        <input type="text" class="form-control" id="id_vendedor" name="id_vendedor" required autofocus>
                    </div>
                    
                    <div class="col-md-6">
                        <label for="nombre_vendedor" class="form-label">Nombre del Vendedor</label>
                        <input type="text" class="form-control" id="nombre_vendedor" name="nombre_vendedor" required>
                    </div>
                    
                    <div class="col-md-6">
                        <label for="telefono_vendedor" class="form-label">Teléfono</label>
                        <input type="text" class="form-control" id="telefono_vendedor" name="telefono_vendedor">
                    </div>
                    
                    <div class="col-12">
                        <a href="gestion_vendedores.php" class="btn btn-secondary">Volver</a>
                        <button type="submit" class="btn btn-success">Registrar Vendedor</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</main>

<?php require_once 'includes/layout_foot.php'; ?>