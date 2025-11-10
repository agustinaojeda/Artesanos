<?php
// Controlador para agregar nuevas imágenes a un álbum existente
header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', 0);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once MODEL_PATH . '/albumModelo.php';
require_once MODEL_PATH . '/imagenModelo.php';
require_once CTRL_PATH . '/albumControlador.php';

$publicPath = rtrim(BASE_PATH, '/\\') . '/public';

try {
    if (!isset($_POST['idAlbum']) || !isset($_SESSION['usuario']['id'])) {
        throw new Exception('Datos incompletos o sesión no iniciada');
    }

    $idAlbum = (int)$_POST['idAlbum'];
    $idUsuario = (int)$_SESSION['usuario']['id'];

    // Verificar que el álbum pertenece al usuario
    $albumModelo = new AlbumModelo();
    if (!$albumModelo->esPropietarioDelAlbum($idAlbum, $idUsuario)) {
        throw new Exception('No tienes permiso para agregar imágenes a este álbum');
    }

    // Función auxiliar para guardar archivo (similar a guardarAlbum.php)
    function guardarArchivo($archivo, $carpeta) {
        global $publicPath;
        $destDir = $publicPath . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . $carpeta;
        
        if (!is_dir($destDir)) {
            if (!mkdir($destDir, 0755, true)) {
                throw new Exception('No se pudo crear la carpeta de destino');
            }
        }

        if ($archivo['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('Error al subir el archivo');
        }

        $permitidos = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($archivo['type'], $permitidos, true)) {
            throw new Exception('Tipo de archivo no permitido. Use JPG, PNG, GIF o WEBP.');
        }

        $extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
        $nombreArchivo = 'img_' . time() . '_' . bin2hex(random_bytes(6)) . '.' . $extension;
        $rutaDestino = $destDir . DIRECTORY_SEPARATOR . $nombreArchivo;

        if (!move_uploaded_file($archivo['tmp_name'], $rutaDestino)) {
            throw new Exception('Error al mover el archivo');
        }

        return $nombreArchivo;
    }

    // Procesar las nuevas imágenes
    $cantidad = isset($_POST['cantidadImagenes']) ? (int)$_POST['cantidadImagenes'] : 0;
    $imagenesAgregadas = 0;
    $controlador = new AlbumCont();

    for ($i = 0; $i < $cantidad; $i++) {
        if (!isset($_FILES["imagen$i"]) || $_FILES["imagen$i"]['error'] !== UPLOAD_ERR_OK) {
            continue;
        }

        $img = $_FILES["imagen$i"];
        $titulo = isset($_POST["tituloImagen$i"]) ? trim($_POST["tituloImagen$i"]) : '';
        $descripcion = isset($_POST["descripcionImagen$i"]) ? trim($_POST["descripcionImagen$i"]) : '';
        $etiqueta = isset($_POST["etiquetaImagen$i"]) ? trim($_POST["etiquetaImagen$i"]) : '';

        try {
            $url = guardarArchivo($img, 'imagenes');
            $ok = $controlador->guardarImagen($idAlbum, $titulo, $descripcion, $etiqueta, $url);
            if ($ok) {
                $imagenesAgregadas++;
            }
        } catch (Exception $e) {
            error_log('[agregarImagenesAlbum] Error al procesar imagen ' . $i . ': ' . $e->getMessage());
            // Continuar con las siguientes imágenes
        }
    }

    if ($imagenesAgregadas === 0 && $cantidad > 0) {
        throw new Exception('No se pudo agregar ninguna imagen');
    }

    echo json_encode([
        'success' => true,
        'message' => "Se agregaron $imagenesAgregadas imagen(es) correctamente",
        'imagenesAgregadas' => $imagenesAgregadas
    ], JSON_UNESCAPED_UNICODE);
    exit;

} catch (Throwable $e) {
    error_log('[agregarImagenesAlbum] ' . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

