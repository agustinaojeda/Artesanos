<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json; charset=utf-8');

require_once dirname(__DIR__) . '/models/albumModelo.php';
require_once dirname(__DIR__) . '/models/imagenModelo.php';
require_once dirname(__DIR__) . '/models/comentarioModelo.php';
require_once dirname(__DIR__) . '/models/usuarioHelper.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Validar ID de álbum
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo json_encode([
        'tituloAlbum' => 'Error',
        'izquierda' => '<p class="text-danger">ID de álbum no válido.</p>',
        'derecha' => ''
    ]);
    exit;
}

$id = (int)$_GET['id'];
$idUsuario = $_SESSION['usuario']['id'] ?? 0;

$modeloAlbum = new AlbumModelo();
$imagenModelo = new ImagenModelo();
$comentarioModelo = new ComentarioModelo();

// Obtener álbum
$album = $modeloAlbum->mostrarAlbumId($id);

if (!$album) {
    http_response_code(404);
    echo json_encode(['error' => 'Álbum no encontrado o no existe.']);
    exit;
}

// Obtener imágenes
$imagenes = $imagenModelo->mostrarPorAlbum($album);

// Obtener usuario del álbum
$usuario = $modeloAlbum->obtenerUsuarioDe($album->idAlbum);

if (!$usuario || !isset($usuario['idUsuario'])) {
    http_response_code(404);
    echo json_encode(['error' => 'Usuario del álbum no encontrado.']);
    exit;
}

if (isset($_GET['ajax'])) {
    ob_start();
    include 'detalleAlbumIzquierda.php'; // o el bloque HTML que muestra las fotos
    $izquierda = ob_get_clean();

    ob_start();
    include 'detalleAlbumDerecha.php'; // o el bloque que muestra descripción, usuario, etc.
    $derecha = ob_get_clean();

    $response = [
        'izquierda' => $izquierda,
        'derecha' => $derecha,
        'fotoPerfil' => $fotoPerfil ?? '',
        'nombreUsuario' => $nombreUsuario ?? ''
    ];

    echo json_encode($response);
    exit;
}


// Contadores
$cantAlbumes = $modeloAlbum->contarAlbumesDeUsuario($usuario['idUsuario']);
$cantSeguidores = $modeloAlbum->contarSeguidoresDeUsuario($usuario['idUsuario']);

// HTML IZQUIERDA: carrusel + imagenes
$htmlIzquierda = '<h4>' . htmlspecialchars($album->tituloAlbum) . '</h4>
<div id="carouselAlbum" class="carousel slide mb-3" data-bs-ride="false">
  <div class="carousel-inner">';

if (!empty($imagenes)) {
    foreach ($imagenes as $i => $img) {
        $active = $i === 0 ? 'active' : '';
        $htmlIzquierda .= '<div class="carousel-item ' . $active . '" 
              data-idimagen="' . $img['idImagen'] . '"
              data-titulo="' . htmlspecialchars($img['tituloImagen'] ?? '') . '" 
              data-descripcion="' . htmlspecialchars($img['descripcionImagen'] ?? '') . '" 
              data-fechaunix="' . (
                preg_match('/\d{2}:\d{2}/', (string)($img['fechaImagen'] ?? '')) 
                  ? (strtotime($img['fechaImagen']) * 1000) 
                  : (strtotime($album->fechaCreacion) * 1000)
              ) . '">
            <div style="width: 100%; max-width: 500px; aspect-ratio: 1 / 1; overflow: hidden; margin: auto;">
                <img src="../../public/uploads/imagenes/' . htmlspecialchars($img['urlImagen'] ?? 'sin-imagen.png') . '" 
                     class="w-100 h-100" style="object-fit: contain;">
            </div>
          </div>';
    }
} else {
    $htmlIzquierda .= '
    <div class="carousel-item active">
        <div class="text-center text-muted py-5">Sin imágenes en este álbum.</div>
    </div>';
}

$htmlIzquierda .= '
  </div>
  <button class="carousel-control-prev" type="button" data-bs-target="#carouselAlbum" data-bs-slide="prev">
    <span class="carousel-control-prev-icon"></span>
  </button>
  <button class="carousel-control-next" type="button" data-bs-target="#carouselAlbum" data-bs-slide="next">
    <span class="carousel-control-next-icon"></span>
  </button>
</div>

<h5 id="tituloImagen"></h5>
<p id="descripcionImagen" class="text-muted"></p>';

// Iconos de interacción
$htmlIzquierda .= '
<div class="d-flex align-items-center mb-3 gap-2">
    <img src="../../public/assets/images/like.png" 
        alt="Me gusta" 
        id="btn-like-imagen" 
        data-idimagen="' . ($imagenes[0]['idImagen'] ?? 0) . '"
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

// Nota: Ahora los elementos de interacción están dentro de un div flex para mejor alineación.
// Comentarios
$comentariosPorImagen = [];
foreach ($imagenes as $img) {
    $comentarios = $comentarioModelo->mostrarComentariosDeImagen($img['idImagen']);
    $comentariosPorImagen[$img['idImagen']] = $comentarios;
}

if ($idUsuario == 0) {
    $htmlIzquierda .= '<p class="text-muted mt-4">Inicia sesión para agregar comentarios.</p>';
} else {
    $avatar = obtenerAvatar($idUsuario);
    $htmlIzquierda .= '<div id="formularioComentario" class="d-flex align-items-start gap-2 mt-4">
      <img id="avatarUsuarioComentario" src="' . $avatar . '" class="rounded-circle" style="width: 40px; height: 40px; object-fit: cover;">
      <div class="flex-grow-1">
        <div class="d-flex">
          <input type="text" id="inputComentario" class="form-control mb-3" placeholder="¿Agregar comentario?" maxlength="200" style="resize: none; height: 50px;">
          <button id="btnEnviarComentario" class="btn btn-link" data-idimagen="' . ($imagenes[0]['idImagen'] ?? 0) . '">
            <i class="bi bi-arrow-right-circle" style="font-size: 1.5rem; color: #4B944B;"></i>
          </button>
        </div>
      </div>
    </div>';
}
$htmlIzquierda .= '<div id="listaComentarios" class="mt-3"></div>';

// HTML DERECHA: perfil
$htmlDerecha = '<div class="text-center mt-5">
  <img src="' . obtenerAvatar($usuario['idUsuario']) . '" class="rounded-circle mb-3" style="width: 100px; height: 100px; object-fit: cover;">
  <h5>' . htmlspecialchars($usuario['apodo'] ?? 'Sin apodo') . '</h5>
  <p class="text-muted">@' . htmlspecialchars(ltrim($usuario['arroba'] ?? '', '@')) . '</p>
  <div class="d-flex justify-content-center gap-3 mt-2">
    <div><strong>' . (int)$cantAlbumes . '</strong><br><small>Álbumes</small></div>
    <div><strong>' . (int)$cantSeguidores . '</strong><br><small>Seguidores</small></div>
  </div>
</div>';

// Respuesta final
date_default_timezone_set('America/Lima');

echo json_encode([
    'tituloAlbum' => $album->tituloAlbum,
    'fotoPerfil' => obtenerAvatar($usuario['idUsuario']),
    'fecha' => date('c', strtotime($album->fechaCreacion)),
    'fechaUnix' => strtotime($album->fechaCreacion) * 1000,
    // Eliminado nowUnix y fechaRelativa: el cliente usa Date.now()
    'apodo' => $usuario['apodo'] ?? 'Usuario',
    'usuario' => ltrim($usuario['arroba'] ?? '', '@'),
    'idUsuario' => $usuario['idUsuario'],
    'cantAlbumes' => (int)$cantAlbumes,
    'cantSeguidores' => (int)$cantSeguidores,
    'izquierda' => $htmlIzquierda,
    'derecha' => $htmlDerecha,
    'comentarios' => $comentariosPorImagen
]);
