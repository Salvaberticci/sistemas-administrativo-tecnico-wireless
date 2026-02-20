<?php
// Incluye el archivo de conexión.
require_once 'conexion.php';

$path_to_root = "../";
$page_title = "Gestión de Planes";
$breadcrumb = ["Admin"];
$back_url = "menu.php";
include $path_to_root . 'paginas/includes/layout_head.php';
include $path_to_root . 'paginas/includes/sidebar.php';
include $path_to_root . 'paginas/includes/header.php';

$message = '';
$message_class = '';
$action = isset($_GET['action']) ? $_GET['action'] : '';
$stmt = null;

// Variables para la búsqueda
$search_term = isset($_GET['search']) ? $_GET['search'] : '';
$sql = "SELECT * FROM planes";

// --- LÓGICA DE GESTIÓN (ELIMINAR) ---
if ($action === 'delete_plan' && isset($_GET['id'])) {
    $id_to_delete = $_GET['id'];
    $stmt = $conn->prepare("DELETE FROM planes WHERE id_plan = ?");
    $stmt->bind_param("i", $id_to_delete);
    if ($stmt->execute()) {
        $message = "Plan eliminado con éxito.";
        $message_class = 'success';
    } else {
        $message = "Error al eliminar el plan: " . $stmt->error;
        $message_class = 'error';
    }
    // Redirigimos para limpiar la URL
    echo "<script>window.location.href = 'gestion_planes.php?message=" . urlencode($message) . "&class=" . urlencode($message_class) . "';</script>";
    exit();
}

// --- LÓGICA DE MODIFICACIÓN (PROCESAR FORMULARIO POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_plan'])) {
    $id = $_POST['id_plan_update'];
    $nombre = $_POST['nombre_plan'];
    $monto = $_POST['monto'];
    $descripcion = $_POST['descripcion'];

    $monto_float = str_replace(',', '.', $monto);

    $stmt = $conn->prepare("UPDATE planes SET nombre_plan = ?, monto = ?, descripcion = ? WHERE id_plan = ?");
    $stmt->bind_param("sssi", $nombre, $monto_float, $descripcion, $id);

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            $message = "¡Plan actualizado con éxito!";
            $message_class = 'success';
        } else {
            $message = "ADVERTENCIA: No se realizaron cambios en el Plan.";
            $message_class = 'warning';
        }
    } else {
        $message = "Error al actualizar el plan: " . $stmt->error;
        $message_class = 'error';
    }

    if ($stmt) {
        $stmt->close();
    }
    echo "<script>window.location.href = 'gestion_planes.php?message=" . urlencode($message) . "&class=" . urlencode($message_class) . "';</script>";
    exit();
}

// --- CONSULTA PARA MOSTRAR LOS DATOS ---
if (!empty($search_term)) {
    $sql .= " WHERE nombre_plan LIKE ?";
    $search_param = "%" . $search_term . "%";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $search_param);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $sql .= " ORDER BY nombre_plan ASC";
    $result = $conn->query($sql);
}

$data = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
}

// Cierre de la conexión
if ($stmt) {
    $stmt->close();
}
$conn->close();

// Manejo del mensaje de redirección
if (isset($_GET['message'])) {
    $message = $_GET['message'];
    $message_class = $_GET['class'];
}
?>

<main class="main-content">
    <div class="page-content">
        <div class="container-fluid">
            <!-- Header de la página -->
            <div class="row mb-4">
                <div class="col-12 d-flex justify-content-between align-items-center flex-wrap gap-3">
                    <div>
                        <h2 class="h4 fw-bold mb-1 text-primary">Gestión de Planes</h2>
                        <p class="text-muted mb-0">Administración de planes de servicio</p>
                    </div>
                    <div>
                        <a href="registro_planes.php" class="btn btn-primary d-flex align-items-center gap-2">
                            <i class="fa-solid fa-plus"></i>
                            <span>Nuevo Plan</span>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Alertas -->
            <?php if ($message): ?>
                <div class="alert alert-<?php echo $message_class === 'success' ? 'success' : ($message_class === 'warning' ? 'warning' : 'danger'); ?> alert-dismissible fade show shadow-sm"
                    role="alert">
                    <div class="d-flex align-items-center gap-2">
                        <i
                            class="fa-solid <?php echo $message_class === 'success' ? 'fa-circle-check' : ($message_class === 'warning' ? 'fa-triangle-exclamation' : 'fa-circle-xmark'); ?>"></i>
                        <div><?php echo htmlspecialchars($message); ?></div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <!-- Contenedor Principal -->
            <div class="card border-0 shadow-sm overflow-hidden">
                <div class="card-body p-0">
                    <!-- Buscador -->
                    <div class="p-4 bg-light border-bottom">
                        <form action="gestion_planes.php" method="GET" class="row g-3 align-items-center">
                            <div class="col-md-6">
                                <div class="input-group">
                                    <span class="input-group-text bg-white border-end-0 text-muted">
                                        <i class="fa-solid fa-magnifying-glass"></i>
                                    </span>
                                    <input type="text" name="search" class="form-control border-start-0 ps-0"
                                        placeholder="Buscar por nombre de plan..."
                                        value="<?php echo htmlspecialchars($search_term); ?>">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-primary w-100">Buscar</button>
                            </div>
                        </form>
                    </div>

                    <!-- Tabla -->
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="ps-4">ID</th>
                                    <th>Nombre</th>
                                    <th>Monto (USD)</th>
                                    <th>Descripción</th>
                                    <th class="text-end pe-4">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($data)): ?>
                                    <?php foreach ($data as $row): ?>
                                        <tr>
                                            <td class="ps-4 fw-medium text-secondary">
                                                #<?php echo htmlspecialchars($row['id_plan']); ?></td>
                                            <td class="fw-bold text-dark"><?php echo htmlspecialchars($row['nombre_plan']); ?>
                                            </td>
                                            <td>
                                                <span
                                                    class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3">
                                                    $<?php echo htmlspecialchars($row['monto']); ?>
                                                </span>
                                            </td>
                                            <td class="text-muted small text-truncate" style="max-width: 300px;">
                                                <?php echo htmlspecialchars($row['descripcion']); ?>
                                            </td>
                                            <td class="text-end pe-4">
                                                <div class="btn-group gap-2">
                                                    <button type="button" data-bs-toggle="modal"
                                                        data-bs-target="#modalModificacionPlan"
                                                        data-id="<?php echo htmlspecialchars($row['id_plan']); ?>"
                                                        data-nombre="<?php echo htmlspecialchars($row['nombre_plan']); ?>"
                                                        data-monto="<?php echo htmlspecialchars($row['monto']); ?>"
                                                        data-descripcion="<?php echo htmlspecialchars($row['descripcion']); ?>"
                                                        class="btn btn-sm btn-outline-primary rounded-2" title="Modificar">
                                                        <i class="fa-solid fa-pen-to-square"></i>
                                                    </button>
                                                    <button type="button"
                                                        data-bs-href="gestion_planes.php?action=delete_plan&id=<?php echo urlencode($row['id_plan']); ?>"
                                                        data-bs-toggle="modal" data-bs-target="#eliminaModal"
                                                        class="btn btn-sm btn-outline-danger rounded-2" title="Eliminar">
                                                        <i class="fa-solid fa-trash-can"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-5 text-muted">
                                            <div class="d-flex flex-column align-items-center gap-2">
                                                <i class="fa-solid fa-inbox fa-2x opacity-25"></i>
                                                <p class="mb-0">No se encontraron planes registrados</p>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="mt-4 text-center">
                <a href="../menu.php" class="btn btn-outline-secondary px-4">
                    <i class="fa-solid fa-arrow-left me-2"></i>Volver al Menú
                </a>
            </div>
        </div>
    </div>

    <?php include $path_to_root . 'paginas/includes/layout_foot.php'; ?>
</main>

<!-- Modal Modificación -->
<div class="modal fade" id="modalModificacionPlan" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold" id="modalModificacionPlanLabel">
                    <i class="fa-solid fa-pen-to-square me-2 opacity-75"></i>Modificar Plan
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>
            <form id="form-modificacion-plan" action="gestion_planes.php" method="POST" novalidate>
                <div class="modal-body p-4">
                    <input type="hidden" name="update_plan" value="1">
                    <input type="hidden" name="id_plan_update" id="id_plan_modal" value="">

                    <div class="mb-3">
                        <label for="nombre_plan_modal"
                            class="form-label fw-semibold text-secondary small text-uppercase">Nombre del Plan</label>
                        <input type="text" id="nombre_plan_modal" name="nombre_plan" class="form-control" required
                            placeholder="Ej: Fibra 100MB">
                    </div>

                    <div class="mb-3">
                        <label for="monto_modal"
                            class="form-label fw-semibold text-secondary small text-uppercase">Monto (USD)</label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input type="number" id="monto_modal" name="monto" step="0.01" class="form-control" required
                                placeholder="0.00">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="descripcion_modal"
                            class="form-label fw-semibold text-secondary small text-uppercase">Descripción</label>
                        <textarea id="descripcion_modal" name="descripcion" class="form-control" rows="3"
                            placeholder="Detalles del servicio..."></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light border-top-0 py-3">
                    <button type="button" class="btn btn-outline-secondary px-4"
                        data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" id="btn-actualizar-plan" class="btn btn-primary px-4">Actualizar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Eliminación -->
<div class="modal fade" id="eliminaModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-body p-4 text-center">
                <div class="mb-3 text-danger">
                    <i class="fa-solid fa-circle-exclamation fa-3x"></i>
                </div>
                <h5 class="mb-2 fw-bold text-dark">¿Eliminar registro?</h5>
                <p class="text-muted small mb-4">Esta acción no se puede deshacer.</p>
                <div class="d-grid gap-2">
                    <a class="btn btn-danger btn-ok fw-medium">Eliminar</a>
                    <button type="button" class="btn btn-light text-secondary fw-medium"
                        data-bs-dismiss="modal">Cancelar</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Lógica para pasar la URL de eliminación al modal
    let eliminaModal = document.getElementById('eliminaModal')
    if (eliminaModal) {
        eliminaModal.addEventListener('shown.bs.modal', event => {
            let button = event.relatedTarget
            let url = button.getAttribute('data-bs-href')
            eliminaModal.querySelector('.btn-ok').href = url
        })
    }

    // --- LÓGICA DEL MODAL DE MODIFICACIÓN DE PLANES ---
    const modalModificacionPlan = document.getElementById('modalModificacionPlan');

    if (modalModificacionPlan) {
        modalModificacionPlan.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;

            const id = button.getAttribute('data-id');
            const nombre = button.getAttribute('data-nombre');
            const monto = button.getAttribute('data-monto');
            const descripcion = button.getAttribute('data-descripcion');

            document.getElementById('modalModificacionPlanLabel').innerHTML = `<i class="fa-solid fa-pen-to-square me-2 opacity-75"></i>Modificar Plan: ${nombre}`;
            document.getElementById('id_plan_modal').value = id;
            document.getElementById('nombre_plan_modal').value = nombre;
            document.getElementById('monto_modal').value = parseFloat(monto).toFixed(2);
            document.getElementById('descripcion_modal').value = descripcion;

            document.getElementById('form-modificacion-plan').classList.remove('was-validated');
        });
    }

    // Validación y envío del formulario
    const btnActualizarPlan = document.getElementById('btn-actualizar-plan');
    const formModificacionPlan = document.getElementById('form-modificacion-plan');

    if (btnActualizarPlan && formModificacionPlan) {
        btnActualizarPlan.addEventListener('click', function (event) {
            if (formModificacionPlan.checkValidity()) {
                formModificacionPlan.submit();
            } else {
                formModificacionPlan.classList.add('was-validated');
            }
        });
    }
</script>