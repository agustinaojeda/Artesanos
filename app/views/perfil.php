<?php
// app/views/perfil.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Incluir conexión (ruta CORREGIDA y robusta)
require_once dirname(__DIR__, 2) . '/config/conexion.php';

// Incluir helper de usuario
require_once dirname(__DIR__) . '/models/usuarioHelper.php';

// Helper de escape
function e($s)
{
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

// Aseguramos que exista la función abrirConexion
if (!function_exists('abrirConexion')) {
    die("Error crítico: La función abrirConexion() no fue cargada. Verifica el archivo config/conexion.php");
}

$conexion = abrirConexion();

if ($conexion === false || $conexion->connect_error) {
    $error_msg = ($conexion === false) ? "Imposible establecer la conexión." : $conexion->connect_error;
    die("Error de conexión a la base de datos. Detalles: " . $error_msg);
}

// Obtener ID del perfil
$perfilId = null;
if (isset($_GET['id'])) {
    $perfilId = (int)$_GET['id'];
} else if (isset($_SESSION['usuario']['id'])) {
    $perfilId = (int)$_SESSION['usuario']['id'];
}

if (!$perfilId) {
    header('Location: home.php');
    exit;
}

$isOwner = (isset($_SESSION['usuario']['id']) && (int)$_SESSION['usuario']['id'] === $perfilId);

// Consulta de datos del usuario
$sqlUser = "
    SELECT
    u.idUsuario, u.arrobaUsuario, u.apodoUsuario, u.nombreUsuario,
    u.apellidoUsuario,
    u.correoUsuario,
    u.descripcionUsuario,
    fp.imagenPerfil
    FROM usuario u
    LEFT JOIN fotosdeperfil fp ON fp.idFotoPerfil = u.idFotoPerfilUsuario
    WHERE u.idUsuario = ? LIMIT 1
";

$userStmt = $conexion->prepare($sqlUser);
if (!$userStmt) {
    http_response_code(500);
    exit("Error al preparar la consulta de usuario: " . $conexion->error);
}

$userStmt->bind_param("i", $perfilId);
$userStmt->execute();
$resultUser = $userStmt->get_result();
$userData = $resultUser->fetch_assoc();
$userStmt->close();

if (!$userData) {
    http_response_code(404);
    exit("Usuario no encontrado.");
}

// Avatar con helper
$avatarUrl = obtenerAvatar($perfilId);

// Consulta de álbumes
$sqlAlbums = "
    SELECT 
        a.idAlbum, a.tituloAlbum AS nombreAlbum, a.urlPortadaAlbum,
        a.fechaCreacionAlbum,
        COUNT(i.idImagen) AS total_imagenes
    FROM album a
    LEFT JOIN imagen i ON i.idAlbumImagen = a.idAlbum
    WHERE a.idUsuarioAlbum = ? 
    GROUP BY a.idAlbum, a.tituloAlbum, a.urlPortadaAlbum, a.fechaCreacionAlbum 
    ORDER BY a.fechaCreacionAlbum DESC
";

$albumsStmt = $conexion->prepare($sqlAlbums);
if (!$albumsStmt) {
    http_response_code(500);
    exit("Error al preparar la consulta de álbumes: " . $conexion->error);
}

$albumsStmt->bind_param("i", $perfilId);
$albumsStmt->execute();
$resultAlbums = $albumsStmt->get_result();
$albums = $resultAlbums->fetch_all(MYSQLI_ASSOC);
$albumsStmt->close();

if (!is_array($albums)) {
    $albums = [];
}

// Conteo de seguidores
$sqlFollowers = "SELECT COUNT(*) AS seguidores FROM seguimiento WHERE idSeguido = ?";
$followersStmt = $conexion->prepare($sqlFollowers);
if ($followersStmt) {
    $followersStmt->bind_param("i", $perfilId);
    $followersStmt->execute();
    $resultFollowers = $followersStmt->get_result();
    $followersData = $resultFollowers->fetch_assoc();
    $followersCount = $followersData['seguidores'] ?? 0;
    $followersStmt->close();
} else {
    $followersCount = 0;
}

// Estado de seguimiento (si corresponde)
$followStatus = null; // puede ser 'pendiente', 'activo' o null
if (!$isOwner && isset($_SESSION['usuario']['id'])) {
    $currentUserId = (int)$_SESSION['usuario']['id'];
    $sqlStatus = "SELECT estadoSeguimiento FROM seguimiento WHERE idSeguidor = ? AND idSeguido = ? LIMIT 1";
    $stmtStatus = $conexion->prepare($sqlStatus);
    if ($stmtStatus) {
        $stmtStatus->bind_param("ii", $currentUserId, $perfilId);
        $stmtStatus->execute();
        $resultStatus = $stmtStatus->get_result();
        if ($rowStatus = $resultStatus->fetch_assoc()) {
            $followStatus = $rowStatus['estadoSeguimiento'];
        }
        $stmtStatus->close();
    }
}

// === Me gusta del usuario (álbums e imágenes) ===
// Álbums que el usuario ha dado like
$likedAlbums = [];
$sqlLikedAlbums = "
    SELECT a.idAlbum, a.tituloAlbum AS nombreAlbum, a.urlPortadaAlbum, a.fechaCreacionAlbum
    FROM megusta_album ma
    JOIN album a ON a.idAlbum = ma.idAlbumLike
    WHERE ma.idUsuarioLike = ?
    ORDER BY ma.fechaLike DESC
";
$stmtLikedAlbums = $conexion->prepare($sqlLikedAlbums);
if ($stmtLikedAlbums) {
    $stmtLikedAlbums->bind_param("i", $perfilId);
    $stmtLikedAlbums->execute();
    $resLikedAlbums = $stmtLikedAlbums->get_result();
    $likedAlbums = $resLikedAlbums ? $resLikedAlbums->fetch_all(MYSQLI_ASSOC) : [];
    $stmtLikedAlbums->close();
}

// Imágenes que el usuario ha dado like
$likedImages = [];
$sqlLikedImages = "
    SELECT i.idImagen, i.tituloImagen, i.descripcionImagen, i.urlImagen, i.idAlbumImagen,
           a.tituloAlbum AS nombreAlbum
    FROM megusta m
    JOIN imagen i ON i.idImagen = m.idImagenLike
    JOIN album a ON a.idAlbum = i.idAlbumImagen
    WHERE m.idUsuarioLike = ?
    ORDER BY m.fechaLike DESC
";
$stmtLikedImages = $conexion->prepare($sqlLikedImages);
if ($stmtLikedImages) {
    $stmtLikedImages->bind_param("i", $perfilId);
    $stmtLikedImages->execute();
    $resLikedImages = $stmtLikedImages->get_result();
    $likedImages = $resLikedImages ? $resLikedImages->fetch_all(MYSQLI_ASSOC) : [];
    $stmtLikedImages->close();
}



$conexion->close();
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Artesanos</title>
    <link rel="icon" href="../../public/assets/images/logo.png" type="image/x-icon">

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">

    <link rel="stylesheet" href="../../public/assets/css/nav.css">
    <link rel="stylesheet" href="../../public/assets/css/home.css">
    <link rel="stylesheet" href="../../public/assets/css/perfil.css">

    <style>
        .modal {
            z-index: 20000 !important;
        }

        .modal-backdrop {
            z-index: 19999 !important;
        }
    </style>


    <style>
        /* Layout de 5 cajas */
        .profile-grid {
            display: grid;
            grid-template-columns: 140px 1fr 2fr 1fr 240px;
            gap: 20px;
            align-items: center;
        }

        @media (max-width: 992px) {
            .profile-grid {
                grid-template-columns: 1fr;
                text-align: center;
            }

            .box-buttons-container {
                display: flex;
                gap: 10px;
                justify-content: center;
                flex-wrap: wrap;
                margin-top: 18px;
            }

            .action-buttons {
                align-items: center;
            }
        }

        .box {
            padding: 12px;
        }

        .box-avatar {
            display: flex;
            justify-content: center;
            align-items: flex-start;
            flex-direction: column;
            gap: 10px;
        }

        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid #f7931e;
            display: block;
            margin: 0 auto;
        }

        .apodo {
            font-size: 1.6rem;
            font-weight: 700;
        }

        .arroba {
            color: #6c757d;
            margin-top: 4px;
        }

        .descripcion {
            font-size: 1rem;
            color: #333;
        }

        .counters {
            display: flex;
            gap: 20px;
            justify-content: flex-end;
            align-items: center;
        }

        .counter-item {
            text-align: center;
        }

        .counter-item strong {
            display: block;
            font-size: 1.4rem;
        }

        .box-buttons-container {
            display: flex;
            align-items: center;
            gap: 12px;
            justify-content: flex-end;
        }

        .action-buttons {
            display: flex;
            flex-direction: column;
            gap: 8px;
            align-items: flex-end;
        }

        .action-buttons .btn {
            width: 150px;
        }

        .btn-contact-circle {
            background-color: #22c55e;
            color: #fff;
            border: none;
            width: 48px;
            height: 48px;
            border-radius: 50%;
            padding: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.15rem;
            margin-right: 8px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08);
        }

        .btn-contact-circle:hover {
            background-color: #1a9d4a;
        }

        .btn-orange-full {
            background-color: #f7931e;
            color: #fff;
            border: none;
        }

        .btn-orange-full:hover {
            background-color: #e58514;
            color: #fff;
        }

        .btn-success-full {
            background-color: #22c55e;
            color: #fff;
            border: none;
        }

        .btn-success-full:hover {
            background-color: #1a9d4a;
        }

        .profile-top {
            padding-top: 40px;
            padding-bottom: 20px;
        }

        .album-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(230px, 1fr));
            gap: 20px;
        }

        .album-card {
            cursor: pointer;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
            background: #fff;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .album-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.15);
        }

        .album-img-wrapper {
            width: 100%;
            aspect-ratio: 1 / 1;
            overflow: hidden;
        }

        .album-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .album-info {
            padding: 10px 14px 14px;
        }

        .album-info h5 {
            margin: 0;
            font-size: 1.05rem;
            font-weight: 600;
            color: #333;
        }
    </style>
</head>

<body>
    <?php include 'nav.php'; ?>
    <div class="modal fade" id="modalDetalleAlbum" tabindex="-1" aria-labelledby="modalDetalleAlbumLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content p-4">
                <div class="modal-header d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center gap-3">
                        <img id="modalFotoPerfil" src="" class="rounded-circle" style="width: 50px; height: 50px; object-fit: cover;">
                        <h5 class="modal-title mb-0" id="modalDetalleAlbumLabel">Nombre del usuario</h5>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <button id="btnSeguir" class="btn btn-outline-primary btn-sm">Seguir</button>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                </div>


                <div class="modal-body">
                    <div class="row">
                        <div class="col-lg-9" id="detalleAlbumIzquierda">
                            <!--aca va el carrusel de las imagenes etc -->
                            en construccion
                        </div>

                        <div class="col-lg-3" id="detalleAlbumDerecha">
                            <!--aca va el perfil -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container profile-top">
        <div class="profile-grid align-items-center">
            <div class="box box-avatar">
                <img src="<?= e($avatarUrl) ?>" alt="Avatar" class="profile-avatar">
            </div>

            <div class="box d-flex flex-column justify-content-center">
                <div class="apodo"><?= e($userData['apodoUsuario'] ?: $userData['nombreUsuario']) ?></div>
                <div class="arroba"><?= '@' . e(ltrim(($userData['arrobaUsuario'] ?: $userData['apodoUsuario']), '@')) ?></div>
            </div>

            <div class="box">
                <p class="descripcion mb-0" style="white-space: pre-line;">
                    <?php
                        $desc = $userData['descripcionUsuario'] ?? '';
                        $desc = $desc !== '' ? $desc : 'Sin descripción.';
                        echo htmlspecialchars(str_replace(["\r\n", "\n", "\r"], "\n", $desc));
                    ?>
                </p>

            </div>

            <div class="box">
                <div class="counters">
                    <div class="counter-item">
                        <strong><?= count($albums) ?></strong>
                        <small class="text-muted">Álbumes</small>
                    </div>
                    <div class="counter-item">
                        <strong><?= e($followersCount) ?></strong>
                        <small class="text-muted">Seguidores</small>
                    </div>
                </div>
            </div>

            <div class="box">
                <div class="box-buttons-container">
                    <button class="btn btn-contact-circle" data-bs-toggle="modal" data-bs-target="#modalContacto" title="Contactar">
                        <i class="bi bi-envelope-fill"></i>
                    </button>

                    <div class="action-buttons">
                        <?php if ($isOwner): ?>
                            <a href="editarPerfil.php" class="btn btn-orange-full d-flex align-items-center justify-content-center">
                                <i class="bi bi-pencil me-2"></i> Editar perfil
                            </a>

                            <form method="POST" action="cerrarSesion.php" style="margin:0;">
                                <button type="submit" class="btn btn-orange-full d-flex align-items-center justify-content-center">
                                    <i class="bi bi-door-open me-2"></i> Cerrar sesión
                                </button>
                            </form>
                        <?php elseif (isset($_SESSION['usuario']['id'])): ?>
                            <?php
                                                       
                                if ($followStatus === 'pendiente') {
                                $followBtnClass = 'btn-secondary';
                                $followBtnText = '<i class="bi bi-hourglass-split me-2"></i> Pendiente';
                            } elseif ($followStatus === 'activo') {
                                $followBtnClass = 'btn-success-full';
                                $followBtnText = '<i class="bi bi-check2 me-2"></i> Siguiendo';
                            } else {
                                $followBtnClass = 'btn-orange-full';
                                $followBtnText = '<i class="bi bi-person-plus me-2"></i> Seguir';
                            }

                            ?>
                            <button id="follow-btn" class="btn <?= $followBtnClass ?> d-flex align-items-center justify-content-center" data-id-seguido="<?= $perfilId ?>">
                            <?= $followBtnText ?>
                            </button>


                        <?php else: ?>
                            <a href="login.php" class="btn btn-orange-full d-flex align-items-center justify-content-center">
                                <i class="bi bi-person-plus me-2"></i> Seguir
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <hr class="my-4">

        <ul class="nav nav-tabs mb-4">
            <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#albums-tab">Álbumes</a></li>
            <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#likes-tab">Me gusta</a></li>
        </ul>

        <div class="tab-content">
            <div class="tab-pane fade show active" id="albums-tab">
                <h3 class="mb-4">Álbumes (<?= count($albums) ?>)</h3>
                <?php if (empty($albums)): ?>
                    <p class="text-muted text-center py-5">Sin álbumes para este usuario.</p>
                <?php else: ?>
                    <div class="album-grid">
                        <?php foreach ($albums as $album): ?>
                            <?php
                            $coverUrl = $album['urlPortadaAlbum']
                                ? '../../public/uploads/portadas/' . e($album['urlPortadaAlbum'])
                                : '../../public/assets/images/imagen.png';

                            // ✅ Agregá esta línea
                            $albumDate = new DateTime($album['fechaCreacionAlbum']);
                            ?>
                            <div class="album-card" data-id="<?= (int)$album['idAlbum'] ?>" data-bs-toggle="modal" data-bs-target="#modalDetalleAlbum">
                                <div class="album-img-wrapper">
                                    <img src="<?= $coverUrl ?>" alt="Portada de álbum" class="album-img">
                                </div>
                                <div class="album-info">
                                    <h5><?= e($album['nombreAlbum']) ?></h5>
                                    <small class="text-muted">
                                        <?= (int)$album['total_imagenes'] ?> imágenes • <?= $albumDate->format('d/m/Y') ?>
                                    </small>
                                    <div class="d-flex gap-1 align-items-center mt-1">
                                        <img src="../../public/assets/images/like.png"
                                             alt="Me gusta"
                                             class="img-fluid btn-like-galeria"
                                             data-idalbum="<?= (int)$album['idAlbum'] ?>"
                                             style="max-height: 25px; cursor: pointer;">
                                        <span id="likes-count-album-<?= (int)$album['idAlbum'] ?>" class="text-muted small align-self-center">0</span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>

                    </div>

                <?php endif; ?>
            </div>

            <div class="tab-pane fade" id="likes-tab">
                <!-- Álbumes con Me Gusta -->
                <h3 class="mb-3">Álbumes que le gustan (<?= count($likedAlbums) ?>)</h3>
                <?php if (empty($likedAlbums)): ?>
                    <p class="text-muted">No hay álbumes con “Me gusta”.</p>
                <?php else: ?>
                    <div class="album-grid mb-4">
                        <?php foreach ($likedAlbums as $album): ?>
                            <?php
                                $coverUrl = $album['urlPortadaAlbum']
                                    ? '../../public/uploads/portadas/' . e($album['urlPortadaAlbum'])
                                    : '../../public/assets/images/imagen.png';
                                $albumDate = new DateTime($album['fechaCreacionAlbum']);
                            ?>
                            <div class="album-card" data-id="<?= (int)$album['idAlbum'] ?>" data-bs-toggle="modal" data-bs-target="#modalDetalleAlbum">
                                <div class="album-img-wrapper">
                                    <img src="<?= $coverUrl ?>" alt="Portada de álbum" class="album-img">
                                </div>
                                <div class="album-info">
                                    <h5><?= e($album['nombreAlbum']) ?></h5>
                                    <small class="text-muted"><?= $albumDate->format('d/m/Y') ?></small>
                                    <div class="d-flex gap-1 align-items-center mt-1">
                                        <img src="../../public/assets/images/like.png"
                                             alt="Me gusta"
                                             class="img-fluid btn-like-galeria"
                                             data-idalbum="<?= (int)$album['idAlbum'] ?>"
                                             style="max-height: 25px; cursor: pointer;">
                                        <span id="likes-count-album-<?= (int)$album['idAlbum'] ?>" class="text-muted small align-self-center">0</span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <!-- Imágenes con Me Gusta -->
                <h3 class="mb-3">Imágenes que le gustan (<?= count($likedImages) ?>)</h3>
                <?php if (empty($likedImages)): ?>
                    <p class="text-muted">No hay imágenes con “Me gusta”.</p>
                <?php else: ?>
                    <div class="album-grid">
                        <?php foreach ($likedImages as $img): ?>
                            <?php
                                $imgUrl = $img['urlImagen']
                                    ? '../../public/uploads/imagenes/' . e($img['urlImagen'])
                                    : '../../public/assets/images/imagen.png';
                            ?>
                            <div class="album-card" data-id="<?= (int)$img['idAlbumImagen'] ?>" data-bs-toggle="modal" data-bs-target="#modalDetalleAlbum" title="<?= e($img['tituloImagen'] ?? '') ?>">
                                <div class="album-img-wrapper">
                                    <img src="<?= $imgUrl ?>" alt="Imagen con Me Gusta" class="album-img">
                                </div>
                                <div class="album-info">
                                    <h5><?= e($img['tituloImagen'] ?? 'Sin título') ?></h5>
                                    <small class="text-muted">Álbum: <?= e($img['nombreAlbum'] ?? '') ?></small>
                                    <div class="d-flex gap-1 align-items-center mt-1">
                                        <img src="../../public/assets/images/like.png"
                                             alt="Me gusta imagen"
                                             class="img-fluid btn-like-imagen-perfil"
                                             data-idimagen="<?= (int)$img['idImagen'] ?>"
                                             style="max-height: 25px; cursor: pointer;">
                                        <span id="likes-count-image-<?= (int)$img['idImagen'] ?>" class="text-muted small align-self-center">0</span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Modal Contacto -->
    <div class="modal fade" id="modalContacto" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        Contacto de <?= e($userData['apodoUsuario']) ?>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <p><strong>Nombre:</strong> <?= e($userData['nombreUsuario']) . ' ' . e($userData['apellidoUsuario']) ?></p>
                    <p><strong>Email de contacto:</strong> <?= e($userData['correoUsuario']) ?></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    <a
                        href="https://mail.google.com/mail/?view=cm&to=<?= urlencode($userData['correoUsuario']) ?>"
                        target="_blank"
                        class="btn btn-success d-flex align-items-center justify-content-center">
                        <i class="bi bi-envelope-fill me-2"></i> Enviar email
                    </a>
                </div>
            </div>
        </div>
    </div>



    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../public/assets/js/perfil.js"></script>

    <!-- ====== Pequeño script adicional (solo lo necesario) ======
         Se encarga de actualizar título/descripcion al cambiar slide
         y de re-ligar el evento cuando se abre el modal.
         No modifica nada más del comportamiento actual.
    -->
    <script>
      (function() {
        function actualizarInfoImagen() {
          const carrusel = document.getElementById('carouselAlbum');
          if (!carrusel) return;
          const activo = carrusel.querySelector('.carousel-item.active');
          if (!activo) return;
          const titulo = activo.getAttribute('data-titulo') || '';
          const descripcion = activo.getAttribute('data-descripcion') || '';
          const tituloEl = document.getElementById('tituloImagen');
          const descEl = document.getElementById('descripcionImagen');
          if (tituloEl) tituloEl.textContent = titulo;
          if (descEl) descEl.textContent = descripcion;
        }

        // Cuando el modal se muestra (después del fetch que inyecta el HTML),
        // inicializamos/actualizamos la info y vinculamos el event listener.
        const modal = document.getElementById('modalDetalleAlbum');
        if (modal) {
          modal.addEventListener('shown.bs.modal', function () {
            // pequeña espera para asegurar que el HTML inyectado esté en el DOM
            setTimeout(() => {
              actualizarInfoImagen();

              const carrusel = document.getElementById('carouselAlbum');
              if (!carrusel) return;

              // Evitar duplicar listeners: quitamos uno previo (si existe) y agregamos otro.
              // No usamos nombres de listener complejos para mantener compatibilidad.
              carrusel.removeEventListener('slid.bs.carousel', actualizarInfoImagen);
              carrusel.addEventListener('slid.bs.carousel', actualizarInfoImagen);

              // Inicializar instancia de bootstrap Carousel si no existe
              try {
                // eslint-disable-next-line no-undef
                if (typeof bootstrap !== 'undefined') {
                  // crear/actualizar instancia (si ya existe, Bootstrap la reutiliza)
                  new bootstrap.Carousel(carrusel, { ride: false });
                }
              } catch (e) {
                console.warn('No se pudo inicializar carousel:', e);
              }
            }, 50);
          });

          // cuando se oculta, limpiamos listeners para evitar duplicados
          modal.addEventListener('hidden.bs.modal', function () {
            const carrusel = document.getElementById('carouselAlbum');
            if (carrusel) {
              carrusel.removeEventListener('slid.bs.carousel', actualizarInfoImagen);
            }
          });
        }
      })();
    </script>
    <script>
document.addEventListener('DOMContentLoaded', () => {

  /* =====================================================
     🟠 1. ACEPTAR / RECHAZAR SOLICITUD DE SEGUIMIENTO
  ====================================================== */
  document.querySelectorAll('.aceptar-seguimiento, .rechazar-seguimiento').forEach(btn => {
    btn.addEventListener('click', function() {
      const idSeguidor = this.dataset.id || this.dataset.idseguidor || this.dataset.idSeguidor;
      const accion = this.classList.contains('aceptar-seguimiento') ? 'aceptar' : 'rechazar';

      if (!idSeguidor) {
        alert('Error interno: faltan datos. Reintentá.');
        return;
      }

      fetch('responderSolicitud.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `idSeguidor=${encodeURIComponent(idSeguidor)}&accion=${encodeURIComponent(accion)}`
      })
      .then(res => res.text())
      .then(data => {
        data = data.trim();
        const followBtn = document.querySelector('#follow-btn');

        if (data === 'aceptado' && followBtn) {
          followBtn.classList.remove('btn-secondary', 'btn-orange-full');
          followBtn.classList.add('btn-success-full');
          followBtn.innerHTML = '<i class="bi bi-check2 me-2"></i> Siguiendo';
          alert("✅ Has aceptado la solicitud. Ahora ambos se siguen.");
        } else if (data === 'rechazado' && followBtn) {
          followBtn.classList.remove('btn-secondary', 'btn-success-full');
          followBtn.classList.add('btn-orange-full');
          followBtn.innerHTML = '<i class="bi bi-person-plus me-2"></i> Seguir';
          alert("❌ Has rechazado la solicitud de seguimiento.");
        } else {
          alert('Ocurrió un error: ' + data);
        }

        // Eliminar la notificación
        const card = this.closest('.notificacion-card, .list-group-item');
        if (card) card.remove();
      })
      .catch(err => {
        console.error(err);
        alert("⚠️ Error de red. Intenta nuevamente.");
      });
    });
  });

  /* =====================================================
     🟢 2. BOTÓN SEGUIR / DEJAR DE SEGUIR
  ====================================================== */
  document.addEventListener('click', function(e) {
    const btn = e.target.closest('#follow-btn');
    if (!btn) return;

    const idSeguido = btn.dataset.idSeguido || btn.dataset.idseguido || btn.dataset.id;
    if (!idSeguido) return;

    const actualizarBoton = (estado) => {
      btn.classList.remove('btn-orange-full', 'btn-success-full', 'btn-secondary');
      switch (estado) {
        case 'siguiendo':
          btn.classList.add('btn-success-full');
          btn.innerHTML = '<i class="bi bi-check2 me-2"></i> Siguiendo';
          break;
        case 'pendiente':
          btn.classList.add('btn-secondary');
          btn.innerHTML = '<i class="bi bi-hourglass-split me-2"></i> Pendiente';
          break;
        default:
          btn.classList.add('btn-orange-full');
          btn.innerHTML = '<i class="bi bi-person-plus me-2"></i> Seguir';
      }
    };

    // Si ya sigue o está pendiente → dejar de seguir
    if (btn.classList.contains('btn-success-full') || btn.classList.contains('btn-secondary')) {
      fetch('dejarSeguir.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `idSeguido=${encodeURIComponent(idSeguido)}`
      })
      .then(res => res.text())
      .then(data => {
        if (data.trim() === 'ok') {
          actualizarBoton('ninguno');
          btn.dataset.ignoreCheck = "1";
          setTimeout(() => delete btn.dataset.ignoreCheck, 6000);
        } else {
          alert('Error al dejar de seguir: ' + data);
        }
      })
      .catch(err => console.error(err));
      return;
    }

    // Si no sigue → seguir
    fetch('seguir.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/x-www-form-urlencoded'},
      body: `idSeguido=${encodeURIComponent(idSeguido)}`
    })
    .then(res => res.text())
    .then(data => {
      const estado = data.trim();
      if (estado === 'pendiente' || estado === 'siguiendo') {
        actualizarBoton(estado);
      }
    })
    .catch(err => console.error(err));
  });

  /* =====================================================
     🔵 3. POLLING: verificar estado del seguimiento cada 5s
  ====================================================== */
  setInterval(() => {
    const btn = document.querySelector('#follow-btn');
    if (!btn || btn.dataset.ignoreCheck) return;

    const idSeguido = btn.dataset.idSeguido || btn.dataset.idseguido || btn.dataset.id;
    if (!idSeguido) return;

    fetch('checkFollowStatus.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/x-www-form-urlencoded'},
      body: `idSeguido=${encodeURIComponent(idSeguido)}`
    })
    .then(res => res.text())
    .then(status => {
      const estado = status.trim();
      if (['activo','aceptado'].includes(estado)) actualizarBoton('siguiendo');
      else if (estado === 'pendiente') actualizarBoton('pendiente');
      else actualizarBoton('ninguno');
    })
    .catch(err => console.error(err));
  }, 5000);

  /* =====================================================
     🔹 4. MODAL CARRUSEL
  ====================================================== */
  const modal = document.getElementById('modalDetalleAlbum');
  if (modal) {
    const actualizarInfoImagen = () => {
      const carrusel = document.getElementById('carouselAlbum');
      if (!carrusel) return;
      const activo = carrusel.querySelector('.carousel-item.active');
      if (!activo) return;

      const tituloEl = document.getElementById('tituloImagen');
      const descEl = document.getElementById('descripcionImagen');

      if (tituloEl) tituloEl.textContent = activo.dataset.titulo || '';
      if (descEl) descEl.textContent = activo.dataset.descripcion || '';
    };

    modal.addEventListener('shown.bs.modal', () => {
      setTimeout(() => {
        actualizarInfoImagen();

        const carrusel = document.getElementById('carouselAlbum');
        if (!carrusel) return;

        carrusel.removeEventListener('slid.bs.carousel', actualizarInfoImagen);
        carrusel.addEventListener('slid.bs.carousel', actualizarInfoImagen);

        try { if (typeof bootstrap !== 'undefined') new bootstrap.Carousel(carrusel, { ride: false }); }
        catch(e){ console.warn('No se pudo inicializar carousel:', e); }
      }, 50);
    });

    modal.addEventListener('hidden.bs.modal', () => {
      const carrusel = document.getElementById('carouselAlbum');
      if (carrusel) carrusel.removeEventListener('slid.bs.carousel', actualizarInfoImagen);
    });
  }

});
</script>

</body>

</html>
