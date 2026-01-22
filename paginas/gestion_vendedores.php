<?php
// Incluye el archivo de conexión.
require_once 'conexion.php';

$path_to_root = "../";
include $path_to_root . 'paginas/includes/layout_head.php';
include $path_to_root . 'paginas/includes/sidebar.php';
include $path_to_root . 'paginas/includes/header.php';

$message = '';
$message_class = '';
$action = isset($_GET['action']) ? $_GET['action'] : '';
$stmt = null; 

// Variables para la búsqueda
$search_term = isset($_GET['search']) ? $_GET['search'] : '';
$sql_base = "SELECT * FROM `vendedores`";

// --- LÓGICA DE GESTIÓN (ELIMINAR) ---
if ($action === 'delete' && isset($_GET['id'])) {
    $id_to_delete = $_GET['id'];
    $stmt = $conn->prepare("DELETE FROM `vendedores` WHERE `id_vendedor` = ?");
    // El id_vendedor es un string (s) según tu código original
    $stmt->bind_param("s", $id_to_delete); 
    if ($stmt->execute()) {
        $message = "Vendedor con ID '{$id_to_delete}' eliminado con éxito.";
        $message_class = 'success';
    } else {
        $message = "Error al eliminar el vendedor: " . $stmt->error;
        $message_class = 'error';
    }
    // Redirigimos para limpiar la URL y mostrar el mensaje
    echo "<script>window.location.href = 'gestion_vendedores.php?message=" . urlencode($message) . "&class=" . urlencode($message_class) . "';</script>";
    exit();
}

// --- LÓGICA DE MODIFICACIÓN (PROCESAR FORMULARIO POST DEL MODAL) ---
// Usamos 'update_vendedor' como flag para el envío del formulario del modal
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_vendedor'])) {
    $id = $_POST['id_vendedor_update']; // ID viene del campo oculto del modal
    $nombre = $_POST['nombre_vendedor'];
    $telefono = $_POST['telefono_vendedor'];

    $stmt = $conn->prepare("UPDATE `vendedores` SET `nombre_vendedor` = ?, `telefono_vendedor` = ? WHERE `id_vendedor` = ?");
    // Parámetros: string (nombre), string (telefono), string (id)
    $stmt->bind_param("sss", $nombre, $telefono, $id);

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            $message = "¡Vendedor '{$nombre}' actualizado con éxito!";
            $message_class = 'success';
        } else {
            $message = "ADVERTENCIA: No se realizaron cambios en el Vendedor.";
            $message_class = 'warning';
        }
    } else {
        $message = "Error al actualizar el vendedor: " . $stmt->error;
        $message_class = 'error';
    }
    
    if ($stmt) {
        $stmt->close();
    }
    // Redirigimos para mostrar el mensaje y limpiar POST
    echo "<script>window.location.href = 'gestion_vendedores.php?message=" . urlencode($message) . "&class=" . urlencode($message_class) . "';</script>";
    exit();
}

// --- LÓGICA DE CONSULTA (BUSCAR Y MOSTRAR) ---
$sql = $sql_base;
if (!empty($search_term)) {
    $sql .= " WHERE `id_vendedor` LIKE ? OR `nombre_vendedor` LIKE ?";
    $stmt = $conn->prepare($sql);
    $search_param = "%" . $search_term . "%";
    $stmt->bind_param("ss", $search_param, $search_param);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query($sql);
}

// Manejo del mensaje de redirección (después de eliminar o actualizar)
if (isset($_GET['message'])) {
    $message = $_GET['message'];
    $message_class = $_GET['class'];
}

// Cierre de la conexión (si no se cerró en la lógica de POST/DELETE)
if ($stmt) {
    $stmt->close();
}
$conn->close();

?>

<main class="main-content">
    <div class="page-content">
        <div class="container-fluid">
            <!-- Header de la página -->
            <div class="row mb-4">
                <div class="col-12 d-flex justify-content-between align-items-center flex-wrap gap-3">
                    <div>
                        <h2 class="h4 fw-bold mb-1 text-primary">Gestión de Vendedores</h2>
                        <p class="text-muted mb-0">Administración del equipo de ventas</p>
                    </div>
                    <div>
                        <a href="registro_vendedores.php" class="btn btn-primary d-flex align-items-center gap-2">
                            <i class="fa-solid fa-plus"></i>
                            <span>Nuevo Vendedor</span>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Alertas -->
            <?php if ($message): ?>
                <div class="alert alert-<?php echo $message_class === 'success' ? 'success' : ($message_class === 'warning' ? 'warning' : 'danger'); ?> alert-dismissible fade show shadow-sm" role="alert">
                    <div class="d-flex align-items-center gap-2">
                        <i class="fa-solid <?php echo $message_class === 'success' ? 'fa-circle-check' : ($message_class === 'warning' ? 'fa-triangle-exclamation' : 'fa-circle-xmark'); ?>"></i>
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
                        <form action="gestion_vendedores.php" method="GET" class="row g-3 align-items-center">
                            <div class="col-md-6">
                                <div class="input-group">
                                    <span class="input-group-text bg-white border-end-0 text-muted">
                                        <i class="fa-solid fa-magnifying-glass"></i>
                                    </span>
                                    <input type="text" name="search" class="form-control border-start-0 ps-0" 
                                           placeholder="Buscar por ID o Nombre..." 
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
                                    <th class="ps-4">ID Vendedor</th>
                                    <th>Nombre</th>
                                    <th>Teléfono</th>
                                    <th class="text-end pe-4">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($result && $result->num_rows > 0): ?>
                                    <?php while($row = $result->fetch_assoc()): ?>
                                        <tr>
                                            <td class="ps-4 fw-medium text-secondary">#<?php echo htmlspecialchars($row['id_vendedor']); ?></td>
                                            <td class="fw-bold text-dark"><?php echo htmlspecialchars($row['nombre_vendedor']); ?></td>
                                            <td>
                                                <div class="d-flex align-items-center gap-2">
                                                    <i class="fa-solid fa-phone text-muted small"></i>
                                                    <?php echo htmlspecialchars($row['telefono_vendedor']); ?>
                                                </div>
                                            </td>
                                            <td class="text-end pe-4">
                                                <div class="btn-group gap-2">
                                                    <button type="button" 
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#modalModificacionVendedor"
                                                            data-id="<?php echo htmlspecialchars($row['id_vendedor']); ?>"
                                                            data-nombre="<?php echo htmlspecialchars($row['nombre_vendedor']); ?>"
                                                            data-telefono="<?php echo htmlspecialchars($row['telefono_vendedor']); ?>"
                                                            class="btn btn-sm btn-outline-primary rounded-2" 
                                                            title="Modificar">
                                                        <i class="fa-solid fa-pen-to-square"></i>
                                                    </button>
                                                    <button type="button" 
                                                            data-bs-href="gestion_vendedores.php?action=delete&id=<?php echo urlencode($row['id_vendedor']); ?>" 
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#eliminaModal" 
                                                            class="btn btn-sm btn-outline-danger rounded-2" 
                                                            title="Eliminar">
                                                        <i class="fa-solid fa-trash-can"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="text-center py-5 text-muted">
                                            <div class="d-flex flex-column align-items-center gap-2">
                                                <i class="fa-solid fa-user-slash fa-2x opacity-25"></i>
                                                <p class="mb-0">No se encontraron vendedores registrados</p>
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
<div class="modal fade" id="modalModificacionVendedor" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold" id="modalModificacionVendedorLabel">
                    <i class="fa-solid fa-pen-to-square me-2 opacity-75"></i>Modificar Vendedor
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="form-modificacion-vendedor" action="gestion_vendedores.php" method="POST" novalidate>
                <div class="modal-body p-4">
                    <input type="hidden" name="update_vendedor" value="1">
                    <input type="hidden" name="id_vendedor_update" id="id_vendedor_modal" value="">
                    
                    <div class="mb-3">
                        <label for="nombre_vendedor_modal" class="form-label fw-semibold text-secondary small text-uppercase">Nombre del Vendedor</label>
                        <input type="text" id="nombre_vendedor_modal" name="nombre_vendedor" class="form-control" required placeholder="Nombre Completo"> 
                    </div>
                    
                    <div class="mb-3">
                        <label for="telefono_vendedor_modal" class="form-label fw-semibold text-secondary small text-uppercase">Teléfono</label>
                        <input type="text" id="telefono_vendedor_modal" name="telefono_vendedor" class="form-control" placeholder="Ej: 0414-1234567"> 
                    </div>
                </div>
                <div class="modal-footer bg-light border-top-0 py-3">
                    <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" id="btn-actualizar-vendedor" class="btn btn-primary px-4">Actualizar</button>
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
                    <button type="button" class="btn btn-light text-secondary fw-medium" data-bs-dismiss="modal">Cancelar</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Lógica para pasar la URL de eliminación al modal de Bootstrap 
    let eliminaModal = document.getElementById('eliminaModal')
    if (eliminaModal) {
        eliminaModal.addEventListener('shown.bs.modal', event => {
            let button = event.relatedTarget
            let url = button.getAttribute('data-bs-href') 
            eliminaModal.querySelector('.btn-ok').href = url
        })
    }

    // --- LÓGICA DEL MODAL DE MODIFICACIÓN DE VENDEDORES ---
    const modalModificacionVendedor = document.getElementById('modalModificacionVendedor');

    if (modalModificacionVendedor) {
        modalModificacionVendedor.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget; 
            
            // 1. Obtener los 3 datos pasados desde la tabla
            const id = button.getAttribute('data-id');
            const nombre = button.getAttribute('data-nombre');
            const telefono = button.getAttribute('data-telefono');
            
            // 2. Asignar valores a los campos del modal
            document.getElementById('modalModificacionVendedorLabel').innerHTML = `<i class="fa-solid fa-pen-to-square me-2 opacity-75"></i>Modificar Vendedor: ${nombre}`;
            document.getElementById('id_vendedor_modal').value = id;
            document.getElementById('nombre_vendedor_modal').value = nombre;
            document.getElementById('telefono_vendedor_modal').value = telefono;
            
            // 3. Reiniciar la validación
            document.getElementById('form-modificacion-vendedor').classList.remove('was-validated');
        });
    }

    // 4. Lógica para el botón de Actualizar y validación manual
    const btnActualizarVendedor = document.getElementById('btn-actualizar-vendedor');
    const formModificacionVendedor = document.getElementById('form-modificacion-vendedor');

    if (btnActualizarVendedor && formModificacionVendedor) {
        btnActualizarVendedor.addEventListener('click', function(event) {
            
            // Verificar si el formulario es válido (HTML5 validation)
            if (formModificacionVendedor.checkValidity()) {
                formModificacionVendedor.submit(); // Enviar el formulario
            } else {
                // Si no es válido, mostrar los mensajes de error de Bootstrap
                formModificacionVendedor.classList.add('was-validated');
            }
        });
    }
</script>