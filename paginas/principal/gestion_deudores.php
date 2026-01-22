<?php
/**
 * Gestión de Clientes Deudores
 */
require_once '../conexion.php';

$path_to_root = "../../";
$page_title = "Clientes Deudores";
require_once '../includes/layout_head.php';
require_once '../includes/sidebar.php';
?>

<link href="<?php echo $path_to_root; ?>css/datatables.min.css" rel="stylesheet">

<main class="main-content">
    <?php include '../includes/header.php'; ?>

    <div class="page-content">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center bg-white border-bottom-0 pt-4 px-4">
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
                            $sql = "SELECT d.*, c.nombre_completo, c.cedula, c.ip 
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
                                    <td><code>{$row['ip']}</code></td>
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
                                            <a href='../principal/modifica.php?id={$row['id_contrato']}' class='btn btn-sm btn-outline-primary' title='Ver Contrato'>
                                                <i class='fa-solid fa-eye'></i>
                                            </a>
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

<?php require_once '../includes/layout_foot.php'; ?>

<script src="<?php echo $path_to_root; ?>js/jquery.min.js"></script>
<script src="<?php echo $path_to_root; ?>js/datatables.min.js"></script>

<script>
$(document).ready(function() {
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
        $.post('marcar_pagado.php', { id: id }, function(resp) {
            if (resp === 'OK') {
                alert('Cliente marcado como pagado');
                location.reload();
            } else {
                alert('Error al actualizar');
            }
        });
    }
}
</script>
