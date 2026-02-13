<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Contrato - Instalador</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
        }

        .signature-pad {
            border: 2px dashed #ccc;
            borderradius: 5px;
            width: 100%;
            height: 200px;
            background-color: #fff;
            touch-action: none;
        }

        .section-title {
            background-color: #e9ecef;
            padding: 8px 15px;
            border-left: 4px solid #0d6efd;
            font-weight: bold;
            margin-top: 20px;
            margin-bottom: 15px;
        }

        .preview-image {
            max-width: 200px;
            max-height: 200px;
            border: 1px solid #ddd;
            border-radius: 5px;
            margin-top: 10px;
            margin-top: 10px;
        }

        .campo-ftth,
        .campo-radio {
            display: none;
        }
    </style>
</head>

<body>

    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="card shadow-lg border-0">
                    <div class="card-header bg-success text-white text-center py-3">
                        <h4 class="mb-0"><i class="fas fa-file-signature me-2"></i>Registro de Nuevo Contrato -
                            Instalador</h4>
                    </div>
                    <div class="card-body p-4">
                        <form id="formContrato" enctype="multipart/form-data">

                            <!-- 1. Datos del Cliente -->
                            <div class="section-title">Datos del Cliente</div>
                            <div class="row mb-3">
                                <div class="col-md-6 mb-2">
                                    <label class="form-label fw-bold">Cédula de Identidad o RIF <span
                                            class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="cedula" required
                                        placeholder="Ej. V12345678 o J123456789">
                                </div>
                                <div class="col-md-6 mb-2">
                                    <label class="form-label fw-bold">Nombre y Apellido Titular <span
                                            class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="nombre_completo" required
                                        placeholder="Nombre completo">
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-12 mb-2">
                                    <label class="form-label fw-bold">Dirección Exacta y Referencias <span
                                            class="text-danger">*</span></label>
                                    <textarea class="form-control" name="direccion" rows="2" required
                                        placeholder="Dirección completa con referencias..."></textarea>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6 mb-2">
                                    <label class="form-label">Municipio <span class="text-danger">*</span></label>
                                    <select name="id_municipio" id="municipio" class="form-select" required>
                                        <option value="">-- Seleccione --</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-2">
                                    <label class="form-label">Parroquia <span class="text-danger">*</span></label>
                                    <select name="id_parroquia" id="parroquia" class="form-select" required disabled>
                                        <option value="">-- Primero seleccione municipio --</option>
                                    </select>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6 mb-2">
                                    <label class="form-label">Teléfono de Contacto 1 <span
                                            class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="telefono" required
                                        placeholder="0424-1234567">
                                </div>
                                <div class="col-md-6 mb-2">
                                    <label class="form-label">Teléfono de Contacto 2</label>
                                    <input type="text" class="form-control" name="telefono_secundario"
                                        placeholder="0414-7654321">
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6 mb-2">
                                    <label class="form-label">Correo Electrónico</label>
                                    <input type="email" class="form-control" name="correo"
                                        placeholder="email@ejemplo.com">
                                </div>
                                <div class="col-md-6 mb-2">
                                    <label class="form-label">Correo Electrónico Adicional</label>
                                    <input type="email" class="form-control" name="correo_adicional"
                                        placeholder="otro@ejemplo.com">
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6 mb-2">
                                    <label class="form-label fw-bold">Plan de Servicio <span
                                            class="text-danger">*</span></label>
                                    <select class="form-select" name="id_plan" id="id_plan" required>
                                        <option value="">-- Seleccione un Plan --</option>
                                    </select>
                                </div>
                            </div>

                            <!-- 2. Información de Instalación y Pago -->
                            <div class="section-title">Información de Instalación y Pago</div>
                            <div class="row mb-3">
                                <div class="col-md-6 mb-2">
                                    <label class="form-label fw-bold">Fecha de Instalación <span
                                            class="text-danger">*</span></label>
                                    <input type="date" class="form-control" name="fecha_instalacion"
                                        value="<?php echo date('Y-m-d'); ?>" required>
                                </div>
                                <div class="col-md-6 mb-2">
                                    <label class="form-label fw-bold">Tipo de Instalación <span
                                            class="text-danger">*</span></label>
                                    <select class="form-select" name="tipo_instalacion" required>
                                        <option value="">-- Seleccione --</option>
                                        <?php
                                        // ⚠️ CARGA JSON TIPOS
                                        $jsonTypes = '../../paginas/principal/data/tipos_instalacion.json';
                                        if (file_exists($jsonTypes)) {
                                            $types = json_decode(file_get_contents($jsonTypes), true) ?: [];
                                            foreach ($types as $t) {
                                                echo '<option value="' . htmlspecialchars($t) . '">' . htmlspecialchars($t) . '</option>';
                                            }
                                        } else {
                                            // Fallback default
                                            echo '<option value="NUEVO FTTH">Nuevo FTTH</option>
                                                  <option value="NUEVO RADIO">Nuevo Radio</option>
                                                  <option value="MIGRACION">Migración</option>
                                                  <option value="MUDANZA">Mudanza</option>';
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6 mb-2">
                                    <label class="form-label">Método de Pago</label>
                                    <select class="form-select" name="medio_pago">
                                        <option value="">-- Seleccione --</option>
                                        <option value="Efectivo">Efectivo</option>
                                        <option value="Transferencia">Transferencia</option>
                                        <option value="Pago Móvil">Pago Móvil</option>
                                        <option value="Zelle">Zelle</option>
                                        <option value="Otro">Otro</option>
                                    </select>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-md-4 mb-2">
                                        <label class="form-label">Monto de Instalación ($)</label>
                                        <input type="number" step="0.01" class="form-control" name="monto_instalacion"
                                            id="monto_instalacion" placeholder="0.00">
                                    </div>
                                    <div class="col-md-4 mb-2">
                                        <label class="form-label">Gasto Adicional ($)</label>
                                        <input type="number" step="0.01" class="form-control" name="gastos_adicionales"
                                            id="gastos_adicionales" value="0" placeholder="0.00">
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-4 mb-2">
                                        <label class="form-label">Plan para Prorrateo</label>
                                        <select class="form-select" id="plan_prorrateo" name="plan_prorrateo">
                                            <option value="">-- Seleccione --</option>
                                            <?php
                                            // ⚠️ CARGA JSON PRORRATEO
                                            $jsonProrrateo = '../../paginas/principal/data/planes_prorrateo.json';
                                            if (file_exists($jsonProrrateo)) {
                                                $pPlans = json_decode(file_get_contents($jsonProrrateo), true) ?: [];
                                                foreach ($pPlans as $p) {
                                                    // Value = price, Text = Name - $Price
                                                    echo '<option value="' . htmlspecialchars($p['precio']) . '">' .
                                                        htmlspecialchars($p['nombre']) . ' - $' . htmlspecialchars($p['precio']) .
                                                        '</option>';
                                                }
                                            } else {
                                                echo '<option value="17.50">100 Mbps - $17.50</option>
                                                      <option value="23.20">250 Mbps - $23.20</option>
                                                      <option value="25.00">650 Mbps - $25.00</option>
                                                      <option value="38.00">850 Mbps - $38.00</option>
                                                      <option value="48.00">1 Gb - $48.00</option>';
                                            }
                                            ?>
                                        </select>
                                    </div>
                                    <div class="col-md-4 mb-2">
                                        <label class="form-label">Días de Prorrateo</label>
                                        <input type="number" class="form-control" name="dias_prorrateo"
                                            id="dias_prorrateo" value="0" placeholder="0">
                                    </div>
                                    <div class="col-md-4 mb-2">
                                        <label class="form-label">Monto Prorrateo ($)</label>
                                        <input type="number" step="0.01" class="form-control" name="monto_prorrateo_usd"
                                            id="monto_prorrateo_usd" readonly placeholder="0.00">
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-6 mb-2">
                                        <label class="form-label fw-bold">Monto Total a Pagar ($)</label>
                                        <input type="number" step="0.01" class="form-control" name="monto_pagar"
                                            id="monto_pagar" placeholder="0.00">
                                    </div>
                                    <div class="col-md-6 mb-2">
                                        <label class="form-label fw-bold">Monto Pagado</label>
                                        <div class="input-group">
                                            <input type="number" step="0.01" class="form-control" name="monto_pagado"
                                                placeholder="0.00">
                                            <select class="form-select" name="moneda_pago" style="max-width: 80px;">
                                                <option value="USD">USD</option>
                                                <option value="BS">BS</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Observaciones</label>
                                    <textarea class="form-control" name="observaciones" rows="2"
                                        placeholder="Observaciones generales..."></textarea>
                                </div>

                                <!-- 3. Detalles Técnicos de Conexión -->
                                <div class="section-title">Detalles Técnicos de Conexión</div>
                                <div class="row mb-3">
                                    <div class="col-md-6 mb-2">
                                        <div class="col-md-6 mb-2">
                                            <label class="form-label fw-bold">Tipo de Conexión <span
                                                    class="text-danger">*</span></label>
                                            <select class="form-select" name="tipo_conexion" id="tipo_conexion"
                                                required>
                                                <option value="">-- Seleccione --</option>
                                                <option value="FTTH">FTTH (Fibra Óptica)</option>
                                                <option value="RADIO">Radio/Antena</option>
                                            </select>
                                        </div>
                                    </div>

                                    <!-- CAMPOS FTTH -->
                                    <div class="row mb-3 campo-ftth">
                                        <div class="col-md-6 mb-2">
                                            <label class="form-label">MAC o Serial de la ONU</label>
                                            <input type="text" class="form-control" name="mac_onu"
                                                placeholder="MAC o Serial">
                                        </div>
                                        <div class="col-md-6 mb-2">
                                            <label class="form-label">Dirección IP Asignada a la ONU</label>
                                            <input type="text" class="form-control" name="ip_onu" value="192.168."
                                                placeholder="192.168.x.x">
                                        </div>
                                    </div>

                                    <div class="row mb-3 campo-ftth">
                                        <div class="col-md-6 mb-2">
                                            <label class="form-label">Identificación Caja NAP</label>
                                            <input type="text" class="form-control" name="ident_caja_nap"
                                                placeholder="ID Caja NAP">
                                        </div>
                                        <div class="col-md-6 mb-2">
                                            <label class="form-label">Puerto NAP</label>
                                            <input type="text" class="form-control" name="puerto_nap"
                                                placeholder="Puerto">
                                        </div>
                                    </div>

                                    <div class="row mb-3 campo-ftth">
                                        <div class="col-md-4 mb-2">
                                            <label class="form-label">NAP TX Power (dBm)</label>
                                            <input type="text" class="form-control" name="nap_tx_power"
                                                placeholder="-25">
                                        </div>
                                        <div class="col-md-4 mb-2">
                                            <label class="form-label">ONU RX Power (dBm)</label>
                                            <input type="text" class="form-control" name="onu_rx_power"
                                                placeholder="-27">
                                        </div>
                                        <div class="col-md-4 mb-2">
                                            <label class="form-label">Distancia de Drop (m)</label>
                                            <input type="text" class="form-control" name="distancia_drop"
                                                placeholder="50">
                                        </div>
                                    </div>

                                    <!-- CAMPOS RADIO -->
                                    <div class="row mb-3 campo-radio">
                                        <div class="col-md-4 mb-2">
                                            <label class="form-label">Dirección IP <span
                                                    class="text-danger">*</span></label>
                                            <input type="text" class="form-control" name="ip" value="192.168."
                                                placeholder="192.168.x.x">
                                        </div>
                                        <div class="col-md-4 mb-2">
                                            <label class="form-label">Punto de Acceso</label>
                                            <input type="text" class="form-control" name="punto_acceso"
                                                placeholder="Nombre AP">
                                        </div>
                                        <div class="col-md-4 mb-2">
                                            <label class="form-label">Valor de Conexión (dBm)</label>
                                            <input type="text" class="form-control" name="valor_conexion_dbm"
                                                placeholder="-55">
                                        </div>
                                    </div>

                                    <div class="row mb-3" style="display:none;">
                                        <div class="col-md-6 mb-2">
                                            <label class="form-label">Número de Precinto de Identificación ODN</label>
                                            <input type="text" class="form-control" name="num_presinto_odn"
                                                placeholder="Número de precinto">
                                        </div>
                                        <div class="col-md-6 mb-2">
                                            <label class="form-label">Evidencia de Fibra</label>
                                            <input type="text" class="form-control" name="evidencia_fibra"
                                                placeholder="Descripción o ubicación">
                                        </div>
                                    </div>

                                    <!-- OTROS DATOS -->
                                    <div class="row mb-3">
                                        <div class="col-md-12 mb-2">
                                            <label class="form-label">Instalador <span
                                                    class="text-danger">*</span></label>
                                            <?php
                                            // ⚠️ CARGA JSON INSTALADORES (Ruta ajustada por carpeta soporte)
                                            $jsonInstaladores = '../../paginas/principal/data/instaladores.json';
                                            $instaladoresList = [];
                                            if (file_exists($jsonInstaladores)) {
                                                $instaladoresList = json_decode(file_get_contents($jsonInstaladores), true) ?: [];
                                            }
                                            ?>
                                            <select name="instalador" class="form-select" required>
                                                <option value="">-- Seleccione un Instalador --</option>
                                                <?php
                                                if (!empty($instaladoresList)) {
                                                    foreach ($instaladoresList as $inst) {
                                                        echo '<option value="' . htmlspecialchars($inst) . '">' . htmlspecialchars($inst) . '</option>';
                                                    }
                                                } else {
                                                    // En este archivo no tenemos $conn abierta fácilmente aca arriba, 
                                                    // así que fallback simple o requerir conexión.
                                                    // Como es vista "publica/soporte", mejor confiar en el JSON o input texto fallback
                                                    echo '<option value="">Error cargando lista</option>';
                                                }
                                                ?>
                                            </select>
                                        </div>
                                    </div>

                                    <!-- 4. Evidencia Fotográfica y Documentación -->
                                    <div class="section-title">Evidencia y Documentación</div>
                                    <div class="row mb-3">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label fw-bold">Foto Instalación Terminada</label>
                                            <input type="file" class="form-control" name="evidencia_foto_file"
                                                id="foto_instalacion" accept="image/*">
                                            <div id="preview_foto" class="mt-2 text-center"></div>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label fw-bold">Documento de Identidad
                                                (Cédula/RIF)</label>
                                            <input type="file" class="form-control" name="evidencia_documento_file"
                                                id="foto_documento" accept="image/*">
                                            <div id="preview_documento" class="mt-2 text-center"></div>
                                        </div>
                                    </div>

                                    <!-- 5. Firmas Digitales -->
                                    <div class="section-title">Firmas Digitales</div>

                                    <div class="mb-4">
                                        <label class="form-label fw-bold">Firma del Cliente <span
                                                class="text-danger">*</span></label>
                                        <canvas id="sigCliente" class="signature-pad"></canvas>
                                        <button type="button" class="btn btn-sm btn-outline-secondary mt-1"
                                            onclick="clearPad('cliente')">Limpiar</button>
                                        <input type="hidden" name="firma_cliente_data" id="firma_cliente_data">
                                    </div>

                                    <div class="mb-4">
                                        <label class="form-label fw-bold">Firma del Técnico <span
                                                class="text-danger">*</span></label>
                                        <canvas id="sigTecnico" class="signature-pad"></canvas>
                                        <button type="button" class="btn btn-sm btn-outline-secondary mt-1"
                                            onclick="clearPad('tecnico')">Limpiar</button>
                                        <input type="hidden" name="firma_tecnico_data" id="firma_tecnico_data">
                                    </div>

                                    <div class="d-grid gap-2">
                                        <button type="submit" class="btn btn-success btn-lg" id="btnGuardar">
                                            <i class="fas fa-save me-2"></i> Registrar Contrato
                                        </button>
                                        <button type="button" class="btn btn-outline-primary btn-lg"
                                            id="btnGenerarLink">
                                            <i class="fa-brands fa-whatsapp me-2"></i> Registrar y Generar Link de Firma
                                        </button>
                                    </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Link Generado -->
    <div class="modal fade" id="modalLink" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="fa-solid fa-link me-2"></i>Enlace de Contrato Generado</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"
                        onclick="location.reload()"></button>
                </div>
                <div class="modal-body text-center p-4">
                    <div class="mb-4">
                        <i class="fa-regular fa-circle-check text-primary fa-4x mb-3"></i>
                        <h4>¡Contrato Pre-registrado!</h4>
                        <p class="text-muted">El contrato está pendiente de firma del cliente.</p>
                    </div>

                    <label class="form-label fw-bold text-start w-100">Enlace para el Cliente:</label>
                    <div class="input-group mb-3">
                        <input type="text" class="form-control" id="linkInput" readonly>
                        <button class="btn btn-outline-secondary" type="button" onclick="copiarLink()">
                            <i class="fa-regular fa-copy"></i>
                        </button>
                    </div>

                    <div class="d-grid gap-2">
                        <a href="#" id="btnWhatsapp" target="_blank" class="btn btn-success">
                            <i class="fa-brands fa-whatsapp me-2"></i> Enviar por WhatsApp
                        </a>
                        <button type="button" class="btn btn-secondary" onclick="location.reload()">
                            Cerrar y Nuevo Registro
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/signature_pad@4.1.5/dist/signature_pad.umd.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        // Inicializar Signature Pads
        const canvasCliente = document.getElementById('sigCliente');
        const canvasTecnico = document.getElementById('sigTecnico');

        function resizeCanvas(canvas) {
            var ratio = Math.max(window.devicePixelRatio || 1, 1);
            canvas.width = canvas.offsetWidth * ratio;
            canvas.height = canvas.offsetHeight * ratio;
            canvas.getContext("2d").scale(ratio, ratio);
        }
        window.onresize = function () { resizeCanvas(canvasCliente); resizeCanvas(canvasTecnico); };
        resizeCanvas(canvasCliente); resizeCanvas(canvasTecnico);

        const padCliente = new SignaturePad(canvasCliente);
        const padTecnico = new SignaturePad(canvasTecnico);

        function clearPad(type) {
            if (type === 'cliente') padCliente.clear();
            if (type === 'tecnico') padTecnico.clear();
        }

        // Cargar Ubicaciones desde JSON
        let ubicacionesData = [];

        $(document).ready(function () {
            // Cargar JSON completo al inicio
            $.get('../principal/api_ubicaciones.php', function (data) {
                ubicacionesData = data;
                let options = '<option value="">-- Seleccione --</option>';

                // Llenar Municipios
                ubicacionesData.forEach(function (item) {
                    options += `<option value="${item.municipio}">${item.municipio}</option>`;
                });
                $('#municipio').html(options);
            });

            // Cargar Planes de Servicio
            $.get('../principal/api_planes.php', function (data) {
                let options = '<option value="">-- Seleccione un Plan --</option>';
                data.forEach(function (plan) {
                    options += `<option value="${plan.id_plan}" data-monto-plan="${plan.monto}">${plan.nombre_plan} ($${plan.monto})</option>`;
                });
                $('#id_plan').html(options);
            });

            // Cargar Tipos de Instalación
            $.get('../principal/api_tipos_instalacion.php', function (data) {
                let options = '<option value="">-- Seleccione --</option>';
                data.forEach(function (tipo) {
                    options += `<option value="${tipo}">${tipo}</option>`;
                });
                $('select[name="tipo_instalacion"]').html(options);
            });

            // Cargar Parroquias al cambiar Municipio
            $('#municipio').on('change', function () {
                const munNombre = $(this).val();
                let options = '<option value="">-- Seleccione --</option>';

                if (munNombre) {
                    // Buscar el municipio seleccionado en el array
                    const municipioObj = ubicacionesData.find(m => m.municipio === munNombre);

                    if (municipioObj && municipioObj.parroquias) {
                        municipioObj.parroquias.forEach(function (p) {
                            options += `<option value="${p}">${p}</option>`;
                        });
                        $('#parroquia').html(options).prop('disabled', false);
                    } else {
                        $('#parroquia').html('<option value="">No hay parroquias</option>').prop('disabled', true);
                    }
                } else {
                    $('#parroquia').html('<option value="">-- Primero seleccione municipio --</option>').prop('disabled', true);
                }
            });

            // LÓGICA DE CAMPOS TÉCNICOS DINÁMICOS
            // Mostrar/Ocultar campos según Tipo de Conexión
            $('#tipo_conexion').on('change', function () {
                var tipo = $(this).val();
                // Ocultar todos primero
                $('.campo-ftth, .campo-radio').hide();

                if (tipo === 'FTTH') {
                    $('.campo-ftth').show();
                } else if (tipo === 'RADIO') {
                    $('.campo-radio').show();
                }
            });

            // Trigger change al cargar (por si hay valor preseleccionado)
            $('#tipo_conexion').trigger('change');

            // LÓGICA DE CÁLCULOS DE PAGO
            // Calcular prorrateo cuando cambia el plan manual o los días
            $('#plan_prorrateo, #dias_prorrateo').on('change input', function () {
                calcularProrrateo();
                calcularTotal();
            });

            // Calcular total al cambiar montos
            $('#monto_instalacion, #gastos_adicionales').on('input', function () {
                calcularTotal();
            });

            // Función para calcular prorrateo: (Precio Plan Manual / 30) * Días
            function calcularProrrateo() {
                var montoPlan = parseFloat($('#plan_prorrateo').val()) || 0;
                var diasProrrateo = parseInt($('#dias_prorrateo').val()) || 0;
                var prorrateo = (montoPlan / 30) * diasProrrateo;
                $('#monto_prorrateo_usd').val(prorrateo.toFixed(2));
            }

            // Función para calcular monto total
            function calcularTotal() {
                var instalacion = parseFloat($('#monto_instalacion').val()) || 0;
                var adicionales = parseFloat($('#gastos_adicionales').val()) || 0;
                var prorrateo = parseFloat($('#monto_prorrateo_usd').val()) || 0;
                var total = instalacion + adicionales + prorrateo;
                $('#monto_pagar').val(total.toFixed(2));
            }

            // Preview de Foto

            // Preview de Foto Instalación
            $('#foto_instalacion').on('change', function (e) {
                const file = e.target.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function (event) {
                        $('#preview_foto').html(`<img src="${event.target.result}" class="preview-image">`);
                    };
                    reader.readAsDataURL(file);
                }
            });

            // Preview de Foto Documento
            $('#foto_documento').on('change', function (e) {
                const file = e.target.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function (event) {
                        $('#preview_documento').html(`<img src="${event.target.result}" class="preview-image">`);
                    };
                    reader.readAsDataURL(file);
                }
            });
        });

        // Enviar Formulario
        document.getElementById('formContrato').addEventListener('submit', function (e) {
            e.preventDefault();

            // Validar firmas
            if (padCliente.isEmpty() || padTecnico.isEmpty()) {
                Swal.fire('Error', 'Debe capturar ambas firmas (cliente y técnico).', 'error');
                return;
            }

            // Guardar firmas en inputs ocultos
            document.getElementById('firma_cliente_data').value = padCliente.toDataURL();
            document.getElementById('firma_tecnico_data').value = padTecnico.toDataURL();

            const formData = new FormData(this);
            guardarContrato(formData);
        });

        // Botón Generar Link
        const btnGenerarLink = document.getElementById('btnGenerarLink');
        const modalLink = new bootstrap.Modal(document.getElementById('modalLink'));

        btnGenerarLink.addEventListener('click', function () {
            const form = document.getElementById('formContrato');
            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }

            // Para link NO validamos firmas obligatorias en este punto
            const formData = new FormData(form);
            formData.append('generate_link', '1');

            // UI Loading
            const originalText = this.innerHTML;
            this.disabled = true;
            this.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Generando...';

            fetch('guardar_contrato_instalador.php', {
                method: 'POST',
                body: formData
            })
                .then(r => r.json())
                .then(data => {
                    this.disabled = false;
                    this.innerHTML = originalText;

                    if (data.status === 'success') {
                        // Mostrar Modal
                        const linkCompleto = window.location.origin + window.location.pathname.replace('registro_contrato_instalador.php', '') + data.link;
                        document.getElementById('linkInput').value = linkCompleto;

                        // Configurar WhatsApp
                        const telefono = formData.get('telefono') || '';
                        const mensaje = `Estimado cliente, por favor firme su contrato de servicio en el siguiente enlace: ${linkCompleto}`;

                        // Intentar abrir whatsapp directo si hay telefono, sino share generico
                        let whatsappUrl = `https://wa.me/?text=${encodeURIComponent(mensaje)}`;
                        // Si el numero tiene formato valido (simple check), podriamos usarlo:
                        // if(telefono.length > 9) whatsappUrl = `https://wa.me/58${telefono.substring(1)}?text=...`; 

                        document.getElementById('btnWhatsapp').href = whatsappUrl;

                        modalLink.show();
                    } else {
                        Swal.fire('Error', data.msg || 'Error desconocido', 'error');
                    }
                })
                .catch(err => {
                    this.disabled = false;
                    this.innerHTML = originalText;
                    console.error(err);
                    Swal.fire('Error', 'Error de conexión', 'error');
                });
        });

        function guardarContrato(formData) {
            // Agregar foto si existe (re-check, aunque formData ya lo toma del input file)
            /* const fotoFile = document.getElementById('foto_instalacion').files[0];
            if (fotoFile && !formData.has('evidencia_foto_file')) { // formData toma archivos automaticamente
                formData.append('evidencia_foto_file', fotoFile);
            } */

            document.getElementById('btnGuardar').disabled = true;
            document.getElementById('btnGuardar').innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';

            fetch('guardar_contrato_instalador.php', {
                method: 'POST',
                body: formData
            })
                .then(r => r.json())
                .then(data => {
                    if (data.status === 'success') {
                        Swal.fire({
                            title: '¡Éxito!',
                            text: 'Contrato registrado correctamente.',
                            icon: 'success'
                        }).then(() => {
                            window.location.reload();
                        });
                    } else {
                        Swal.fire('Error', data.msg || 'Error al guardar el contrato', 'error');
                        document.getElementById('btnGuardar').disabled = false;
                        document.getElementById('btnGuardar').innerHTML = '<i class="fas fa-save me-2"></i> Registrar Contrato';
                    }
                })
                .catch(err => {
                    console.error(err);
                    Swal.fire('Error', 'Error de conexión con el servidor', 'error');
                    document.getElementById('btnGuardar').disabled = false;
                    document.getElementById('btnGuardar').innerHTML = '<i class="fas fa-save me-2"></i> Registrar Contrato';
                });
        }

        function copiarLink() {
            const copyText = document.getElementById("linkInput");
            copyText.select();
            copyText.setSelectionRange(0, 99999);
            navigator.clipboard.writeText(copyText.value);

            const btn = document.querySelector('button[onclick="copiarLink()"]'); // Simple selector logic
            // Visual feedback handled by user logic usually, simplified here
        }
    </script>

</body>

</html>