<?php
require_once CONFIG_PATH . '/conexion.php';
require_once CONFIG_PATH . '/cerrarConexion.php';

$conexion = abrirConexion();

$idSeguidor = $_SESSION['usuario']['id'] ?? 0;
$idSeguido = $_POST['idSeguido'] ?? 0;

if (empty($_SESSION['usuario']['id'])) {
    echo "error: sesion";
    exit;
}


if (!$idSeguidor || !$idSeguido) {
    echo "error: datos faltantes";
    exit;
}

$sql = "DELETE FROM seguimiento WHERE idSeguidor = ? AND idSeguido = ?";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("ii", $idSeguidor, $idSeguido);
$stmt->execute();

echo ($stmt->affected_rows > 0) ? "ok" : "noexistente";

$stmt->close();
cerrarConexion($conexion);
?>
