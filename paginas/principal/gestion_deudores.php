<?php
/**
 * Gestión de Clientes Deudores
 */
require_once '../conexion.php';

$path_to_root = "../../";
$page_title = "Clientes Deudores";
$breadcrumb = ["Cobranzas"];
$back_url = "../menu.php";
require_once '../includes/layout_head.php';
require_once '../includes/sidebar.php';
?>

<link href="<?php echo $path_to_root; ?>css/datatables.min.css" rel="stylesheet">

<main class="main-content">
    <?php include '../includes/header.php'; ?>

    <div class="page-content">
        <div class="card">
            <div
                class="card-header d-flex justify-content-between align-items-center bg-white border-bottom-0 pt-4 px-4">
                <div>
                    <h5 class="fw-bold text-danger mb-1">Clientes Deudores</h5>
                    <p class="text-muted small mb-0">Listado de clientes con saldos pendientes</p>
                </div>
            </div>

            <div class="card-body px-4">
                <div class="table-responsive">
                    <table class="display table table-hover w-100" id="tabla_deudores">
                        <thead class="bg-light">
                            <tr>
                                <th>ID</th>
                                <th>Cliente</th>
                                <th>Cédula</th>
                                <th>IP</th>
                                <th>Monto Total</th>
                                <th>Monto Pagado</th>
                                <th>Saldo Pendiente</th>
                                <th>Fecha Registro</th>
                                <th>Estado</th>
                                <th width="15%">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $sql = "SELECT d.*, c.nombre_completo, c.cedula, c.ip_onu
                                    FROM clientes_deudores d
                                    INNER JOIN contratos c ON d.id_contrato = c.id
                                    WHERE d.estado = 'PENDIENTE'
                                    ORDER BY d.fecha_registro DESC";
                            $result = $conn->query($sql);

                            while ($row = $result->fetch_assoc()) {
                                $estado_badge = '<span class="badge bg-danger">PENDIENTE</span>';

                                echo "<tr>
                                    <td>{$row['id']}</td>
                                    <td class='fw-bold'>{$row['nombre_completo']}</td>
                                    <td>{$row['cedula']}</td>
                                    <td><code>{$row['ip_onu']}</code></td>
                                    <td class='text-end'>\${$row['monto_total']}</td>
                                    <td class='text-end'>\${$row['monto_pagado']}</td>
                                    <td class='text-end text-danger fw-bold'>\${$row['saldo_pendiente']}</td>
                                    <td>" . date('d/m/Y', strtotime($row['fecha_registro'])) . "</td>
                                    <td>{$estado_badge}</td>
                                    <td>
                                        <div class='d-flex gap-1'>
                                            <button class='btn btn-sm btn-success' onclick='marcarPagado({$row['id']})' title='Marcar como Pagado'>
                                                <i class='fa-solid fa-check'></i> Pagado
                                            </button>
                                            <button onclick='verContrato({$row['id_contrato']})' class='btn btn-sm btn-outline-primary' title='Ver Detalle del Contrato'>
                                                <i class='fa-solid fa-eye'></i>
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
                    <a href="../../paginas/menu.php" class="btn btn-outline-secondary">
                        <i class="fa-solid fa-arrow-left me-2"></i> Volver al Menú
                    </a>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Modal Ver Contrato Detalle -->
<div class="modal fade" id="modalVerContrato" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold"><i class="fas fa-file-contract me-2"></i>Detalle del Contrato #<span id="vc_id_contrato">---</span></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <!-- Banner Superior -->
                <div class="bg-light p-4 border-bottom">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h3 class="fw-bold text-dark mb-1" id="vc_nombre_completo">---</h3>
                            <span class="badge bg-primary px-3 py-2" id="vc_estado">ACTIVO</span>
                            <span class="ms-2 text-muted small"><i class="fas fa-id-card me-1"></i><span id="vc_cedula">---</span></span>
                        </div>
                        <div class="text-end">
                            <label class="small text-muted d-block fw-bold">Plan de Internet</label>
                            <h4 class="text-primary fw-bold mb-0" id="vc_plan">---</h4>
                        </div>
                    </div>
                </div>

                <div class="p-4">
                    <div class="row g-4">
                        <!-- Columna Contacto -->
                        <div class="col-md-6">
                            <div class="card bg-white border h-100">
                                <div class="card-body">
                                    <h6 class="fw-bold mb-3 text-secondary border-bottom pb-2"><i class="fas fa-user-circle me-2"></i>Contacto y Ubicación</h6>
                                    <div class="mb-2"><small class="text-muted d-block small fw-bold uppercase">Teléfono</small><span id="vc_telefono">---</span></div>
                                    <div class="mb-2"><small class="text-muted d-block small fw-bold uppercase">Correo</small><span id="vc_correo">---</span></div>
                                    <div class="mb-2"><small class="text-muted d-block small fw-bold uppercase">Dirección</small>
                                        <p class="small mb-0" id="vc_direccion_completa">---</p>
                                    </div>
                                    <div class="mt-2 small text-muted">
                                        <i class="fas fa-map-marker-alt me-1 text-danger"></i> <span id="vc_municipio">---</span>, <span id="vc_parroquia">---</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Columna Conexión -->
                        <div class="col-md-6">
                            <div class="card bg-white border h-100">
                                <div class="card-body">
                                    <h6 class="fw-bold mb-3 text-secondary border-bottom pb-2"><i class="fas fa-network-wired me-2"></i>Información de Red</h6>
                                    <div class="row">
                                        <div class="col-6 mb-2"><small class="text-muted d-block small fw-bold uppercase">IP ONU</small><code id="vc_ip_onu">---</code></div>
                                        <div class="col-6 mb-2"><small class="text-muted d-block small fw-bold uppercase">IP Antena/Router</small><code id="vc_ip">---</code></div>
                                        <div class="col-12 mb-2"><small class="text-muted d-block small fw-bold uppercase">Fecha Instalación</small><span id="vc_fecha_instalacion">---</span></div>
                                        <div class="col-12 mb-2"><small class="text-muted d-block small fw-bold uppercase">Vendedor</small><span id="vc_vendedor">---</span></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Sección Técnica (FTTH / RADIO) -->
                        <div class="col-12">
                            <div class="card bg-light border-0 shadow-sm" id="vc_tech_section">
                                <div class="card-body">
                                    <h6 class="fw-bold mb-3 text-dark"><i class="fas fa-tools me-2"></i>Detalles Técnicos de <span id="vc_tipo_conexion">---</span></h6>
                                    
                                    <!-- Campos FTTH -->
                                    <div id="vc_tech_ftth" class="row g-3" style="display:none;">
                                        <div class="col-md-3"><small class="text-muted d-block fw-bold small">OLT</small><span id="vc_olt">---</span></div>
                                        <div class="col-md-3"><small class="text-muted d-block fw-bold small">PON</small><span id="vc_pon">---</span></div>
                                        <div class="col-md-3"><small class="text-muted d-block fw-bold small">Caja NAP</small><span id="vc_caja">---</span></div>
                                        <div class="col-md-3"><small class="text-muted d-block fw-bold small">Puerto</small><span id="vc_puerto">---</span></div>
                                        <div class="col-md-3"><small class="text-muted d-block fw-bold small">MAC/Serial</small><code id="vc_mac">---</code></div>
                                        <div class="col-md-3"><small class="text-muted d-block fw-bold small">RX Power</small><span id="vc_rx" class="badge bg-dark">---</span></div>
                                        <div class="col-md-3"><small class="text-muted d-block fw-bold small">Distancia</small><span id="vc_distancia">---</span></div>
                                    </div>

                                    <!-- Campos RADIO -->
                                    <div id="vc_tech_radio" class="row g-3" style="display:none;">
                                        <div class="col-md-6"><small class="text-muted d-block fw-bold small">Punto de Acceso</small><span id="vc_ap">---</span></div>
                                        <div class="col-md-6"><small class="text-muted d-block fw-bold small">Señal (dBm)</small><span id="vc_signal" class="badge bg-dark">---</span></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light border-top-0">
                <button type="button" class="btn btn-dark shadow-sm px-4" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<script src="<?php echo $path_to_root; ?>js/jquery.min.js"></script>
<script src="<?php echo $path_to_root; ?>js/datatables.min.js"></script>

<script>
    $(document).ready(function () {
        $('#tabla_deudores').DataTable({
            "language": {
                "lengthMenu": "Mostrar _MENU_",
                "zeroRecords": "No hay deudores registrados",
                "info": "_START_ - _END_ de _TOTAL_",
                "search": "Buscar:",
                "paginate": { "next": ">", "previous": "<" }
            },
            "order": [[7, "desc"]] // Ordenar por fecha descendente
        });
    });

    function marcarPagado(id) {
        if (confirm('¿Confirma que este cliente ha pagado su deuda?')) {
            $.post('marcar_pagado.php', { id: id }, function (resp) {
                if (resp === 'OK') {
                    alert('Cliente marcado como pagado');
                    location.reload();
                } else {
                    alert('Error al actualizar');
                }
            });
        }
    }
    function verContrato(id) {
        // Mostrar modal de carga o simplemente reset
        $('#modalVerContrato').modal('show');
        
        // Limpiar campos visualmente
        $('#vc_id_contrato').text(id);
        $('#vc_nombre_completo').text('Cargando...');
        
        $.get('get_contrato_detalle.php', { id: id }, function(data) {
            if(data.error) {
                alert(data.error);
                return;
            }
            
            // Llenar campos
            $('#vc_nombre_completo').text(data.nombre_completo);
            $('#vc_cedula').text(data.cedula);
            $('#vc_estado').text(data.estado).removeClass().addClass('badge px-3 py-2 ' + (data.estado === 'ACTIVO' ? 'bg-success' : 'bg-danger'));
            $('#vc_plan').text(data.nombre_plan || 'N/A');
            $('#vc_telefono').text(data.telefono || '---');
            $('#vc_correo').text(data.correo || '---');
            $('#vc_direccion_completa').text(data.direccion || '---');
            $('#vc_municipio').text(data.municipio_texto || data.nombre_municipio || '---');
            $('#vc_parroquia').text(data.parroquia_texto || data.nombre_parroquia || '---');
            
            $('#vc_ip_onu').text(data.ip_onu || '---');
            $('#vc_ip').text(data.ip || '---');
            $('#vc_fecha_instalacion').text(data.fecha_instalacion || '---');
            $('#vc_vendedor').text(data.vendedor_texto || '---');
            $('#vc_tipo_conexion').text(data.tipo_conexion || 'Técnicos');
            
            // Lógica Técnica
            $('#vc_tech_ftth, #vc_tech_radio').hide();
            if(data.tipo_conexion && data.tipo_conexion.includes('FTTH')) {
                $('#vc_tech_ftth').show();
                $('#vc_olt').text(data.nombre_olt || '---');
                $('#vc_pon').text(data.nombre_pon || '---');
                $('#vc_caja').text(data.ident_caja_nap || '---');
                $('#vc_puerto').text(data.puerto_nap || '---');
                $('#vc_mac').text(data.mac_onu || '---');
                $('#vc_rx').text(data.onu_rx_power || '---');
                $('#vc_distancia').text(data.distancia_drop ? data.distancia_drop + ' m' : '---');
            } else {
                $('#vc_tech_radio').show();
                $('#vc_ap').text(data.punto_acceso || '---');
                $('#vc_signal').text(data.valor_conexion_dbm ? data.valor_conexion_dbm + ' dBm' : '---');
            }
            
        });
    }
</script>

<?php require_once '../includes/layout_foot.php'; ?>