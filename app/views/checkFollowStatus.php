<?php
require_once CONFIG_PATH . '/conexion.php';
require_once CONFIG_PATH . '/cerrarConexion.php';
$conexion = abrirConexion();

$idSeguidor = $_SESSION['usuario']['id'] ?? 0;
$idSeguido = $_POST['idSeguido'] ?? 0;

if (!$idSeguidor || !$idSeguido) {
    echo 'none';
    exit;
}

$stmt = $conexion->prepare("SELECT estadoSeguimiento FROM seguimiento WHERE idSeguidor = ? AND idSeguido = ?");
$stmt->bind_param("ii", $idSeguidor, $idSeguido);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows > 0) {
    $estado = $res->fetch_assoc()['estadoSeguimiento'];
    echo ($estado === 'aceptado') ? 'activo' : $estado;

} else {
    echo 'none';
}

cerrarConexion($conexion);
?>
