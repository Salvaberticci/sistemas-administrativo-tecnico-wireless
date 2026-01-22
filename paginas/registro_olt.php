<?php
require_once 'conexion.php';

$message = '';
$message_class = '';
$parroquias = [];

// Consulta para obtener las parroquias
$sql_parroquias = "SELECT id_parroquia, nombre_parroquia FROM parroquia ORDER BY nombre_parroquia ASC";
$result_parroquias = $conn->query($sql_parroquias);

if ($result_parroquias && $result_parroquias->num_rows > 0) {
    while ($row = $result_parroquias->fetch_assoc()) {
        $parroquias[] = $row;
    }
}

// Verifica si se ha enviado el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_olt = $_POST['id_olt'];
    $nombre_olt = $_POST['nombre_olt'];
    $marca = $_POST['marca'];
    $modelo = $_POST['modelo'];
    $parroquias_seleccionadas = isset($_POST['parroquias_id']) ? $_POST['parroquias_id'] : [];
    $descripcion = $_POST['descripcion'];

    $conn->begin_transaction();
    
    try {
        // 1. Insertar la OLT
        $stmt_olt = $conn->prepare("INSERT INTO olt (id_olt, nombre_olt, marca, modelo, descripcion) VALUES (?, ?, ?, ?, ?)");
        $stmt_olt->bind_param("issss", $id_olt, $nombre_olt, $marca, $modelo, $descripcion); 
        
        if (!$stmt_olt->execute()) {
             if ($stmt_olt->errno == 1062) {
                 throw new Exception("Error: El ID de OLT o el Nombre ya existen.");
             }
            throw new Exception("Error al insertar OLT: " . $stmt_olt->error);
        }
        $stmt_olt->close();

        // 2. Insertar las relaciones con parroquias
        if (!empty($parroquias_seleccionadas)) {
            $stmt_relacion = $conn->prepare("INSERT INTO olt_parroquia (olt_id, parroquia_id) VALUES (?, ?)");
            foreach ($parroquias_seleccionadas as $parroquia_id) {
                $stmt_relacion->bind_param("ii", $id_olt, $parroquia_id); 
                if (!$stmt_relacion->execute()) {
                    throw new Exception("Error al insertar relación con parroquia: " . $stmt_relacion->error);
                }
            }
            $stmt_relacion->close();
        }

        $conn->commit();
        $message = "¡OLT registrada y parroquias asignadas con éxito!";
        $message_class = 'success';
        $_POST = array();
        
    } catch (Exception $e) {
        $conn->rollback();
        $message = "Error en el registro: " . $e->getMessage();
        $message_class = 'error';
    }

    $conn->close();
} else {
    if (isset($conn)) {
        $conn->close();
    }
}

$path_to_root = "../";
$page_title = "Registro de OLT";
require_once 'includes/layout_head.php';
require_once 'includes/sidebar.php';
?>

<style>
.checkbox-group {
    border: 1px solid #dee2e6;
    padding: 15px;
    max-height: 250px;
    overflow-y: auto; 
    background-color: #f8f9fa;
    border-radius: 0.375rem;
}
.checkbox-item {
    display: block; 
    margin-bottom: 8px;
}
</style>

<main class="main-content">
    <?php include 'includes/header.php'; ?>

    <div class="page-content">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center bg-white border-bottom-0 pt-4 px-4">
                <div>
                    <h5 class="fw-bold text-primary mb-1">Registro de OLT</h5>
                    <p class="text-muted small mb-0">Crear nueva OLT y asignar parroquias</p>
                </div>
            </div>

            <div class="card-body px-4">
                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $message_class === 'success' ? 'success' : 'danger'; ?> alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <form action="registro_olt.php" method="POST" class="row g-3">
                    <div class="col-md-6">
                        <label for="id_olt" class="form-label">ID de la OLT</label>
                        <input type="number" class="form-control" id="id_olt" name="id_olt" min="1" required autofocus
                               value="<?php echo isset($_POST['id_olt']) ? htmlspecialchars($_POST['id_olt']) : ''; ?>">
                    </div>
                    
                    <div class="col-md-6">
                        <label for="nombre_olt" class="form-label">Nombre de la OLT</label>
                        <input type="text" class="form-control" id="nombre_olt" name="nombre_olt" required
                               value="<?php echo isset($_POST['nombre_olt']) ? htmlspecialchars($_POST['nombre_olt']) : ''; ?>">
                    </div>
                    
                    <div class="col-md-6">
                        <label for="marca" class="form-label">Marca</label>
                        <input type="text" class="form-control" id="marca" name="marca" 
                               value="<?php echo isset($_POST['marca']) ? htmlspecialchars($_POST['marca']) : ''; ?>">
                    </div>
                    
                    <div class="col-md-6">
                        <label for="modelo" class="form-label">Modelo</label>
                        <input type="text" class="form-control" id="modelo" name="modelo" 
                               value="<?php echo isset($_POST['modelo']) ? htmlspecialchars($_POST['modelo']) : ''; ?>">
                    </div>
                    
                    <div class="col-12">
                        <label class="form-label">Parroquias que Atiende (Seleccione una o más)</label>
                        <div class="checkbox-group">
                            <?php if (!empty($parroquias)): ?>
                                <?php 
                                $parroquias_post = isset($_POST['parroquias_id']) ? (array)$_POST['parroquias_id'] : [];
                                foreach ($parroquias as $parroquia): 
                                ?>
                                    <div class="checkbox-item form-check">
                                        <input class="form-check-input" type="checkbox" 
                                               id="parroquia_<?php echo htmlspecialchars($parroquia['id_parroquia']); ?>" 
                                               name="parroquias_id[]" 
                                               value="<?php echo htmlspecialchars($parroquia['id_parroquia']); ?>"
                                               <?php if (in_array($parroquia['id_parroquia'], $parroquias_post)) echo 'checked'; ?>>
                                        <label class="form-check-label" for="parroquia_<?php echo htmlspecialchars($parroquia['id_parroquia']); ?>">
                                            <?php echo htmlspecialchars($parroquia['nombre_parroquia']); ?>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p class="text-muted">No hay parroquias disponibles.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="col-12">
                        <label for="descripcion" class="form-label">Descripción</label>
                        <textarea class="form-control" id="descripcion" name="descripcion" rows="4"><?php echo isset($_POST['descripcion']) ? htmlspecialchars($_POST['descripcion']) : ''; ?></textarea>
                    </div>
                    
                    <div class="col-12">
                        <a href="gestion_olt.php" class="btn btn-secondary">Volver</a>
                        <button type="submit" class="btn btn-success">Registrar OLT</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</main>

<?php require_once 'includes/layout_foot.php'; ?>