<?php
// paginas/soporte/historial_soportes.php
// Listado de soportes técnicos realizados

$path_to_root = "../../";
include_once $path_to_root . 'paginas/conexion.php';
include $path_to_root . 'paginas/includes/layout_head.php';
include $path_to_root . 'paginas/includes/sidebar.php';
include $path_to_root . 'paginas/includes/header.php';
?>

<main class="main-content">
    <div class="page-content">
        <div class="container-fluid">
            <!-- Header -->
            <div class="row mb-4">
                <div class="col-12 d-flex justify-content-between align-items-center">
                    <div>
                        <h2 class="h4 fw-bold mb-1 text-primary">Historial de Soportes</h2>
                        <p class="text-muted mb-0">Registro de trabajos realizados.</p>
                    </div>
                    <a href="registro_soporte.php" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i> Nuevo Soporte
                    </a>
                </div>
            </div>

            <!-- Alertas -->
            <?php if (isset($_GET['status'])): ?>
                <div class="alert alert-<?php echo $_GET['status'] == 'success' ? 'success' : 'danger'; ?> alert-dismissible fade show" role="alert">
                    <?php echo isset($_GET['msg']) ? htmlspecialchars($_GET['msg']) : ''; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="card border-0 shadow-sm">
                <div class="card-body p-0">
                    <div class="table-responsive p-4">
                        <table id="tablaSoportes" class="display table table-striped table-bordered w-100">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Fecha</th>
                                    <th>Cliente</th>
                                    <th>Descripción</th>
                                    <th>Técnico</th>
                                    <th>Total ($)</th>
                                    <th>Pagado ($)</th>
                                    <th>Estado Pago</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>
</main>

<!-- Modal Abonar -->
<div class="modal fade" id="modalAbonar" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form action="procesar_abono.php" method="POST" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Registrar Abono</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="id_soporte" id="id_soporte_abono">
                <p>Abonar al Soporte <strong>#<span id="txt_id_soporte"></span></strong></p>
                <div class="mb-3">
                    <label class="form-label">Deuda Actual: $<span id="txt_deuda_actual"></span></label>
                </div>
                <div class="mb-3">
                    <label for="monto_abono" class="form-label fw-bold">Monto a Abonar ($)</label>
                    <input type="number" step="0.01" min="0.01" class="form-control" name="monto_abono" id="monto_abono" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-success">Registrar Pago</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Editar -->
<div class="modal fade" id="modalEditar" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form action="actualizar_soporte.php" method="POST" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Editar Soporte</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="id_soporte_edit" id="id_soporte_edit">
                
                <div class="mb-3">
                    <label for="fecha_edit" class="form-label">Fecha</label>
                    <input type="date" class="form-control" name="fecha_edit" id="fecha_edit" required>
                </div>
                <!-- Nota: El formato de fecha de entrada debe ser YYYY-MM-DD. El DataTables muestra DD/MM/YYYY. Habrá que convertir en JS. -->

                <div class="mb-3">
                    <label for="tecnico_edit" class="form-label">Técnico</label>
                    <input type="text" class="form-control" name="tecnico_edit" id="tecnico_edit" required>
                </div>

                <div class="mb-3">
                    <label for="descripcion_edit" class="form-label">Descripción</label>
                    <textarea class="form-control" name="descripcion_edit" id="descripcion_edit" rows="3" required></textarea>
                </div>

                <div class="mb-3">
                    <label for="monto_total_edit" class="form-label fw-bold">Monto Total ($)</label>
                    <input type="number" step="0.01" min="0" class="form-control" name="monto_total_edit" id="monto_total_edit" required>
                    <div class="form-text text-muted">Nota: Al modificar el total, la deuda del cliente se recalculará automáticamente.</div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary">Guardar Cambios</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Eliminar -->
<div class="modal fade" id="modalEliminar" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form action="eliminar_soporte.php" method="POST" class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">Eliminar Soporte</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="id_soporte_eliminar" id="id_soporte_eliminar">
                <p class="fw-bold">¿Estás seguro de eliminar el soporte #<span id="txt_id_eliminar"></span>?</p>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i> <strong>Advertencia:</strong> Esta acción también eliminará cualquier deuda o cobro asociado a este soporte en el módulo de cobranzas. Esta acción es irreversible.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-danger">Confirmar Eliminación</button>
            </div>
        </form>
    </div>
</div>

<script src="<?php echo $path_to_root; ?>js/bootstrap.bundle.min.js"></script>
<script src="<?php echo $path_to_root; ?>js/jquery.min.js"></script>
<script src="<?php echo $path_to_root; ?>js/datatables.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.4/moment.min.js"></script>
<script>
$(document).ready(function() {
    $('#tablaSoportes').DataTable({
        "order": [[ 0, "desc" ]],
        "bProcessing": true,
        "bServerSide": true,
        "sAjaxSource": "server_process_soportes.php", 
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json"
        },
        "aoColumnDefs": [
            { "mData": 0, "aTargets": [0] }, // ID
            { "mData": 1, "aTargets": [1] }, // Fecha
            { "mData": 2, "aTargets": [2] }, // Cliente
            { "mData": 3, "aTargets": [3] }, // Descripción
            { "mData": 4, "aTargets": [4] }, // Técnico
            { 
                "mData": 5, 
                "aTargets": [5],
                "mRender": function(data, type, row) {
                    return '$' + parseFloat(data).toFixed(2);
                }
            }, // Total
            { 
                "mData": 6, 
                "aTargets": [6],
                "mRender": function(data, type, row) {
                    return '$' + parseFloat(data).toFixed(2);
                }
            }, // Pagado
            { 
                "mData": 7, // Deuda / Estado
                "aTargets": [7],
                "mRender": function(data, type, row) {
                    var total = parseFloat(row[5]);
                    var pagado = parseFloat(row[6]);
                    
                    if (pagado >= (total - 0.01)) {
                        return '<span class="badge bg-success">Pagado</span>';
                    } else {
                        var deuda = total - pagado;
                        return '<span class="badge bg-danger">Debe: $' + deuda.toFixed(2) + '</span>';
                    }
                }
            },
            {
                "mData": null, // Acciones
                "aTargets": [8],
                "bSortable": false,
                "mRender": function(data, type, row) {
                    var id = row[0];
                    var fecha = row[1]; // DD/MM/YYYY
                    var descripcion = row[3];
                    var tecnico = row[4];
                    var total = parseFloat(row[5]);
                    var pagado = parseFloat(row[6]);
                    var deuda = total - pagado;
                    
                    var btnEdit = `<button type="button" class="btn btn-sm btn-warning me-1" title="Editar"
                        onclick="abrirEditar('${id}', '${fecha}', '${descripcion}', '${tecnico}', '${total}')">
                        <i class="fas fa-edit"></i></button>`;
                    
                    var btnPay = '';
                    if (deuda > 0.01) {
                        btnPay = `<button type="button" class="btn btn-sm btn-success me-1" title="Abonar"
                            onclick="abrirAbonar('${id}', '${deuda.toFixed(2)}')">
                            <i class="fas fa-dollar-sign"></i></button>`;
                    }

                    var btnDel = `<button type="button" class="btn btn-sm btn-danger" title="Eliminar"
                        onclick="abrirEliminar('${id}')">
                        <i class="fas fa-trash-alt"></i></button>`;

                    return '<div class="d-flex justify-content-center">' + btnEdit + btnPay + btnDel + '</div>';
                }
            }
        ]
    });
});

function abrirAbonar(id, deuda) {
    $('#id_soporte_abono').val(id);
    $('#txt_id_soporte').text(id);
    $('#txt_deuda_actual').text(deuda);
    $('#monto_abono').attr('max', deuda); // No permitir pagar más de la deuda
    var modal = new bootstrap.Modal(document.getElementById('modalAbonar'));
    modal.show();
}

function abrirEditar(id, fecha, descripcion, tecnico, total) {
    // Convertir fecha de DD/MM/YYYY a YYYY-MM-DD para el input date
    var parts = fecha.split('/');
    var fechaISO = '';
    if (parts.length === 3) {
        fechaISO = parts[2] + '-' + parts[1] + '-' + parts[0];
    }

    $('#id_soporte_edit').val(id);
    $('#fecha_edit').val(fechaISO);
    $('#tecnico_edit').val(tecnico);
    $('#descripcion_edit').val(descripcion);
    $('#monto_total_edit').val(total); // Asignar el total actual
    
    var modal = new bootstrap.Modal(document.getElementById('modalEditar'));
    modal.show();
}

function abrirEliminar(id) {
    $('#id_soporte_eliminar').val(id);
    $('#txt_id_eliminar').text(id);
    var modal = new bootstrap.Modal(document.getElementById('modalEliminar'));
    modal.show();
}
</script>
</body>
</html>
