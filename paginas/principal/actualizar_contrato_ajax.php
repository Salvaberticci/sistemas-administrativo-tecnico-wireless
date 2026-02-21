<?php
/**
 * Ajax endpoint for updating a contract. Returns JSON {success, message}.
 */
ini_set('display_errors', 0);
header('Content-Type: application/json');
session_start();
require '../conexion.php';

// --- Capture & sanitize ---
$id = intval($_POST['id'] ?? 0);
$cedula = $conn->real_escape_string($_POST['cedula'] ?? '');
$nombre_completo = $conn->real_escape_string($_POST['nombre_completo'] ?? '');
$telefono = $conn->real_escape_string($_POST['telefono'] ?? '');
$correo = $conn->real_escape_string($_POST['correo'] ?? '');
$telefono_sec = $conn->real_escape_string($_POST['telefono_secundario'] ?? '');
$correo_adicional = $conn->real_escape_string($_POST['correo_adicional'] ?? '');
$id_municipio = intval($_POST['id_municipio'] ?? 0) ?: 'NULL';
$id_parroquia = intval($_POST['id_parroquia'] ?? 0) ?: 'NULL';
$id_comunidad = intval($_POST['id_comunidad'] ?? 0) ?: 'NULL';
$id_plan = intval($_POST['id_plan'] ?? 0) ?: 'NULL';
$id_vendedor = intval($_POST['id_vendedor'] ?? 0) ?: 'NULL';
$id_olt = intval($_POST['id_olt'] ?? 0) ?: 'NULL';
$id_pon = intval($_POST['id_pon'] ?? 0) ?: 'NULL';
$direccion = $conn->real_escape_string($_POST['direccion'] ?? '');
$fecha_inst = $conn->real_escape_string($_POST['fecha_instalacion'] ?? '');
$estado = $conn->real_escape_string($_POST['estado'] ?? '');
$ident_caja_nap = trim($conn->real_escape_string($_POST['ident_caja_nap'] ?? ''));
$ident_caja_nap = !empty($ident_caja_nap) ? $ident_caja_nap : null;
$puerto_nap = trim($conn->real_escape_string($_POST['puerto_nap'] ?? ''));
$puerto_nap = !empty($puerto_nap) ? $puerto_nap : null;
$num_presinto_odn = trim($conn->real_escape_string($_POST['num_presinto_odn'] ?? ''));
$tipo_conexion = trim($conn->real_escape_string($_POST['tipo_conexion'] ?? ''));
$mac_onu = strtoupper(trim($conn->real_escape_string($_POST['mac_onu'] ?? '')));
$ip_onu = trim($conn->real_escape_string($_POST['ip_onu'] ?? ''));
$nap_tx_power = trim($conn->real_escape_string($_POST['nap_tx_power'] ?? ''));
$nap_tx_power = !empty($nap_tx_power) ? $nap_tx_power : null;
$onu_rx_power = trim($conn->real_escape_string($_POST['onu_rx_power'] ?? ''));
$onu_rx_power = !empty($onu_rx_power) ? $onu_rx_power : null;
$distancia_drop = trim($conn->real_escape_string($_POST['distancia_drop'] ?? ''));
$distancia_drop = !empty($distancia_drop) ? $distancia_drop : null;
$punto_acceso = trim($conn->real_escape_string($_POST['punto_acceso'] ?? ''));
$punto_acceso = !empty($punto_acceso) ? $punto_acceso : null;
$valor_conexion = trim($conn->real_escape_string($_POST['valor_conexion_dbm'] ?? ''));
$valor_conexion = !empty($valor_conexion) ? $valor_conexion : null;
$observaciones = $conn->real_escape_string($_POST['observaciones'] ?? '');

if (!$id) {
    echo json_encode(['success' => false, 'message' => 'ID de contrato inválido.']);
    exit;
}

// validate fields
$errors = [];
if (empty($cedula))
    $errors[] = 'La cédula es obligatoria.';
if (empty($nombre_completo))
    $errors[] = 'El nombre es obligatorio.';
if (empty($fecha_inst))
    $errors[] = 'La fecha de instalación es obligatoria.';
if (empty($estado))
    $errors[] = 'El estado es obligatorio.';

if (!empty($errors)) {
    echo json_encode(['success' => false, 'message' => implode('<br>', $errors)]);
    exit;
}

// Extra pattern validation
if (!empty($cedula) && !preg_match('/^[VJEGP]\d+$/i', $cedula)) {
    echo json_encode(['success' => false, 'message' => 'Formato de Cédula inválido. Debe empezar con V, J, E, G o P seguida de números.']);
    exit;
}

if (!empty($ip_onu) && $ip_onu !== '192.168.' && !preg_match('/^(?:\d{1,3}\.){3}\d{1,3}$/', $ip_onu)) {
    echo json_encode(['success' => false, 'message' => 'Formato de IP ONU inválido.']);
    exit;
}

// Validar Teléfonos
if (!empty($telefono) && !preg_match('/^[0-9-+\s]{7,15}$/', $telefono)) {
    echo json_encode(['success' => false, 'message' => 'Formato de Teléfono principal inválido.']);
    exit;
}
if (!empty($telefono_sec) && !preg_match('/^[0-9-+\s]{7,15}$/', $telefono_sec)) {
    echo json_encode(['success' => false, 'message' => 'Formato de Teléfono secundario inválido.']);
    exit;
}

// Validar Correos
if (!empty($correo) && !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Formato de Correo principal inválido.']);
    exit;
}
if (!empty($correo_adicional) && !filter_var($correo_adicional, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Formato de Correo adicional inválido.']);
    exit;
}

// Validar MAC / Serial
if (!empty($mac_onu) && !preg_match('/^[A-Za-z0-9:.-]{8,20}$/', $mac_onu)) {
    echo json_encode(['success' => false, 'message' => 'Formato de MAC o Serial ONU inválido.']);
    exit;
}

// Validar Potencias (dBm)
if (!empty($nap_tx_power) && !preg_match('/^-?\d+(\.\d+)?$/', $nap_tx_power)) {
    echo json_encode(['success' => false, 'message' => 'El valor NAP TX Power debe ser numérico.']);
    exit;
}
if (!empty($onu_rx_power) && !preg_match('/^-?\d+(\.\d+)?$/', $onu_rx_power)) {
    echo json_encode(['success' => false, 'message' => 'El valor ONU RX Power debe ser numérico.']);
    exit;
}
if (!empty($valor_conexion) && !preg_match('/^-?\d+(\.\d+)?$/', $valor_conexion)) {
    echo json_encode(['success' => false, 'message' => 'El valor de Conexión (dBm) debe ser numérico.']);
    exit;
}

// 2. <<<< VALIDACIÓN DE IP DUPLICADA >>>>
$ip = trim($ip);
$ip_onu = trim($ip_onu);

if (!empty($ip)) {
    $stmt_ip = $conn->prepare("SELECT id FROM contratos WHERE ip = ? AND id != ? LIMIT 1");
    $stmt_ip->bind_param("si", $ip, $id);
    $stmt_ip->execute();
    if ($stmt_ip->get_result()->num_rows > 0) {
        echo json_encode(['status' => 'error', 'msg' => "La IP '{$ip}' ya está registrada en otro contrato."]);
        exit;
    }
}

if (!empty($ip_onu)) {
    $stmt_ip_onu = $conn->prepare("SELECT id FROM contratos WHERE ip_onu = ? AND id != ? LIMIT 1");
    $stmt_ip_onu->bind_param("si", $ip_onu, $id);
    $stmt_ip_onu->execute();
    if ($stmt_ip_onu->get_result()->num_rows > 0) {
        echo json_encode(['status' => 'error', 'msg' => "La IP de ONU '{$ip_onu}' ya está registrada en otro contrato."]);
        exit;
    }
}
// The original instruction closes $stmt_ip here.
// If $ip was empty, $stmt_ip might not be initialized.
// To be safe, close only if it was opened.
if (isset($stmt_ip) && $stmt_ip instanceof mysqli_stmt) {
    $stmt_ip->close();
}
if (isset($stmt_ip_onu) && $stmt_ip_onu instanceof mysqli_stmt) {
    $stmt_ip_onu->close();
}


// Validar Vendedor
function validarFK($conn, $tabla, $columna, $valor)
{
    if (empty($valor))
        return null;
    $sql = "SELECT $columna FROM $tabla WHERE $columna = '$valor' LIMIT 1";
    $res = $conn->query($sql);
    return ($res && $res->num_rows > 0) ? $valor : null;
}

function getMunicipioId($conn, $nombre)
{
    $nombre = trim($conn->real_escape_string($nombre));
    if (empty($nombre))
        return null;
    $sql = "SELECT id_municipio FROM municipio WHERE nombre_municipio = '$nombre' LIMIT 1";
    $res = $conn->query($sql);
    if ($res && $res->num_rows > 0)
        return $res->fetch_assoc()['id_municipio'];
    if ($conn->query("INSERT INTO municipio (nombre_municipio) VALUES ('$nombre')"))
        return $conn->insert_id;
    return null;
}

function getParroquiaId($conn, $nombre, $id_municipio)
{
    $nombre = trim($conn->real_escape_string($nombre));
    if (empty($nombre) || empty($id_municipio))
        return null;
    $sql = "SELECT id_parroquia FROM parroquia WHERE nombre_parroquia = '$nombre' LIMIT 1";
    $res = $conn->query($sql);
    if ($res && $res->num_rows > 0)
        return $res->fetch_assoc()['id_parroquia'];
    if ($conn->query("INSERT INTO parroquia (nombre_parroquia, id_municipio) VALUES ('$nombre', $id_municipio)"))
        return $conn->insert_id;
    return null;
}

function getComunidadId($conn, $nombre, $id_parroquia)
{
    $nombre = trim($conn->real_escape_string($nombre));
    if (empty($nombre) || empty($id_parroquia))
        return null;
    $sql = "SELECT id_comunidad FROM comunidad WHERE nombre_comunidad = '$nombre' LIMIT 1";
    $res = $conn->query($sql);
    if ($res && $res->num_rows > 0)
        return $res->fetch_assoc()['id_comunidad'];
    if ($conn->query("INSERT INTO comunidad (nombre_comunidad, id_parroquia) VALUES ('$nombre', $id_parroquia)"))
        return $conn->insert_id;
    return null;
}

// --- FIRMAS (New) ---
$firma_cliente_data = $_POST['firma_cliente_data'] ?? '';
$firma_tecnico_data = $_POST['firma_tecnico_data'] ?? '';

$sql_firma_cliente = "";
$sql_firma_tecnico = "";

$upload_dir = "../../uploads/firmas/";
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

if (!empty($firma_cliente_data)) {
    $img = str_replace('data:image/png;base64,', '', $firma_cliente_data);
    $img = str_replace(' ', '+', $img);
    $data = base64_decode($img);
    $filename = "firma_cli_" . $id . "_" . time() . ".png";
    $filepath = $upload_dir . $filename;
    if (file_put_contents($filepath, $data)) {
        $sql_firma_cliente = ", firma_cliente = '$filename'";
    }
}

if (!empty($firma_tecnico_data)) {
    $img = str_replace('data:image/png;base64,', '', $firma_tecnico_data);
    $img = str_replace(' ', '+', $img);
    $data = base64_decode($img);
    $filename = "firma_tec_" . $id . "_" . time() . ".png";
    $filepath = $upload_dir . $filename;
    if (file_put_contents($filepath, $data)) {
        $sql_firma_tecnico = ", firma_tecnico = '$filename'";
    }
}

$resolved_mun = getMunicipioId($conn, $id_municipio);
$resolved_par = getParroquiaId($conn, $id_parroquia, $resolved_mun);
$resolved_com = getComunidadId($conn, $id_comunidad, $resolved_par);

// Build nullable FK values
$mun = ($resolved_mun === null || $resolved_mun === 'NULL') ? 'NULL' : $resolved_mun;
$par = ($resolved_par === null || $resolved_par === 'NULL') ? 'NULL' : $resolved_par;
$com = ($resolved_com === null || $resolved_com === 'NULL') ? 'NULL' : $resolved_com;
$pln = ($id_plan === 'NULL') ? 'NULL' : $id_plan;
$vnd = ($id_vendedor === 'NULL') ? 'NULL' : $id_vendedor;
$olt = ($id_olt === 'NULL') ? 'NULL' : $id_olt;
$pon = ($id_pon === 'NULL') ? 'NULL' : $id_pon;

$sql = "UPDATE contratos SET
    cedula='$cedula', nombre_completo='$nombre_completo',
    telefono='$telefono', correo='$correo',
    telefono_secundario='$telefono_sec', correo_adicional='$correo_adicional',
    id_municipio=$mun, id_parroquia=$par, id_comunidad=$com,
    id_plan=$pln, id_vendedor=$vnd,
    direccion='$direccion', fecha_instalacion='$fecha_inst', estado='$estado',
    ident_caja_nap='$ident_caja_nap', puerto_nap='$puerto_nap', num_presinto_odn='$num_presinto_odn',
    id_olt=$olt, id_pon=$pon,
    tipo_conexion='$tipo_conexion', mac_onu='$mac_onu', ip_onu='$ip_onu',
    nap_tx_power='$nap_tx_power', onu_rx_power='$onu_rx_power', distancia_drop='$distancia_drop',
    punto_acceso='$punto_acceso', valor_conexion_dbm='$valor_conexion',
    observaciones='$observaciones'
    $sql_firma_cliente
    $sql_firma_tecnico
    WHERE id=$id";

if ($conn->query($sql)) {
    echo json_encode(['success' => true, 'message' => 'Contrato actualizado correctamente.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Error en BD: ' . $conn->error]);
}

$conn->close();
?>