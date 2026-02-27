<?php
/**
 * Gestión de Bancos - Migración a JSON API con Seguridad
 */
require_once 'conexion.php';

$message = isset($_GET['message']) ? $_GET['message'] : '';
$message_class = isset($_GET['class']) ? $_GET['class'] : '';

$page_title = "Gestión de Bancos";
$breadcrumb = ["Admin"];
$back_url = "menu.php";
require_once 'includes/layout_head.php';
require_once 'includes/sidebar.php';
?>

<main class="main-content">
    <?php include 'includes/header.php'; ?>

    <div class="page-content">
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_class == 'success' ? 'success' : 'danger'; ?> alert-dismissible fade show"
                role="alert">
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header d-flex flex-column flex-md-row justify-content-between align-items-center">
                <h5 class="mb-3 mb-md-0 fw-bold">Listado de Bancos (API JSON)</h5>
                <div class="d-flex gap-2 w-100 w-md-auto">
                    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal"
                        data-bs-target="#modalRegistroBanco">
                        <i class="fa-solid fa-plus me-1"></i> Nuevo Banco
                    </button>
                </div>
            </div>

            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="tabla_bancos_json">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-4">ID</th>
                                <th>Nombre del Banco</th>
                                <th>Número de Cuenta</th>
                                <th>Propietario</th>
                                <th class="text-end pe-4">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="lista_bancos_api">
                            <tr>
                                <td colspan="5" class="text-center p-4"><i
                                        class="fas fa-spinner fa-spin me-2"></i>Cargando datos...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Modal Registro/Nuevo -->
<div class="modal fade" id="modalRegistroBanco" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header border-bottom-0">
                <h5 class="modal-title fw-bold text-primary">Registrar Nuevo Banco</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="form-registro-banco">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small text-muted fw-bold text-uppercase">Nombre del Banco</label>
                        <input type="text" name="nombre_banco" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small text-muted fw-bold text-uppercase">Número de Cuenta</label>
                        <input type="text" name="numero_cuenta" class="form-control font-monospace"
                            placeholder="0000-0000-00-0000000000" required>
                    </div>
                    <div class="row">
                        <div class="col-md-5 mb-3">
                            <label class="form-label small text-muted fw-bold text-uppercase">Cédula</label>
                            <input type="text" name="cedula_propietario" class="form-control" placeholder="V-12345678"
                                required>
                        </div>
                        <div class="col-md-7 mb-3">
                            <label class="form-label small text-muted fw-bold text-uppercase">Titular</label>
                            <input type="text" name="titular_cuenta" class="form-control" placeholder="Nombre completo"
                                required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary px-4">Guardar Banco</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="../js/jquery.min.js"></script>

<script>
    const API_URL = 'principal/json_bancos_api.php';

    $(document).ready(function () {
        cargarBancos();
    });

    async function cargarBancos() {
        try {
            const resp = await fetch(API_URL + '?action=get');
            const data = await resp.json();
            const tbody = document.getElementById('lista_bancos_api');
            tbody.innerHTML = '';

            if (data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="5" class="text-center p-4 text-muted">No hay bancos registrados.</td></tr>';
                return;
            }

            data.forEach(b => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td class="ps-4 text-muted">#${b.id_banco}</td>
                    <td class="fw-bold">${b.nombre_banco}</td>
                    <td class="font-monospace text-muted">${b.numero_cuenta || 'N/A'}</td>
                    <td>
                        <div class="fw-semibold">${b.nombre_propietario || 'Sin titular'}</div>
                        <small class="text-muted">${b.cedula_propietario || ''}</small>
                    </td>
                    <td class="text-end pe-4">
                        <button class="btn btn-light btn-sm text-danger" onclick="eliminarBanco('${b.id_banco}')" title="Eliminar">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </td>
                `;
                tbody.appendChild(tr);
            });
        } catch (e) {
            console.error(e);
            document.getElementById('lista_bancos_api').innerHTML = '<tr><td colspan="5" class="text-center text-danger p-4">Error al cargar datos.</td></tr>';
        }
    }

    async function solicitarClaveAdmin(titulo = 'Confirmar Acción') {
        // Fix for Bootstrap modal focus trap
        const focusHandler = (e) => {
            if (e.target.closest(".swal2-container")) {
                e.stopImmediatePropagation();
            }
        };
        document.addEventListener('focusin', focusHandler, true);

        const { value: password } = await Swal.fire({
            title: titulo,
            input: 'password',
            inputLabel: 'Ingrese la clave de administrador para proceder',
            inputPlaceholder: 'Clave de seguridad',
            showCancelButton: true,
            confirmButtonText: 'Confirmar',
            cancelButtonText: 'Cancelar',
            didClose: () => {
                document.removeEventListener('focusin', focusHandler, true);
            }
        });

        if (password) {
            const resp = await fetch('principal/verificar_clave.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'clave=' + encodeURIComponent(password)
            });
            const data = await resp.json();
            if (data.success) return true;
            Swal.fire('Error', 'Clave incorrecta', 'error');
        }
        return false;
    }

    $('#form-registro-banco').on('submit', async function (e) {
        e.preventDefault();

        const proceeds = await solicitarClaveAdmin('Registrar Nuevo Banco');
        if (!proceeds) return;

        const formData = new FormData(this);
        try {
            const resp = await fetch(API_URL + '?action=add', {
                method: 'POST',
                body: formData
            });
            const res = await resp.json();
            if (res.success) {
                Swal.fire('¡Éxito!', 'Banco registrado correctamente.', 'success');
                bootstrap.Modal.getInstance(document.getElementById('modalRegistroBanco')).hide();
                this.reset();
                cargarBancos();
            } else {
                Swal.fire('Error', res.message || 'Error al guardar', 'error');
            }
        } catch (e) {
            Swal.fire('Error', 'No se pudo conectar con el servidor', 'error');
        }
    });

    window.eliminarBanco = async function (id) {
        const proceeds = await solicitarClaveAdmin('Eliminar Banco');
        if (!proceeds) return;

        const formData = new FormData();
        formData.append('id', id);
        try {
            const resp = await fetch(API_URL + '?action=delete', {
                method: 'POST',
                body: formData
            });
            const res = await resp.json();
            if (res.success) {
                Swal.fire('Eliminado', 'El banco ha sido eliminado con éxito.', 'success');
                cargarBancos();
            } else {
                Swal.fire('Error', res.message || 'Error al eliminar', 'error');
            }
        } catch (e) {
            Swal.fire('Error', 'No se pudo conectar con el servidor', 'error');
        }
    };
</script>

<?php require_once 'includes/layout_foot.php'; ?>