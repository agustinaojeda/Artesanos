<?php
require_once CONFIG_PATH . '/conexion.php';

$conexion = abrirConexion();

$idUsuarioComentario = $_SESSION['usuario']['id'];
$idImagenComentario = $_POST['idImagen'];
$mensajeComentario = trim($_POST['mensajeComentario']);

// Insertar comentario
$sql = "INSERT INTO comentario (idImagenComentario, idUsuarioComentario, mensajeComentario, fechaComentario)
        VALUES (?, ?, ?, NOW())";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("iis", $idImagenComentario, $idUsuarioComentario, $mensajeComentario);
$stmt->execute();

// Buscar el autor del álbum de esa imagen
$sqlAutor = "SELECT a.idUsuarioAlbum, a.tituloAlbum
             FROM album a
             JOIN imagen i ON i.idAlbumImagen = a.idAlbum
             WHERE i.idImagen = ?";
$stmt2 = $conexion->prepare($sqlAutor);
$stmt2->bind_param("i", $idImagenComentario);
$stmt2->execute();
$result = $stmt2->get_result();
$row = $result->fetch_assoc();

if ($row) {
    $idUsuarioDestino = $row['idUsuarioAlbum'];
    $tituloAlbum = $row['tituloAlbum'];

    if ($idUsuarioDestino != $idUsuarioComentario) {
        $mensaje = "comentó tu álbum '$tituloAlbum'";
        $tipo = "comentario";

        $sqlNotif = "INSERT INTO notificaciones (idUsuarioDestino, idUsuarioAccion, tipo, idReferencia, mensaje, leida, fecha)
                     VALUES (?, ?, ?, ?, ?, 0, NOW())";
        $stmt3 = $conexion->prepare($sqlNotif);
        $stmt3->bind_param("iisis", $idUsuarioDestino, $idUsuarioComentario, $tipo, $idImagenComentario, $mensaje);
        $stmt3->execute();
    }
}

cerrarConexion($conexion);
?>