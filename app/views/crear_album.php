<?php
require_once CONFIG_PATH . '/conexion.php';
$conexion = abrirConexion();

$idUsuarioAlbum = $_SESSION['usuario']['id'];
$tituloAlbum = $_POST['tituloAlbum'];

// Insertar el álbum
$sql = "INSERT INTO album (tituloAlbum, esPublicoAlbum, urlPortadaAlbum, idUsuarioAlbum, fechaCreacionAlbum)
        VALUES (?, 1, ?, ?, NOW())";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("ssi", $tituloAlbum, $_POST['urlPortadaAlbum'], $idUsuarioAlbum);
$stmt->execute();

$idAlbum = $conexion->insert_id;

// Buscar los seguidores del usuario
$sqlSeguidores = "SELECT idSeguidor FROM seguimiento WHERE idSeguido = ? AND estadoSeguimiento = 'activo'";
$stmt2 = $conexion->prepare($sqlSeguidores);
$stmt2->bind_param("i", $idUsuarioAlbum);
$stmt2->execute();
$result = $stmt2->get_result();

// Enviar notificación a cada seguidor
$mensaje = "creó un nuevo álbum: ".htmlspecialchars($tituloAlbum);
$tipo = "album_nuevo";

while ($row = $result->fetch_assoc()) {
    $idSeguidor = $row['idSeguidor'];
    $sqlNotif = "INSERT INTO notificaciones (idUsuarioDestino, idUsuarioAccion, tipo, idReferencia, mensaje, leida, fecha)
                 VALUES (?, ?, ?, ?, ?, 0, NOW())";
    $stmt3 = $conexion->prepare($sqlNotif);
    $stmt3->bind_param("iisis", $idSeguidor, $idUsuarioAlbum, $tipo, $idAlbum, $mensaje);
    $stmt3->execute();
}

cerrarConexion($conexion);
?>