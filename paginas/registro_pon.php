<?php
require_once 'conexion.php';

$message = '';
$message_class = '';
$nombre_pon_post = isset($_POST['nombre_pon']) ? $_POST['nombre_pon'] : '';
$id_olt_post = isset($_POST['id_olt']) ? $_POST['id_olt'] : '';
$descripcion_post = isset($_POST['descripcion']) ? $_POST['descripcion'] : '';

// Consulta para obtener las OLTs disponibles
$olts = [];
$sql_olts = "SELECT id_olt, nombre_olt FROM olt ORDER BY nombre_olt ASC";
$result_olts = $conn->query($sql_olts);

if ($result_olts && $result_olts->num_rows > 0) {
    while ($row = $result_olts->fetch_assoc()) {
        $olts[] = $row;
    }
}

// Procesamiento del formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (empty($nombre_pon_post) || empty($id_olt_post)) {
            throw new Exception("Los campos Nombre PON y OLT son obligatorios.");
        }
        
        $stmt_pon = $conn->prepare("INSERT INTO pon (nombre_pon, id_olt, descripcion) VALUES (?, ?, ?)");
        $stmt_pon->bind_param("sis", $nombre_pon_post, $id_olt_post, $descripcion_post); 
        
        if (!$stmt_pon->execute()) {
            throw new Exception("Error al insertar el PON: " . $stmt_pon->error);
        }
        
        $id_referencia = $conn->insert_id; 
        $stmt_pon->close();
        
        $message = '¡PON registrado con éxito! (ID Asignado: ' . $id_referencia . ')';
        $message_class = 'success';
        
        $nombre_pon_post = $id_olt_post = $descripcion_post = '';

    } catch (Exception $e) {
        $message = 'Error al registrar el PON: ' . $e->getMessage();
        $message_class = 'error';
    }
}

$path_to_root = "../";
$page_title = "Registro de PON";
require_once 'includes/layout_head.php';
require_once 'includes/sidebar.php';
?>

<main class="main-content">
    <?php include 'includes/header.php'; ?>

    <div class="page-content">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center bg-white border-bottom-0 pt-4 px-4">
                <div>
                    <h5 class="fw-bold text-primary mb-1">Registro de PON</h5>
                    <p class="text-muted small mb-0">Crear nuevo PON asociado a una OLT</p>
                </div>
            </div>

            <div class="card-body px-4">
                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $message_class === 'success' ? 'success' : 'danger'; ?> alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST" class="row g-3">
                    <div class="col-md-6">
                        <label for="nombre_pon" class="form-label">Nombre PON</label>
                        <input type="text" class="form-control" id="nombre_pon" name="nombre_pon" 
                               value="<?php echo htmlspecialchars($nombre_pon_post); ?>" required autofocus>
                    </div>

                    <div class="col-md-6">
                        <label for="id_olt" class="form-label">OLT a la que pertenece</label>
                        <select class="form-select" id="id_olt" name="id_olt" required>
                            <option value="">-- Seleccione una OLT --</option>
                            <?php foreach ($olts as $olt): ?>
                                <option value="<?php echo htmlspecialchars($olt['id_olt']); ?>" 
                                    <?php echo ($id_olt_post == $olt['id_olt']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($olt['nombre_olt']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-12">
                        <label for="descripcion" class="form-label">Descripción</label>
                        <textarea class="form-control" id="descripcion" name="descripcion" rows="4"><?php echo htmlspecialchars($descripcion_post); ?></textarea>
                    </div>
                    
                    <div class="col-12">
                        <a href="gestion_pon.php" class="btn btn-secondary">Volver</a>
                        <button type="submit" class="btn btn-success">Registrar PON</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</main>

<?php require_once 'includes/layout_foot.php'; ?>