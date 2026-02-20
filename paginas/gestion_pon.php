<?php
// Incluye el archivo de conexión.
require_once 'conexion.php';

$path_to_root = "../";
$page_title = "Gestión de PON";
$breadcrumb = ["Técnica"];
$back_url = "menu.php";
include $path_to_root . 'paginas/includes/layout_head.php';
include $path_to_root . 'paginas/includes/sidebar.php';
include $path_to_root . 'paginas/includes/header.php';

$message = '';
$message_class = '';
$action = isset($_GET['action']) ? $_GET['action'] : '';
$stmt = null;
// Variables de comunidades y asignaciones eliminadas
$olts_disponibles = [];

// Variables para la búsqueda
$search_term = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';

// ----------------------------------------------------------------------------------
// OBTENCIÓN DE DATOS PARA LOS SELECTS DEL MODAL
// ----------------------------------------------------------------------------------

// --- CONSULTA PARA OBTENER TODAS LAS OLTs (para el SELECT del MODAL de modificación) ---
$sql_olts = "SELECT id_olt, nombre_olt FROM olt ORDER BY nombre_olt ASC";
$result_olts = $conn->query($sql_olts);

if ($result_olts && $result_olts->num_rows > 0) {
    while ($row = $result_olts->fetch_assoc()) {
        $olts_disponibles[] = $row;
    }
}
// Las consultas de comunidades y asignaciones han sido eliminadas.

// ----------------------------------------------------------------------------------
// LÓGICA DE GESTIÓN (MODIFICAR - POST)
// ----------------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_pon'])) {
    $id = $_POST['id_pon'];
    $nombre = $_POST['nombre_pon'];
    $olt_id = $_POST['olt_id'];
    $descripcion = $_POST['descripcion'];
    // El manejo de comunidades ha sido eliminado.

    $message = '';
    $message_class = '';

    try {
        // 1. Actualizar la tabla principal (pon)
        // Ya no se requiere transacción
        $stmt_update_pon = $conn->prepare("UPDATE pon SET nombre_pon = ?, id_olt = ?, descripcion = ? WHERE id_pon = ?");
        // "sisi" = string (nombre), integer (olt_id), string (descripcion), integer (id)
        $stmt_update_pon->bind_param("sisi", $nombre, $olt_id, $descripcion, $id);

        if (!$stmt_update_pon->execute()) {
            throw new Exception("Error al actualizar el registro PON: " . $stmt_update_pon->error);
        }

        if ($stmt_update_pon->affected_rows > 0) {
            $message = "¡PON actualizado con éxito!";
            $message_class = 'success';
        } else {
            $message = "ADVERTENCIA: No se realizaron cambios en el PON.";
            $message_class = 'warning';
        }
        $stmt_update_pon->close();

        // La lógica de eliminación e inserción de pon_comunidad ha sido eliminada.

    } catch (Exception $e) {
        $message = "Error al actualizar el PON: " . $e->getMessage();
        $message_class = 'error';
    }

    // Redirigir para limpiar el POST y mostrar el mensaje
    echo "<script>window.location.href = 'gestion_pon.php?message=" . urlencode($message) . "&class=" . urlencode($message_class) . "';</script>";
    exit();
}

// ----------------------------------------------------------------------------------
// LÓGICA DE GESTIÓN (ELIMINAR - GET)
// ----------------------------------------------------------------------------------
if ($action === 'delete_pon' && isset($_GET['id'])) {
    $id_to_delete = $_GET['id'];

    // Eliminamos la transacción ya que solo se toca la tabla 'pon'
    try {
        // La eliminación de pon_comunidad ha sido eliminada

        // 2. Eliminar de la tabla principal (pon)
        $stmt = $conn->prepare("DELETE FROM pon WHERE id_pon = ?");
        $stmt->bind_param("i", $id_to_delete);
        if (!$stmt->execute()) {
            throw new Exception("Error al eliminar el registro PON.");
        }
        $stmt->close();

        $message = "PON eliminado con éxito.";
        $message_class = 'success';

    } catch (Exception $e) {
        $message = "Error al eliminar el PON: " . $e->getMessage();
        $message_class = 'error';
    }
    // Redirigir para limpiar la URL
    echo "<script>window.location.href = 'gestion_pon.php?message=" . urlencode($message) . "&class=" . urlencode($message_class) . "';</script>";
    exit();
}

// ----------------------------------------------------------------------------------
// LÓGICA DE BÚSQUEDA Y LISTADO
// ----------------------------------------------------------------------------------

// Consulta simplificada para obtener la lista de PONs, solo con OLT
$sql_list = "SELECT 
                p.id_pon, 
                p.nombre_pon, 
                p.descripcion,
                p.id_olt,
                o.nombre_olt
             FROM pon p
             LEFT JOIN olt o ON p.id_olt = o.id_olt"; // Se eliminaron los JOINs a pon_comunidad y comunidad

$where_clause = " WHERE 1=1 ";
if (!empty($search_term)) {
    // Escapa el término de búsqueda para usar en LIKE
    $search_param = '%' . $search_term . '%';
    // La búsqueda por comunidad ha sido eliminada
    $where_clause .= " AND (p.nombre_pon LIKE '{$search_param}' OR p.descripcion LIKE '{$search_param}' OR o.nombre_olt LIKE '{$search_param}')";
}

$sql_list .= $where_clause . " ORDER BY p.nombre_pon ASC";

$result = $conn->query($sql_list);

// Mostrar mensajes de la redirección
$get_message = isset($_GET['message']) ? $_GET['message'] : $message;
$get_class = isset($_GET['class']) ? $_GET['class'] : $message_class;

?>

<main class="main-content">
    <div class="page-content">
        <div class="container-fluid">
            <!-- Header de la página -->
            <div class="row mb-4">
                <div class="col-12 d-flex justify-content-between align-items-center flex-wrap gap-3">
                    <div>
                        <h2 class="h4 fw-bold mb-1 text-primary">Gestión de PON</h2>
                        <p class="text-muted mb-0">Administración de Puntos de Distribución</p>
                    </div>
                    <div>
                        <a href="registro_pon.php" class="btn btn-primary d-flex align-items-center gap-2">
                            <i class="fa-solid fa-plus"></i>
                            <span>Nuevo PON</span>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Alertas -->
            <?php if (!empty($get_message)): ?>
                <div class="alert alert-<?php echo $get_class === 'success' ? 'success' : ($get_class === 'warning' ? 'warning' : 'danger'); ?> alert-dismissible fade show shadow-sm"
                    role="alert">
                    <div class="d-flex align-items-center gap-2">
                        <i
                            class="fa-solid <?php echo $get_class === 'success' ? 'fa-circle-check' : ($get_class === 'warning' ? 'fa-triangle-exclamation' : 'fa-circle-xmark'); ?>"></i>
                        <div><?php echo htmlspecialchars($get_message); ?></div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <!-- Contenedor Principal -->
            <div class="card border-0 shadow-sm overflow-hidden">
                <div class="card-body p-0">
                    <!-- Buscador -->
                    <div class="p-4 bg-light border-bottom">
                        <form action="gestion_pon.php" method="GET" class="row g-3 align-items-center">
                            <div class="col-md-6">
                                <div class="input-group">
                                    <span class="input-group-text bg-white border-end-0 text-muted">
                                        <i class="fa-solid fa-magnifying-glass"></i>
                                    </span>
                                    <input type="text" name="search" class="form-control border-start-0 ps-0"
                                        placeholder="Buscar por nombre, descripción o OLT..."
                                        value="<?php echo htmlspecialchars($search_term); ?>">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-primary w-100">Buscar</button>
                            </div>
                            <?php if (!empty($search_term)): ?>
                                <div class="col-md-2">
                                    <a href="gestion_pon.php" class="btn btn-outline-secondary w-100">Limpiar</a>
                                </div>
                            <?php endif; ?>
                        </form>
                    </div>

                    <!-- Tabla -->
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="ps-4">ID</th>
                                    <th>Nombre del PON</th>
                                    <th>OLT Asignada</th>
                                    <th>Descripción</th>
                                    <th class="text-end pe-4">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($result && $result->num_rows > 0): ?>
                                    <?php while ($row = $result->fetch_assoc()): ?>
                                        <tr data-id="<?php echo htmlspecialchars($row['id_pon']); ?>"
                                            data-nombre="<?php echo htmlspecialchars($row['nombre_pon']); ?>"
                                            data-olt-id="<?php echo htmlspecialchars($row['id_olt']); ?>"
                                            data-descripcion="<?php echo htmlspecialchars($row['descripcion']); ?>">

                                            <td class="ps-4 fw-medium text-secondary">
                                                #<?php echo htmlspecialchars($row['id_pon']); ?></td>
                                            <td class="fw-bold text-dark"><?php echo htmlspecialchars($row['nombre_pon']); ?>
                                            </td>
                                            <td>
                                                <span
                                                    class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-3">
                                                    <?php echo htmlspecialchars($row['nombre_olt']); ?>
                                                </span>
                                            </td>
                                            <td class="text-muted small text-truncate" style="max-width: 300px;">
                                                <?php echo htmlspecialchars($row['descripcion']); ?>
                                            </td>

                                            <td class="text-end pe-4">
                                                <div class="btn-group gap-2">
                                                    <button type="button" data-bs-toggle="modal" data-bs-target="#modificaModal"
                                                        data-id="<?php echo htmlspecialchars($row['id_pon']); ?>"
                                                        class="btn btn-sm btn-outline-primary rounded-2" title="Modificar">
                                                        <i class="fa-solid fa-pen-to-square"></i>
                                                    </button>
                                                    <button type="button"
                                                        data-bs-href="gestion_pon.php?action=delete_pon&id=<?php echo htmlspecialchars($row['id_pon']); ?>"
                                                        data-bs-toggle="modal" data-bs-target="#eliminaModal"
                                                        class="btn btn-sm btn-outline-danger rounded-2" title="Eliminar">
                                                        <i class="fa-solid fa-trash-can"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-5 text-muted">
                                            <div class="d-flex flex-column align-items-center gap-2">
                                                <i class="fa-solid fa-network-wired fa-2x opacity-25"></i>
                                                <p class="mb-0">No se encontraron registros de PONs</p>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>


        </div>
    </div>

    <?php include $path_to_root . 'paginas/includes/layout_foot.php'; ?>
</main>

<!-- Modal Modificación -->
<div class="modal fade" id="modificaModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold" id="modificaModalLabel">
                    <i class="fa-solid fa-pen-to-square me-2 opacity-75"></i>Modificar PON
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>
            <form id="formModificacionPON" class="needs-validation" method="POST" action="gestion_pon.php" novalidate>
                <div class="modal-body p-4">
                    <input type="hidden" name="update_pon" value="1">
                    <input type="hidden" name="id_pon" id="modal-id_pon">

                    <div class="mb-3">
                        <label for="modal-nombre_pon"
                            class="form-label fw-semibold text-secondary small text-uppercase">Nombre del PON</label>
                        <input type="text" class="form-control" id="modal-nombre_pon" name="nombre_pon" required
                            placeholder="Ej: PON-01">
                        <div class="invalid-feedback">Por favor ingrese el nombre del PON.</div>
                    </div>

                    <div class="mb-3">
                        <label for="modal-olt_id" class="form-label fw-semibold text-secondary small text-uppercase">OLT
                            Asignada</label>
                        <select class="form-select" id="modal-olt_id" name="olt_id" required>
                            <option value="">-- Seleccione una OLT --</option>
                            <?php foreach ($olts_disponibles as $olt): ?>
                                <option value="<?php echo htmlspecialchars($olt['id_olt']); ?>">
                                    <?php echo htmlspecialchars($olt['nombre_olt']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="invalid-feedback">Por favor seleccione una OLT.</div>
                    </div>

                    <div class="mb-3">
                        <label for="modal-descripcion"
                            class="form-label fw-semibold text-secondary small text-uppercase">Descripción</label>
                        <textarea class="form-control" id="modal-descripcion" name="descripcion" rows="3"
                            placeholder="Detalles adicionales..."></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light border-top-0 py-3">
                    <button type="button" class="btn btn-outline-secondary px-4"
                        data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary px-4" id="btn-actualizar-pon">Guardar Cambios</button>
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
                    <a href="#" class="btn btn-danger btn-ok fw-medium">Eliminar</a>
                    <button type="button" class="btn btn-light text-secondary fw-medium"
                        data-bs-dismiss="modal">Cancelar</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // -----------------------------------------------------
    // 1. LÓGICA DEL MODAL DE MODIFICACIÓN (modificaModal)
    // -----------------------------------------------------
    const modificaModalElement = document.getElementById('modificaModal');
    const formModificacionPON = document.getElementById('formModificacionPON');

    if (modificaModalElement) {
        modificaModalElement.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const id = button.getAttribute('data-id');
            const row = document.querySelector(`tr[data-id="${id}"]`);

            if (row) {
                const nombre = row.getAttribute('data-nombre');
                const olt_id = row.getAttribute('data-olt-id');
                const descripcion = row.getAttribute('data-descripcion');

                document.getElementById('modificaModalLabel').innerHTML = `<i class="fa-solid fa-pen-to-square me-2 opacity-75"></i>Modificar PON: ${nombre}`;
                document.getElementById('modal-id_pon').value = id;
                document.getElementById('modal-nombre_pon').value = nombre;
                document.getElementById('modal-olt_id').value = olt_id;
                document.getElementById('modal-descripcion').value = descripcion;

                formModificacionPON.classList.remove('was-validated');
            }
        });

        const btnActualizarPON = document.getElementById('btn-actualizar-pon');

        if (btnActualizarPON && formModificacionPON) {
            btnActualizarPON.addEventListener('click', function (event) {

                const bootstrapValido = formModificacionPON.checkValidity();

                if (!bootstrapValido) {
                    event.preventDefault();
                    event.stopPropagation();
                    formModificacionPON.classList.add('was-validated');
                } else {
                    formModificacionPON.submit();
                }
            });
        }
    }

    // -----------------------------------------------------
    // 2. LÓGICA DEL MODAL DE ELIMINACIÓN (eliminaModal)
    // -----------------------------------------------------
    const eliminaModalElement = document.getElementById('eliminaModal');

    if (eliminaModalElement) {
        eliminaModalElement.addEventListener('shown.bs.modal', function (event) {
            const button = event.relatedTarget;
            const url = button.getAttribute('data-bs-href');

            const btnOk = eliminaModalElement.querySelector('.btn-ok');
            if (btnOk) {
                btnOk.href = url;
            }
        });
    }
</script>