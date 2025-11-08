<?php

include MODEL_PATH . '/comentarioModelo.php';
include MODEL_PATH . '/usuarioHelper.php';
require_once CONFIG_PATH . '/conexion.php';

$data = json_decode(file_get_contents("php://input"), true);
$idUsuario = $_SESSION['usuario']['id'] ?? 0;
$idImagen = (int)$data['idImagen'];
$mensaje = trim($data['mensaje']);

if ($idUsuario && $idImagen && $mensaje) {
  $modelo = new ComentarioModelo();
  $modelo->agregarComentario($idImagen, $idUsuario, $mensaje);

  $usuario = $modelo->obtenerUsuario($idUsuario);
  echo json_encode([
    'ok' => true,
    'apodo' => $usuario['apodoUsuario'],
    'avatar' => obtenerAvatar($idUsuario),
    'mensaje' => $mensaje
  ]);
} else {
  echo json_encode(['ok' => false]);
}