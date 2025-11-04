<?php
include '../models/imagenModelo.php';
include '../models/albumModelo.php';
require_once '../../config/conexion.php';
require_once '../../config/cerrarConexion.php';
if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

$idImagen = filter_input(INPUT_GET, 'idImagen', FILTER_VALIDATE_INT);
$idAlbum  = filter_input(INPUT_GET, 'idAlbum', FILTER_VALIDATE_INT);
$idUsuario = isset($_SESSION['usuario']['id']) ? (int)$_SESSION['usuario']['id'] : 0;

if (!$idImagen && !$idAlbum) {
    http_response_code(400);
    echo json_encode(['error' => 'ID inválido']);
    exit;
}

try {
    if ($idImagen) {
        $modeloImagen = new ImagenModelo();
        $totalLikes = $modeloImagen->contarLikes($idImagen);

        $likedByUser = false;
        if ($idUsuario > 0) {
            $conexion = abrirConexion();
            $sql = "SELECT 1 FROM megusta WHERE idImagenLike = $idImagen AND idUsuarioLike = $idUsuario LIMIT 1";
            $res = mysqli_query($conexion, $sql);
            $likedByUser = $res && mysqli_num_rows($res) > 0;
            cerrarConexion($conexion);
        }

        echo json_encode(['totalLikes' => $totalLikes, 'likedByUser' => $likedByUser]);
    } else {
        $modeloAlbum = new AlbumModelo();
        $totalLikes = $modeloAlbum->contarLikesAlbum($idAlbum);

        $likedByUser = false;
        if ($idUsuario > 0) {
            $conexion = abrirConexion();
            $sql = "SELECT 1 FROM megusta_album WHERE idAlbumLike = $idAlbum AND idUsuarioLike = $idUsuario LIMIT 1";
            $res = mysqli_query($conexion, $sql);
            $likedByUser = $res && mysqli_num_rows($res) > 0;
            cerrarConexion($conexion);
        }

        echo json_encode(['totalLikes' => $totalLikes, 'likedByUser' => $likedByUser]);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error al obtener los likes: ' . $e->getMessage()]);
}
?>