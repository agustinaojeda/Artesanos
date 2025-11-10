<?php
// Carga el detalle del álbum virtual de "Me gusta" de un usuario específico
error_reporting(E_ALL);
ini_set('display_errors', 0); // No mostrar errores en la salida JSON
header('Content-Type: application/json; charset=utf-8');

require_once MODEL_PATH . '/albumModelo.php';
require_once MODEL_PATH . '/comentarioModelo.php';
require_once MODEL_PATH . '/usuarioHelper.php';
require_once CONFIG_PATH . '/conexion.php';

// Obtener basePath
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
$basePath = rtrim($scriptDir, '/');

if (!isset($_SESSION['usuario']['id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Usuario no autenticado']);
    exit;
}

$idUsuarioLogueado = (int)$_SESSION['usuario']['id'];
$idUsuarioSeguido = isset($_GET['idUsuario']) ? (int)$_GET['idUsuario'] : 0;

if ($idUsuarioSeguido === 0) {
    http_response_code(400);
    echo json_encode(['error' => 'ID de usuario inválido']);
    exit;
}

// Verificar que el usuario logueado sigue al usuario seguido con estado 'activo'
$conexion = abrirConexion();
$sqlVerificar = "SELECT 1 FROM seguimiento WHERE idSeguidor = ? AND idSeguido = ? AND estadoSeguimiento = 'activo' LIMIT 1";
$stmtVerificar = $conexion->prepare($sqlVerificar);
$stmtVerificar->bind_param("ii", $idUsuarioLogueado, $idUsuarioSeguido);
$stmtVerificar->execute();
$resVerificar = $stmtVerificar->get_result();
if ($resVerificar->num_rows === 0) {
    cerrarConexion($conexion);
    http_response_code(403);
    echo json_encode(['error' => 'No tienes permiso para ver este contenido']);
    exit;
}
$stmtVerificar->close();
cerrarConexion($conexion);

$modeloAlbum = new AlbumModelo();
$comentarioModelo = new ComentarioModelo();

// Obtener todas las imágenes likeadas (portadas + imágenes)
$imagenes = $modeloAlbum->obtenerTodasImagenesLikeadas($idUsuarioLogueado, $idUsuarioSeguido);

// Obtener datos del usuario
$usuario = $modeloAlbum->obtenerDatosUsuarioPorId($idUsuarioSeguido);
if (!$usuario) {
    http_response_code(404);
    echo json_encode(['error' => 'Usuario no encontrado']);
    exit;
}

$cantAlbumes = $modeloAlbum->contarAlbumesDeUsuario($idUsuarioSeguido);
$cantSeguidores = $modeloAlbum->contarSeguidoresDeUsuario($idUsuarioSeguido);

// HTML IZQUIERDA
$htmlIzquierda = '<h5 id="nombreAlbumActivo"></h5>
<div id="carouselAlbumVirtual" class="carousel slide mb-3" data-bs-ride="false">
    <div class="carousel-inner">';

if (!empty($imagenes)) {
    foreach ($imagenes as $i => $img) {
        $active = $i === 0 ? 'active' : '';
        $fechaImg = $img['fechaImagen'] ?? '';
        $fechaUnix = empty($fechaImg) ? time() * 1000 : (strtotime($fechaImg) * 1000);
        
        // Determinar la ruta de la imagen según el tipo
        $rutaImagen = '';
        if ($img['esPortada']) {
            // Si es imagen.png, usar la ruta de assets
            if ($img['urlImagen'] === 'imagen.png' || empty($img['urlImagen'])) {
                $rutaImagen = $basePath . '/assets/images/imagen.png';
            } else {
                $rutaImagen = $basePath . '/uploads/portadas/' . htmlspecialchars($img['urlImagen']);
            }
        } else {
            $rutaImagen = $basePath . '/uploads/imagenes/' . htmlspecialchars($img['urlImagen']);
        }

        $htmlIzquierda .= '<div class="carousel-item ' . $active . '" 
            data-idimagen="' . ($img['esPortada'] ? 'portada_' . $img['idAlbumImagen'] : $img['idImagen']) . '"
            data-titulo="' . htmlspecialchars($img['tituloImagen'] ?? ($img['nombreAlbum'] ?? '')) . '"
            data-descripcion="' . htmlspecialchars($img['descripcionImagen'] ?? '') . '" 
            data-fechaunix="' . $fechaUnix . '"
            data-nombrealbum="' . htmlspecialchars($img['nombreAlbum'] ?? '') . '"
            data-esportada="' . ($img['esPortada'] ? '1' : '0') . '">
            <div style="width: 100%; max-width: 500px; aspect-ratio: 1 / 1; overflow: hidden; margin: auto;">
                <img src="' . $rutaImagen . '" 
                    class="w-100 h-100" style="object-fit: contain;">
            </div>
        </div>';
    }
} else {
    $htmlIzquierda .= '
        <div class="carousel-item active">
            <div class="text-center text-muted py-5">No hay contenido con "Me gusta" de este usuario.</div>
        </div>';
}

$htmlIzquierda .= '
    </div>
    <button class="carousel-control-prev" type="button" data-bs-target="#carouselAlbumVirtual" data-bs-slide="prev">
        <span class="carousel-control-prev-icon"></span>
    </button>
    <button class="carousel-control-next" type="button" data-bs-target="#carouselAlbumVirtual" data-bs-slide="next">
        <span class="carousel-control-next-icon"></span>
    </button>
</div>

<h5 id="tituloImagen"></h5>
<p id="descripcionImagen" class="text-muted"></p>';

$idImagenInicial = !empty($imagenes) && !$imagenes[0]['esPortada'] ? $imagenes[0]['idImagen'] : 0;
$htmlIzquierda .= '
<div class="d-flex align-items-center mb-3 gap-2">';

// Solo mostrar like si no es una portada
if (!empty($imagenes) && !$imagenes[0]['esPortada']) {
    $htmlIzquierda .= '
    <img src="' . $basePath . '/assets/images/like.png" 
        alt="Me gusta" 
        id="btn-like-imagen" 
        data-idimagen="' . $idImagenInicial . '"
        class="img-fluid" 
        style="max-height: 30px; cursor: pointer;">
    
    <span id="likes-count-display" class="text-muted small align-self-center me-3">0</span>';
}

$htmlIzquierda .= '
    <a href="#formularioComentario" class="d-flex align-items-center text-decoration-none text-dark">
        <img src="' . $basePath . '/assets/images/comentario.png" 
            alt="Comentario" 
            class="img-fluid" 
            style="max-height: 27px; cursor: pointer;">
        <span class="ms-1 small">Comentarios</span>
    </a>
</div>';

// Comentarios solo para imágenes reales (no portadas)
$comentariosPorImagen = [];
foreach ($imagenes as $img) {
    if (!$img['esPortada'] && isset($img['idImagen'])) {
        $comentarios = $comentarioModelo->mostrarComentariosDeImagen($img['idImagen']);
        $comentariosPorImagen[$img['idImagen']] = $comentarios;
    }
}

if ($idUsuarioLogueado == 0) { 
    $htmlIzquierda .= '<p class="text-muted mt-4">Inicia sesión para interactuar con la publicación.</p>';
} else {
    $avatar = obtenerAvatar($idUsuarioLogueado);
    $htmlIzquierda .= '<div id="formularioComentario" class="d-flex align-items-start gap-2 mt-4">
        <img id="avatarUsuarioComentario" src="' . $avatar . '" class="rounded-circle" style="width: 40px; height: 40px; object-fit: cover;">
        <div class="flex-grow-1">
            <div class="d-flex">
                <input type="text" id="inputComentario" class="form-control mb-3" placeholder="¿Agregar comentario?" maxlength="200" style="resize: none; height: 50px;">
                <button id="btnEnviarComentario" class="btn btn-link" data-idimagen="' . $idImagenInicial . '">
                    <i class="bi bi-arrow-right-circle" style="font-size: 1.5rem; color: #4B944B;"></i>
                </button>
            </div>
        </div>
    </div>';
}
$htmlIzquierda .= '<div id="listaComentarios" class="mt-3"></div>';

// HTML DERECHA: Perfil del Usuario
$apodoPublicador = htmlspecialchars($usuario['apodo'] ?? 'Usuario');
$arrobaPublicador = htmlspecialchars(ltrim($usuario['arroba'] ?? 'usuario', '@'));
$perfilUrl = $basePath . '/perfil?id=' . $idUsuarioSeguido;

$htmlDerecha = '<div class="text-center mt-5">
    <a href="' . $perfilUrl . '" class="text-decoration-none text-dark d-inline-block">
        <img src="' . obtenerAvatar($idUsuarioSeguido) . '" class="rounded-circle mb-3" style="width: 100px; height: 100px; object-fit: cover;">
        <h5>' . $apodoPublicador . '</h5>
    </a>
    <p class="text-muted">@' . $arrobaPublicador . '</p> 
    <div class="d-flex justify-content-center gap-3 mt-2">
        <div><strong>' . (int)$cantAlbumes . '</strong><br><small>Álbumes</small></div>
        <div><strong>' . (int)$cantSeguidores . '</strong><br><small>Seguidores</small></div>
    </div>
</div>';

date_default_timezone_set('America/Argentina/Buenos_Aires');

// Obtener la fecha más reciente de las imágenes para mostrar en el header
$fechaMasReciente = null;
if (!empty($imagenes)) {
    foreach ($imagenes as $img) {
        if (isset($img['fechaImagen']) && $img['fechaImagen']) {
            $fechaImg = strtotime($img['fechaImagen']);
            if (!$fechaMasReciente || $fechaImg > $fechaMasReciente) {
                $fechaMasReciente = $fechaImg;
            }
        }
    }
}

// Si no hay fecha, usar la fecha actual
if (!$fechaMasReciente) {
    $fechaMasReciente = time();
}

$fechaISO = date('c', $fechaMasReciente);
$fechaUnix = $fechaMasReciente * 1000; // Convertir a milisegundos

echo json_encode([
    'tituloAlbum' => 'Contenido que te gusta de ' . $apodoPublicador,
    'fotoPerfil' => obtenerAvatar($idUsuarioSeguido),
    'fecha' => $fechaISO,
    'fechaUnix' => $fechaUnix,
    'apodo' => $apodoPublicador,
    'usuario' => $arrobaPublicador,
    'idUsuario' => $idUsuarioSeguido, 
    'cantAlbumes' => (int)$cantAlbumes,
    'cantSeguidores' => (int)$cantSeguidores,
    'izquierda' => $htmlIzquierda,
    'derecha' => $htmlDerecha,
    'comentarios' => $comentariosPorImagen,
    'carruselId' => 'carouselAlbumVirtual'
], JSON_UNESCAPED_UNICODE);
?>

