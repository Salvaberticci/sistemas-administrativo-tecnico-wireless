<?php
/**
 * Gestión de Instaladores
 */
require_once 'conexion.php';

$path_to_root = "../";
$page_title = "Gestión de Instaladores";
require_once 'includes/layout_head.php';
require_once 'includes/sidebar.php';
?>

<link href="<?php echo $path_to_root; ?>css/datatables.min.css" rel="stylesheet">

<main class="main-content">
    <?php include 'includes/header.php'; ?>

    <div class="page-content">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center bg-white border-bottom-0 pt-4 px-4">
                <div>
                    <h5 class="fw-bold text-primary mb-1">Instaladores</h5>
                    <p class="text-muted small mb-0">Gestión de técnicos instaladores</p>
                </div>
                <a href="registro_instaladores.php" class="btn btn-primary d-flex align-items-center gap-2">
                    <i class="fa-solid fa-plus"></i> <span class="d-none d-md-inline">Nuevo Instalador</span>
                </a>
            </div>

            <div class="card-body px-4">
                <div class="table-responsive">
                    <table class="display table table-hover w-100" id="tabla_instaladores">
                        <thead class="bg-light">
                            <tr>
                                <th>ID</th>
                                <th>Nombre</th>
                                <th>Teléfono</th>
                                <th>Estado</th>
                                <th>Fecha Registro</th>
                                <th width="10%">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $sql = "SELECT * FROM instaladores ORDER BY nombre_instalador ASC";
                            $result = $conn->query($sql);
                            
                            while ($row = $result->fetch_assoc()) {
                                $estado_badge = $row['activo'] ? 
                                    '<span class="badge bg-success">Activo</span>' : 
                                    '<span class="badge bg-secondary">Inactivo</span>';
                                    
                                echo "<tr>
                                    <td>{$row['id_instalador']}</td>
                                    <td class='fw-bold'>{$row['nombre_instalador']}</td>
                                    <td>{$row['telefono']}</td>
                                    <td>{$estado_badge}</td>
                                    <td>" . date('d/m/Y', strtotime($row['fecha_registro'])) . "</td>
                                    <td>
                                        <div class='d-flex gap-1'>
                                            <a href='editar_instalador.php?id={$row['id_instalador']}' class='btn btn-sm btn-outline-primary' title='Editar'>
                                                <i class='fa-solid fa-pen'></i>
                                            </a>
                                            <button class='btn btn-sm btn-outline-danger' onclick='confirmarEliminar({$row['id_instalador']})' title='Eliminar'>
                                                <i class='fa-solid fa-trash'></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="d-flex justify-content-center mt-4">
                    <a href="menu.php" class="btn btn-outline-secondary">
                        <i class="fa-solid fa-arrow-left me-2"></i> Volver al Menú
                    </a>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Modal Eliminar -->
<div class="modal fade" id="eliminaModal" tabindex="-1">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body text-center p-4">
                <div class="mb-3 text-danger"><i class="fa-solid fa-trash-can fa-3x"></i></div>
                <h5 class="fw-bold">Eliminar Instalador</h5>
                <p class="text-muted small">¿Confirma eliminar este instalador?</p>
                <div class="d-flex justify-content-center gap-2 mt-3">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <a class="btn btn-danger btn-ok text-white">Eliminar</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/layout_foot.php'; ?>

<script src="<?php echo $path_to_root; ?>js/jquery.min.js"></script>
<script src="<?php echo $path_to_root; ?>js/datatables.min.js"></script>

<script>
$(document).ready(function() {
    $('#tabla_instaladores').DataTable({
        "language": {
            "lengthMenu": "Mostrar _MENU_",
            "zeroRecords": "No hay instaladores registrados",
            "info": "_START_ - _END_ de _TOTAL_",
            "search": "Buscar:",
            "paginate": { "next": ">", "previous": "<" }
        }
    });
});

function confirmarEliminar(id) {
    var url = 'eliminar_instalador.php?id=' + id;
    var modalEl = document.getElementById('eliminaModal');
    modalEl.querySelector('.btn-ok').href = url;
    var modal = new bootstrap.Modal(modalEl);
    modal.show();
}
</script>
