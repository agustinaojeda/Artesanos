<?php
// Controlador para obtener los datos de un álbum (para edición)
header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', 0);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once MODEL_PATH . '/albumModelo.php';

try {
    if (!isset($_GET['idAlbum']) || !isset($_SESSION['usuario']['id'])) {
        throw new Exception('Datos incompletos o sesión no iniciada');
    }

    $idAlbum = (int)$_GET['idAlbum'];
    $idUsuario = (int)$_SESSION['usuario']['id'];

    if ($idAlbum <= 0) {
        throw new Exception('ID de álbum inválido');
    }

    $albumModelo = new AlbumModelo();
    
    // Verificar propiedad
    if (!$albumModelo->esPropietarioDelAlbum($idAlbum, $idUsuario)) {
        throw new Exception('No tienes permiso para ver este álbum');
    }

    // Obtener datos del álbum
    $album = $albumModelo->mostrarAlbumId($idAlbum);
    
    if (!$album) {
        throw new Exception('Álbum no encontrado');
    }

    echo json_encode([
        'success' => true,
        'data' => [
            'idAlbum' => $album->idAlbum,
            'titulo' => $album->tituloAlbum,
            'esPublico' => $album->esPublico ? 1 : 0,
            'urlPortada' => $album->urlPortada
        ]
    ], JSON_UNESCAPED_UNICODE);
    exit;

} catch (Throwable $e) {
    error_log('[obtenerDatosAlbum] ' . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

