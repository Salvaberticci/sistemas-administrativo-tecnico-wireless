<?php
require_once 'conexion.php';

$message_municipio = '';
$message_parroquia = '';
$message_comunidad = '';
$message_class = '';

// Lógica de registro de municipio
if (isset($_POST['submit_municipio'])) {
    $nombre_municipio = $_POST['nombre_municipio'];

    $stmt = $conn->prepare("INSERT INTO `municipio` (`nombre_municipio`) VALUES (?)");
    $stmt->bind_param("s", $nombre_municipio);

    if ($stmt->execute()) {
        $message_municipio = "¡Municipio registrado con éxito!";
        $message_class = 'success';
    } else {
        $message_municipio = "Error al registrar el municipio: " . $stmt->error;
        $message_class = 'error';
    }
    $stmt->close();
}

// Lógica de registro de parroquia
if (isset($_POST['submit_parroquia'])) {
    $nombre_parroquia = $_POST['nombre_parroquia'];
    $id_municipio = $_POST['id_municipio'];

    $stmt = $conn->prepare("INSERT INTO `parroquia` (`nombre_parroquia`, `id_municipio`) VALUES (?, ?)");
    $stmt->bind_param("si", $nombre_parroquia, $id_municipio);

    if ($stmt->execute()) {
        $message_parroquia = "¡Parroquia registrada con éxito!";
        $message_class = 'success';
    } else {
        $message_parroquia = "Error al registrar la parroquia: " . $stmt->error;
        $message_class = 'error';
    }
    $stmt->close();
}

// Lógica de registro de comunidad
if (isset($_POST['submit_comunidad'])) {
    $nombre_comunidad = $_POST['nombre_comunidad'];
    $id_parroquia = $_POST['id_parroquia_comunidad'];

    $stmt = $conn->prepare("INSERT INTO `comunidad` (`nombre_comunidad`, `id_parroquia`) VALUES (?, ?)");
    $stmt->bind_param("si", $nombre_comunidad, $id_parroquia);

    if ($stmt->execute()) {
        $message_comunidad = "¡Comunidad registrada con éxito!";
        $message_class = 'success';
    } else {
        $message_comunidad = "Error al registrar la comunidad: " . $stmt->error;
        $message_class = 'error';
    }
    $stmt->close();
}

// Consultas para cargar selects
$municipios = $conn->query("SELECT id_municipio, nombre_municipio FROM `municipio` ORDER BY nombre_municipio ASC")->fetch_all(MYSQLI_ASSOC);
$parroquias = $conn->query("SELECT id_parroquia, nombre_parroquia FROM `parroquia` ORDER BY nombre_parroquia ASC")->fetch_all(MYSQLI_ASSOC);

$conn->close();

$path_to_root = "../";
$page_title = "Registro Geográfico";
require_once 'includes/layout_head.php';
require_once 'includes/sidebar.php';
?>

<main class="main-content">
    <?php include 'includes/header.php'; ?>

    <div class="page-content">
        <div class="card mb-4">
            <div class="card-header bg-white border-bottom-0 pt-4 px-4">
                <h5 class="fw-bold text-primary mb-1">Registro Geográfico</h5>
                <p class="text-muted small mb-0">Municipios, Parroquias y Comunidades</p>
            </div>
        </div>

        <div class="row g-4">
            <!-- Formulario Municipio -->
            <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-header bg-primary text-white">
                        <h6 class="mb-0">Registrar Municipio</h6>
                    </div>
                    <div class="card-body">
                        <?php if ($message_municipio): ?>
                            <div class="alert alert-<?php echo $message_class === 'success' ? 'success' : 'danger'; ?> alert-dismissible fade show" role="alert">
                                <?php echo htmlspecialchars($message_municipio); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>
                        
                        <form action="registro_municipios.php" method="POST">
                            <div class="mb-3">
                                <label for="nombre_municipio" class="form-label">Nombre del Municipio</label>
                                <input type="text" class="form-control" id="nombre_municipio" name="nombre_municipio" required autofocus>
                            </div>
                            <button type="submit" name="submit_municipio" class="btn btn-primary w-100">Registrar Municipio</button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Formulario Parroquia -->
            <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-header bg-info text-white">
                        <h6 class="mb-0">Registrar Parroquia</h6>
                    </div>
                    <div class="card-body">
                        <?php if ($message_parroquia): ?>
                            <div class="alert alert-<?php echo $message_class === 'success' ? 'success' : 'danger'; ?> alert-dismissible fade show" role="alert">
                                <?php echo htmlspecialchars($message_parroquia); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>
                        
                        <form action="registro_municipios.php" method="POST">
                            <div class="mb-3">
                                <label for="id_municipio" class="form-label">Seleccionar Municipio</label>
                                <select class="form-select" id="id_municipio" name="id_municipio" required>
                                    <option value="">Seleccione un municipio</option>
                                    <?php foreach ($municipios as $municipio): ?>
                                        <option value="<?php echo htmlspecialchars($municipio['id_municipio']); ?>">
                                            <?php echo htmlspecialchars($municipio['nombre_municipio']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="nombre_parroquia" class="form-label">Nombre de la Parroquia</label>
                                <input type="text" class="form-control" id="nombre_parroquia" name="nombre_parroquia" required>
                            </div>
                            <button type="submit" name="submit_parroquia" class="btn btn-info w-100">Registrar Parroquia</button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Formulario Comunidad -->
            <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-header bg-success text-white">
                        <h6 class="mb-0">Registrar Comunidad</h6>
                    </div>
                    <div class="card-body">
                        <?php if ($message_comunidad): ?>
                            <div class="alert alert-<?php echo $message_class === 'success' ? 'success' : 'danger'; ?> alert-dismissible fade show" role="alert">
                                <?php echo htmlspecialchars($message_comunidad); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>
                        
                        <form action="registro_municipios.php" method="POST">
                            <div class="mb-3">
                                <label for="id_parroquia_comunidad" class="form-label">Seleccionar Parroquia</label>
                                <select class="form-select" id="id_parroquia_comunidad" name="id_parroquia_comunidad" required>
                                    <option value="">Seleccione una parroquia</option>
                                    <?php foreach ($parroquias as $parroquia): ?>
                                        <option value="<?php echo htmlspecialchars($parroquia['id_parroquia']); ?>">
                                            <?php echo htmlspecialchars($parroquia['nombre_parroquia']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="nombre_comunidad" class="form-label">Nombre de la Comunidad</label>
                                <input type="text" class="form-control" id="nombre_comunidad" name="nombre_comunidad" required>
                            </div>
                            <button type="submit" name="submit_comunidad" class="btn btn-success w-100">Registrar Comunidad</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="text-center mt-4">
            <a href="gestion_municipios.php" class="btn btn-outline-secondary">
                <i class="fa-solid fa-arrow-left me-2"></i> Volver a Gestión
            </a>
        </div>
    </div>
</main>

<?php require_once 'includes/layout_foot.php'; ?>