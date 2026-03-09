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

        /* Estilos para intl-tel-input premium */
        .iti {
            width: 100%;
            display: block;
        }

        .iti__country-list {
            z-index: 1056;
            background-color: #fff;
            border: 1px solid #ddd;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
        }
    </style>
    <!-- intl-tel-input CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/intl-tel-input@23.0.4/build/css/intlTelInput.css">
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
                                    <div class="input-group">
                                        <select class="form-select" name="tipo_cedula" id="tipo_cedula"
                                            style="max-width: 80px;" required>
                                            <option value="V" selected>V</option>
                                            <option value="E">E</option>
                                            <option value="J">J</option>
                                        </select>
                                        <input type="text" class="form-control" name="cedula" id="cedula" required
                                            pattern="[0-9]+" placeholder="12345678">
                                    </div>
                                    <div class="form-text small">Seleccione tipo e ingrese solo números.</div>
                                </div>
                                <div class="col-md-6 mb-2">
                                    <label class="form-label fw-bold">Nombre y Apellido Titular <span
                                            class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="nombre_completo" id="nombre_completo"
                                        required pattern="[A-Za-zñÑáéíóúÁÉÍÓÚ\s]+" placeholder="Nombre completo">
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-12 mb-2">
                                    <label class="form-label fw-bold">Dirección (Referencia de localidad) <span
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
                                    <label class="form-label fw-bold">Teléfono de Contacto 1 <span
                                            class="text-danger">*</span></label>
                                    <input type="tel" class="form-control" name="telefono" id="telefono" required
                                        placeholder="0424-1234567">
                                </div>
                                <div class="col-md-6 mb-2">
                                    <label class="form-label">Teléfono de Contacto 2</label>
                                    <input type="tel" class="form-control" name="telefono_secundario"
                                        id="telefono_secundario" placeholder="0414-7654321">
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
                                    <label class="form-label">Método de Pago <span class="text-danger">*</span></label>
                                    <select class="form-select" name="medio_pago" required>
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
                                        <label class="form-label">Monto de Instalación ($) <span
                                                class="text-danger">*</span></label>
                                        <input type="number" step="0.01" min="0" class="form-control"
                                            name="monto_instalacion" id="monto_instalacion" required placeholder="0.00">
                                    </div>
                                    <div class="col-md-4 mb-2">
                                        <label class="form-label">Gasto Adicional ($)</label>
                                        <input type="number" step="0.01" class="form-control" name="gastos_adicionales"
                                            id="gastos_adicionales" value="0" placeholder="0.00">
                                    </div>
                                </div>

                                <!-- Switch de Prorrateo -->
                                <div class="col-12 mt-3 mb-2">
                                    <div
                                        class="form-check form-switch bg-light border rounded p-3 d-flex align-items-center gap-3">
                                        <input class="form-check-input ms-0 mt-0" type="checkbox" role="switch"
                                            id="incluye_prorrateo" name="incluye_prorrateo" value="SI"
                                            style="width: 3em; height: 1.5em; cursor: pointer;">
                                        <label class="form-check-label mb-0 fw-bold text-primary"
                                            for="incluye_prorrateo" style="cursor: pointer;">¿Aplica días de
                                            prorrateo?</label>
                                    </div>
                                </div>

                                <!-- Contenedor Oculto Prorrateo -->
                                <div class="col-12" id="contenedor_prorrateo" style="display: none;">
                                    <div class="row p-3 border rounded bg-white mt-1 shadow-sm">
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label fw-semibold text-secondary">Plan para
                                                Prorrateo</label>
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
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label fw-semibold text-secondary">Días de
                                                Prorrateo</label>
                                            <input type="number" class="form-control" name="dias_prorrateo"
                                                id="dias_prorrateo" value="0" placeholder="0">
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label fw-semibold text-secondary">Monto Prorrateo
                                                ($)</label>
                                            <input type="number" step="0.01" class="form-control fw-bold bg-light"
                                                name="monto_prorrateo_usd" id="monto_prorrateo_usd" readonly
                                                placeholder="0.00">
                                        </div>
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-4 mb-2">
                                        <label class="form-label fw-bold">Monto Total a Pagar ($)</label>
                                        <input type="number" step="0.01" class="form-control" name="monto_pagar"
                                            id="monto_pagar" placeholder="0.00">
                                    </div>
                                    <div class="col-md-4 mb-2">
                                        <label class="form-label fw-bold">Monto Pagado</label>
                                        <div class="input-group">
                                            <input type="number" step="0.01" class="form-control" name="monto_pagado"
                                                id="monto_pagado" placeholder="0.00">
                                            <select class="form-select" name="moneda_pago" style="max-width: 80px;">
                                                <option value="USD">USD</option>
                                                <option value="BS">BS</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-4 mb-2">
                                        <label class="form-label fw-bold text-danger">Restante a Pagar ($)</label>
                                        <input type="number" step="0.01" class="form-control text-danger fw-bold"
                                            id="monto_debe" readonly placeholder="0.00">
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
                                            <label class="form-label">MAC o Serial de la ONU <span
                                                    class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="mac_onu" name="mac_onu"
                                                pattern="[A-Fa-f0-9:\.\-]{8,20}" placeholder="FABBCC112233">
                                        </div>
                                        <div class="col-md-6 mb-2">
                                            <label class="form-label">Dirección IP Asignada a la ONU <span
                                                    class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="ip_onu" name="ip_onu" value=""
                                                pattern="^(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)$"
                                                placeholder="192.168.x.x">
                                        </div>
                                    </div>

                                    <div class="row mb-3 campo-ftth">
                                        <div class="col-md-6 mb-2">
                                            <label class="form-label">Identificación Caja NAP <span
                                                    class="text-danger">*</span></label>
                                            <input type="text" class="form-control" name="ident_caja_nap"
                                                placeholder="ID Caja NAP">
                                        </div>
                                        <div class="col-md-6 mb-2">
                                            <label class="form-label">Puerto NAP <span
                                                    class="text-danger">*</span></label>
                                            <input type="text" class="form-control" name="puerto_nap"
                                                placeholder="Puerto">
                                        </div>
                                    </div>

                                    <div class="row mb-3 campo-ftth">
                                        <div class="col-md-4 mb-2">
                                            <label class="form-label">NAP TX Power (dBm) <span
                                                    class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="nap_tx_power"
                                                name="nap_tx_power" pattern="-?[0-9.]+" placeholder="-25">
                                        </div>
                                        <div class="col-md-4 mb-2">
                                            <label class="form-label">ONU RX Power (dBm) <span
                                                    class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="onu_rx_power"
                                                name="onu_rx_power" pattern="-?[0-9.]+" placeholder="-27">
                                        </div>
                                        <div class="col-md-4 mb-2">
                                            <label class="form-label">Distancia de Drop (m) <span
                                                    class="text-danger">*</span></label>
                                            <input type="number" step="1" class="form-control" id="distancia_drop"
                                                name="distancia_drop" placeholder="50">
                                        </div>
                                        <div class="col-md-4 mb-2">
                                            <label class="form-label fw-bold text-primary">Precinto ODN <span
                                                    class="text-danger">*</span></label>
                                            <input type="text" class="form-control border-primary"
                                                name="num_presinto_odn" placeholder="Número de precinto">
                                        </div>
                                    </div>

                                    <!-- CAMPOS RADIO -->
                                    <div class="row mb-3 campo-radio">
                                        <div class="col-md-4 mb-2">
                                            <label class="form-label">Dirección IP <span
                                                    class="text-danger">*</span></label>
                                            <input type="text" class="form-control" name="ip" id="ip" value="" required
                                                pattern="^(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)$"
                                                placeholder="192.168.x.x">
                                        </div>
                                        <div class="col-md-4 mb-2">
                                            <label class="form-label">Punto de Acceso <span
                                                    class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="punto_acceso"
                                                name="punto_acceso" placeholder="Nombre AP">
                                        </div>
                                        <div class="col-md-4 mb-2">
                                            <label class="form-label">Valor de Conexión (dBm) <span
                                                    class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="valor_conexion_dbm"
                                                name="valor_conexion_dbm" pattern="-?[0-9.]+" placeholder="-55">
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

                                    <!-- Campo Vendedor -->
                                    <div class="row mb-3">
                                        <div class="col-md-12 mb-2">
                                            <label class="form-label fw-bold">Vendido Por</label>
                                            <?php
                                            $jsonVendedores = '../../paginas/principal/data/vendedores.json';
                                            $vendedoresList = [];
                                            if (file_exists($jsonVendedores)) {
                                                $vendedoresList = json_decode(file_get_contents($jsonVendedores), true) ?: [];
                                                if (isset($vendedoresList['vendedores'])) {
                                                    $vendedoresList = $vendedoresList['vendedores'];
                                                }
                                            }
                                            ?>
                                            <select name="vendedor_texto" id="vendedor_texto" class="form-select">
                                                <option value="">-- Seleccione un Vendedor --</option>
                                                <?php
                                                if (!empty($vendedoresList)) {
                                                    foreach ($vendedoresList as $vend) {
                                                        $nombre = is_array($vend) ? ($vend['nombre'] ?? $vend['name'] ?? $vend) : $vend;
                                                        echo '<option value="' . htmlspecialchars($nombre) . '">' . htmlspecialchars($nombre) . '</option>';
                                                    }
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
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
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
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-outline-secondary w-100" data-bs-dismiss="modal"
                                onclick="mostrarOpcionesExito(window.lastSavedId)">
                                <i class="fa-solid fa-arrow-left me-1"></i> Volver
                            </button>
                            <button type="button" class="btn btn-primary w-100" onclick="location.reload()">
                                Finalizar
                            </button>
                        </div>
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

        // ======================================================
        // VALIDACIÓN Y FORMATEO DE CAMPOS (REQUERIMIENTO USUARIO)
        // ======================================================
        // 1. Forzar prefijo 'V' en Cédula y permitir solo dígitos después
        $('#cedula').on('input', function () {
            let val = $(this).val().toUpperCase();

            // Asegurar que siempre empiece con V
            if (!val.startsWith('V')) {
                val = 'V' + val.replace(/[^0-9]/g, '');
            } else {
                // Mantener la V inicial y limpiar el resto
                val = 'V' + val.substring(1).replace(/[^0-9]/g, '');
            }

            $(this).val(val);
        });

        $('#ip, #ip_onu').on('input', function () {
            let val = $(this).val().replace(/[^0-9.]/g, '');
            // Restringir IP a números y puntos y validar octetos 0-255
            let parts = val.split('.');

            // Validar que cada octeto no pase de 255
            for (let i = 0; i < parts.length; i++) {
                if (parts[i] !== '' && parseInt(parts[i]) > 255) {
                    parts[i] = '255';
                }
                // Limitar a máximo 4 octetos
                if (i >= 4) {
                    parts.splice(4);
                    break;
                }
            }

            $(this).val(parts.join('.'));
        });

        // Restringir Teléfono a números, guiones, más y espacios
        $('#telefono, #telefono_secundario').on('input', function () {
            let val = $(this).val().replace(/[^0-9-+\s]/g, '');
            $(this).val(val);
        });

        $('#nombre_completo').on('input', function () {
            let val = $(this).val().replace(/[^A-Za-zñÑáéíóúÁÉÍÓÚ\s]/g, '');
            $(this).val(val);
        });

        $('#mac_onu').on('input', function () {
            let val = $(this).val().toUpperCase().replace(/[^A-F0-9:.-]/g, '');
            $(this).val(val);
        });

        $('#nap_tx_power, #onu_rx_power, #valor_conexion_dbm').on('input', function () {
            let val = $(this).val().replace(/[^0-9.-]/g, '');
            // Only allow one '-' at the beginning
            if (val.indexOf('-') > 0) val = val.substring(0, val.indexOf('-')) + val.substring(val.indexOf('-') + 1);
            $(this).val(val);
        });

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
                    const municipioObj = ubicacionesData.find(m => m.municipio === munNombre);

                    if (municipioObj && municipioObj.parroquias) {
                        municipioObj.parroquias.forEach(function (p) {
                            // Ahora p es un objeto {nombre: "...", comunidades: []}
                            options += `<option value="${p.nombre}">${p.nombre}</option>`;
                        });
                        $('#parroquia').html(options).prop('disabled', false);
                    } else {
                        $('#parroquia').html('<option value="">No hay parroquias</option>').prop('disabled', true);
                    }
                } else {
                    $('#parroquia').html('<option value="">-- Primero seleccione municipio --</option>').prop('disabled', true);
                }
                $('#comunidad').html('<option value="">-- Primero seleccione parroquia --</option>').prop('disabled', true);
            });

            // Cargar Comunidades al cambiar Parroquia (Agregado para consistencia)
            $('#parroquia').on('change', function () {
                const munNombre = $('#municipio').val();
                const parrNombre = $(this).val();
                let options = '<option value="">-- Seleccione --</option>';

                if (munNombre && parrNombre) {
                    const munObj = ubicacionesData.find(m => m.municipio === munNombre);
                    if (munObj && munObj.parroquias) {
                        const parObj = munObj.parroquias.find(p => p.nombre === parrNombre);
                        if (parObj && parObj.comunidades) {
                            parObj.comunidades.forEach(function (c) {
                                options += `<option value="${c}">${c}</option>`;
                            });
                        }
                    }
                    $('#comunidad').html(options).prop('disabled', false);
                } else {
                    $('#comunidad').html('<option value="">-- Primero seleccione parroquia --</option>').prop('disabled', true);
                }
            });

            // Mostrar/Ocultar campos según Tipo de Conexión
            $('#tipo_conexion').on('change', function () {
                var tipo = $(this).val();
                // Ocultar todos primero
                $('.campo-ftth, .campo-radio').hide();
                // Quitar required de todos los campos técnicos
                $('.campo-ftth input, .campo-radio input').prop('required', false);

                if (tipo === 'FTTH') {
                    $('.campo-ftth').show();
                    $('#mac_onu, #ip_onu, [name="ident_caja_nap"], [name="puerto_nap"], #nap_tx_power, #onu_rx_power, #distancia_drop, [name="num_presinto_odn"]').prop('required', true);
                } else if (tipo === 'RADIO') {
                    $('.campo-radio').show();
                    $('#ip, #punto_acceso, #valor_conexion_dbm').prop('required', true);
                }
            });

            // Trigger change al cargar (por si hay valor preseleccionado)
            $('#tipo_conexion').trigger('change');

            // LÓGICA DE CÁLCULOS DE PAGO

            // Lógica del Switch de Prorrateo
            $('#incluye_prorrateo').on('change', function () {
                if ($(this).is(':checked')) {
                    $('#contenedor_prorrateo').slideDown();
                } else {
                    $('#contenedor_prorrateo').slideUp();
                    // Reset prorrateo fields
                    $('#plan_prorrateo').val('');
                    $('#dias_prorrateo').val('0');
                    $('#monto_prorrateo_usd').val('0.00');
                    calcularTotal();
                }
            });

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

        // Modal Link Generado
        const modalLink = new bootstrap.Modal(document.getElementById('modalLink'));

        function guardarContrato(formData) {
            // Mostrar Confirmación antes de enviar
            const cedula = document.getElementById('cedula').value;
            const nombre = document.getElementById('nombre_completo').value;
            const tipo = document.querySelector('select[name="tipo_instalacion"]').value;
            const plan = document.getElementById('id_plan').options[document.getElementById('id_plan').selectedIndex].text;
            const total = document.getElementById('monto_pagar').value;
            const pagado = document.getElementById('monto_pagado').value;
            const moneda = document.querySelector('select[name="moneda_pago"]').value;
            const medio = document.querySelector('select[name="medio_pago"]').value;

            const htmlResumen = `
                <div class="text-start">
                    <table class="table table-sm table-bordered mt-2">
                        <tbody>
                            <tr><th class="bg-light">Cédula:</th><td>${cedula}</td></tr>
                            <tr><th class="bg-light">Titular:</th><td>${nombre}</td></tr>
                            <tr><th class="bg-light">Tipo:</th><td>${tipo}</td></tr>
                            <tr><th class="bg-light">Plan:</th><td>${plan}</td></tr>
                            <tr><th class="bg-light">Monto Total:</th><td>$${total}</td></tr>
                            <tr><th class="bg-light">Monto Pagado:</th><td>${pagado} ${moneda}</td></tr>
                            <tr><th class="bg-light">Medio de Pago:</th><td>${medio}</td></tr>
                        </tbody>
                    </table>
                    <p class="text-center fw-bold text-success mt-1">¿Desea registrar este contrato?</p>
                </div>
            `;

            Swal.fire({
                title: 'Confirmar Datos',
                html: htmlResumen,
                icon: 'info',
                showCancelButton: true,
                confirmButtonColor: '#198754',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Sí, Registrar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    ejecutarGuardado(formData);
                }
            });
        }

        function ejecutarGuardado(formData) {
            document.getElementById('btnGuardar').disabled = true;
            document.getElementById('btnGuardar').innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';

            fetch('guardar_contrato_instalador.php', {
                method: 'POST',
                body: formData
            })
                .then(r => r.json())
                .then(data => {
                    if (data.status === 'success') {
                        window.lastSavedId = data.id;
                        mostrarOpcionesExito(data.id);
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
            navigator.clipboard.writeText(copyText.value).then(() => {
                Swal.fire({
                    icon: 'success',
                    title: '¡Copiado!',
                    timer: 1000,
                    showConfirmButton: false
                });
            });
        }

        function mostrarOpcionesExito(id) {
            const pdf_url = `../reportes_pdf/generar_contrato_pdf.php?id_contrato=${id}`;
            Swal.fire({
                title: '¡Éxito!',
                text: 'Contrato registrado correctamente.',
                icon: 'success',
                showCancelButton: true,
                showDenyButton: true,
                confirmButtonColor: '#198754',
                denyButtonColor: '#0d6efd',
                cancelButtonColor: '#6c757d',
                confirmButtonText: '<i class="fa-solid fa-file-pdf"></i> Ver PDF',
                denyButtonText: '<i class="fa-solid fa-link"></i> Generar Link',
                cancelButtonText: 'Nueva Instalación',
                allowOutsideClick: false
            }).then((res) => {
                if (res.isConfirmed) {
                    window.open(pdf_url, '_blank');
                    mostrarOpcionesExito(id);
                } else if (res.isDenied) {
                    // Si ya tenemos el link en el modal, solo lo abrimos
                    // Pero para ser consistentes con la otra pagina, llamamos a la funcion que genera el token si es necesario
                    // En este portal, usualmente se genera el link AL GUARDAR o específicamente con el botón.
                    // Si el usuario ya guardó normal, podemos generar el link ahora.
                    generarLinkRemoto(id);
                } else {
                    window.location.reload();
                }
            });
        }

        function generarLinkRemoto(id) {
            Swal.fire({
                title: 'Generando enlace...',
                allowOutsideClick: false,
                didOpen: () => { Swal.showLoading(); }
            });

            $.post('../principal/generar_token_firma.php', { id: id }, function (resp) {
                Swal.close();
                if (resp.success) {
                    const baseUrl = window.location.origin + window.location.pathname.split('/paginas/')[0] + '/paginas/soporte/firmar_remoto.php';
                    const link = `${baseUrl}?token=${resp.token}&type=contrato`;

                    document.getElementById('linkInput').value = link;
                    const mensaje = encodeURIComponent(`Hola, por favor firma tu contrato de servicio en el siguiente enlace: ${link}`);
                    document.getElementById('btnWhatsapp').href = `https://wa.me/?text=${mensaje}`;

                    modalLink.show();
                } else {
                    Swal.fire('Error', resp.message || 'Error al generar link', 'error');
                }
            }, 'json').fail(() => {
                Swal.fire('Error', 'Error de comunicación', 'error');
            });
        }

        // CÁLCULO AUTOMÁTICO DE RESTANTE A PAGAR
        const inputMontoPagar = document.getElementById('monto_pagar');
        const inputMontoPagado = document.getElementById('monto_pagado');
        const inputMontoDebe = document.getElementById('monto_debe');

        // LÓGICA DE CONVERSIÓN DE MONEDA Y FILTRADO DE PAGOS (BCV)
        let tasaBCV = 0;
        $.get('../../paginas/principal/get_tasa_dolar.php', function (data) {
            if (data.success) {
                tasaBCV = data.promedio;
                console.log("Tasa BCV cargada (Instalador): " + tasaBCV);
            }
        });

        const selectMoneda = document.querySelector('select[name="moneda_pago"]');
        const selectMedio = document.querySelector('select[name="medio_pago"]');

        const mediosPorMoneda = {
            'USD': ['Efectivo', 'Zelle', 'Otro'],
            'BS': ['Efectivo', 'Transferencia', 'Pago Móvil', 'Otro']
        };

        function filtrarMedios(moneda) {
            const actual = selectMedio.value;
            selectMedio.innerHTML = '<option value="">-- Seleccione --</option>';
            if (mediosPorMoneda[moneda]) {
                mediosPorMoneda[moneda].forEach(medio => {
                    const opt = document.createElement('option');
                    opt.value = medio;
                    opt.textContent = medio;
                    selectMedio.appendChild(opt);
                });
            }
            if (mediosPorMoneda[moneda].includes(actual)) {
                selectMedio.value = actual;
            }
        }

        if (selectMoneda) {
            selectMoneda.addEventListener('change', function () {
                const moneda = this.value;
                const monto = parseFloat(inputMontoPagado.value) || 0;

                if (tasaBCV > 0 && monto > 0) {
                    if (moneda === 'BS') {
                        inputMontoPagado.value = (monto * tasaBCV).toFixed(2);
                    } else {
                        inputMontoPagado.value = (monto / tasaBCV).toFixed(2);
                    }
                }
                filtrarMedios(moneda);
                calcularDebe();
            });
            // Inicializar
            filtrarMedios(selectMoneda.value);
        }

        function calcularDebe() {
            const total = parseFloat(inputMontoPagar.value) || 0;
            const pagado = parseFloat(inputMontoPagado.value) || 0;
            const moneda = selectMoneda ? selectMoneda.value : 'USD';

            let pagadoUSD = pagado;
            if (moneda === 'BS' && tasaBCV > 0) {
                pagadoUSD = pagado / tasaBCV;
            }

            // Validar que el monto pagado no exceda el total (con margen de 0.01 por redondeo)
            if (pagadoUSD > (total + 0.01)) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Monto Excedido',
                    text: 'El monto pagado no puede ser mayor al total a pagar ($' + total.toFixed(2) + ').',
                    timer: 2000,
                    showConfirmButton: false
                });

                if (moneda === 'BS' && tasaBCV > 0) {
                    inputMontoPagado.value = (total * tasaBCV).toFixed(2);
                } else {
                    inputMontoPagado.value = total.toFixed(2);
                }

                calcularDebe();
                return;
            }

            const debe = (total - pagadoUSD).toFixed(2);

            // Si el debe es negativo (pagó de más), mostramos 0.
            if (parseFloat(debe) > 0) {
                inputMontoDebe.value = debe;
                inputMontoDebe.classList.add('is-invalid'); // Visual highlight optional
                inputMontoDebe.style.color = 'red';
            } else {
                inputMontoDebe.classList.remove('is-invalid');
                inputMontoDebe.style.color = 'green';
                inputMontoDebe.value = "0.00";
            }
        }

        if (inputMontoPagar && inputMontoPagado && inputMontoDebe) {
            inputMontoPagar.addEventListener('input', calcularDebe);
            inputMontoPagado.addEventListener('input', calcularDebe);
        }
    </script>

    <!-- intl-tel-input JS -->
    <script src="https://cdn.jsdelivr.net/npm/intl-tel-input@23.0.4/build/js/intlTelInput.min.js"></script>
    <script>
        const tel1 = document.querySelector("#telefono");
        const tel2 = document.querySelector("#telefono_secundario");

        const iti1 = window.intlTelInput(tel1, {
            initialCountry: "ve",
            separateDialCode: true,
            utilsScript: "https://cdn.jsdelivr.net/npm/intl-tel-input@23.0.4/build/js/utils.js",
        });

        const iti2 = window.intlTelInput(tel2, {
            initialCountry: "ve",
            separateDialCode: true,
            utilsScript: "https://cdn.jsdelivr.net/npm/intl-tel-input@23.0.4/build/js/utils.js",
        });

        const setupPhoneCorrection = (input, iti) => {
            input.addEventListener('input', () => {
                let value = input.value;
                const data = iti.getSelectedCountryData();
                if (data.iso2 === 've' && value.startsWith('0')) {
                    input.value = value.substring(1);
                }
            });
        };

        setupPhoneCorrection(tel1, iti1);
        setupPhoneCorrection(tel2, iti2);

        // Bloquear signos negativos en Monto Instalación
        const inputMontoInstalacion = document.querySelector("#monto_instalacion");
        if (inputMontoInstalacion) {
            inputMontoInstalacion.addEventListener('keydown', (e) => {
                if (e.key === '-' || e.key === 'e') {
                    e.preventDefault();
                }
            });
            inputMontoInstalacion.addEventListener('input', () => {
                if (inputMontoInstalacion.value < 0) {
                    inputMontoInstalacion.value = Math.abs(inputMontoInstalacion.value);
                }
            });
        }

        // Opcional: Validar antes de enviar
        document.getElementById('formContrato').addEventListener('submit', function (e) {
            // Si bien el backend lo guarda, podríamos advertir si el número es inválido
            // if (!iti1.isValidNumber()) { ... }
        });
    </script>
</body>

</html>