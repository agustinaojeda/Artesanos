<?php
// Controlador para eliminar la portada de un álbum
header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', 0);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once MODEL_PATH . '/albumModelo.php';

$publicPath = rtrim(BASE_PATH, '/\\') . '/public';

try {
    if (!isset($_POST['idAlbum']) || !isset($_SESSION['usuario']['id'])) {
        throw new Exception('Datos incompletos o sesión no iniciada');
    }

    $idAlbum = (int)$_POST['idAlbum'];
    $idUsuario = (int)$_SESSION['usuario']['id'];

    if ($idAlbum <= 0) {
        throw new Exception('ID de álbum inválido');
    }

    $albumModelo = new AlbumModelo();
    
    // Verificar propiedad
    if (!$albumModelo->esPropietarioDelAlbum($idAlbum, $idUsuario)) {
        throw new Exception('No tienes permiso para eliminar la portada de este álbum');
    }

    // Obtener portada actual
    $portadaActual = $albumModelo->getPortadaActual($idAlbum);
    
    if ($portadaActual && $portadaActual !== 'imagen.png') {
        // Eliminar archivo físico solo si no es la imagen por defecto
        $rutaArchivo = $publicPath . '/uploads/portadas/' . $portadaActual;
        if (is_file($rutaArchivo)) {
            @unlink($rutaArchivo);
        }
    }
    
    // Actualizar en BD (poner imagen.png por defecto)
    $ok = $albumModelo->eliminarPortadaAlbum($idAlbum);
    
    if (!$ok) {
        throw new Exception('Error al actualizar el álbum en la base de datos');
    }

    echo json_encode([
        'success' => true,
        'message' => 'Portada eliminada correctamente'
    ], JSON_UNESCAPED_UNICODE);
    exit;

} catch (Throwable $e) {
    error_log('[eliminarPortadaAlbum] ' . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

