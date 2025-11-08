<?php

ini_set('display_errors', 0);
error_reporting(0);

header('Content-Type: application/json; charset=utf-8');

// Incluir los helpers/modelos necesarios
require_once dirname(__DIR__) . '/models/albumModelo.php';
require_once dirname(__DIR__) . '/models/imagenModelo.php';
require_once dirname(__DIR__) . '/models/comentarioModelo.php';
require_once dirname(__DIR__) . '/models/usuarioHelper.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$idUsuarioLogueado = $_SESSION['usuario']['id'] ?? 0;

$dadorLikesId = (int)$_GET['dadorLikesId'] ?? 0;  
$fotosDeId = (int)$_GET['fotosDeId'] ?? 0;        

if ($dadorLikesId === 0 || $fotosDeId === 0) {
    http_response_code(400);
    echo json_encode([
        'error' => 'Petición inválida. Faltan IDs (Dador/Dueño).'
    ]);
    exit;
}

$modeloAlbum = new AlbumModelo();
$comentarioModelo = new ComentarioModelo();

$imagenes = $modeloAlbum->obtenerImagenesLikeadasDelArtista($dadorLikesId, $fotosDeId);

$usuario = $modeloAlbum->obtenerDatosUsuarioPorId($fotosDeId);

if (!$usuario) {
    http_response_code(404);
    echo json_encode(['error' => 'Artista (Dueño de las fotos) no encontrado.']);
    exit;
}

$cantAlbumes = $modeloAlbum->contarAlbumesDeUsuario($fotosDeId);
$cantSeguidores = $modeloAlbum->contarSeguidoresDeUsuario($fotosDeId);

//HTML IZQUIERDA 
$htmlIzquierda = '<h5 id="nombreAlbumActivo"></h5>
<div id="carouselAlbumVirtual" class="carousel slide mb-3" data-bs-ride="false">
    <div class="carousel-inner">';

if (!empty($imagenes)) {
    foreach ($imagenes as $i => $img) {
        $active = $i === 0 ? 'active' : '';
        $fechaImg = $img['fechaImagen'] ?? '';
        $fechaUnix = empty($fechaImg) ? time() * 1000 : (strtotime($fechaImg) * 1000);

        $htmlIzquierda .= '<div class="carousel-item ' . $active . '" 
            data-idimagen="' . $img['idImagen'] . '"
            data-titulo="' . htmlspecialchars($img['tituloImagen'] ?? '') . '"
            data-descripcion="' . htmlspecialchars($img['descripcionImagen'] ?? '') . '" 
            data-fechaunix="' . $fechaUnix . '"
            data-nombrealbum="' . htmlspecialchars($img['nombreAlbum'] ?? '') . '">
            <div style="width: 100%; max-width: 500px; aspect-ratio: 1 / 1; overflow: hidden; margin: auto;">
                <img src="../../public/uploads/imagenes/' . htmlspecialchars($img['urlImagen'] ?? 'sin-imagen.png') . '" 
                    class="w-100 h-100" style="object-fit: contain;">
            </div>
        </div>';
    }
} else {
    $htmlIzquierda .= '
        <div class="carousel-item active">
            <div class="text-center text-muted py-5">Este usuario no le ha dado like a ninguna imagen de este artista.</div>
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

$idImagenInicial = $imagenes[0]['idImagen'] ?? 0;
$htmlIzquierda .= '
<div class="d-flex align-items-center mb-3 gap-2">
    <img src="../../public/assets/images/like.png" 
        alt="Me gusta" 
        id="btn-like-imagen" 
        data-idimagen="' . $idImagenInicial . '"
        class="img-fluid" 
        style="max-height: 30px; cursor: pointer;">
    
    <span id="likes-count-display" class="text-muted small align-self-center me-3">0</span>
    
    <a href="#formularioComentario" class="d-flex align-items-center text-decoration-none text-dark">
        <img src="../../public/assets/images/comentario.png" 
            alt="Comentario" 
            class="img-fluid" 
            style="max-height: 27px; cursor: pointer;">
        <span class="ms-1 small">Comentarios</span>
    </a>
</div>';

$comentariosPorImagen = [];
foreach ($imagenes as $img) {
    $comentarios = $comentarioModelo->mostrarComentariosDeImagen($img['idImagen']);
    $comentariosPorImagen[$img['idImagen']] = $comentarios;
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


// HTML DERECHA: Perfil del Artista
$apodoPublicador = htmlspecialchars($usuario['apodo'] ?? ''); // Apodo de Miguel
$arrobaPublicador = htmlspecialchars(ltrim($usuario['arroba'] ?? '', '@'));
$urlPerfil = "perfil.php?id={$fotosDeId}";

$htmlDerecha = '<div class="text-center mt-5">
    <a href="' . $urlPerfil . '" class="text-decoration-none text-dark d-inline-block">
        <img src="' . obtenerAvatar($fotosDeId) . '" class="rounded-circle mb-3" style="width: 100px; height: 100px; object-fit: cover;">
        <h5>' . $apodoPublicador . '</h5>
    </a>
    <p class="text-muted">@' . $arrobaPublicador . '</p> 
    <div class="d-flex justify-content-center gap-3 mt-2">
        <div><strong>' . (int)$cantAlbumes . '</strong><br><small>Álbumes</small></div>
        <div><strong>' . (int)$cantSeguidores . '</strong><br><small>Seguidores</small></div>
    </div>
</div>';


date_default_timezone_set('America/Argentina/Buenos_Aires');

echo json_encode([
    'tituloAlbum' => 'Likes de ' . $apodoPublicador,
    'fotoPerfil' => obtenerAvatar($fotosDeId),
    'fecha' => date('c'), 
    'apodo' => $apodoPublicador,
    'usuario' => $arrobaPublicador,
    'idUsuario' => $fotosDeId, 
    'cantAlbumes' => (int)$cantAlbumes,
    'cantSeguidores' => (int)$cantSeguidores,
    'izquierda' => $htmlIzquierda,
    'derecha' => $htmlDerecha,
    'comentarios' => $comentariosPorImagen,
    'carruselId' => 'carouselAlbumVirtual'
]);
?>