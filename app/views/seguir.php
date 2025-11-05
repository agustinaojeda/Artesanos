<?php
session_start();
require_once '../../config/conexion.php';
require_once '../../config/cerrarConexion.php';
$conexion = abrirConexion();

$idSeguidor = $_SESSION['usuario']['id'] ?? 0;
$idSeguido = $_POST['idSeguido'] ?? 0;

if (!$idSeguidor || !$idSeguido) {
    echo 'error:datos';
    exit;
}

$check = $conexion->prepare("SELECT estadoSeguimiento FROM seguimiento WHERE idSeguidor = ? AND idSeguido = ?");
$check->bind_param("ii", $idSeguidor, $idSeguido);
$check->execute();
$result = $check->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();

    echo $row['estadoSeguimiento']; // puede ser 'pendiente' o 'activo'
    exit;
}

$sql = "INSERT INTO seguimiento (idSeguidor, idSeguido, estadoSeguimiento, fechaSeguimiento)
        VALUES (?, ?, 'pendiente', NOW())";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("ii", $idSeguidor, $idSeguido);
$stmt->execute();

// notificación al seguido
$mensaje = "te ha enviado una solicitud de seguimiento";
$tipo = "solicitud_seguir";

$sqlNotif = "INSERT INTO notificaciones (idUsuarioDestino, idUsuarioAccion, tipo, mensaje, leida, fecha)
             VALUES (?, ?, ?, ?, 0, NOW())";
$stmt2 = $conexion->prepare($sqlNotif);
$stmt2->bind_param("iiss", $idSeguido, $idSeguidor, $tipo, $mensaje);
$stmt2->execute();

cerrarConexion($conexion);
echo 'pendiente';
?>



