<?php
// Controlador para eliminar una imagen individual
header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', 0);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once MODEL_PATH . '/imagenModelo.php';

try {
    if (!isset($_POST['idImagen']) || !isset($_SESSION['usuario']['id'])) {
        throw new Exception('Datos incompletos o sesión no iniciada');
    }

    $idImagen = (int)$_POST['idImagen'];
    $idUsuario = (int)$_SESSION['usuario']['id'];

    if ($idImagen <= 0) {
        throw new Exception('ID de imagen inválido');
    }

    $imagenModelo = new ImagenModelo();
    $eliminado = $imagenModelo->eliminarImagen($idImagen, $idUsuario);

    if (!$eliminado) {
        throw new Exception('No se pudo eliminar la imagen. Verifica que tengas permisos.');
    }

    echo json_encode([
        'success' => true,
        'message' => 'Imagen eliminada correctamente'
    ], JSON_UNESCAPED_UNICODE);
    exit;

} catch (Throwable $e) {
    error_log('[eliminarImagen] ' . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

