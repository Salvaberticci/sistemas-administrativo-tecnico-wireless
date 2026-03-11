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

// 2. Obtener el estado y Referencia del cobro antes de eliminar
$stmt_check = $conn->prepare("SELECT estado, referencia_pago FROM cuentas_por_cobrar WHERE id_cobro = ?");
$stmt_check->bind_param("i", $id_cobro);
$stmt_check->execute();
$result_check = $stmt_check->get_result();
$cobro = $result_check->fetch_assoc();
$stmt_check->close();

if (!$cobro) {
    header("Location: gestion_mensualidades.php?maintenance_done=1&message=El cobro no existe&class=danger");
    exit();
}

$referencia = $cobro['referencia_pago'];

// NUEVA LÓGICA: Se permite eliminar cobros PAGADOS si provienen de un cobro manual (Tienen referencia común).
// El botón en el Frontend (gestion_mensualidades) se activará para todos los cobros manuales.

// 3. Eliminar registros
$conn->begin_transaction();
try {
    if (!empty($referencia)) {
        // ES UN PAGO GLOBAL (Capture desglosado)
        // Borrar todo el bloque basándonos en la referencia
        
        // 3.1 Obtener todos los IDs afectados
        $stmt_ids = $conn->prepare("SELECT id_cobro FROM cuentas_por_cobrar WHERE referencia_pago = ?");
        $stmt_ids->bind_param("s", $referencia);
        $stmt_ids->execute();
        $res_ids = $stmt_ids->get_result();
        
        $ids_a_borrar = [];
        while($fila = $res_ids->fetch_assoc()) {
            $ids_a_borrar[] = $fila['id_cobro'];
        }
        $stmt_ids->close();

        if (count($ids_a_borrar) > 0) {
            $in_clause = implode(',', $ids_a_borrar);
            
            // Eliminar historiales asociados
            $conn->query("DELETE FROM cobros_manuales_historial WHERE id_cobro_cxc IN ($in_clause)");
            
            // Eliminar CxC asociados
            $conn->query("DELETE FROM cuentas_por_cobrar WHERE id_cobro IN ($in_clause)");
        }

        $msg = "Se eliminó el capture completo (Referencia $referencia), afectando " . count($ids_a_borrar) . " registro(s).";

    } else {
        // ES UN COBRO AISLADO (Viejo sistema o sin referencia)
        if ($cobro['estado'] == 'PAGADO') {
            throw new Exception("No se puede eliminar un cobro PAGADO antiguo sin referencia vinculada.");
        }
        // Eliminar historial
        $stmt_hist = $conn->prepare("DELETE FROM cobros_manuales_historial WHERE id_cobro_cxc = ?");
        $stmt_hist->bind_param("i", $id_cobro);
        $stmt_hist->execute();

        // Eliminar CxC
        $stmt_del = $conn->prepare("DELETE FROM cuentas_por_cobrar WHERE id_cobro = ?");
        $stmt_del->bind_param("i", $id_cobro);
        $stmt_del->execute();

        $msg = "Cobro aislado eliminado correctamente.";
    }

    $conn->commit();
    header("Location: gestion_mensualidades.php?maintenance_done=1&message=" . urlencode($msg) . "&class=success");
} catch (Exception $e) {
    $conn->rollback();
    header("Location: gestion_mensualidades.php?maintenance_done=1&message=Error al eliminar: " . urlencode($e->getMessage()) . "&class=danger");
}

$conn->close();
exit();
?>