<?php
require_once CONFIG_PATH . '/conexion.php';
require_once CONFIG_PATH . '/cerrarConexion.php';

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



if ($accion === 'aceptar') {

    $sql = "UPDATE seguimiento 
            SET estadoSeguimiento = 'activo'
            WHERE idSeguido = ? AND idSeguidor = ? AND estadoSeguimiento = 'pendiente'";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("ii", $idUsuario, $idSeguidor);
    $stmt->execute();
    

    if ($stmt->affected_rows > 0) {
        $mensaje = "ha aceptado tu solicitud de seguimiento";
        $tipo = "aceptar_seguimiento";

        $notif = $conexion->prepare(
            "INSERT INTO notificaciones (idUsuarioDestino, idUsuarioAccion, tipo, mensaje, leida, fecha)
             VALUES (?, ?, ?, ?, 0, NOW())"
        );
        $notif->bind_param("iiss", $idSeguidor, $idUsuario, $tipo, $mensaje);
        $notif->execute();

        echo "activo"; // 👈 para que el botón cambie automáticamente
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
?>


