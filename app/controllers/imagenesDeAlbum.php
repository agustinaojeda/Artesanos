<?php
// API: SIEMPRE JSON. Nuca HTML de errores.
header('Content-Type: application/json; charset=utf-8');

ini_set('display_errors', '0');
error_reporting(E_ALL);

// Convierte cualquier warning/notice en Exception → lo capturamos y respondemos JSON
set_error_handler(function ($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});

require_once MODEL_PATH . '/albumModelo.php';

try {
    if (!isset($_GET['idAlbum'])) {
        throw new Exception('Parámetro idAlbum requerido');
    }

    $idAlbum = (int)$_GET['idAlbum'];
    if ($idAlbum <= 0) {
        throw new Exception('idAlbum inválido');
    }

    $modelo = new AlbumModelo();

    //SI EL MÉTODO NO EXISTE, devolvemos error legible
    if (!method_exists($modelo, 'listarImagenesDeAlbum')) {
        throw new Exception('Falta método listarImagenesDeAlbum en AlbumModelo');
    }

    $imagenes = $modelo->listarImagenesDeAlbum($idAlbum);

    echo json_encode([
        'success' => true,
        'data'    => $imagenes,
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
}

