<?php
require_once '../conexion.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_prorroga = (int) $_POST['id_prorroga'];
    $monto = (float) $_POST['monto'];
    $referencia = $_POST['referencia'];
    $id_banco = (int) $_POST['id_banco'];
    $nota = $_POST['nota'];

    // 1. Obtener datos de la prórroga
    $resP = $conn->query("SELECT * FROM prorrogas WHERE id_prorroga = $id_prorroga");
    if (!$resP || $resP->num_rows == 0) {
        die("Prórroga no encontrada");
    }
    $p = $resP->fetch_assoc();

    // 2. Registrar el pago en la base de datos (mensualidades y cuentas_por_cobrar)
    // Para simplificar, usaremos la lógica de generar_cobro_manual.php adaptada

    // Si tiene contrato asociado, usamos ese ID. Si no, es una venta nueva que requiere contrato primero.
    // Para este MVP, si no tiene id_contrato_asociado, lanzaremos error o lo dejaremos como pendiente de contrato.
    // Pero el usuario pidió "ingresarlo como un pago", así que asumiremos que si es VENTA, creamos el pago vinculado al nombre/cédula.

    $id_contrato = $p['id_contrato_asociado'] ?? 0;

    // Iniciar transacción
    $conn->begin_transaction();

    try {
        // Crear el cobro (cuentas_por_cobrar)
        $sqlC = "INSERT INTO cuentas_por_cobrar (id_contrato, fecha_emision, fecha_vencimiento, monto_total, estado, concepto) 
                 VALUES (?, CURRENT_DATE, CURRENT_DATE, ?, 'PAGADO', ?)";
        $stmtC = $conn->prepare($sqlC);
        $concepto = "Pago de Solicitud " . $p['tipo_solicitud'] . ": " . $nota;
        $stmtC->bind_param("ids", $id_contrato, $monto, $concepto);
        $stmtC->execute();
        $id_cobro = $conn->insert_id;

        // Registrar el pago (mensualidades)
        $sqlM = "INSERT INTO mensualidades (id_cobro, id_contrato, monto_pagado, fecha_pago, id_banco, referencia_pago, metodo_pago, estado, origen) 
                 VALUES (?, ?, ?, CURRENT_DATE, ?, ?, 'SISTEMA', 'PAGADO', 'SISTEMA')";
        $stmtM = $conn->prepare($sqlM);
        $ref = $referencia;
        $stmtM->bind_param("iidis", $id_cobro, $id_contrato, $monto, $id_banco, $ref);
        $stmtM->execute();

        // 3. Actualizar estatus de la prórroga
        $conn->query("UPDATE prorrogas SET estado = 'PROCESADO' WHERE id_prorroga = $id_prorroga");

        $conn->commit();
        header("Location: gestion_prorrogas.php?message=Pago procesado con éxito&class=success");
    } catch (Exception $e) {
        $conn->rollback();
        header("Location: gestion_prorrogas.php?message=Error al procesar: " . $e->getMessage() . "&class=danger");
    }
}
?>