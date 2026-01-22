<?php
// Incluye el archivo de conexión.
require_once 'conexion.php';

$message = '';
$message_class = '';
$action = isset($_GET['action']) ? $_GET['action'] : '';
$stmt = null; // Inicializamos la variable stmt para evitar errores

// Variables para la búsqueda
$search_term = isset($_GET['search']) ? $_GET['search'] : '';
$sql = "SELECT m.id_municipio, m.nombre_municipio, p.id_parroquia, p.nombre_parroquia 
        FROM `municipio` m
        LEFT JOIN `parroquia` p ON m.id_municipio = p.id_municipio";

// --- LÓGICA DE GESTIÓN (ELIMINAR) ---
if ($action === 'delete_municipio' && isset($_GET['id'])) {
    $id_to_delete = $_GET['id'];
    // Se asume eliminación en cascada en la BD o que se maneja la eliminación de dependencias
    $stmt = $conn->prepare("DELETE FROM `municipio` WHERE `id_municipio` = ?");
    $stmt->bind_param("i", $id_to_delete);
    if ($stmt->execute()) {
        $message = "Municipio, parroquias y comunidades eliminados con éxito.";
        $message_class = 'success';
    } else {
        $message = "Error al eliminar el municipio: " . $stmt->error;
        $message_class = 'error';
    }
} elseif ($action === 'delete_parroquia' && isset($_GET['id'])) {
    $id_to_delete = $_GET['id'];
    $stmt = $conn->prepare("DELETE FROM `parroquia` WHERE `id_parroquia` = ?");
    $stmt->bind_param("i", $id_to_delete);
    if ($stmt->execute()) {
        $message = "Parroquia y sus comunidades eliminadas con éxito.";
        $message_class = 'success';
    } else {
        $message = "Error al eliminar la parroquia: " . $stmt->error;
        $message_class = 'error';
    }
} elseif ($action === 'delete_comunidad' && isset($_GET['id'])) {
    $id_to_delete = $_GET['id'];
    $stmt = $conn->prepare("DELETE FROM `comunidad` WHERE `id_comunidad` = ?");
    $stmt->bind_param("i", $id_to_delete);
    if ($stmt->execute()) {
        $message = "Comunidad eliminada con éxito.";
        $message_class = 'success';
    } else {
        $message = "Error al eliminar la comunidad: " . $stmt->error;
        $message_class = 'error';
    }
}

// --- LÓGICA DE MODIFICACIÓN (PROCESAR FORMULARIO POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_type'])) {
    $update_type = $_POST['update_type'];
    $stmt = null; // Reiniciar $stmt
    $message_suffix = '';
    $id_principal = null;

    if ($update_type === 'municipio') {
        // BUSCA EL NUEVO NOMBRE ÚNICO: id_municipio_update
        $id_principal = isset($_POST['id_municipio_update']) ? $_POST['id_municipio_update'] : null;
        $nombre = isset($_POST['nombre_municipio']) ? $_POST['nombre_municipio'] : '';
        $stmt = $conn->prepare("UPDATE `municipio` SET `nombre_municipio` = ? WHERE `id_municipio` = ?");
        $stmt->bind_param("si", $nombre, $id_principal);
        $message_suffix = 'Municipio';
    } elseif ($update_type === 'parroquia') {
        // BUSCA EL NUEVO NOMBRE ÚNICO: id_parroquia_update
        $id_principal = isset($_POST['id_parroquia_update']) ? $_POST['id_parroquia_update'] : null;
        $nombre = isset($_POST['nombre_parroquia']) ? $_POST['nombre_parroquia'] : '';
        $id_municipio = isset($_POST['id_municipio']) ? $_POST['id_municipio'] : null;
        $stmt = $conn->prepare("UPDATE `parroquia` SET `nombre_parroquia` = ?, `id_municipio` = ? WHERE `id_parroquia` = ?");
        $stmt->bind_param("sii", $nombre, $id_municipio, $id_principal);
        $message_suffix = 'Parroquia';
    } elseif ($update_type === 'comunidad') { 
        // BUSCA EL NUEVO NOMBRE ÚNICO: id_comunidad_update
        $id_principal = isset($_POST['id_comunidad_update']) ? $_POST['id_comunidad_update'] : null;
        $nombre = isset($_POST['nombre_comunidad']) ? $_POST['nombre_comunidad'] : '';
        $id_parroquia = isset($_POST['id_parroquia_comunidad']) ? $_POST['id_parroquia_comunidad'] : null; 
        $stmt = $conn->prepare("UPDATE `comunidad` SET `nombre_comunidad` = ?, `id_parroquia` = ? WHERE `id_comunidad` = ?");
        $stmt->bind_param("sii", $nombre, $id_parroquia, $id_principal);
        $message_suffix = 'Comunidad';
    }

    if (isset($stmt)) {
        if (empty($id_principal)) {
            $message = "ERROR Crítico: La ID de la {$message_suffix} a modificar no se pudo obtener. Actualización fallida.";
            $message_class = 'error';
        } elseif ($stmt->execute()) {
            // VERIFICACIÓN DE FILAS AFECTADAS (CLAVE PARA SABER SI HUBO CAMBIOS)
            if ($stmt->affected_rows > 0) { 
                $message = "¡" . $message_suffix . " actualizada(o) con éxito!";
                $message_class = 'success';
            } else {
                $message = "ADVERTENCIA: No se realizaron cambios en el registro de " . $message_suffix . ". Los datos ingresados son idénticos a los existentes, o la ID no existe. ID: {$id_principal}";
                $message_class = 'warning'; 
            }
        } else {
            $message = "ERROR Crítico al actualizar " . $message_suffix . ": " . $stmt->error;
            $message_class = 'error';
        }
        if ($stmt) {
            $stmt->close();
        }
    } else {
        $message = "ERROR: Tipo de actualización desconocido.";
        $message_class = 'error';
    }
    
    // Redirigimos para mostrar el mensaje
    header("Location: gestion_municipios.php?message=" . urlencode($message) . "&class=" . urlencode($message_class));
    exit();
}


// --- CONSULTA PARA MOSTRAR LOS DATOS ---
if (!empty($search_term)) {
    $sql .= " WHERE m.nombre_municipio LIKE ? OR p.nombre_parroquia LIKE ?";
    $search_param = "%" . $search_term . "%";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $search_param, $search_param);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $sql .= " ORDER BY m.nombre_municipio ASC, p.nombre_parroquia ASC";
    $result = $conn->query($sql);
}

$data = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
}

// Obtener TODAS las ubicaciones para el modal (datos CLAVE para el JS)
$comunidades = $conn->query("SELECT id_comunidad, nombre_comunidad, id_parroquia FROM `comunidad` ORDER BY id_parroquia, nombre_comunidad ASC")->fetch_all(MYSQLI_ASSOC);
$municipios_all = $conn->query("SELECT id_municipio, nombre_municipio FROM `municipio` ORDER BY nombre_municipio ASC")->fetch_all(MYSQLI_ASSOC);
$parroquias_all = $conn->query("SELECT id_parroquia, nombre_parroquia, id_municipio FROM `parroquia` ORDER BY nombre_parroquia ASC")->fetch_all(MYSQLI_ASSOC);

// Manejo del mensaje de redirección
if (isset($_GET['message'])) {
    $message = $_GET['message'];
    $message_class = $_GET['class'];
}

// Cerramos la conexión a la base de datos al final
if ($stmt) {
    $stmt->close();
}
$conn->close();

?>
<?php
$page_title = "Gestión de Ubicaciones";
require_once 'includes/layout_head.php';
require_once 'includes/sidebar.php';
?>

<main class="main-content">
    <?php include 'includes/header.php'; ?>

    <div class="page-content">
        
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_class == 'success' ? 'success' : 'danger'; ?> alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header d-flex flex-column flex-md-row justify-content-between align-items-center">
                <h5 class="mb-3 mb-md-0">Municipios y Parroquias</h5>
                <div class="d-flex gap-2 w-100 w-md-auto">
                    <form action="gestion_municipios.php" method="GET" class="d-flex gap-2 flex-grow-1 header-search">
                        <input type="text" name="search" class="form-control form-control-sm" placeholder="Buscar..." value="<?php echo htmlspecialchars($search_term); ?>">
                        <button type="submit" class="btn btn-primary btn-sm"><i class="fa-solid fa-search"></i></button>
                    </form>
                    <a href="registro_municipios.php" class="btn btn-primary btn-sm text-nowrap">
                        <i class="fa-solid fa-plus"></i> Nuevo
                    </a>
                </div>
            </div>
            
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-4">ID Mun.</th>
                                <th>Municipio</th>
                                <th>ID Parr.</th>
                                <th>Parroquia</th>
                                <th class="text-center">Comunidades</th>
                                <th class="text-end pe-4">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $current_municipio_id = null;
                            if (!empty($data)): ?>
                                <?php foreach ($data as $row): ?>
                                    <?php if ($current_municipio_id !== $row['id_municipio']): ?>
                                        <tr class="table-secondary fw-bold">
                                            <td class="ps-4"><?php echo htmlspecialchars($row['id_municipio']); ?></td>
                                            <td><?php echo htmlspecialchars($row['nombre_municipio']); ?></td>
                                            <td>-</td>
                                            <td>-</td>
                                            <td class="text-center">-</td>
                                            <td class="text-end pe-4"> 
                                                <div class="btn-group">
                                                    <a href="#" 
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#modalModificacion"
                                                        data-id="<?php echo urlencode($row['id_municipio']); ?>"
                                                        data-nombre="<?php echo htmlspecialchars($row['nombre_municipio']); ?>"
                                                        data-type="municipio"
                                                        class="btn btn-light btn-sm text-primary" title="Modificar Municipio">
                                                        <i class="fa-solid fa-pen"></i>
                                                    </a>
                                                    <a href="#" 
                                                        data-bs-href="gestion_municipios.php?action=delete_municipio&id=<?php echo urlencode($row['id_municipio']); ?>" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#eliminaModal" 
                                                        class="btn btn-light btn-sm text-danger" 
                                                        title="Eliminar Municipio">
                                                        <i class="fa-solid fa-trash"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php $current_municipio_id = $row['id_municipio']; ?>
                                    <?php endif; ?>
                                    <?php if ($row['id_parroquia']): ?>
                                        <tr>
                                            <td class="ps-4 text-muted border-0"></td>
                                            <td class="border-0"></td>
                                            <td class="text-muted small">#<?php echo htmlspecialchars($row['id_parroquia']); ?></td>
                                            <td><?php echo htmlspecialchars($row['nombre_parroquia']); ?></td>
                                            
                                            <td class="text-center">
                                                <a href="#" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#modalComunidades"
                                                    data-parroquia-id="<?php echo urlencode($row['id_parroquia']); ?>"
                                                    data-parroquia-nombre="<?php echo htmlspecialchars($row['nombre_parroquia']); ?>"
                                                    class="btn btn-sm btn-outline-info btn-comunidades px-3 rounded-pill" 
                                                    title="Ver Comunidades">
                                                    <i class="fa-solid fa-eye me-1"></i> Ver
                                                </a>
                                            </td>
                                            
                                            <td class="text-end pe-4">
                                                <div class="btn-group">
                                                    <a href="#" 
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#modalModificacion"
                                                        data-id="<?php echo urlencode($row['id_parroquia']); ?>"
                                                        data-nombre="<?php echo htmlspecialchars($row['nombre_parroquia']); ?>"
                                                        data-municipio-id="<?php echo urlencode($row['id_municipio']); ?>"
                                                        data-type="parroquia"
                                                        class="btn btn-light btn-sm text-primary" title="Modificar Parroquia">
                                                        <i class="fa-solid fa-pen"></i>
                                                    </a>
                                                    <a href="#" 
                                                        data-bs-href="gestion_municipios.php?action=delete_parroquia&id=<?php echo urlencode($row['id_parroquia']); ?>" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#eliminaModal" 
                                                        class="btn btn-light btn-sm text-danger" 
                                                        title="Eliminar Parroquia">
                                                        <i class="fa-solid fa-trash"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="6" class="text-center p-4 text-muted">No se encontraron registros.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Modal Modificar -->
<div class="modal fade" id="modalModificacion" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title fw-bold text-primary" id="modalModificacionLabel">Modificar Ubicación</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="form-modificacion" action="gestion_municipios.php" method="POST" novalidate>
                <div class="modal-body">
                    <input type="hidden" name="update_type" id="update_type_modal" value="">
                    <input type="hidden" name="id_municipio_update" id="id_municipio_modal" value="">
                    <input type="hidden" name="id_parroquia_update" id="id_parroquia_modal" value="">
                    <input type="hidden" name="id_comunidad_update" id="id_comunidad_modal" value="">
                    
                    <div id="modal-content-municipio" style="display:none;">
                        <div class="mb-3">
                            <label for="nombre_municipio_modal" class="form-label small text-muted fw-bold text-uppercase">Nombre del Municipio</label>
                            <input type="text" id="nombre_municipio_modal" name="nombre_municipio" class="form-control"> 
                        </div>
                    </div>
                    
                    <div id="modal-content-parroquia" style="display:none;">
                        <div class="mb-3">
                            <label for="id_municipio_parroquia_modal" class="form-label small text-muted fw-bold text-uppercase">Municipio Asociado</label>
                            <select id="id_municipio_parroquia_modal" name="id_municipio" class="form-select"> 
                                <option value="">Seleccione un municipio</option>
                                <?php foreach ($municipios_all as $mun_row): ?>
                                    <option value="<?php echo htmlspecialchars($mun_row['id_municipio']); ?>">
                                        <?php echo htmlspecialchars($mun_row['nombre_municipio']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="nombre_parroquia_modal" class="form-label small text-muted fw-bold text-uppercase">Nombre de la Parroquia</label>
                            <input type="text" id="nombre_parroquia_modal" name="nombre_parroquia" class="form-control"> 
                        </div>
                    </div>

                    <div id="modal-content-comunidad" style="display:none;">
                        <div class="mb-3">
                            <label for="id_municipio_comunidad_modal" class="form-label small text-muted fw-bold text-uppercase">Municipio</label>
                            <select id="id_municipio_comunidad_modal" class="form-select" onchange="filterParroquias(this.value, 'id_parroquia_comunidad_modal')"> 
                                <option value="">Seleccione un municipio</option>
                                <?php foreach ($municipios_all as $mun_row): ?>
                                    <option value="<?php echo htmlspecialchars($mun_row['id_municipio']); ?>">
                                        <?php echo htmlspecialchars($mun_row['nombre_municipio']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="id_parroquia_comunidad_modal" class="form-label small text-muted fw-bold text-uppercase">Parroquia</label>
                            <select id="id_parroquia_comunidad_modal" name="id_parroquia_comunidad" class="form-select"> 
                                <option value="">Seleccione un municipio primero</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="nombre_comunidad_modal" class="form-label small text-muted fw-bold text-uppercase">Nombre de la Comunidad</label>
                            <input type="text" id="nombre_comunidad_modal" name="nombre_comunidad" class="form-control"> 
                        </div>
                    </div>
                    
                </div>
                <div class="modal-footer border-top-0 pt-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" id="btn-actualizar-modal" class="btn btn-primary px-4">Actualizar</button>
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
                <h5 class="fw-bold mb-2">Eliminar Registro</h5>
                <p class="text-muted small mb-4">Esta acción no se puede deshacer.</p>
                <div class="d-flex justify-content-center gap-2">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <a href="#" class="btn btn-danger btn-ok px-4">Eliminar</a> 
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Comunidades -->
<div class="modal fade" id="modalComunidades" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header border-bottom-0">
                <h5 class="modal-title fw-bold text-primary" id="modalComunidadesLabel">Comunidades: <span id="parroquia-name-modal" class="text-dark"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-hover mb-0 align-middle">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-4" style="width: 10%;">ID</th>
                                <th style="width: 60%;">Comunidad</th>
                                <th class="text-end pe-4" style="width: 30%;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="comunidades-list-body">
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer border-top-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/layout_foot.php'; ?>

<script>
    // DATOS DE UBICACIONES SERIALIZADOS DESDE PHP A JAVASCRIPT
    const ALL_COMUNIDADES = <?php echo json_encode($comunidades); ?>;
    const ALL_MUNICIPIOS = <?php echo json_encode($municipios_all); ?>;
    const ALL_PARROQUIAS = <?php echo json_encode($parroquias_all); ?>;

    // --- FUNCIÓN CLAVE PARA LA VALIDACIÓN DINÁMICA ---
    function setRequiredFields(containerId) {
        const formModificacion = document.getElementById('form-modificacion');
        formModificacion.classList.remove('was-validated'); 
        document.querySelectorAll('#form-modificacion input, #form-modificacion select').forEach(field => {
            field.removeAttribute('required');
        });
        const container = document.getElementById(containerId);
        container.querySelectorAll('input, select').forEach(field => {
            field.setAttribute('required', 'required');
        });
    }

    // Función para filtrar dinámicamente el select de Parroquias
    function filterParroquias(municipioId, targetSelectId, selectedParroquiaId = null) {
        const targetSelect = document.getElementById(targetSelectId);
        targetSelect.innerHTML = '';
        
        if (!municipioId || municipioId === "") {
            targetSelect.innerHTML = '<option value="">Seleccione un municipio primero</option>';
            return;
        }

        const parroquiasFiltradas = ALL_PARROQUIAS.filter(p => p.id_municipio == municipioId);

        const defaultOption = document.createElement('option');
        defaultOption.value = "";
        defaultOption.textContent = "Seleccione una parroquia";
        targetSelect.appendChild(defaultOption);

        parroquiasFiltradas.forEach(p => {
            const option = document.createElement('option');
            option.value = p.id_parroquia;
            option.textContent = p.nombre_parroquia;
            if (selectedParroquiaId && p.id_parroquia == selectedParroquiaId) {
                option.selected = true;
            }
            targetSelect.appendChild(option);
        });
    }
    
    document.addEventListener('DOMContentLoaded', function() {
        
        const modalModificacion = document.getElementById('modalModificacion');
        const formModificacion = document.getElementById('form-modificacion');

        if (modalModificacion) {
            modalModificacion.addEventListener('show.bs.modal', function (event) {
                const button = event.relatedTarget; 
                const id = button.getAttribute('data-id');
                const nombre = button.getAttribute('data-nombre');
                const type = button.getAttribute('data-type');
                
                const modalTitle = document.getElementById('modalModificacionLabel');
                const typeInput = document.getElementById('update_type_modal');
                
                document.getElementById('modal-content-municipio').style.display = 'none';
                document.getElementById('modal-content-parroquia').style.display = 'none';
                document.getElementById('modal-content-comunidad').style.display = 'none';
                
                document.getElementById('id_municipio_modal').value = '';
                document.getElementById('id_parroquia_modal').value = '';
                document.getElementById('id_comunidad_modal').value = '';

                typeInput.value = type;

                if (type === 'municipio') {
                    const munId = button.getAttribute('data-id');
                    const munNombre = button.getAttribute('data-nombre');
                    modalTitle.textContent = `Modificar Municipio: ${munNombre}`;
                    document.getElementById('modal-content-municipio').style.display = 'block';
                    document.getElementById('id_municipio_modal').value = munId; 
                    document.getElementById('nombre_municipio_modal').value = munNombre;
                    setRequiredFields('modal-content-municipio'); 

                } else if (type === 'parroquia') {
                    const parId = button.getAttribute('data-id');
                    const parNombre = button.getAttribute('data-nombre');
                    const municipioId = button.getAttribute('data-municipio-id');
                    modalTitle.textContent = `Modificar Parroquia: ${parNombre}`;
                    document.getElementById('modal-content-parroquia').style.display = 'block';
                    document.getElementById('id_parroquia_modal').value = parId; 
                    document.getElementById('nombre_parroquia_modal').value = parNombre;
                    document.getElementById('id_municipio_parroquia_modal').value = municipioId;
                    setRequiredFields('modal-content-parroquia'); 

                } else if (type === 'comunidad') {
                    const comId = button.getAttribute('data-id');
                    const comNombre = button.getAttribute('data-nombre');
                    const parroquiaId = button.getAttribute('data-parroquia-id');
                    const parroquiaData = ALL_PARROQUIAS.find(p => p.id_parroquia == parroquiaId);
                    const municipioId = parroquiaData ? parroquiaData.id_municipio : null;
                    
                    modalTitle.textContent = `Modificar Comunidad: ${comNombre}`;
                    document.getElementById('modal-content-comunidad').style.display = 'block';
                    document.getElementById('id_comunidad_modal').value = comId; 
                    document.getElementById('nombre_comunidad_modal').value = comNombre;
                    document.getElementById('id_municipio_comunidad_modal').value = municipioId;
                    filterParroquias(municipioId, 'id_parroquia_comunidad_modal', parroquiaId);
                    setRequiredFields('modal-content-comunidad'); 
                }
            });
        }

        const btnActualizar = document.getElementById('btn-actualizar-modal');
        if (btnActualizar && formModificacion) {
            btnActualizar.addEventListener('click', function(event) {
                if (formModificacion.checkValidity()) {
                    formModificacion.submit();
                } else {
                    formModificacion.classList.add('was-validated'); 
                    formModificacion.reportValidity(); 
                }
            });
        } 
        
        const eliminaModal = document.getElementById('eliminaModal');
        if (eliminaModal) {
            eliminaModal.addEventListener('shown.bs.modal', event => {
                const button = event.relatedTarget;
                const url = button.getAttribute('data-bs-href');
                eliminaModal.querySelector('.btn-ok').href = url;
            });
        }
        
        const modalComunidades = document.getElementById('modalComunidades');
        if (modalComunidades) {
            modalComunidades.addEventListener('show.bs.modal', function (event) {
                const button = event.relatedTarget; 
                const parroquiaId = button.getAttribute('data-parroquia-id');
                const parroquiaNombre = button.getAttribute('data-parroquia-nombre');
                
                const parroquiaData = ALL_PARROQUIAS.find(p => p.id_parroquia == parroquiaId);
                const municipioId = parroquiaData ? parroquiaData.id_municipio : null;
                
                document.getElementById('parroquia-name-modal').textContent = parroquiaNombre;
                
                const comunidadesFiltradas = ALL_COMUNIDADES.filter(c => c.id_parroquia == parroquiaId);

                let htmlContent = '';
                if (comunidadesFiltradas.length > 0) {
                    comunidadesFiltradas.forEach(c => {
                        const editAttributes = `
                            data-bs-toggle="modal" 
                            data-bs-target="#modalModificacion"
                            data-id="${c.id_comunidad}"
                            data-nombre="${c.nombre_comunidad}"
                            data-parroquia-id="${parroquiaId}"
                            data-municipio-id="${municipioId}"
                            data-type="comunidad"
                        `;
                        const deleteUrl = `gestion_municipios.php?action=delete_comunidad&id=${c.id_comunidad}`;
                        
                        htmlContent += `
                            <tr>
                                <td class="ps-4 text-muted">${c.id_comunidad}</td>
                                <td>${c.nombre_comunidad}</td>
                                <td class="text-end pe-4">
                                    <div class="btn-group">
                                        <a href="#" ${editAttributes}
                                            class="btn btn-light btn-sm text-primary" title="Modificar">
                                            <i class="fa-solid fa-pen"></i> 
                                        </a>
                                        <a href="#" 
                                            data-bs-href="${deleteUrl}" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#eliminaModal" 
                                            class="btn btn-light btn-sm text-danger" 
                                            title="Eliminar">
                                            <i class="fa-solid fa-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        `;
                    });
                } else {
                    htmlContent = '<tr><td colspan="3" class="text-center text-muted p-3">No hay comunidades para esta parroquia.</td></tr>';
                }

                document.getElementById('comunidades-list-body').innerHTML = htmlContent;
                
                const handleModalToggle = () => {
                    const modalComunidadesBS = bootstrap.Modal.getInstance(modalComunidades);
                    if (modalComunidadesBS) {
                        modalComunidadesBS.hide();
                    }
                };

                document.querySelectorAll('#modalComunidades a[data-bs-target="#modalModificacion"], #modalComunidades a[data-bs-target="#eliminaModal"]').forEach(link => {
                    link.addEventListener('click', handleModalToggle);
                });
                
                const showComunidadesAgain = function() {
                    const modalComunidadesBS = new bootstrap.Modal(modalComunidades);
                    modalComunidadesBS.show();
                    eliminaModal.removeEventListener('hidden.bs.modal', showComunidadesAgain);
                };
                
                if (event.relatedTarget.closest('.btn-comunidades')) {
                    eliminaModal.addEventListener('hidden.bs.modal', showComunidadesAgain);
                }
            });
        }
    }); 
</script>