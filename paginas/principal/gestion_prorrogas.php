<?php
require_once '../conexion.php';

$path_to_root = "../../";
$page_title = "Gestión de Prórrogas y Ventas";
require_once '../includes/layout_head.php';
require_once '../includes/sidebar.php';

// Obtener planes para el select
$planes = $conn->query("SELECT id_plan, nombre_plan FROM planes ORDER BY nombre_plan ASC");
$municipios = $conn->query("SELECT id_municipio, nombre_municipio FROM municipio ORDER BY nombre_municipio ASC");
$metodos_pago = ["TRANSFERENCIA", "PAGO MOVIL", "EFECTIVO (DOLARES)", "EFECTIVO (BOLIVARES)", "ZELLE", "RESERVE", "BINANCE"];
?>

<link href="<?php echo $path_to_root; ?>css/datatables.min.css" rel="stylesheet">
<style>
    .form-section-title {
        font-size: 0.85rem;
        background: #f8f9fa;
        padding: 5px 15px;
        border-radius: 5px;
        margin-bottom: 20px;
        color: #6c757d;
        border-left: 4px solid #0d6efd;
    }

    .modal-header-sales {
        background: linear-gradient(135deg, #0d6efd 0%, #0043a8 100%);
    }

    .modal-header-internal {
        background: linear-gradient(135deg, #6c757d 0%, #343a40 100%);
    }

    .dropzone-area {
        border: 2px dashed #dee2e6;
        padding: 30px;
        text-align: center;
        border-radius: 10px;
        cursor: pointer;
        transition: all 0.3s;
    }

    .dropzone-area:hover {
        border-color: #0d6efd;
        background: #f0f7ff;
    }
</style>

<main class="main-content">
    <?php include '../includes/header.php'; ?>

    <div class="page-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold text-primary mb-1">Centro de Prórrogas y Ventas</h4>
                <p class="text-muted small mb-0">Gestione solicitudes internas de prórroga y nuevos contratos de venta.
                </p>
            </div>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-dark shadow-sm" data-bs-toggle="modal"
                    data-bs-target="#modalInternal">
                    <i class="fa-solid fa-user-clock me-1"></i> Nueva Prórroga
                </button>
                <button type="button" class="btn btn-primary shadow-sm" data-bs-toggle="modal"
                    data-bs-target="#modalSales">
                    <i class="fa-solid fa-file-signature me-1"></i> Nueva Venta
                </button>
                <div class="vr mx-1"></div>
                <button type="button" class="btn btn-success shadow-sm" onclick="exportExcel()">
                    <i class="fa-solid fa-file-excel me-1"></i> Exportar
                </button>
                <button type="button" class="btn btn-outline-success shadow-sm" data-bs-toggle="modal"
                    data-bs-target="#modalImportExcel">
                    <i class="fa-solid fa-file-import me-1"></i> Importar
                </button>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="table-responsive">
                    <table id="tabla_prorrogas" class="table table-hover align-middle w-100">
                        <thead class="bg-light">
                            <tr>
                                <th>Fecha</th>
                                <th>Tipo</th>
                                <th>Cédula</th>
                                <th>Nombre</th>
                                <th>Estado</th>
                                <th class="text-end">Acciones</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Modal Interno: Galanet-Prórroga -->
<div class="modal fade" id="modalInternal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header modal-header-internal text-white">
                <div>
                    <h5 class="modal-title fw-bold">Galanet-Prórroga</h5>
                    <small class="opacity-75">Solicitud Interna de Extensión</small>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="formInternal" action="guardar_prorroga.php" method="POST">
                <input type="hidden" name="tipo_solicitud" value="PRORROGA">
                <div class="modal-body p-4">
                    <div class="alert alert-info border-0 shadow-sm small py-2 mb-4">
                        <i class="fa-solid fa-info-circle me-2"></i>
                        Los datos solicitados serán utilizados para cargar la solicitud de prórroga. Asegúrese de
                        ingresar la información exacta del titular.
                    </div>

                    <h6 class="form-section-title fw-bold text-uppercase">Datos del Titular del Servicio</h6>

                    <div class="mb-3 position-relative">
                        <label class="form-label small fw-bold">Vincular Contrato Existente (Opcional)</label>
                        <input type="text" id="search_contrato_internal" class="form-control form-control-sm"
                            placeholder="Buscar por Nombre o ID...">
                        <input type="hidden" name="id_contrato_asociado" id="id_contrato_internal">
                        <div id="results_contrato_internal" class="list-group position-absolute w-100 shadow-sm"
                            style="z-index:1000"></div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Cédula de Identidad *</label>
                        <input type="text" name="cedula_titular" class="form-control" placeholder="Ejemplo: 12345678"
                            required pattern="[0-9]+">
                        <div class="form-text xsmall text-danger">⚠️ Colocar la cédula sin espacios ni puntos "." solo
                            números.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Nombres y Apellidos *</label>
                        <input type="text" name="nombre_titular" class="form-control" placeholder="Titular del Servicio"
                            required>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-12">
                            <label class="form-label small fw-bold">Fecha del Corte *</label>
                            <input type="date" name="fecha_corte" class="form-control" required>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-bold">¿Existe en SAEPLUS? *</label>
                            <select name="existe_saeplus" class="form-select" required>
                                <option value="SI">SÍ</option>
                                <option value="NO" selected>NO</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold">¿La Prórroga es Regular? *</label>
                            <select name="prorroga_regular" class="form-select" required>
                                <option value="SI" selected>SÍ</option>
                                <option value="NO">NO</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0">
                    <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-dark px-4 shadow">Registrar Prórroga</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Ventas: Galanet-Ventas -->
<div class="modal fade" id="modalSales" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header modal-header-sales text-white">
                <div>
                    <h5 class="modal-title fw-bold">Galanet-Ventas</h5>
                    <small class="opacity-75">Carga de Nueva Firma / Solicitud de Venta</small>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="formSales" action="guardar_prorroga.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="tipo_solicitud" value="VENTA">
                <div class="modal-body p-4">
                    <div class="alert alert-primary border-0 shadow-sm small py-2 mb-4">
                        <i class="fa-solid fa-circle-check me-2"></i>
                        Por favor, asegúrese de ingresar la información exacta. Si posee más de un servicio, llene el
                        formulario por cada uno.
                    </div>

                    <div class="row">
                        <!-- Columna Izquierda: Datos del Titular -->
                        <div class="col-md-6 border-end">
                            <h6 class="form-section-title fw-bold text-uppercase">Datos del Titular</h6>

                            <div class="mb-3">
                                <label class="form-label small fw-bold">Cédula de Identidad *</label>
                                <input type="text" name="cedula_titular" class="form-control" placeholder="Ej: 12345678"
                                    required pattern="[0-9]+">
                            </div>

                            <div class="mb-3">
                                <label class="form-label small fw-bold">Nombres y Apellidos Completos *</label>
                                <input type="text" name="nombre_titular" class="form-control" required>
                            </div>

                            <div class="row g-2 mb-3">
                                <div class="col-6">
                                    <label class="form-label small fw-bold">Teléfono *</label>
                                    <input type="text" name="telefono" class="form-control" required>
                                </div>
                                <div class="col-6">
                                    <label class="form-label small fw-bold">Teléfono Extra</label>
                                    <input type="text" name="telefono_extra" class="form-control">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label small fw-bold">Correo Electrónico</label>
                                <input type="email" name="email" class="form-control">
                            </div>

                            <div class="row g-2 mb-3">
                                <div class="col-6">
                                    <label class="form-label small fw-bold">Municipio *</label>
                                    <select name="id_municipio" class="form-select" required>
                                        <option value="">Seleccione...</option>
                                        <?php while ($m = $municipios->fetch_assoc()): ?>
                                            <option value="<?= $m['id_municipio'] ?>">
                                                <?= $m['nombre_municipio'] ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                                <div class="col-6">
                                    <label class="form-label small fw-bold">Parroquia *</label>
                                    <select name="id_parroquia" class="form-select" required>
                                        <option value="">Seleccione...</option>
                                    </select>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label small fw-bold">Dirección Completa *</label>
                                <textarea name="direccion" class="form-control" rows="2"
                                    placeholder="Dirección con punto de referencia" required></textarea>
                            </div>
                        </div>

                        <!-- Columna Derecha: Datos Contrato/Servicio -->
                        <div class="col-md-6">
                            <h6 class="form-section-title fw-bold text-uppercase">Datos del Servicio</h6>

                            <div class="mb-3">
                                <label class="form-label small fw-bold">Plan de Internet *</label>
                                <select name="id_plan" class="form-select" required>
                                    <option value="">Seleccione...</option>
                                    <?php while ($p = $planes->fetch_assoc()): ?>
                                        <option value="<?= $p['id_plan'] ?>">
                                            <?= $p['nombre_plan'] ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>

                            <div class="row g-2 mb-3">
                                <div class="col-6">
                                    <label class="form-label small fw-bold">Fecha de Corte *</label>
                                    <input type="date" name="fecha_corte" class="form-control" required>
                                </div>
                                <div class="col-6">
                                    <label class="form-label small fw-bold">Firma del Contrato *</label>
                                    <input type="date" name="fecha_firma" class="form-control" required>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label small fw-bold">Foto del Contrato *</label>
                                <input type="file" name="foto_contrato" class="form-control" accept="image/*" required>
                            </div>

                            <div class="row g-2 mb-3">
                                <div class="col-6">
                                    <label class="form-label small fw-bold">Prorateo *</label>
                                    <select name="prorateo" class="form-select" required>
                                        <option value="SI">SÍ</option>
                                        <option value="NO">NO</option>
                                    </select>
                                </div>
                                <div class="col-6">
                                    <label class="form-label small fw-bold">Método de Pago</label>
                                    <select name="metodo_pago" class="form-select">
                                        <option value="">Seleccione...</option>
                                        <?php foreach ($metodos_pago as $m): ?>
                                            <option value="<?= $m ?>">
                                                <?= $m ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label small fw-bold">Fecha de Instalación / Estado</label>
                                <div class="input-group">
                                    <input type="date" name="fecha_instalacion" class="form-control">
                                    <input type="text" name="estado_venta" class="form-control" placeholder="Estado">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0">
                    <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Cerrar</button>
                    <button type="submit" class="btn btn-primary px-4 shadow">Cargar Venta</button>
                </div>
            </form>
        </div>
    </div>
</div>

</div>

<!-- Modal Procesar Pago -->
<div class="modal fade" id="modalProcesarPago" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title fw-bold">Convertir en Pago</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="procesar_prorroga_pago.php" method="POST">
                <input type="hidden" name="id_prorroga" id="pago_id_prorroga">
                <div class="modal-body p-4">
                    <div class="text-center mb-4">
                        <i class="fa-solid fa-money-bill-transfer fa-3x text-success mb-3"></i>
                        <h6 class="text-muted small fw-bold text-uppercase mb-1">Solicitud de <span
                                id="pago_tipo"></span></h6>
                        <h5 class="fw-bold" id="pago_nombre_titular"></h5>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Monto a Cobrar ($) *</label>
                        <input type="number" step="0.01" name="monto"
                            class="form-control form-control-lg fw-bold text-success" required placeholder="0.00">
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Referencia de Pago *</label>
                        <input type="text" name="referencia" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Banco / Cuenta *</label>
                        <select name="id_banco" class="form-select" required>
                            <option value="">Seleccione...</option>
                            <?php
                            $b = $conn->query("SELECT id_banco, nombre_banco FROM bancos");
                            while ($r = $b->fetch_assoc())
                                echo "<option value='" . $r['id_banco'] . "'>" . $r['nombre_banco'] . "</option>";
                            ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Justificación / Nota</label>
                        <textarea name="nota" class="form-control" rows="2"
                            placeholder="Ej: Pago de prórroga aprobada"></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    <button type="submit" class="btn btn-success px-4">Confirmar y Registrar Pago</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Importar Excel -->
<div class="modal fade" id="modalImportExcel" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title fw-bold"><i class="fa-solid fa-file-import me-2"></i>Importar desde Excel</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4 text-center">
                <div id="dropzone-excel" class="dropzone-area mb-3">
                    <i class="fa-solid fa-file-circle-plus fa-3x text-success mb-3"></i>
                    <h6>Arrastra tu archivo Excel aquí</h6>
                    <p class="text-muted small">O haz clic para seleccionar (Formato .xlsx, .xls)</p>
                    <input type="file" id="input-excel-import" accept=".xlsx, .xls" style="display: none;">
                </div>
                <div id="import-preview" style="display:none;">
                    <div class="alert alert-info py-2 small mb-0">
                        <i class="fa-solid fa-info-circle me-1"></i>
                        Se han detectado <span id="import-count" class="fw-bold">0</span> registros listos para
                        procesar.
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light border-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" id="btn-confirm-import" class="btn btn-success px-4" disabled>Procesar
                    Importación</button>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/layout_foot.php'; ?>
<script src="<?php echo $path_to_root; ?>js/jquery.min.js"></script>
<script src="<?php echo $path_to_root; ?>js/datatables.min.js"></script>
<script src="https://cdn.sheetjs.com/xlsx-0.19.3/package/dist/xlsx.full.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/exceljs/4.4.0/exceljs.min.js"></script>
<script>
    $(document).ready(function () {
        var table = $('#tabla_prorrogas').DataTable({
            "ajax": "get_prorrogas_data.php",
            "columns": [
                { "data": "fecha_registro" },
                {
                    "data": "tipo_solicitud", "render": function (data) {
                        return data == 'PRORROGA' ? '<span class="badge bg-dark">Prórroga</span>' : '<span class="badge bg-primary">Venta</span>';
                    }
                },
                { "data": "cedula_titular" },
                { "data": "nombre_titular" },
                {
                    "data": "estado", "render": function (data) {
                        let badge = 'bg-warning';
                        if (data == 'PROCESADO') badge = 'bg-success';
                        if (data == 'RECHAZADO') badge = 'bg-danger';
                        return `<span class="badge ${badge}">${data}</span>`;
                    }
                },
                {
                    "data": "id_prorroga", "className": "text-end", "render": function (id, type, row) {
                        return `<div class="btn-group">
                            ${row.estado == 'PENDIENTE' ? `<button class="btn btn-sm btn-outline-success" title="Procesar Pago" onclick="procesarPago(${id})"><i class="fa-solid fa-dollar-sign"></i></button>` : ''}
                            <button class="btn btn-sm btn-outline-danger" title="Eliminar" onclick="eliminarProrroga(${id})"><i class="fa-solid fa-trash"></i></button>
                        </div>`;
                    }
                }
            ],
            "order": [[0, "desc"]],
            "language": { "url": "<?php echo $path_to_root; ?>js/es-ES.json" }
        });

        $('select[name="id_municipio"]').change(function () {
            let id = $(this).val();
            if (id) {
                $.get('get_parroquias.php?id_municipio=' + id, function (data) {
                    $('select[name="id_parroquia"]').html(data);
                });
            }
        });

        // Autocomplete para el Modal Interno
        const searchInt = document.getElementById('search_contrato_internal');
        const resultsInt = document.getElementById('results_contrato_internal');
        const hiddenInt = document.getElementById('id_contrato_internal');

        if (searchInt) {
            let timer;
            searchInt.addEventListener('input', function () {
                clearTimeout(timer);
                const q = this.value.trim();
                if (q.length < 3) { resultsInt.innerHTML = ''; return; }
                timer = setTimeout(() => {
                    fetch(`buscar_contratos.php?q=${encodeURIComponent(q)}`)
                        .then(r => r.json())
                        .then(data => {
                            resultsInt.innerHTML = '';
                            data.forEach(c => {
                                const a = document.createElement('a');
                                a.className = 'list-group-item list-group-item-action small';
                                a.innerHTML = `<strong>ID ${c.id}</strong>: ${c.nombre_completo}`;
                                a.onclick = (e) => {
                                    e.preventDefault();
                                    searchInt.value = c.nombre_completo;
                                    hiddenInt.value = c.id;
                                    // Auto-llenar campos
                                    document.querySelector('#formInternal input[name="cedula_titular"]').value = c.cedula;
                                    document.querySelector('#formInternal input[name="nombre_titular"]').value = c.nombre_completo;
                                    resultsInt.innerHTML = '';
                                };
                                resultsInt.appendChild(a);
                            });
                        });
                }, 300);
            });
        }
    });

    function eliminarProrroga(id) {
        Swal.fire({
            title: '¿Estás seguro?',
            text: "Esta acción no se puede deshacer.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                fetch('eliminar_prorroga.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: id })
                })
                .then(r => r.json())
                .then(res => {
                    if (res.success) {
                        Swal.fire('Eliminado', res.message, 'success');
                        $('#tabla_prorrogas').DataTable().ajax.reload();
                    } else {
                        Swal.fire('Error', res.message, 'error');
                    }
                })
                .catch(err => {
                    console.error(err);
                    Swal.fire('Error', 'No se pudo conectar con el servidor', 'error');
                });
            }
        });
    }

    function procesarPago(id) {
        // Abrir modal de pago con los datos de la prórroga
        fetch(`get_prorroga_detalle.php?id=${id}`)
            .then(r => r.json())
            .then(data => {
                $('#pago_id_prorroga').val(data.id_prorroga);
                $('#pago_nombre_titular').text(data.nombre_titular);
                $('#pago_tipo').text(data.tipo_solicitud);
                $('#modalProcesarPago').modal('show');
            });
    }

    async function exportExcel() {
        fetch('get_prorrogas_data.php')
            .then(r => r.json())
            .then(async res => {
                const data = res.data.filter(r => r.tipo_solicitud === 'PRORROGA');

                const workbook = new ExcelJS.Workbook();
                const worksheet = workbook.addWorksheet('Prórrogas');

                // 1. Título y Mes (Fila 1)
                const monthName = new Intl.DateTimeFormat('es-ES', { month: 'long' }).format(new Date()).toUpperCase();
                worksheet.mergeCells('A1:F1');
                const titleCell = worksheet.getCell('A1');
                titleCell.value = "Galanet-Prórroga " + new Date().getFullYear();
                titleCell.font = { name: 'Arial', family: 4, size: 14, bold: true };
                titleCell.alignment = { vertical: 'middle', horizontal: 'left' };

                const monthCell = worksheet.getCell('G1');
                monthCell.value = monthName;
                monthCell.font = { name: 'Arial', family: 4, size: 12, bold: true };
                monthCell.alignment = { vertical: 'middle', horizontal: 'right' };

                // 2. Cabeceras (Fila 2)
                const headers = ["CEDULA", "NOMBRE", "SAEPLUS", "CORTE", "PRORROGA", "REGULAR?", "CARGADO"];
                const headerRow = worksheet.getRow(2);
                headerRow.values = headers;
                headerRow.font = { bold: true, color: { argb: 'FFFFFFFF' } };
                headerRow.alignment = { vertical: 'middle', horizontal: 'center' };

                headerRow.eachCell((cell) => {
                    cell.fill = {
                        type: 'pattern',
                        pattern: 'solid',
                        fgColor: { argb: 'FF1E4D2B' } // Verde oscuro tipo imagen
                    };
                    cell.border = {
                        top: { style: 'thin' },
                        left: { style: 'thin' },
                        bottom: { style: 'thin' },
                        right: { style: 'thin' }
                    };
                });

                // 3. Filas de Datos
                data.forEach((r, index) => {
                    const row = worksheet.addRow([
                        r.cedula_titular || '',
                        r.nombre_titular || '',
                        r.existe_saeplus || 'NO',
                        r.fecha_corte ? new Date(r.fecha_corte + ' 00:00:00').getDate() : '',
                        r.dia_prorroga || '',
                        r.prorroga_regular || 'SI',
                        r.estado || 'PENDIENTE'
                    ]);

                    row.eachCell((cell, colNumber) => {
                        cell.border = {
                            top: { style: 'thin' },
                            left: { style: 'thin' },
                            bottom: { style: 'thin' },
                            right: { style: 'thin' }
                        };

                        // Colores por estado en la última columna
                        if (colNumber === 7) {
                            const status = (r.estado || 'PENDIENTE').toUpperCase();
                            if (status === 'PROCESADO') {
                                cell.font = { color: { argb: 'FF157347' }, bold: true }; // Verde éxito
                            } else if (status === 'RECHAZADO') {
                                cell.font = { color: { argb: 'FFBB2D3B' }, bold: true }; // Rojo error
                            } else {
                                cell.font = { color: { argb: 'FF6C757D' }, bold: true }; // Gris pendiente
                            }
                        }
                    });
                });

                // Configurar anchos de columna
                worksheet.getColumn(1).width = 15; // CEDULA
                worksheet.getColumn(2).width = 45; // NOMBRE
                worksheet.getColumn(3).width = 10; // SAEPLUS
                worksheet.getColumn(4).width = 10; // CORTE
                worksheet.getColumn(5).width = 12; // PRORROGA
                worksheet.getColumn(6).width = 12; // REGULAR?
                worksheet.getColumn(7).width = 15; // CARGADO

                // Descargar archivo
                const buffer = await workbook.xlsx.writeBuffer();
                const blob = new Blob([buffer], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' });
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = "Galanet-Prorroga_" + new Date().getFullYear() + ".xlsx";
                a.click();
                window.URL.revokeObjectURL(url);
            })
            .catch(err => {
                console.error(err);
                alert('Error al generar el Excel con estilos');
            });
    }

    // Lógica de Importación de Excel
    const dropzone = document.getElementById('dropzone-excel');
    const inputExcel = document.getElementById('input-excel-import');
    let dataToImport = [];

    dropzone.onclick = () => inputExcel.click();

    inputExcel.onchange = (e) => {
        const file = e.target.files[0];
        if (file) handleExcelFile(file);
    };

    dropzone.ondragover = (e) => { e.preventDefault(); dropzone.classList.add('bg-light', 'border-primary'); };
    dropzone.ondragleave = () => dropzone.classList.remove('bg-light', 'border-primary');
    dropzone.ondrop = (e) => {
        e.preventDefault();
        dropzone.classList.remove('bg-light', 'border-primary');
        const file = e.dataTransfer.files[0];
        if (file) handleExcelFile(file);
    };

    function handleExcelFile(file) {
        const reader = new FileReader();
        reader.onload = (e) => {
            const data = new Uint8Array(e.target.result);
            const workbook = XLSX.read(data, { type: 'array' });
            const sheet = workbook.Sheets[workbook.SheetNames[0]];

            // Buscar la cabecera real
            const rows = XLSX.utils.sheet_to_json(sheet, { header: 1 });
            let headerIdx = -1;
            for (let i = 0; i < Math.min(rows.length, 10); i++) {
                if (rows[i].some(c => String(c).toUpperCase().includes('CEDULA'))) {
                    headerIdx = i;
                    break;
                }
            }

            if (headerIdx === -1) {
                alert('No se encontró la columna CEDULA en las primeras filas.');
                return;
            }

            const json = XLSX.utils.sheet_to_json(sheet, { range: headerIdx });
            dataToImport = json.map(row => {
                // Mapear nombres de columnas insensibles a mayúsculas
                const mapped = {};
                Object.keys(row).forEach(k => {
                    const key = k.toUpperCase().trim();
                    if (key.includes('CEDULA')) mapped.cedula = row[k];
                    if (key.includes('NOMBRE')) mapped.nombre = row[k];
                    if (key.includes('SAEPLUS')) mapped.saeplus = row[k];
                    if (key.includes('CORTE')) mapped.corte = row[k];
                    if (key.includes('PRORROGA')) mapped.prorroga = row[k];
                    if (key.includes('REGULAR')) mapped.regular = row[k];
                });
                return mapped;
            }).filter(r => r.cedula && r.nombre);

            if (dataToImport.length > 0) {
                document.getElementById('import-preview').style.display = 'block';
                document.getElementById('import-count').innerText = dataToImport.length;
                document.getElementById('btn-confirm-import').disabled = false;
            }
        };
        reader.readAsArrayBuffer(file);
    }

    document.getElementById('btn-confirm-import').onclick = function () {
        this.disabled = true;
        this.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-2"></i>Procesando...';

        fetch('importar_prorrogas_excel.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ data: dataToImport })
        })
            .then(r => r.json())
            .then(res => {
                if (res.success) {
                    alert('Importación completada: ' + res.imported + ' registros nuevos.');
                    location.reload();
                } else {
                    alert('Error: ' + res.message);
                    this.disabled = false;
                    this.innerHTML = 'Procesar Importación';
                }
            })
            .catch(err => {
                console.error(err);
                alert('Error de conexión');
                this.disabled = false;
                this.innerHTML = 'Procesar Importación';
            });
    };
</script>