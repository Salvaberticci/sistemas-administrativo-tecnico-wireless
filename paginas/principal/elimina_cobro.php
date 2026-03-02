<?php
// elimina_cobro.php - Elimina una cuenta por cobrar después de verificar la clave
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
require '../conexion.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: gestion_mensualidades.php?maintenance_done=1&message=Método no permitido&class=danger");
    exit();
}

$id_cobro = isset($_POST['id']) ? intval($_POST['id']) : 0;
$clave = isset($_POST['clave']) ? $_POST['clave'] : '';

if ($id_cobro <= 0) {
    header("Location: gestion_mensualidades.php?maintenance_done=1&message=ID de cobro no válido&class=danger");
    exit();
}

// 1. Verificar sesión y clave
if (!isset($_SESSION['usuario_id'])) {
    header("Location: gestion_mensualidades.php?maintenance_done=1&message=Sesión no iniciada&class=danger");
    exit();
}

$usuario_id = $_SESSION['usuario_id'];
$stmt_user = $conn->prepare("SELECT clave FROM usuarios WHERE id_usuario = ?");
$stmt_user->bind_param("i", $usuario_id);
$stmt_user->execute();
$res_user = $stmt_user->get_result();
$user = $res_user->fetch_assoc();

if (!$user || !password_verify($clave, $user['clave'])) {
    header("Location: gestion_mensualidades.php?maintenance_done=1&message=Contraseña administrativa incorrecta&class=danger");
    exit();
}

// 2. Obtener el estado del cobro antes de eliminar (para validación)
$stmt_check = $conn->prepare("SELECT estado FROM cuentas_por_cobrar WHERE id_cobro = ?");
$stmt_check->bind_param("i", $id_cobro);
$stmt_check->execute();
$result_check = $stmt_check->get_result();
$cobro = $result_check->fetch_assoc();
$stmt_check->close();

if (!$cobro) {
    header("Location: gestion_mensualidades.php?maintenance_done=1&message=El cobro no existe&class=danger");
    exit();
}

if ($cobro['estado'] == 'PAGADO') {
    header("Location: gestion_mensualidades.php?maintenance_done=1&message=No se puede eliminar un cobro PAGADO&class=danger");
    exit();
}

// 3. Eliminar registros
$conn->begin_transaction();
try {
    // Eliminar historial
    $stmt_hist = $conn->prepare("DELETE FROM cobros_manuales_historial WHERE id_cobro_cxc = ?");
    $stmt_hist->bind_param("i", $id_cobro);
    $stmt_hist->execute();

    // Eliminar CxC
    $stmt_del = $conn->prepare("DELETE FROM cuentas_por_cobrar WHERE id_cobro = ?");
    $stmt_del->bind_param("i", $id_cobro);
    $stmt_del->execute();

    $conn->commit();
    header("Location: gestion_mensualidades.php?maintenance_done=1&message=Cobro eliminado correctamente&class=success");
} catch (Exception $e) {
    $conn->rollback();
    header("Location: gestion_mensualidades.php?maintenance_done=1&message=Error al eliminar: " . urlencode($e->getMessage()) . "&class=danger");
}

$conn->close();
exit();
?>