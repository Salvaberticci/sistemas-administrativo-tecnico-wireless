<?php
// reportar_pago.php - Formulario público para que clientes reporten sus pagos
include 'paginas/conexion.php';

// Cargar bancos para el combo
$json_bancos = @file_get_contents('paginas/principal/bancos.json');
$bancosArr = json_decode($json_bancos, true) ?: [];

// Generar lista de meses base (nombres en español)
$meses_nombres = ["Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre"];
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reportar Pago - Wireless Supply</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .payment-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .header-gradient {
            background: linear-gradient(135deg, #0d6efd, #0dcaf0);
            color: white;
            border-radius: 15px 15px 0 0;
        }

        .section-title {
            font-size: 0.9rem;
            font-weight: 700;
            color: #6c757d;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 20px;
            border-bottom: 2px solid #e9ecef;
            padding-bottom: 5px;
        }

        .form-label {
            font-weight: 600;
            color: #495057;
        }

        .important-note {
            font-size: 0.85rem;
            color: #6c757d;
        }

        .btn-report {
            background: linear-gradient(135deg, #198754, #20c997);
            border: none;
            padding: 12px;
            font-weight: 700;
            letter-spacing: 1px;
            transition: all 0.3s;
        }

        .btn-report:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(25, 135, 84, 0.3);
        }
    </style>
</head>

<body>

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="text-center mb-4">
                    <img src="images/logo.jpg" alt="Logo" class="img-fluid rounded-circle shadow-sm mb-3"
                        style="max-height: 100px;">
                    <h2 class="fw-bold">Wireless Supply, C.A.</h2>
                    <p class="text-muted">Reporte de Pago de Mensualidad</p>
                </div>

                <div class="card payment-card">
                    <div class="card-header header-gradient p-4 text-center">
                        <h4 class="mb-0">Formulario de Reporte de Pago</h4>
                    </div>
                    <div class="card-body p-4 p-md-5">
                        <form action="procesar_reporte_pago.php" method="POST" enctype="multipart/form-data"
                            id="formReportePago">

                            <!-- SECCIÓN 1: DATOS DEL TITULAR -->
                            <div class="section-title"><i class="fas fa-user me-2"></i> Datos del Titular del Servicio
                            </div>
                            <div class="row g-3 mb-4">
                                <div class="col-md-6">
                                    <label class="form-label">Cédula de Identidad <span
                                            class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="cedula" id="cedula"
                                        placeholder="12345678" required pattern="[0-9]{5,9}">
                                    <div class="important-note mt-1">
                                        <i class="fas fa-exclamation-triangle text-warning me-1"></i> Colocar la cédula
                                        sin espacios ni puntos "." solo números.
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Nombres y Apellidos <span
                                            class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="nombre" name="nombre"
                                        placeholder="Ej: Juan Pérez" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Número de Teléfono <span
                                            class="text-danger">*</span></label>
                                    <input type="tel" class="form-control" id="telefono" name="telefono"
                                        placeholder="Ej: 04121234567" required pattern="[0-9+\-\s]{7,15}">
                                </div>
                            </div>

                            <!-- SECCIÓN 2: DATOS DEL PAGO -->
                            <div class="section-title"><i class="fas fa-money-bill-transfer me-2"></i> Datos del Pago
                            </div>
                            <div class="row g-3 mb-4">
                                <div class="col-md-6">
                                    <label class="form-label">Fecha del Pago <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" name="fecha_pago"
                                        value="<?php echo date('Y-m-d'); ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Método de Pago <span class="text-danger">*</span></label>
                                    <select class="form-select" name="metodo_pago" id="metodo_pago" required>
                                        <option value="">Seleccione...</option>
                                        <option value="Pago Móvil">Pago Móvil</option>
                                        <option value="Transferencia">Transferencia</option>
                                        <option value="Efectivo">Efectivo</option>
                                        <option value="Divisas">Divisas</option>
                                        <option value="Zelle">Zelle</option>
                                    </select>
                                </div>

                                <!-- Campos condicionales para Banco y Referencia -->
                                <div class="col-md-6 d-none" id="div_banco">
                                    <label class="form-label">¿A qué banco pagó? <span
                                            class="text-danger">*</span></label>
                                    <select class="form-select" name="id_banco_destino" id="id_banco_destino">
                                        <option value="">Seleccione el banco receptor...</option>
                                        <?php foreach ($bancosArr as $b): ?>
                                            <option value="<?php echo $b['id_banco']; ?>">
                                                <?php echo htmlspecialchars($b['nombre_banco']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <label class="form-label">Número de Referencia <span
                                        class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="referencia" id="referencia"
                                    placeholder="Últimos 4 o 6 dígitos" inputmode="numeric" pattern="[0-9]{4,20}">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Monto Pagado (Bs) <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">Bs.</span>
                                    <input type="number" step="0.01" class="form-control fw-bold" name="monto_bs"
                                        id="monto_bs" placeholder="0,00" required>
                                </div>
                                <div class="important-note mt-1">
                                    Ingrese el monto exacto que aparece en su comprobante.
                                </div>
                            </div>

                            <div class="col-md-12">
                                <div class="form-check form-switch mb-2">
                                    <input class="form-check-input" type="checkbox" id="paga_varios_meses"
                                        name="paga_varios_meses">
                                    <label class="form-check-label fw-bold" for="paga_varios_meses">¿Pagará más de
                                        un mes?</label>
                                </div>
                                <div id="container_meses">
                                    <label class="form-label">Concepto del Pago (Mes) <span
                                            class="text-danger">*</span></label>
                                    <select class="form-select selector-mes" name="meses[]" required>
                                        <option value="">Seleccione mes...</option>
                                    </select>
                                </div>
                                <div id="add_mes_btn" class="mt-2 d-none">
                                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="agregarMes()">
                                        <i class="fas fa-plus me-1"></i> Agregar otro mes
                                    </button>
                                </div>
                            </div>

                            <div class="col-md-12">
                                <label class="form-label">Notas Adicionales / Concepto de Pago</label>
                                <textarea class="form-control" name="concepto" rows="2"
                                    placeholder="Describa el detalle del pago"></textarea>
                                <div class="important-note mt-1 text-primary">
                                    <i class="fas fa-info-circle me-1"></i> ⚠️ En caso de cancelar la mensualidad de
                                    un tercero, describa a quién corresponde el pago para agilizar la acreditación.
                                </div>
                            </div>

                            <div class="col-md-12">
                                <label class="form-label">Comprobante / Capture del Pago <span
                                        class="text-danger">*</span></label>
                                <input type="file" class="form-control" name="capture_pago" accept="image/*" required>
                                <div class="important-note mt-1">Solo se aceptan archivos de imagen (JPG, PNG).
                                </div>
                            </div>
                    </div>

                    <div class="d-grid mt-4">
                        <button type="submit" class="btn btn-report text-white btn-lg">
                            <i class="fas fa-paper-plane me-2"></i> ENVIAR REPORTE DE PAGO
                        </button>
                    </div>

                    </form>
                </div>
                <div class="card-footer bg-light text-center py-3 border-0 rounded-bottom">
                    <p class="mb-0 text-muted small">&copy;
                        <?php echo date('Y'); ?> Wireless Supply, C.A. Todos los derechos reservados.
                    </p>
                </div>
            </div>
        </div>
    </div>
    </div>

    <script src="js/bootstrap.bundle.min.js"></script>
    <script>
        const metodoPago = document.getElementById('metodo_pago');
        const divBanco = document.getElementById('div_banco');
        const divRef = document.getElementById('div_referencia');
        const inputBanco = document.getElementById('id_banco_destino');
        const inputRef = document.getElementById('referencia');

        metodoPago.addEventListener('change', function () {
            const val = this.value;
            if (val === 'Pago Móvil' || val === 'Transferencia' || val === 'Zelle') {
                divBanco.classList.remove('d-none');
                divRef.classList.remove('d-none');
                inputBanco.required = true;
                inputRef.required = true;
            } else {
                divBanco.classList.add('d-none');
                divRef.classList.add('d-none');
                inputBanco.required = false;
                inputRef.required = false;
            }
        });

        const checkVariosMeses = document.getElementById('paga_varios_meses');
        const btnAddMes = document.getElementById('add_mes_btn');
        const containerMeses = document.getElementById('container_meses');

        checkVariosMeses.addEventListener('change', function () {
            if (this.checked) {
                btnAddMes.classList.remove('d-none');
            } else {
                btnAddMes.classList.add('d-none');
                // Limpiar extras si se desmarca
                const selects = containerMeses.querySelectorAll('.selector-mes');
                if (selects.length > 1) {
                    for (let i = 1; i < selects.length; i++) {
                        selects[i].parentElement.remove();
                    }
                }
            }
        });

        const inputFecha = document.querySelector('[name="fecha_pago"]');
        const mesesNombres = <?php echo json_encode($meses_nombres); ?>;

        function generarOpcionesMeses(fechaStr) {
            const fecha = new Date(fechaStr + 'T00:00:00');
            const options = [];
            const year = fecha.getFullYear();
            const month = fecha.getMonth();

            // Generamos una ventana de 6 meses antes y 6 meses después de la fecha seleccionada
            for (let i = -6; i <= 6; i++) {
                const d = new Date(year, month + i, 1);
                const label = mesesNombres[d.getMonth()] + " " + d.getFullYear();
                options.push(label);
            }
            return options;
        }

        function actualizarTodosLosSelects() {
            const fechaVal = inputFecha.value;
            if (!fechaVal) return;

            const opciones = generarOpcionesMeses(fechaVal);
            const fechaObj = new Date(fechaVal + 'T00:00:00');
            const mesActualLabel = mesesNombres[fechaObj.getMonth()] + " " + fechaObj.getFullYear();

            document.querySelectorAll('.selector-mes').forEach(select => {
                const selectedVal = select.value;
                select.innerHTML = '<option value="">Seleccione mes...</option>';
                opciones.forEach(opt => {
                    const el = document.createElement('option');
                    el.value = opt;
                    el.textContent = opt;
                    if (opt === selectedVal || (!selectedVal && opt === mesActualLabel)) {
                        el.selected = true;
                    }
                    select.appendChild(el);
                });
            });
        }

        inputFecha.addEventListener('change', actualizarTodosLosSelects);

        // Ejecutar inicialmente
        actualizarTodosLosSelects();

        function agregarMes() {
            const selects = containerMeses.querySelectorAll('.selector-mes');
            if (selects.length >= 3) {
                Swal.fire('Límite alcanzado', 'Solo puede reportar un máximo de 3 meses por pago.', 'warning');
                return;
            }

            const div = document.createElement('div');
            div.className = 'mt-2 d-flex align-items-center month-row';

            div.innerHTML = `
                <select class="form-select selector-mes me-2" name="meses[]" required>
                    <option value="">Seleccione mes...</option>
                </select>
                <button type="button" class="btn btn-outline-danger btn-sm" onclick="removerMes(this)">
                    <i class="fas fa-times"></i>
                </button>
            `;
            containerMeses.appendChild(div);
            actualizarTodosLosSelects();
            verificarLimiteMeses();
        }

        window.removerMes = function (btn) {
            btn.parentElement.remove();
            verificarLimiteMeses();
        }

        function verificarLimiteMeses() {
            const selects = containerMeses.querySelectorAll('.selector-mes');
            if (selects.length >= 3) {
                btnAddMes.classList.add('d-none');
            } else {
                if (checkVariosMeses.checked) {
                    btnAddMes.classList.remove('d-none');
                }
            }
        }

        // ==============================================================
        // VALIDACIÓN Y RESTRICCIÓN DE CAMPOS EN TIEMPO REAL
        // ==============================================================

        // Cédula: solo dígitos (en este formulario no lleva prefijo V/J)
        document.getElementById('cedula')?.addEventListener('input', function () {
            this.value = this.value.replace(/[^0-9]/g, '');
        });

        // Nombre: solo letras y espacios
        document.getElementById('nombre')?.addEventListener('input', function () {
            this.value = this.value.replace(/[^A-Za-zñÑáéíóúÁÉÍÓÚ\s]/g, '');
        });

        // Teléfono: solo dígitos, +, - y espacios
        document.getElementById('telefono')?.addEventListener('input', function () {
            this.value = this.value.replace(/[^0-9+\-\s]/g, '');
        });

        // Referencia: solo dígitos
        document.getElementById('referencia')?.addEventListener('input', function () {
            this.value = this.value.replace(/[^0-9]/g, '');
        });

    </script>
</body>

</html>
```