<?php

/**
 * Script para actualizar los datos del registro
 *
 * Este script recibe los datos del registro a través del método POST
 * y realiza la actualización en la base de datos. También permite la carga de archivos adjuntos.
 *
 * @author MRoblesDev
 * @version 1.0
 * https://github.com/mroblesdev
 *
 */

require '../conexion.php';

// 1. CAPTURA Y SANEO DE DATOS
$id = intval($_POST['id']);
$ip = trim($conn->real_escape_string($_POST['ip'] ?? ''));
$cedula = $conn->real_escape_string($_POST['cedula'] ?? '');
$nombre_completo = $conn->real_escape_string($_POST['nombre_completo'] ?? '');
$telefono = $conn->real_escape_string($_POST['telefono'] ?? '');
$correo = $conn->real_escape_string($_POST['correo'] ?? '');
$id_municipio = $conn->real_escape_string($_POST['id_municipio'] ?? '');
$id_parroquia = $conn->real_escape_string($_POST['id_parroquia'] ?? '');
$id_comunidad = $conn->real_escape_string($_POST['id_comunidad'] ?? ''); // 🔑 NUEVO: Captura de id_comunidad
$id_plan = $conn->real_escape_string($_POST['id_plan'] ?? '');
$id_vendedor = $conn->real_escape_string($_POST['id_vendedor'] ?? '');
$direccion = $conn->real_escape_string($_POST['direccion'] ?? '');
$fecha_instalacion = $conn->real_escape_string($_POST['fecha_instalacion'] ?? '');
$estado = $conn->real_escape_string($_POST['estado'] ?? '');
$ident_caja_nap = $conn->real_escape_string($_POST['ident_caja_nap'] ?? '');
$puerto_nap = $conn->real_escape_string($_POST['puerto_nap'] ?? '');
$num_presinto_odn = $conn->real_escape_string($_POST['num_presinto_odn'] ?? '');
$id_olt = $conn->real_escape_string($_POST['id_olt'] ?? '');
$id_pon = $conn->real_escape_string($_POST['id_pon'] ?? '');

// NUEVOS CAMPOS TÉCNICOS
$tipo_conexion = $conn->real_escape_string($_POST['tipo_conexion'] ?? '');
$mac_onu = strtoupper(trim($conn->real_escape_string($_POST['mac_onu'] ?? '')));
$ip_onu = trim($conn->real_escape_string($_POST['ip_onu'] ?? ''));
$nap_tx_power = $conn->real_escape_string($_POST['nap_tx_power'] ?? '');
$onu_rx_power = $conn->real_escape_string($_POST['onu_rx_power'] ?? '');
$distancia_drop = $conn->real_escape_string($_POST['distancia_drop'] ?? '');
$punto_acceso = $conn->real_escape_string($_POST['punto_acceso'] ?? '');
$valor_conexion_dbm = $conn->real_escape_string($_POST['valor_conexion_dbm'] ?? '');

$errores = '';

// 2. VALIDACIÓN DE IP DUPLICADA (Excluyendo el ID actual)
$ip = trim($ip);
$ip_onu = trim($ip_onu ?? '');

if (!empty($ip)) {
	$stmt_ip = $conn->prepare("SELECT id FROM contratos WHERE ip = ? AND id != ? LIMIT 1");
	if ($stmt_ip) {
		$stmt_ip->bind_param('si', $ip, $id);
		$stmt_ip->execute();
		$stmt_ip->store_result();
		if ($stmt_ip->num_rows > 0) {
			$errores .= "La IP '{$ip}' ya está registrada en otro contrato. Por favor, ingrese una IP única.<br>";
		}
		$stmt_ip->close();
	} else {
		$errores .= "Error interno al verificar la IP (Prepare failed).<br>";
	}
}

if (!empty($ip_onu)) {
	$stmt_ip_onu = $conn->prepare("SELECT id FROM contratos WHERE ip_onu = ? AND id != ? LIMIT 1");
	if ($stmt_ip_onu) {
		$stmt_ip_onu->bind_param('si', $ip_onu, $id);
		$stmt_ip_onu->execute();
		$stmt_ip_onu->store_result();
		if ($stmt_ip_onu->num_rows > 0) {
			$errores .= "La IP de ONU '{$ip_onu}' ya está registrada en otro contrato. Por favor, ingrese una IP única.<br>";
		}
		$stmt_ip_onu->close();
	} else {
		$errores .= "Error interno al verificar la IP de ONU (Prepare failed).<br>";
	}
}


// CHEQUEO DE CAMPOS OBLIGATORIOS (Prevención de Error 500 por Strict SQL Mode)
if (empty($id_municipio))
	$errores .= "El campo Municipio es obligatorio.<br>";
if (empty($id_parroquia))
	$errores .= "El campo Parroquia es obligatorio.<br>";
if (empty($id_comunidad))
	$errores .= "El campo Comunidad es obligatorio.<br>";
if (empty($id_plan))
	$errores .= "El campo Plan es obligatorio.<br>";
if (empty($id_vendedor))
	$errores .= "El campo Vendedor es obligatorio.<br>";
if (empty($id_olt))
	$errores .= "El campo OLT es obligatorio.<br>";
if (empty($id_pon))
	$errores .= "El campo PON es obligatorio.<br>";
if (empty($fecha_instalacion))
	$errores .= "La Fecha de Instalación es obligatoria.<br>";
if (empty($estado))
	$errores .= "El Estado es obligatorio.<br>";



// 3. EJECUCIÓN DE LA ACTUALIZACIÓN SOLO SI NO HAY ERRORES
if (empty($errores)) {
	// 🔑 MODIFICADO: Se agrega el campo id_comunidad
	$sql = "UPDATE contratos SET ip='$ip', cedula='$cedula', nombre_completo='$nombre_completo', telefono='$telefono', correo='$correo',
	 id_municipio='$id_municipio', id_parroquia='$id_parroquia', id_comunidad='$id_comunidad', id_plan='$id_plan', id_vendedor='$id_vendedor', direccion='$direccion',
	  fecha_instalacion='$fecha_instalacion', estado='$estado', ident_caja_nap='$ident_caja_nap', puerto_nap='$puerto_nap',
	   num_presinto_odn='$num_presinto_odn', id_olt='$id_olt', id_pon='$id_pon',
	   tipo_conexion='$tipo_conexion', mac_onu='$mac_onu', ip_onu='$ip_onu', nap_tx_power='$nap_tx_power', onu_rx_power='$onu_rx_power',
	   distancia_drop='$distancia_drop', punto_acceso='$punto_acceso', valor_conexion_dbm='$valor_conexion_dbm'
	   WHERE id = $id";

	$resultado = $conn->query($sql);

	// ... (El código de manejo de archivos adjuntos comentado permanece igual)
	// ...
} else {
	// Si hay errores, forzamos $resultado a false para mostrar el mensaje de error
	$resultado = false;
}

// ... (El resto del script HTML permanece igual)
// Configuración del Layout
$path_to_root = "../../";
$page_title = "Actualización de Contrato";
$breadcrumb = ["Admin", "Gestión de Contratos"];
$back_url = "gestion_contratos.php";
require_once '../includes/layout_head.php';
require_once '../includes/sidebar.php';
?>

<main class="main-content">
	<?php include '../includes/header.php'; ?>

	<div class="page-content">
		<div class="card">
			<div class="card-header bg-white border-bottom-0 pt-4 px-4">
				<h5 class="fw-bold text-primary mb-1">Resultado de la Actualización</h5>
			</div>

			<div class="card-body px-4 text-center py-5">
				<?php if ($resultado) { ?>
					<div class="mb-4">
						<i class="bi bi-check-circle-fill text-success" style="font-size: 4rem;"></i>
					</div>
					<h3 class="text-success fw-bold mb-3">REGISTRO ACTUALIZADO CORRECTAMENTE</h3>
					<p class="text-muted mb-4">El contrato con ID: <b><?php echo $id; ?></b> ha sido actualizado
						exitosamente en el sistema.</p>

					<div class="d-flex justify-content-center gap-2">
						<a href="gestion_contratos.php" class="btn btn-primary px-4">
							<i class="bi bi-arrow-left me-2"></i>Regresar al Listado
						</a>
						<!-- Opcional: Botón para volver a editar -->
						<a href="modifica.php?id=<?php echo $id; ?>" class="btn btn-outline-secondary px-4">
							<i class="bi bi-pencil me-2"></i>Volver a Editar
						</a>
					</div>

				<?php } else { ?>
					<div class="mb-4">
						<i class="bi bi-x-circle-fill text-danger" style="font-size: 4rem;"></i>
					</div>
					<h3 class="text-danger fw-bold mb-3">ERROR AL ACTUALIZAR</h3>

					<?php if (!empty($errores)) { ?>
						<div class="alert alert-danger d-inline-block text-start" role="alert" style="max-width: 600px;">
							<h6 class="alert-heading fw-bold"><i class="bi bi-exclamation-triangle me-2"></i>Detalles del error:
							</h6>
							<hr>
							<div class="mb-0"><?php echo $errores; ?></div>
						</div>
					<?php } else { ?>
						<p class="text-muted">Hubo un error inesperado al intentar actualizar el registro.</p>
					<?php } ?>

					<div class="mt-4">
						<a href="javascript:history.back()" class="btn btn-secondary px-4 me-2">
							<i class="bi bi-arrow-left me-2"></i>Volver Atrás
						</a>
						<a href="gestion_contratos.php" class="btn btn-outline-primary px-4">
							<i class="bi bi-list-ul me-2"></i>Ir al Listado
						</a>
					</div>
				<?php } ?>
			</div>
		</div>
	</div>
</main>

<?php require_once '../includes/layout_foot.php'; ?>