<?php
// nuevo.php

/**
 * Formulario para agregar un nuevo registro
 *
 * Este formulario permite agregar un nuevo registro a la base de datos.
 */

// 1. CONEXIÓN A LA BASE DE DATOS Y CONSULTAS ESTATICAS (INICIO DEL ARCHIVO)
require_once '../conexion.php';

// --- CONSULTA PARA OBTENER TODOS LOS MUNICIPIOS (para el select estático) ---
$sql_municipios = "SELECT id_municipio, nombre_municipio FROM municipio ORDER BY nombre_municipio ASC";
$resultado_municipios = $conn->query($sql_municipios);

if (!$resultado_municipios) {
    die("Error en la consulta de municipios: " . $conn->error);
}

// --- NUEVA CONSULTA PARA OBTENER TODAS LAS OLTs (para el select estático) ---
$olts = [];
$sql_olts = "SELECT id_olt, nombre_olt FROM olt ORDER BY nombre_olt ASC";
$resultado_olts = $conn->query($sql_olts);

if ($resultado_olts && $resultado_olts->num_rows > 0) {
    while ($row = $resultado_olts->fetch_assoc()) {
        $olts[] = $row;
    }
}
// La conexión se mantendrá abierta para su uso en los bloques PHP dentro del HTML

$path_to_root = "../../";
$page_title = "Nuevo Contrato";
require_once '../includes/layout_head.php';
require_once '../includes/sidebar.php';
?>

<style>
    .section-title {
        background-color: #e9ecef;
        padding: 8px 15px;
        border-left: 4px solid #0d6efd;
        font-weight: bold;
        margin-top: 20px;
        margin-bottom: 15px;
    }

    .signature-pad {
        border: 2px dashed #ccc;
        border-radius: 5px;
        width: 100%;
        height: 200px;
        background-color: #fff;
        touch-action: none;
    }

    .campo-ftth,
    .campo-radio {
        display: none;
    }
</style>

<main class="main-content">
    <?php include '../includes/header.php'; ?>

    <div class="page-content">
        <div class="card">
            <div
                class="card-header d-flex justify-content-between align-items-center bg-white border-bottom-0 pt-4 px-4">
                <div>
                    <h5 class="fw-bold text-primary mb-1">Nuevo Contrato</h5>
                    <p class="text-muted small mb-0">Registro de nuevo cliente y servicio</p>
                </div>
            </div>

            <div class="card-body px-4">
                <form class="row g-3" method="POST" action="guarda.php" enctype="multipart/form-data"
                    autocomplete="off">

                    <div class="section-title">Datos del Cliente</div>

                    <div class="col-md-6">
                        <label for="ip" class="form-label">IP</label>
                        <input type="text" class="form-control" id="ip" name="ip" required autofocus>
                    </div>

                    <div class="col-md-6">
                        <label for="cedula" class="form-label">Cedula</label>
                        <input type="text" class="form-control" id="cedula" name="cedula" required>
                    </div>

                    <div class="col-md-6">
                        <label for="nombre_completo" class="form-label">Nombre Completo</label>
                        <input type="text" class="form-control" id="nombre_completo" name="nombre_completo" required>
                    </div>

                    <div class="col-md-6">
                        <label for="telefono" class="form-label">Teléfono</label>
                        <input type="text" class="form-control" id="telefono" name="telefono">
                    </div>

                    <div class="col-md-6">
                        <label for="correo" class="form-label">Correo</label>
                        <input type="text" class="form-control" id="correo" name="correo">
                    </div>

                    <div class="col-md-6">
                        <label for="telefono_secundario" class="form-label">Teléfono Secundario</label>
                        <input type="text" class="form-control" id="telefono_secundario" name="telefono_secundario">
                    </div>

                    <div class="col-md-6">
                        <label for="correo_adicional" class="form-label">Correo Adicional</label>
                        <input type="text" class="form-control" id="correo_adicional" name="correo_adicional">
                    </div>

                    <div class="col-md-6">
                        <label for="municipio" class="form-label">Municipio</label>
                        <select name="id_municipio" id="municipio" class="form-select" required>
                            <option value="">-- Seleccione un Municipio --</option>
                            <?php
                            if ($resultado_municipios->num_rows > 0) {
                                while ($fila = $resultado_municipios->fetch_assoc()) {
                                    echo '<option value="' . htmlspecialchars($fila["id_municipio"]) . '">' .
                                        htmlspecialchars($fila["nombre_municipio"]) .
                                        '</option>';
                                }
                            }
                            ?>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label for="parroquia" class="form-label">Parroquia</label>
                        <select name="id_parroquia" id="parroquia" class="form-select" disabled required>
                            <option value="">-- Primero seleccione un municipio --</option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label for="comunidad" class="form-label">Comunidad</label>
                        <select name="id_comunidad" id="comunidad" class="form-select" disabled>
                            <option value="">-- Primero seleccione una parroquia --</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <?php
                        // ⚠️ RE-USANDO LA CONEXIÓN ABIERTA - Incluye monto para cálculo de prorrateo
                        $sql_planes = "SELECT id_plan, nombre_plan, monto FROM planes ORDER BY nombre_plan ASC";
                        $resultado_planes = $conn->query($sql_planes);

                        if (!$resultado_planes) {
                            die("Error en la consulta SQL de planes: " . $conn->error);
                        }
                        ?>
                        <label for="id_plan" class="form-label">Planes</label>

                        <select name="id_plan" id="id_plan" class="form-select" required>
                            <option value="">-- Seleccione un Plan --</option>

                            <?php
                            if ($resultado_planes->num_rows > 0) {
                                while ($fila = $resultado_planes->fetch_assoc()) {
                                    echo '<option value="' . htmlspecialchars($fila["id_plan"]) . '" data-monto-plan="' . htmlspecialchars($fila["monto"]) . '">' .
                                        htmlspecialchars($fila["nombre_plan"]) . ' ($' . htmlspecialchars($fila["monto"]) . ')' .
                                        '</option>';
                                }
                            } else {
                                echo '<option value="" disabled>No se encontraron planes en la base de datos.</option>';
                            }
                            ?>

                        </select>

                    </div>


                    <div class="col-md-6">
                        <?php
                        // ⚠️ MODIFICADO: Usar siempre base de datos, ignorar JSON para evitar error de claves foráneas
                        $sql_vendedores_fb = "SELECT * FROM vendedores ORDER BY nombre_vendedor ASC";
                        $res_fb = $conn->query($sql_vendedores_fb);
                        ?>
                        <label for="id_vendedor" class="form-label">Vendedor</label>

                        <select name="id_vendedor" id="id_vendedor" class="form-select" required>
                            <option value="">-- Seleccione un Vendedor --</option>
                            <?php
                            if ($res_fb && $res_fb->num_rows > 0) {
                                while ($row = $res_fb->fetch_assoc()) {
                                    // Usar id_vendedor como VALUE, nombre_vendedor como TEXTO
                                    echo '<option value="' . htmlspecialchars($row['id_vendedor']) . '">' . htmlspecialchars($row['nombre_vendedor']) . '</option>';
                                }
                            } else {
                                echo '<option value="" disabled>Sin vendedores registrados</option>';
                            }
                            ?>
                        </select>
                    </div>



                    <div class="col-md-6">
                        <label for="direccion" class="form-label">Direccion</label>
                        <textarea class="form-control" id="direccion" name="direccion" rows="3" required></textarea>
                    </div>

                    <div class="col-md-6">
                        <label for="fecha_instalacion" class="form-label">Fecha_Instalacion</label>
                        <input type="date" class="form-control" id="fecha_instalacion" name="fecha_instalacion"
                            required>
                    </div>

                    <div class="col-md-6">
                        <label for="estado_contrato" class="form-label">Estado del Contrato</label>
                        <select class="form-select" id="estado_contrato" name="estado_contrato" required>
                            <option value="">-- Seleccione el Estado --</option>

                            <option value="ACTIVO" selected>ACTIVO</option>
                            <option value="INACTIVO">INACTIVO</option>
                            <option value="SUSPENDIDO">SUSPENDIDO</option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label for="ident_caja_nap" class="form-label">Identificacion Caja Nap</label>
                        <input type="text" class="form-control" id="ident_caja_nap" name="ident_caja_nap">
                    </div>

                    <div class="col-md-6">
                        <label for="puerto_nap" class="form-label">Puerto_Nap</label>
                        <input type="text" class="form-control" id="puerto_nap" name="puerto_nap">
                    </div>

                    <div class="col-md-6">
                        <label for="num_presinto_odn" class="form-label">Numero_Presinto_ODN</label>
                        <input type="text" class="form-control" id="num_presinto_odn" name="num_presinto_odn">
                    </div>

                    <div class="col-md-6">
                        <label for="id_olt" class="form-label">OLT</label>

                        <select name="id_olt" id="id_olt" class="form-select" required>
                            <option value="">-- Seleccione una OLT --</option>

                            <?php
                            if (!empty($olts)) {
                                foreach ($olts as $olt) {
                                    echo '<option value="' . htmlspecialchars($olt["id_olt"]) . '">' .
                                        htmlspecialchars($olt["nombre_olt"]) .
                                        '</option>';
                                }
                            } else {
                                echo '<option value="" disabled>No se encontraron OLTs en la base de datos.</option>';
                            }
                            ?>

                        </select>

                    </div>

                    <div class="col-md-6">
                        <label for="id_pon" class="form-label">PON</label>

                        <select name="id_pon" id="id_pon" class="form-select" required disabled>
                            <option value="">-- Seleccione una OLT primero --</option>
                        </select>

                    </div>

                    <!-- NUEVOS CAMPOS: Tipo de Instalación y Costos -->
                    <div class="section-title">Información de Instalación y Pago</div>

                    <div class="col-md-6">
                        <label for="tipo_conexion" class="form-label">Tipo de Conexión</label>
                        <select name="tipo_conexion" id="tipo_conexion" class="form-select" required>
                            <option value="">-- Seleccione Conexión --</option>
                            <?php
                            // Cargar Tipos desde JSON (Repurposed for Connection Type)
                            $jsonFileTypes = 'data/tipos_instalacion.json';
                            if (file_exists($jsonFileTypes)) {
                                $typesData = json_decode(file_get_contents($jsonFileTypes), true);
                                if (is_array($typesData)) {
                                    foreach ($typesData as $type) {
                                        echo '<option value="' . $type . '">' . $type . '</option>';
                                    }
                                }
                            } else {
                                // Fallback básico
                                echo '<option value="FTTH">FTTH</option>';
                                echo '<option value="RADIO">RADIO</option>';
                            }
                            ?>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label for="monto_instalacion" class="form-label">Monto Instalación ($)</label>
                        <input type="number" step="0.01" class="form-control" id="monto_instalacion"
                            name="monto_instalacion">
                    </div>

                    <div class="col-md-6">
                        <label for="gastos_adicionales" class="form-label">Gastos Adicionales ($)</label>
                        <input type="number" step="0.01" class="form-control" id="gastos_adicionales"
                            name="gastos_adicionales" value="0">
                    </div>

                    <div class="col-md-6">
                        <label for="plan_prorrateo" class="form-label">Plan para Prorrateo</label>
                        <select class="form-select" id="plan_prorrateo" name="plan_prorrateo">
                            <option value="">-- Seleccione --</option>
                            <option value="17.50">100 Mbps - $17.50</option>
                            <option value="23.20">250 Mbps - $23.20</option>
                            <option value="25.00">650 Mbps - $25.00</option>
                            <option value="38.00">850 Mbps - $38.00</option>
                            <option value="48.00">1 Gb - $48.00</option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label for="dias_prorrateo" class="form-label">Días de Prorrateo</label>
                        <input type="number" class="form-control" id="dias_prorrateo" name="dias_prorrateo" value="0"
                            placeholder="0">
                    </div>

                    <div class="col-md-6">
                        <label for="monto_prorrateo_usd" class="form-label">Monto Prorrateo ($)</label>
                        <input type="number" step="0.01" class="form-control" id="monto_prorrateo_usd"
                            name="monto_prorrateo_usd" readonly>
                    </div>

                    <div class="col-md-6">
                        <label for="monto_pagar" class="form-label">Monto Total a Pagar ($)</label>
                        <input type="number" step="0.01" class="form-control" id="monto_pagar" name="monto_pagar"
                            readonly>
                    </div>

                    <div class="col-md-6">
                        <label for="monto_pagado" class="form-label">Monto Pagado</label>
                        <div class="input-group">
                            <input type="number" step="0.01" class="form-control" id="monto_pagado" name="monto_pagado"
                                required>
                            <select class="form-select" id="moneda_pago" name="moneda_pago" style="max-width: 100px;">
                                <option value="USD" selected>USD</option>
                                <option value="BS">BS</option>
                            </select>
                        </div>
                    </div>

                    <div class="col-md-6" id="div_saldo_pendiente" style="display:none;">
                        <label for="saldo_pendiente" class="form-label text-danger fw-bold">Saldo Pendiente ($)</label>
                        <input type="number" step="0.01" class="form-control border-danger text-danger fw-bold"
                            id="saldo_pendiente" name="saldo_pendiente" readonly>
                    </div>

                    <div class="col-md-6">
                        <label for="medio_pago" class="form-label">Medio de Pago</label>
                        <select class="form-select" id="medio_pago" name="medio_pago">
                            <option value="">-- Seleccione --</option>
                            <option value="Efectivo">Efectivo</option>
                            <option value="Transferencia">Transferencia</option>
                            <option value="Pago Móvil">Pago Móvil</option>
                            <option value="Zelle">Zelle</option>
                            <option value="Otro">Otro</option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label for="dias_prorrateo" class="form-label">Días de Prorrateo</label>
                        <input type="number" class="form-control" id="dias_prorrateo" name="dias_prorrateo" value="0">
                    </div>

                    <div class="col-md-12">
                        <label for="observaciones" class="form-label">Observaciones</label>
                        <textarea class="form-control" id="observaciones" name="observaciones" rows="2"></textarea>
                    </div>
                    <!-- NUEVOS CAMPOS: Detalles Técnicos de Conexión -->
                    <div class="section-title">Detalles Técnicos de Conexión</div>



                    <!-- CAMPOS FTTH -->
                    <div class="col-md-6 campo-ftth">
                        <label for="mac_onu" class="form-label">MAC o Serial de la ONU</label>
                        <input type="text" class="form-control" id="mac_onu" name="mac_onu">
                    </div>

                    <div class="col-md-6 campo-ftth">
                        <label for="ip_onu" class="form-label">Dirección IP Asignada a la ONU</label>
                        <input type="text" class="form-control" id="ip_onu" name="ip_onu" value="192.168.">
                    </div>

                    <div class="col-md-6 campo-ftth">
                        <label for="ident_caja_nap" class="form-label">Identificación Caja NAP</label>
                        <input type="text" class="form-control" id="ident_caja_nap" name="ident_caja_nap">
                    </div>

                    <div class="col-md-6 campo-ftth">
                        <label for="puerto_nap" class="form-label">Puerto NAP</label>
                        <input type="text" class="form-control" id="puerto_nap" name="puerto_nap">
                    </div>

                    <div class="col-md-6 campo-ftth">
                        <label for="nap_tx_power" class="form-label">NAP TX Power (dBm)</label>
                        <input type="text" class="form-control" id="nap_tx_power" name="nap_tx_power">
                    </div>

                    <div class="col-md-6 campo-ftth">
                        <label for="onu_rx_power" class="form-label">ONU RX Power (dBm)</label>
                        <input type="text" class="form-control" id="onu_rx_power" name="onu_rx_power">
                    </div>

                    <div class="col-md-6 campo-ftth">
                        <label for="distancia_drop" class="form-label">Distancia Drop (m)</label>
                        <input type="text" class="form-control" id="distancia_drop" name="distancia_drop">
                    </div>

                    <!-- CAMPOS RADIO -->
                    <div class="col-md-6 campo-radio">
                        <label for="ip" class="form-label">Dirección IP</label>
                        <input type="text" class="form-control" id="ip" name="ip" value="192.168.">
                    </div>

                    <div class="col-md-6 campo-radio">
                        <label for="punto_acceso" class="form-label">Punto de Acceso</label>
                        <input type="text" class="form-control" id="punto_acceso" name="punto_acceso">
                    </div>

                    <div class="col-md-6 campo-radio">
                        <label for="valor_conexion_dbm" class="form-label">Valor Conexión (dBm)</label>
                        <input type="text" class="form-control" id="valor_conexion_dbm" name="valor_conexion_dbm">
                    </div>

                    <!-- Otros campos (Ocultos o generales según necesidad, por ahora fuera de la lógica dinámica estricta o ocultos) -->
                    <div class="col-md-6" style="display:none;"> <!-- Ocultando temporalmente si no se piden -->
                        <label for="num_presinto_odn" class="form-label">Número Presinto ODN</label>
                        <input type="text" class="form-control" id="num_presinto_odn" name="num_presinto_odn">
                    </div>

                    <div class="col-md-12" style="display:none;">
                        <label for="evidencia_fibra" class="form-label">Evidencia de Fibra</label>
                        <input type="text" class="form-control" id="evidencia_fibra" name="evidencia_fibra">
                    </div>

                    <!-- Campos específicos RADIO -->
                    <div class="col-md-6 campo-radio">
                        <label for="punto_acceso" class="form-label">Punto de Acceso</label>
                        <input type="text" class="form-control" id="punto_acceso" name="punto_acceso">
                    </div>

                    <div class="col-md-6 campo-radio">
                        <label for="valor_conexion_dbm" class="form-label">Valor Conexión (dBm)</label>
                        <input type="text" class="form-control" id="valor_conexion_dbm" name="valor_conexion_dbm">
                    </div>

                    <div class="col-md-12">
                        <label for="evidencia_foto" class="form-label">Evidencia Fotográfica</label>
                        <input type="file" class="form-control" id="evidencia_foto" name="evidencia_foto"
                            accept="image/*">
                    </div>

                    <div class="col-md-6">
                        <?php
                        // ⚠️ CARGA JSON INSTALADORES
                        $jsonInstaladores = 'data/instaladores.json';
                        $instaladoresList = [];
                        if (file_exists($jsonInstaladores)) {
                            $instaladoresList = json_decode(file_get_contents($jsonInstaladores), true) ?: [];
                        }
                        ?>
                        <label for="instaladores" class="form-label font-weight-bold">Instalador</label>
                        <select name="instaladores[]" id="instaladores" class="form-select">
                            <option value="">-- Seleccione un Instalador --</option>
                            <?php
                            if (!empty($instaladoresList)) {
                                foreach ($instaladoresList as $inst) {
                                    // El valor y el texto son el nombre, ya que JSON es un array simple de nombres
                                    echo '<option value="' . htmlspecialchars($inst) . '">' .
                                        htmlspecialchars($inst) . '</option>';
                                }
                            } else {
                                // Fallback SQL
                                $sql_inst_fb = "SELECT * FROM instaladores WHERE activo = 1 ORDER BY nombre_instalador ASC";
                                $res_inst_fb = $conn->query($sql_inst_fb);
                                if ($res_inst_fb && $res_inst_fb->num_rows > 0) {
                                    while ($inst = $res_inst_fb->fetch_assoc()) {
                                        echo '<option value="' . $inst['nombre_instalador'] . '">' . htmlspecialchars($inst['nombre_instalador']) . '</option>'; // Use name as value to align with JSON
                                    }
                                } else {
                                    echo '<option disabled>No hay instaladores activos</option>';
                                }
                            }
                            ?>
                        </select>
                    </div>

                    <!-- EVIDENCIAS DE FIRMA -->
                    <div class="section-title">Firmas Digitales</div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Firma del Cliente</label>
                        <canvas id="sigCliente" class="signature-pad"></canvas>
                        <button type="button" class="btn btn-sm btn-outline-secondary mt-1"
                            onclick="clearPad('cliente')">Limpiar</button>
                        <input type="hidden" name="firma_cliente_data" id="firma_cliente_data">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold">Firma del Técnico</label>
                        <canvas id="sigTecnico" class="signature-pad"></canvas>
                        <button type="button" class="btn btn-sm btn-outline-secondary mt-1"
                            onclick="clearPad('tecnico')">Limpiar</button>
                        <input type="hidden" name="firma_tecnico_data" id="firma_tecnico_data">
                    </div>

                    <div class="col-12 d-flex gap-2">
                        <a href="gestion_contratos.php" class="btn btn-secondary">Regresar</a>
                        <button type="submit" class="btn btn-success">Guardar</button>
                        <button type="button" class="btn btn-outline-primary" id="btnGenerarLink">
                            <i class="fa-solid fa-link me-2"></i> Registrar y Generar Link
                        </button>
                    </div>

                </form>
            </div>
        </div>
    </div>
</main>

<!-- jQuery (Necesario para los scripts de cascada y cálculos) -->
<script src="../../js/jquery.min.js"></script>

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    // CÓDIGO PARA nuevo.php (Lógica de Cascada)

    $(document).ready(function () {

        // ======================================================
        // LÓGICA DE UBICACIÓN (Municipio -> Parroquia -> Comunidad)
        // ======================================================

        // 1. FUNCIÓN para cargar dinámicamente las Comunidades
        function cargarComunidades(idParroquia) {
            $('#comunidad').html('<option value="">Cargando comunidades...</option>');
            $('#comunidad').prop('disabled', true);

            if (idParroquia) {
                $.ajax({
                    url: 'obtener_comunidades.php',
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        id_parroquia: idParroquia
                    },
                    success: function (comunidades) {
                        $('#comunidad').html('<option value="">-- Seleccione una Comunidad --</option>');

                        $.each(comunidades, function (key, value) {
                            $('#comunidad').append('<option value="' + key + '">' + value + '</option>');
                        });

                        $('#comunidad').prop('disabled', false);
                    },
                    error: function () {
                        $('#comunidad').html('<option value="">Error al cargar comunidades</option>');
                    }
                });
            } else {
                $('#comunidad').html('<option value="">-- Primero seleccione una parroquia --</option>');
            }
        }

        // 2. FUNCIÓN para cargar dinámicamente las Parroquias
        function cargarParroquias(idMunicipio) {
            $('#parroquia').html('<option value="">Cargando parroquias...</option>');
            $('#parroquia').prop('disabled', true);

            // Resetear y deshabilitar Comunidad
            $('#comunidad').html('<option value="">-- Primero seleccione una parroquia --</option>');
            $('#comunidad').prop('disabled', true);

            if (idMunicipio) {
                $.ajax({
                    url: 'get_parroquias.php',
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        id: idMunicipio
                    },
                    success: function (parroquias) {
                        $('#parroquia').html('<option value="">-- Seleccione una Parroquia --</option>');

                        $.each(parroquias, function (key, value) {
                            $('#parroquia').append('<option value="' + key + '">' + value + '</option>');
                        });

                        $('#parroquia').prop('disabled', false);
                    },
                    error: function () {
                        $('#parroquia').html('<option value="">Error al cargar parroquias</option>');
                    }
                });
            } else {
                $('#parroquia').html('<option value="">-- Primero seleccione un municipio --</option>');
            }
        }

        // 3. MANEJAR CAMBIO DE MUNICIPIO
        $('#municipio').on('change', function () {
            var idMunicipio = $(this).val();
            cargarParroquias(idMunicipio);
        });

        // 4. MANEJAR CAMBIO DE PARROQUIA
        $('#parroquia').on('change', function () {
            var idParroquia = $(this).val();
            cargarComunidades(idParroquia);
        });


        // ======================================================
        // LÓGICA DE CAMPOS TÉCNICOS DINÁMICOS
        // ======================================================

        // Mostrar/Ocultar campos según Tipo de Conexión
        $('#tipo_conexion').on('change', function () {
            var tipo = $(this).val();
            // Ocultar todos primero
            $('.campo-ftth, .campo-radio').hide();
            // Limpiar valores (opcional, para evitar enviar datos basura)
            //$('.campo-ftth input, .campo-radio input').val('');

            if (tipo === 'FTTH') {
                $('.campo-ftth').show();
            } else if (tipo === 'RADIO') {
                $('.campo-radio').show();
            }
        });

        // Trigger change al cargar (por si hay valor preseleccionado)
        $('#tipo_conexion').trigger('change');


        // ======================================================
        // LÓGICA DE RED (OLT -> PON) -- NUEVA FUNCIÓN
        // ======================================================

        // 5. FUNCIÓN para cargar dinámicamente los PONs
        function cargarPons(idOlt) {
            var $ponSelect = $('#id_pon');

            // Limpiar el select de PON y deshabilitarlo
            $ponSelect.html('<option value="">Cargando PONs...</option>').prop('disabled', true);

            if (idOlt) {
                $.ajax({
                    // *** RUTA CLAVE: Apunta al archivo endpoint ***
                    url: 'gets_pon_by_olt.php',
                    type: 'GET',
                    data: { id_olt: idOlt },
                    dataType: 'json',
                    success: function (response) {
                        $ponSelect.empty();

                        if (!response.error && response.pons && response.pons.length > 0) {
                            $ponSelect.append('<option value="">-- Seleccione un PON --</option>');

                            $.each(response.pons, function (index, pon) {
                                $ponSelect.append('<option value="' + pon.id_pon + '">' + pon.nombre_pon + '</option>');
                            });
                            $ponSelect.prop('disabled', false); // Habilitar select de PON
                        } else {
                            var msg = response.message || 'No se encontraron PONs.';
                            $ponSelect.append('<option value="" disabled>' + msg + '</option>');
                            $ponSelect.prop('disabled', true);
                        }
                    },
                    error: function () {
                        $ponSelect.html('<option value="" disabled>Error de comunicación al cargar PONs.</option>');
                        $ponSelect.prop('disabled', true);
                    }
                });
            } else {
                $ponSelect.html('<option value="">-- Seleccione una OLT primero --</option>');
            }
        }

        // 6. MANEJAR CAMBIO DE OLT (Llama a cargarPons)
        $('#id_olt').on('change', function () {
            var idOlt = $(this).val();
            cargarPons(idOlt);
        });

        // ======================================================
        // LÓGICA DE INSTALACIÓN Y COSTOS
        // ======================================================

        // 8. Calcular prorrateo cuando cambia el plan manual o los días
        $('#plan_prorrateo, #dias_prorrateo').on('change input', function () {
            calcularProrrateo();
            calcularTotal();
        });

        // 9. Calcular total al cambiar gastos adicionales o monto instalación
        $('#gastos_adicionales, #monto_instalacion').on('input', function () {
            calcularTotal();
        });

        // 10. Función para calcular prorrateo: (Precio Plan Manual / 30) * Días
        function calcularProrrateo() {
            var montoPlan = parseFloat($('#plan_prorrateo').val()) || 0;
            var diasProrrateo = parseInt($('#dias_prorrateo').val()) || 0;
            var prorrateo = (montoPlan / 30) * diasProrrateo;
            $('#monto_prorrateo_usd').val(prorrateo.toFixed(2));
        }

        // 11. Función para calcular monto total
        function calcularTotal() {
            var instalacion = parseFloat($('#monto_instalacion').val()) || 0;
            var adicionales = parseFloat($('#gastos_adicionales').val()) || 0;
            var prorrateo = parseFloat($('#monto_prorrateo_usd').val()) || 0;
            var total = instalacion + adicionales + prorrateo;
            $('#monto_pagar').val(total.toFixed(2));
            calcularSaldo();
        }

        // 12. Calcular saldo pendiente al escribir en monto pagado
        $('#monto_pagado').on('input', function () {
            calcularSaldo();
        });

        // 13. Función para calcular saldo pendiente
        function calcularSaldo() {
            var total = parseFloat($('#monto_pagar').val()) || 0;
            var pagado = parseFloat($('#monto_pagado').val()) || 0;
            var saldo = total - pagado;

            if (pagado > 0 && saldo > 0) {
                $('#saldo_pendiente').val(saldo.toFixed(2));
                $('#div_saldo_pendiente').fadeIn();
            } else if (saldo > 0) {
                // Si el total es mayor que 0 pero no se ha pagado nada
                $('#saldo_pendiente').val(saldo.toFixed(2));
                $('#div_saldo_pendiente').fadeIn();
            } else {
                $('#div_saldo_pendiente').fadeOut();
                $('#saldo_pendiente').val("0.00");
            }
        }

    });
</script>

<!-- Librería Signature Pad -->
<script src="https://cdn.jsdelivr.net/npm/signature_pad@4.1.5/dist/signature_pad.umd.min.js"></script>

<script>
    // LÓGICA DE FIRMAS DIGITALES PARA nuevo.php
    $(document).ready(function () {
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

        window.clearPad = function (type) {
            if (type === 'cliente') padCliente.clear();
            if (type === 'tecnico') padTecnico.clear();
        }

        // Exponer pads globalmente
        window.padCliente = padCliente;
        window.padTecnico = padTecnico;

        // CAPTURAR FIRMAS ANTES DE ENVIAR
        $('form').on('submit', function (e) {
            if (!padCliente.isEmpty()) {
                $('#firma_cliente_data').val(padCliente.toDataURL());
            }
            if (!padTecnico.isEmpty()) {
                $('#firma_tecnico_data').val(padTecnico.toDataURL());
            }
        });
    });
</script>

<!-- Modal Link Generado -->
<div class="modal fade" id="modalLink" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fa-solid fa-link me-2"></i>Enlace de Contrato Generado</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"
                    onclick="location.href='gestion_contratos.php'"></button>
            </div>
            <div class="modal-body text-center p-4">
                <div class="mb-4">
                    <i class="fa-regular fa-circle-check text-primary fa-4x mb-3"></i>
                    <h4>¡Contrato Registrado!</h4>
                    <p class="text-muted">El contrato ha sido guardado y está pendiente de firma.</p>
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
                    <button type="button" class="btn btn-secondary" onclick="location.href='gestion_contratos.php'">
                        Cerrar y Regresar
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Lógica para Generar Link
    document.getElementById('btnGenerarLink').addEventListener('click', function () {
        // Validar formulario básico
        const form = document.querySelector('form');
        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        // Obtener firmas si existen (aunque para link suelen estar vacías, pero por si acaso el admin firmó)
        // Usamos la logica existente de los pads
        if (window.padCliente && !window.padCliente.isEmpty()) {
            $('#firma_cliente_data').val(window.padCliente.toDataURL());
        }
        if (window.padTecnico && !window.padTecnico.isEmpty()) {
            $('#firma_tecnico_data').val(window.padTecnico.toDataURL());
        }

        const formData = new FormData(form);
        formData.append('generate_link', '1');

        const btn = this;
        const originalText = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-2"></i> Generando...';

        fetch('guarda.php', {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    document.getElementById('linkInput').value = data.link;

                    // Configurar WhatsApp
                    const telefono = document.getElementById('telefono').value.replace(/\D/g, '');
                    // Usamos backticks para template string
                    const mensaje = encodeURIComponent(`Hola, por favor firma tu contrato de servicio en el siguiente enlace: ${data.link}`);
                    const waLink = `https://wa.me/${telefono}?text=${mensaje}`;
                    document.getElementById('btnWhatsapp').href = waLink;

                    const modal = new bootstrap.Modal(document.getElementById('modalLink'));
                    modal.show();
                } else {
                    Swal.fire('Error', data.msg || 'Error al generar el link', 'error');
                }
            })
            .catch(error => {
                console.error(error);
                Swal.fire('Error', 'Error de conexión', 'error');
            })
            .finally(() => {
                btn.disabled = false;
                btn.innerHTML = originalText;
            });
    });

    function copiarLink() {
        const linkInput = document.getElementById("linkInput");
        linkInput.select();
        linkInput.setSelectionRange(0, 99999);
        navigator.clipboard.writeText(linkInput.value).then(() => {
            Swal.fire({
                icon: 'success',
                title: '¡Copiado!',
                text: 'Enlace copiado al portapapeles',
                timer: 1500,
                showConfirmButton: false
            });
        });
    }
</script>

<?php require_once '../includes/layout_foot.php'; ?>