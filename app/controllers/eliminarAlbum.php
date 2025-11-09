<?php

header('Content-Type: application/json');

if (!isset($_POST['idAlbum'])) {
    echo json_encode(["success" => false, "message" => "ID no recibido"]);
    exit;
}

$idAlbum = (int) $_POST['idAlbum'];
$idUsuario = $_SESSION['usuario']['id'] ?? 0;

if ($idUsuario === 0) {
    echo json_encode(["success" => false, "message" => "Usuario no autenticado"]);
    exit;
}

require_once MODEL_PATH . '/albumModelo.php';


$albumModelo = new AlbumModelo();

// Se verifica que el álbum pertenezca al usuario
if (!$albumModelo->esPropietarioDelAlbum($idAlbum, $idUsuario)) {
    echo json_encode(["success" => false, "message" => "No tienes permiso para eliminar este álbum"]);
    exit;
}

$eliminado = $albumModelo->eliminarAlbum($idAlbum);

if ($eliminado) {
    echo json_encode(["success" => true]);
} else {
    echo json_encode(["success" => false, "message" => "No se pudo eliminar"]);
}