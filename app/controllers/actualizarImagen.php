<?php
// Controlador para actualizar título y descripción de una imagen
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
    $titulo = trim($_POST['titulo'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');

    if ($idImagen <= 0) {
        throw new Exception('ID de imagen inválido');
    }

    $imagenModelo = new ImagenModelo();
    $actualizado = $imagenModelo->actualizarImagen($idImagen, $idUsuario, $titulo, $descripcion);

    if (!$actualizado) {
        throw new Exception('No se pudo actualizar la imagen. Verifica que tengas permisos.');
    }

    echo json_encode([
        'success' => true,
        'message' => 'Imagen actualizada correctamente'
    ], JSON_UNESCAPED_UNICODE);
    exit;

} catch (Throwable $e) {
    error_log('[actualizarImagen] ' . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

