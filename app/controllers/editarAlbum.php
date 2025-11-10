<?php
// 🔧 Evita que PHP mezcle HTML de errores con tu JSON
error_reporting(E_ALL);
// IMPORTANTE: no envíes errores al output; loguéalos
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Sesión para usar $_SESSION
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Fuerza JSON y evita caché
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

require_once MODEL_PATH . '/albumModelo.php';
$publicPath = rtrim(BASE_PATH, '/\\') . '/public';
try {
    // Protección por si algo imprimió antes
    if (ob_get_length()) { ob_clean(); }

    if (!isset($_POST['idAlbum']) || !isset($_SESSION['usuario']['id'])) {
        throw new Exception('Datos incompletos o sesión no iniciada');
    }

    $idAlbum  = (int)$_POST['idAlbum'];
    $idUsuario= (int)$_SESSION['usuario']['id'];
    $titulo   = trim($_POST['titulo'] ?? '');
    // Asegura int 0/1
    $esPublico = isset($_POST['esPublico']) ? (int)$_POST['esPublico'] : 1;

    if ($titulo === '') {
        throw new Exception('El título no puede estar vacío');
    }

    $albumModelo = new AlbumModelo();

    // Verifica propiedad
    if (!$albumModelo->esPropietarioDelAlbum($idAlbum, $idUsuario)) {
        throw new Exception('No tienes permiso para editar este álbum');
    }

    $nuevaPortada = null;

    // Subida portada (opcional)
    if (!empty($_FILES['portada']) && $_FILES['portada']['error'] === UPLOAD_ERR_OK) {
        // Portada actual (para borrar luego)
        if (!method_exists($albumModelo, 'getPortadaActual')) {
            throw new Exception('Falta método getPortadaActual en el modelo');
        }
        $portadaActual = $albumModelo->getPortadaActual($idAlbum);

        $portada    = $_FILES['portada'];
        $permitidos = ['image/jpeg','image/png','image/gif','image/webp'];
        if (!in_array($portada['type'], $permitidos, true)) {
            throw new Exception('Tipo de archivo no permitido. Use JPG, PNG, GIF o WEBP.');
        }

        $extension = strtolower(pathinfo($portada['name'], PATHINFO_EXTENSION));
        $nuevaPortada = 'album_' . $idAlbum . '_' . bin2hex(random_bytes(6)) . '.' . $extension;

       $dirDestino = $publicPath . '/uploads/portadas';
        if (!is_dir($dirDestino) && !mkdir($dirDestino, 0755, true)) {
            throw new Exception('No se pudo crear la carpeta de destino');
        }

        $rutaDestino = $dirDestino . '/' . $nuevaPortada;
        if (!move_uploaded_file($portada['tmp_name'], $rutaDestino)) {
            throw new Exception('Error al subir la imagen. Verifica permisos de carpeta.');
        }

        // Borra la anterior si existe
        if ($portadaActual) {
            $rutaAnterior = $dirDestino . '/' . $portadaActual;
            if (is_file($rutaAnterior)) { @unlink($rutaAnterior); }
        }
    }

    // Actualiza
    $ok = $albumModelo->actualizarAlbum($idAlbum, $titulo, $esPublico, $nuevaPortada);

    if (!$ok) {
        // Limpia la nueva portada si algo falló
        if ($nuevaPortada) {
            $rutaNueva = $publicPath . '/uploads/portadas/' . $nuevaPortada;
            if (is_file($rutaNueva)) { @unlink($rutaNueva); }
        }
        throw new Exception('Error al actualizar el álbum en la base de datos');
    }

    // Respuesta JSON limpia
    echo json_encode([
        'success' => true,
        'message' => 'Álbum actualizado correctamente',
        'data' => [
            'id'       => $idAlbum,
            'titulo'   => $titulo,
            'portada' => $nuevaPortada ? $nuevaPortada : null,
            'esPublico'=> $esPublico
        ]
    ], JSON_UNESCAPED_UNICODE);
    exit;

} catch (Throwable $e) {
    error_log('[editarAlbum] ' . $e->getMessage());
    http_response_code(400);
    // Asegura que salga SOLO JSON
    if (ob_get_length()) { ob_clean(); }
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
