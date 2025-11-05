<?php
session_start();
require_once '../../config/conexion.php';
require_once '../../config/cerrarConexion.php';

$conexion = abrirConexion();

if (!isset($_SESSION['usuario']['id'])) {
    echo "error:no_autorizado";
    exit;
}

if (!isset($_POST['idSeguidor']) || !isset($_POST['accion'])) {
    echo "error:faltan_datos";
    exit;
}

$idUsuario = $_SESSION['usuario']['id']; // el usuario que acepta o rechaza
$idSeguidor = intval($_POST['idSeguidor']);
$accion = $_POST['accion'];


if ($accion === 'aceptar' || $accion === 'rechazar') {
    $deleteNotif = $conexion->prepare(
        "DELETE FROM notificaciones 
     WHERE idUsuarioDestino = ? 
     AND idUsuarioAccion = ? 
     AND tipo = 'solicitud_seguir'"
    );
    // $idUsuario es quien responde, $idSeguidor es quien envió la solicitud
    $deleteNotif->bind_param("ii", $idUsuario, $idSeguidor);
    $deleteNotif->execute();
    $deleteNotif->close();
}
if ($accion === 'aceptar') {

    $sql = "UPDATE seguimiento 
            SET estadoSeguimiento = 'activo'
            WHERE idSeguido = ? AND idSeguidor = ? AND estadoSeguimiento = 'pendiente'";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("ii", $idUsuario, $idSeguidor);
    $stmt->execute();


    if ($stmt->affected_rows > 0) {
        //notificacion para el usuario que acepta
        $mensajeUsuario = "ha comenzado a seguirte";
        $tipoUsuario = "nuevo_seguimiento"; 

        $notifUsuario = $conexion->prepare(
            "INSERT INTO notificaciones (idUsuarioDestino, idUsuarioAccion, tipo, mensaje, leida, fecha)
             VALUES (?, ?, ?, ?, 0, NOW())"
        );
        $notifUsuario->bind_param("iiss", $idUsuario, $idSeguidor, $tipoUsuario, $mensajeUsuario);
        $notifUsuario->execute();

        //notificacion para el usuario que envió la solicitud
        $mensaje = "ha aceptado tu solicitud de seguimiento";
        $tipo = "aceptar_seguimiento";

        $notif = $conexion->prepare(
            "INSERT INTO notificaciones (idUsuarioDestino, idUsuarioAccion, tipo, mensaje, leida, fecha)
             VALUES (?, ?, ?, ?, 0, NOW())"
        );
        $notif->bind_param("iiss", $idSeguidor, $idUsuario, $tipo, $mensaje);
        $notif->execute();

        echo "activo";
    } else {
        echo "error:no_encontrado";
    }
} elseif ($accion === 'rechazar') {

    $sql = "DELETE FROM seguimiento WHERE idSeguido = ? AND idSeguidor = ? AND estadoSeguimiento = 'pendiente'";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("ii", $idUsuario, $idSeguidor);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {

        $mensaje = "ha rechazado tu solicitud de seguimiento";
        $tipo = "respuesta_seguimiento";

        $notif = $conexion->prepare(
            "INSERT INTO notificaciones (idUsuarioDestino, idUsuarioAccion, tipo, mensaje, leida, fecha)
             VALUES (?, ?, ?, ?, 0, NOW())"
        );
        $notif->bind_param("iiss", $idSeguidor, $idUsuario, $tipo, $mensaje);
        $notif->execute();

        echo "rechazado";
    } else {
        echo "error:no_encontrado";
    }
} else {
    echo "error:accion_invalida";
}



cerrarConexion($conexion);
