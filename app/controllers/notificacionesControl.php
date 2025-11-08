<?php
require_once CONFIG_PATH . '/conexion.php';
require_once CONFIG_PATH . '/cerrarConexion.php';
$conexion = abrirConexion();

$idUsuario = $_SESSION['usuario']['id'] ?? 0;

$sql = "SELECT COUNT(*) AS noLeidas FROM notificaciones WHERE idUsuarioDestino = ? AND leida = 0";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("i", $idUsuario);
$stmt->execute();
$result = $stmt->get_result();
$count = $result->fetch_assoc()['noLeidas'] ?? 0;

echo json_encode(['noLeidas' => $count]);
?>