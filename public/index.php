<?php
declare(strict_types=1);

/**
 * Front controller
 * (/artesanos/Artesanos_Empanada2.0/public).
 */

//debug for dev
ini_set('display_errors', '1');
error_reporting(E_ALL);

header('Content-Type: text/html; charset=utf-8');

if (session_status() !== PHP_SESSION_ACTIVE) {

    session_set_cookie_params([
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_name('ARTESANOSSESSID');
    session_start();
}

define('BASE_PATH', dirname(__DIR__));
define('APP_PATH', BASE_PATH . '/app');
define('CONFIG_PATH', BASE_PATH . '/config');
define('VIEW_PATH', APP_PATH . '/views');
define('CTRL_PATH', APP_PATH . '/controllers');
define('MODEL_PATH',  APP_PATH . '/models');

$DOCROOT   = dirname(__DIR__);
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
$basePath  = rtrim($scriptDir, '/');
$GLOBALS['basePath'] = $basePath;

$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$uri = str_replace('\\', '/', $uri);

if ($basePath !== '' && $basePath !== '/' && strpos($uri, $basePath) === 0) {
    $uri = substr($uri, strlen($basePath));
}
$uri = '/' . ltrim($uri, '/');
$uri = rtrim($uri, '/');
if ($uri === '') {
    $uri = '/';
}

//routing
try {
    switch ($uri) {
        case '/':
        case '/home':
            require VIEW_PATH . '/home.php';
            break;
        case '/login':
            require VIEW_PATH . '/login.php';
            break;
        case '/perfil':
            require VIEW_PATH . '/perfil.php';
            break;
        case '/editarPerfil':
            require VIEW_PATH . '/editarPerfil.php';
            break;
        case '/busqueda':
            require VIEW_PATH . '/busqueda.php';
            break;
        case '/procesarEdicion':
            require VIEW_PATH . '/procesarEdicion.php';
            break;
        case '/cerrarSesion':
            require VIEW_PATH . '/cerrarSesion.php';
            break;
        case '/actualizarContrasena':
            require VIEW_PATH . '/actualizarContrasena.php';
            break;

        //endpoints api, only return json response 
        case '/api/listarNotificaciones':
            require CTRL_PATH . '/listarNotificaciones.php';
            break;
        case '/api/notificaciones':
            require CTRL_PATH . '/notificacionesControl.php';
            break;
        case '/api/guardar-album':
            require CTRL_PATH . '/guardarAlbum.php';
            break;
        case '/api/detalleAlbum':
            require CTRL_PATH . '/detalleAlbum.php';
            break;
        case '/api/eliminarAlbum':
            require CTRL_PATH . '/eliminarAlbum.php';
            break;
        case '/api/obtenerLikes':
            require CTRL_PATH . '/obtenerLikes.php';
            break;
        case '/api/agregarComentario':
            require CTRL_PATH . '/agregarComentario.php';
            break;
        case '/api/megusta':
            require VIEW_PATH . '/megusta.php';
            break;
        case '/api/responderSolicitud':
            require VIEW_PATH . '/responderSolicitud.php';
            break;
        case '/api/recuperar':
            require VIEW_PATH . '/recuperar.php';
            break;
        case '/api/seguir':
            require VIEW_PATH . '/seguir.php';
            break;
        case '/api/dejarSeguir':
            require VIEW_PATH . '/dejarSeguir.php';
            break;
        case '/api/checkFollowStatus':
            require VIEW_PATH . '/checkFollowStatus.php';
            break;
        case '/api/editarAlbum':
            require CTRL_PATH . '/editarAlbum.php';
            break;
        case '/api/imagenesDeAlbum':
            require CTRL_PATH . '/imagenesDeAlbum.php';
            break;
        case '/api/eliminarImagen':
            require CTRL_PATH . '/eliminarImagen.php';
            break;
        case '/api/agregarImagenesAlbum':
            require CTRL_PATH . '/agregarImagenesAlbum.php';
            break;
        case '/api/obtenerDatosAlbum':
            require CTRL_PATH . '/obtenerDatosAlbum.php';
            break;
        case '/api/actualizarImagen':
            require CTRL_PATH . '/actualizarImagen.php';
            break;
        case '/api/eliminarPortadaAlbum':
            require CTRL_PATH . '/eliminarPortadaAlbum.php';
            break;
        case '/api/detalleLikesUsuario':
            require CTRL_PATH . '/detalleLikesUsuario.php';
            break;
        case '/api/historialFotosPerfil':
            require CTRL_PATH . '/historialFotosPerfil.php';
            break;
        case '/api/detalleFotoHistorial':
            require CTRL_PATH . '/detalleFotoHistorial.php';
            break;
        case '/api/eliminarFotoPerfil':
            require CTRL_PATH . '/eliminarFotoPerfil.php';
            break;
        case '/api/eliminarFotoHistorial':
            require CTRL_PATH . '/eliminarFotoHistorial.php';
            break;
        default:
            http_response_code(404);
            echo '404 Not Found';
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo '500 Internal Server Error';
    // dev debug:
    echo '<pre>' . htmlspecialchars($e->__toString(), ENT_QUOTES, 'UTF-8') . '</pre>';
}
