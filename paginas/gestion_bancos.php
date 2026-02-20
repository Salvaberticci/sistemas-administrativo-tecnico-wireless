<?php
// Incluye el archivo de conexión.
require_once 'conexion.php';


$message = '';
$message_class = '';
$action = isset($_GET['action']) ? $_GET['action'] : '';
$stmt = null; // Inicializamos $stmt

// Variables para la búsqueda
$search_term = isset($_GET['search']) ? $_GET['search'] : '';
$sql = "SELECT * FROM bancos";

// --- LÓGICA DE GESTIÓN (ELIMINAR) ---
if ($action === 'delete_banco' && isset($_GET['id'])) {
    $id_to_delete = $_GET['id'];
    $stmt = $conn->prepare("DELETE FROM bancos WHERE id_banco = ?");
    $stmt->bind_param("i", $id_to_delete);
    if ($stmt->execute()) {
        $message = "Banco eliminado con éxito.";
        $message_class = 'success';
    } else {
        $message = "Error al eliminar el banco: " . $stmt->error;
        $message_class = 'error';
    }
    // Redirigimos para limpiar la URL y mostrar el mensaje
    header("Location: gestion_bancos.php?message=" . urlencode($message) . "&class=" . urlencode($message_class));
    exit();
}

// --- LÓGICA DE MODIFICACIÓN (PROCESAR FORMULARIO POST DEL MODAL) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_banco'])) {
    $id = $_POST['id_banco_update']; // El ID viene del campo oculto del modal
    $nombre_banco = $_POST['nombre_banco'];
    $numero_cuenta = $_POST['numero_cuenta'];
    $cedula_propietario = $_POST['cedula_propietario'];
    $nombre_propietario = $_POST['nombre_propietario'];

    // Asegúrate de que el número de parámetros de bind_param coincida con los '?' en el UPDATE
    $stmt = $conn->prepare("UPDATE bancos SET nombre_banco = ?, numero_cuenta = ?, cedula_propietario = ?, nombre_propietario = ? WHERE id_banco = ?");
    $stmt->bind_param("ssssi", $nombre_banco, $numero_cuenta, $cedula_propietario, $nombre_propietario, $id);

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            $message = "¡Banco actualizado con éxito!";
            $message_class = 'success';
        } else {
            $message = "ADVERTENCIA: No se realizaron cambios en el Banco. Los datos ingresados son idénticos.";
            $message_class = 'warning';
        }
    } else {
        $message = "Error al actualizar el banco: " . $stmt->error;
        $message_class = 'error';
    }

    if ($stmt) {
        $stmt->close();
    }
    // Redirigimos para mostrar el mensaje y limpiar POST
    header("Location: gestion_bancos.php?message=" . urlencode($message) . "&class=" . urlencode($message_class));
    exit();
}

// --- CONSULTA PARA MOSTRAR LOS DATOS ---
if (!empty($search_term)) {
    $sql .= " WHERE nombre_banco LIKE ?";
    $search_param = "%" . $search_term . "%";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $search_param);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $sql .= " ORDER BY nombre_banco ASC";
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
<?php
$page_title = "Gestión de Bancos";
$breadcrumb = ["Admin"];
$back_url = "menu.php";
require_once 'includes/layout_head.php';
require_once 'includes/sidebar.php';
?>

<main class="main-content">
    <?php include 'includes/header.php'; ?>

    <div class="page-content">

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_class == 'success' ? 'success' : 'danger'; ?> alert-dismissible fade show"
                role="alert">
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header d-flex flex-column flex-md-row justify-content-between align-items-center">
                <h5 class="mb-3 mb-md-0">Listado de Bancos</h5>
                <div class="d-flex gap-2 w-100 w-md-auto">
                    <form action="gestion_bancos.php" method="GET" class="d-flex gap-2 flex-grow-1 header-search">
                        <input type="text" name="search" class="form-control form-control-sm" placeholder="Buscar..."
                            value="<?php echo htmlspecialchars($search_term); ?>">
                        <button type="submit" class="btn btn-primary btn-sm"><i class="fa-solid fa-search"></i></button>
                    </form>
                    <a href="registro_bancos.php" class="btn btn-primary btn-sm text-nowrap">
                        <i class="fa-solid fa-plus"></i> Nuevo
                    </a>
                </div>
            </div>

            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-4">ID</th>
                                <th>Nombre del Banco</th>
                                <th>Número de Cuenta</th>
                                <th>Cédula Propietario</th>
                                <th>Nombre Propietario</th>
                                <th class="text-end pe-4">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($data)): ?>
                                <?php foreach ($data as $row): ?>
                                    <tr>
                                        <td class="ps-4 text-muted">#<?php echo htmlspecialchars($row['id_banco']); ?></td>
                                        <td class="fw-bold"><?php echo htmlspecialchars($row['nombre_banco']); ?></td>
                                        <td class="font-monospace text-muted">
                                            <?php echo htmlspecialchars($row['numero_cuenta']); ?></td>
                                        <td><?php echo htmlspecialchars($row['cedula_propietario']); ?></td>
                                        <td><?php echo htmlspecialchars($row['nombre_propietario']); ?></td>
                                        <td class="text-end pe-4">
                                            <div class="btn-group">
                                                <a href="#" data-bs-toggle="modal" data-bs-target="#modalModificacionBanco"
                                                    data-id="<?php echo htmlspecialchars($row['id_banco']); ?>"
                                                    data-nombre="<?php echo htmlspecialchars($row['nombre_banco']); ?>"
                                                    data-cuenta="<?php echo htmlspecialchars($row['numero_cuenta']); ?>"
                                                    data-cedula="<?php echo htmlspecialchars($row['cedula_propietario']); ?>"
                                                    data-propietario="<?php echo htmlspecialchars($row['nombre_propietario']); ?>"
                                                    class="btn btn-light btn-sm text-primary" title="Modificar">
                                                    <i class="fa-solid fa-pen"></i>
                                                </a>
                                                <a href="#"
                                                    data-bs-href="gestion_bancos.php?action=delete_banco&id=<?php echo urlencode($row['id_banco']); ?>"
                                                    data-bs-toggle="modal" data-bs-target="#eliminaModal"
                                                    class="btn btn-light btn-sm text-danger" title="Eliminar">
                                                    <i class="fa-solid fa-trash"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center p-4 text-muted">No se encontraron bancos.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Modal Modificar -->
<div class="modal fade" id="modalModificacionBanco" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title fw-bold text-primary" id="modalModificacionBancoLabel">Modificar Banco</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="form-modificacion-banco" action="gestion_bancos.php" method="POST" novalidate>
                <div class="modal-body">
                    <input type="hidden" name="update_banco" value="1">
                    <input type="hidden" name="id_banco_update" id="id_banco_modal" value="">

                    <div class="mb-3">
                        <label for="nombre_banco_modal"
                            class="form-label small text-muted fw-bold text-uppercase">Nombre del Banco</label>
                        <input type="text" id="nombre_banco_modal" name="nombre_banco" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label for="numero_cuenta_modal"
                            class="form-label small text-muted fw-bold text-uppercase">Número de Cuenta</label>
                        <input type="text" id="numero_cuenta_modal" name="numero_cuenta"
                            class="form-control font-monospace" required>
                    </div>

                    <div class="row">
                        <div class="col-md-5 mb-3">
                            <label for="cedula_propietario_modal"
                                class="form-label small text-muted fw-bold text-uppercase">Cédula</label>
                            <input type="text" id="cedula_propietario_modal" name="cedula_propietario"
                                class="form-control" required>
                        </div>
                        <div class="col-md-7 mb-3">
                            <label for="nombre_propietario_modal"
                                class="form-label small text-muted fw-bold text-uppercase">Propietario</label>
                            <input type="text" id="nombre_propietario_modal" name="nombre_propietario"
                                class="form-control" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top-0 pt-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" id="btn-actualizar-banco" class="btn btn-primary px-4">Actualizar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Eliminar -->
<div class="modal fade" id="eliminaModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-body text-center p-4">
                <div class="mb-3 text-danger">
                    <i class="fa-solid fa-trash-can fa-3x"></i>
                </div>
                <h5 class="fw-bold mb-2">Eliminar Banco</h5>
                <p class="text-muted small mb-4">Esta acción no se puede deshacer.</p>
                <div class="d-flex justify-content-center gap-2">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <a href="#" class="btn btn-danger btn-ok px-4">Eliminar</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/layout_foot.php'; ?>

<script>
    // Delete Modal Logic
    let eliminaModal = document.getElementById('eliminaModal')
    if (eliminaModal) {
        eliminaModal.addEventListener('shown.bs.modal', event => {
            let button = event.relatedTarget
            let url = button.getAttribute('data-bs-href')
            eliminaModal.querySelector('.btn-ok').href = url
        })
    }

    // Edit Modal Logic
    const modalModificacionBanco = document.getElementById('modalModificacionBanco');
    if (modalModificacionBanco) {
        modalModificacionBanco.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const id = button.getAttribute('data-id');
            const nombre = button.getAttribute('data-nombre');
            const cuenta = button.getAttribute('data-cuenta');
            const cedula = button.getAttribute('data-cedula');
            const propietario = button.getAttribute('data-propietario');

            document.getElementById('modalModificacionBancoLabel').textContent = `Editar Banco`;
            document.getElementById('id_banco_modal').value = id;
            document.getElementById('nombre_banco_modal').value = nombre;
            document.getElementById('numero_cuenta_modal').value = cuenta;
            document.getElementById('cedula_propietario_modal').value = cedula;
            document.getElementById('nombre_propietario_modal').value = propietario;

            document.getElementById('form-modificacion-banco').classList.remove('was-validated');
        });
    }

    // Validation Logic
    const btnActualizarBanco = document.getElementById('btn-actualizar-banco');
    const formModificacionBanco = document.getElementById('form-modificacion-banco');
    if (btnActualizarBanco && formModificacionBanco) {
        btnActualizarBanco.addEventListener('click', function (event) {
            if (formModificacionBanco.checkValidity()) {
                formModificacionBanco.submit();
            } else {
                formModificacionBanco.classList.add('was-validated');
                formModificacionBanco.reportValidity();
            }
        });
    }
</script>