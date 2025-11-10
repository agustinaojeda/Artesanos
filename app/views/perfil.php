<?php
require_once CONFIG_PATH . '/conexion.php';
require_once MODEL_PATH . '/usuarioHelper.php';

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
// Álbums que el usuario dio like
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

// Imágenes que el usuario dio like
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

$pageTitle = 'Artesanos - Perfil';
include VIEW_PATH . '/header.php'; 
include VIEW_PATH . '/nav.php'; 
$likedContentByUser = [];
$sqlLikedByUser = "
    SELECT DISTINCT 
        u.idUsuario,
        u.nombreUsuario,
        u.apodoUsuario,
        u.arrobaUsuario,
        (
            SELECT fp.imagenPerfil
            FROM fotosdeperfil fp
            WHERE fp.idFotoPerfil = u.idFotoPerfilUsuario
            LIMIT 1
        ) as avatarUrl,
        GROUP_CONCAT(DISTINCT CONCAT('album:', a.idAlbum)) as albumLikes,
        GROUP_CONCAT(DISTINCT CONCAT('image:', i.idImagen)) as imageLikes
    FROM usuario u
    LEFT JOIN album a ON a.idUsuarioAlbum = u.idUsuario
    LEFT JOIN megusta_album ma ON ma.idAlbumLike = a.idAlbum AND ma.idUsuarioLike = ?
    LEFT JOIN imagen i ON i.idAlbumImagen = a.idAlbum
    LEFT JOIN megusta m ON m.idImagenLike = i.idImagen AND m.idUsuarioLike = ?
    WHERE u.idUsuario IN (
        SELECT idSeguido 
        FROM seguimiento 
        WHERE idSeguidor = ? 
        AND estadoSeguimiento = 'activo'
    )
    AND (ma.idAlbumLike IS NOT NULL OR m.idImagenLike IS NOT NULL)
    GROUP BY u.idUsuario, u.nombreUsuario, u.apodoUsuario, u.arrobaUsuario
";

$stmtLikedByUser = $conexion->prepare($sqlLikedByUser);
if ($stmtLikedByUser) {
    $stmtLikedByUser->bind_param("iii", $perfilId, $perfilId, $perfilId);
    $stmtLikedByUser->execute();
    $resLikedByUser = $stmtLikedByUser->get_result();
    
    while ($row = $resLikedByUser->fetch_assoc()) {
        $userId = $row['idUsuario'];
        $likedContentByUser[$userId] = [
            'user' => [
                'idUsuario' => $row['idUsuario'],
                'nombreUsuario' => $row['nombreUsuario'],
                'apodoUsuario' => $row['apodoUsuario'],
                'arrobaUsuario' => $row['arrobaUsuario'],
                'avatarUrl' => $row['avatarUrl']
            ],
            'albums' => [],
            'images' => []
        ];
        
        // Procesar álbumes
        if ($row['albumLikes']) {
            $albumIds = array_map(function($item) {
                return (int)substr($item, 6); // Remover 'album:' y convertir a int
            }, explode(',', $row['albumLikes']));
            
            // Obtener detalles de los álbumes
            $sqlAlbumDetails = "
                SELECT 
                    a.idAlbum, 
                    a.tituloAlbum as nombreAlbum, 
                    a.urlPortadaAlbum,
                    a.fechaCreacionAlbum,
                    COUNT(DISTINCT ma.idUsuarioLike) as totalLikes
                FROM album a
                LEFT JOIN megusta_album ma ON ma.idAlbumLike = a.idAlbum
                WHERE a.idAlbum IN (" . implode(',', $albumIds) . ")
                GROUP BY a.idAlbum
            ";
            
            $albumResult = $conexion->query($sqlAlbumDetails);
            if ($albumResult) {
                while ($album = $albumResult->fetch_assoc()) {
                    $likedContentByUser[$userId]['albums'][] = $album;
                }
            }
        }
        
        // Procesar imágenes
        if ($row['imageLikes']) {
            $imageIds = array_map(function($item) {
                return (int)substr($item, 6); // Remover 'image:' y convertir a int
            }, explode(',', $row['imageLikes']));
            
            // Obtener detalles de las imágenes
            $sqlImageDetails = "
                SELECT 
                    i.idImagen,
                    i.tituloImagen,
                    i.urlImagen,
                    i.idAlbumImagen,
                    a.tituloAlbum as nombreAlbum,
                    COUNT(DISTINCT m.idUsuarioLike) as totalLikes
                FROM imagen i
                JOIN album a ON a.idAlbum = i.idAlbumImagen
                LEFT JOIN megusta m ON m.idImagenLike = i.idImagen
                WHERE i.idImagen IN (" . implode(',', $imageIds) . ")
                GROUP BY i.idImagen
            ";
            
            $imageResult = $conexion->query($sqlImageDetails);
            if ($imageResult) {
                while ($image = $imageResult->fetch_assoc()) {
                    $likedContentByUser[$userId]['images'][] = $image;
                }
            }
        }
    }
    $stmtLikedByUser->close();
}
?>
    <link rel="stylesheet" href="<?= $basePath ?>/assets/css/perfil.css">
    <link rel="stylesheet" href="<?= $basePath ?>/assets/css/modalEliminarAlbum.css">
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
        /* Tres puntitos */
        .opciones-album {
            position: absolute;
            top: 8px;
            right: 8px;
            z-index: 10;
        }

        .opciones-btn {
            background: rgba(255, 255, 255, 0.9);
            border-radius: 50%;
            padding: 4px 6px;
        }

    </style>

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
<!-- Agregar esto después de tus otros modales -->
<div class="modal fade" id="modalEditarAlbum" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg"> <!-- Changed to modal-lg for more space -->
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Editar álbum</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Album Info Section -->
                <form id="formEditarAlbum">
                    <input type="hidden" name="idAlbum" id="editAlbumId">
                    
                    <div class="mb-3">
                        <label class="form-label">Título del álbum</label>
                        <input type="text" class="form-control" name="titulo" id="editAlbumTitulo" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Portada</label>
                        <input type="file" class="form-control" name="portada" accept="image/*">
                        <div class="position-relative d-inline-block mt-2" id="portadaPreviewContainer" style="display: none;">
                            <img id="editAlbumPortadaPreview" style="max-width: 200px; display: block;">
                            <button type="button" class="btn btn-sm btn-danger position-absolute top-0 end-0 m-2 eliminar-portada-btn" 
                                    style="z-index: 10; opacity: 0.9;"
                                    title="Eliminar portada">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Privacidad</label>
                        <select name="esPublico" class="form-select">
                            <option value="1">Público</option>
                            <option value="0">Solo seguidores</option>
                        </select>
                    </div>
                </form>

                <!-- Images Section -->
                <hr>
                <h6 class="mt-4">Imágenes del álbum</h6>
                <div id="albumImagesList" class="row g-3 mt-2">
                    <!-- Images will be loaded here dynamically -->
                </div>
                
                <!-- Input oculto para agregar nuevas imágenes -->
                <input type="file" id="inputNuevasImagenes" multiple accept="image/*" style="display: none;">

                <!-- Image Edit Form (initially hidden) -->
                <div id="imageEditForm" class="mt-4 d-none">
                    <hr>
                    <h6>Editar imagen</h6>
                    <form id="formEditarImagen">
                        <input type="hidden" id="editImagenId" name="idImagen">
                        <div class="mb-3">
                            <label class="form-label">Título de la imagen</label>
                            <input type="text" class="form-control" id="editImagenTitulo" name="tituloImagen" placeholder="Título de la imagen">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Descripción</label>
                            <textarea class="form-control" id="editImagenDescripcion" name="descripcionImagen" rows="3" placeholder="Descripción de la imagen"></textarea>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-sm btn-secondary" id="btnCancelarEditarImagen">Cancelar</button>
                            <button type="button" class="btn btn-sm btn-orange-full" id="btnGuardarImagen">Guardar cambios</button>
                        </div>
                    </form>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-success-full" id="btnAgregarImagenes">
                    <i class="bi bi-plus-circle"></i> Agregar imágenes
                </button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-orange-full" id="btnGuardarEdicion">Guardar cambios</button>
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
                            <a href="editarPerfil" class="btn btn-orange-full d-flex align-items-center justify-content-center">
                                <i class="bi bi-pencil me-2"></i> Editar perfil
                            </a>

                            <form method="POST" action="cerrarSesion" style="margin:0;">
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
                            <a href="login" class="btn btn-orange-full d-flex align-items-center justify-content-center">
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
                            // Si urlPortadaAlbum es 'imagen.png' o está vacío, usar la imagen por defecto
                            $portada = $album['urlPortadaAlbum'] ?? '';
                            if (empty($portada) || $portada === 'imagen.png') {
                                $coverUrl = "$basePath/assets/images/imagen.png";
                            } else {
                                $coverUrl = "$basePath/uploads/portadas/" . e($portada);
                            }

                            // ✅ Agregá esta línea
                            $albumDate = new DateTime($album['fechaCreacionAlbum']);
                            ?>
                            <div class="album-card position-relative" data-id="<?= (int)$album['idAlbum'] ?>">

                            <!-- ✅ Menú de tres puntitos (NO abre el modal) -->
                            <div class="dropdown opciones-album position-absolute top-0 end-0 m-2">
                                <button class="btn btn-light btn-sm opciones-btn" data-bs-toggle="dropdown"
                                        onclick="event.stopPropagation();">
                                    <i class="bi bi-three-dots-vertical"></i>
                                </button>

                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li>
                                        <a class="dropdown-item editar-album" href="#"
                                        data-id="<?= $album['idAlbum'] ?>"
                                        onclick="event.stopPropagation();">Editar álbum</a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item text-danger eliminar-album" href="#"
                                        data-id="<?= $album['idAlbum'] ?>"
                                        onclick="event.stopPropagation();">Eliminar álbum</a>
                                    </li>
                                </ul>
                            </div>

                            <!-- El contenido que abre el modal -->
                            <div class="album-content" data-bs-toggle="modal" data-bs-target="#modalDetalleAlbum">

                                <div class="album-img-wrapper">
                                    <img src="<?= $coverUrl ?>" alt="Portada de álbum" class="album-img">
                                </div>
                                <div class="album-info">
                                    <h5><?= e($album['nombreAlbum']) ?></h5>
                                    <small class="text-muted">
                                        <?= (int)$album['total_imagenes'] ?> imágenes • <?= $albumDate->format('d/m/Y') ?>
                                    </small>
                                    <div class="d-flex gap-1 align-items-center mt-1">
                                        <img src="<?= $basePath ?>/assets/images/like.png"
                                             alt="Me gusta"
                                             class="img-fluid btn-like-galeria"
                                             data-idalbum="<?= (int)$album['idAlbum'] ?>"
                                             style="max-height: 25px; cursor: pointer;">
                                        <span id="likes-count-album-<?= (int)$album['idAlbum'] ?>" class="text-muted small align-self-center">0</span>
                                    </div>
                                </div>

                            </div> <!-- FIN del div que abre modal -->

                        </div> <!-- FIN del album-card -->
                    <?php endforeach; ?>

                    </div>

                <?php endif; ?>
            </div>

            <div class="tab-pane fade" id="likes-tab">
                <h3 class="mb-4">Contenido que te gusta de usuarios que sigues</h3>
                <?php if (empty($likedContentByUser)): ?>
                    <p class="text-muted text-center py-5">No hay contenido con "Me gusta" de usuarios que sigues.</p>
                <?php else: ?>
                    <div class="album-grid">
                        <?php foreach ($likedContentByUser as $userId => $userData): ?>
                            <?php
                            // Obtener la primera imagen para mostrar como preview
                            $previewImage = null;
                            $previewId = null;
                            if (!empty($userData['albums'])) {
                                $previewImage = $userData['albums'][0]['urlPortadaAlbum'];
                                $previewPath = "$basePath/uploads/portadas/";
                                $previewId = $userData['albums'][0]['idAlbum'];
                            } elseif (!empty($userData['images'])) {
                                $previewImage = $userData['images'][0]['urlImagen'];
                                $previewPath = "$basePath/uploads/imagenes/";
                                $previewId = $userData['images'][0]['idAlbumImagen'];
                            }
                            ?>
                            
                            <!-- Card del usuario que abre el modal de detalle -->
                            <div class="album-card position-relative" 
                                data-id="<?= $previewId ?>"
                                data-user-id="<?= $userId ?>"
                                data-bs-toggle="modal" 
                                data-bs-target="#modalDetalleAlbum"
                                onclick="cargarDetalleAlbum(null, this.dataset.userId);">
                                <div class="album-img-wrapper">
                                    <img src="<?= $previewImage ? $previewPath . e($previewImage) : "$basePath/assets/images/imagen.png" ?>" 
                                        alt="Preview" class="album-img">
                                </div>
                                <div class="album-info">
                                    <div class="d-flex align-items-center gap-2">
                                        <img src="<?= $basePath ?>/uploads/avatares/<?= e($userData['user']['avatarUrl'] ?? 'default.png') ?>" 
                                            alt="Avatar" 
                                            class="rounded-circle"
                                            style="width: 30px; height: 30px; object-fit: cover;">
                                        <div>
                                            <h5 class="mb-0"><?= e($userData['user']['apodoUsuario']) ?></h5>
                                            <small class="text-muted">@<?= e(ltrim($userData['user']['arrobaUsuario'], '@')) ?></small>
                                        </div>
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
    <!-- Modal de confirmación de eliminación -->
        <div class="modal fade" id="modalConfirmarEliminar" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">Eliminar álbum</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                ¿Estás seguro de que querés eliminar este álbum? Si elimina, no puede deshacer esa acción.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" id="btnConfirmarEliminar" class="btn btn-danger">Eliminar</button>
            </div>
            </div>
        </div>
        </div>
        <!-- Modal estilo éxito al eliminar álbum -->
        <div id="modalExitoEliminar" class="modal-exito-eliminar" style="display: none;">
        <div class="modal-exito-contenido">
            <div class="icono-check">
            <i class="bi bi-check2"></i>
            </div>
            <h2>¡Álbum eliminado con éxito!</h2>
            <p>El álbum fue eliminado correctamente.</p>
        </div>
        </div>
<script>
    const basePath = '<?= $basePath ?>'; // Define basePath globalmente
</script>
<script>
function cargarDetalleAlbum(albumId, userId = null) {
    const url = userId 
        ? `${basePath}/api/detalleAlbum?id=${albumId}&userId=${userId}&showAll=true`
        : `${basePath}/api/detalleAlbum?id=${albumId}`;

    fetch(url)
        .then(response => response.json())
        .then(data => {
            const modal = document.getElementById('modalDetalleAlbum');
            const modalLabel = modal.querySelector('#modalDetalleAlbumLabel');
            const modalBodyIzq = modal.querySelector('#detalleAlbumIzquierda');
            const modalBodyDer = modal.querySelector('#detalleAlbumDerecha');
            const fotoPerfil = modal.querySelector('#modalFotoPerfil');

            // Actualizar contenido
            if (data.usuario) {
                modalLabel.textContent = data.usuario.apodoUsuario;
                if (data.usuario.avatarUrl) {
                    fotoPerfil.src = `${basePath}/uploads/avatares/${data.usuario.avatarUrl}`;
                }
            }
            modalBodyIzq.innerHTML = data.htmlIzquierda;
            modalBodyDer.innerHTML = data.htmlDerecha;

            // Inicializar carrusel
            const carrusel = document.getElementById('carouselAlbum');
            if (carrusel) {
                new bootstrap.Carousel(carrusel, { interval: false });
            }
        })
        .catch(error => console.error('Error:', error));
}
</script>
<script>
function renderAlbumImages(list) {
  const cont = document.getElementById('albumImagesList');
  if (!cont) return;

  if (!list || list.length === 0) {
    cont.innerHTML = `
      <div class="col-12">
        <p class="text-muted mb-0">Este álbum no tiene imágenes todavía.</p>
      </div>`;
    return;
  }

  // pinta cards Bootstrap con miniaturas y botón de eliminar
  cont.innerHTML = list.map(row => {
    const imgUrl = `${basePath}/uploads/imagenes/${encodeURIComponent(row.urlImagen)}`;
    const titulo = row.tituloImagen ? row.tituloImagen : '(Sin título)';
    const desc   = row.descripcionImagen ? row.descripcionImagen : '';

    return `
      <div class="col-12 col-sm-6 col-md-4" data-imagen-id="${row.idImagen}">
        <div class="card h-100 shadow-sm position-relative">
          <button type="button" class="btn btn-sm btn-danger position-absolute top-0 end-0 m-2 eliminar-imagen-btn" 
                  data-imagen-id="${row.idImagen}" 
                  style="z-index: 10; opacity: 0.9;"
                  title="Eliminar imagen">
            <i class="bi bi-trash"></i>
          </button>
          <div class="ratio ratio-1x1 editar-imagen-card" style="cursor: pointer;" 
               data-imagen-id="${row.idImagen}"
               data-titulo="${escapeHtml(titulo)}"
               data-descripcion="${escapeHtml(desc)}">
            <img src="${imgUrl}" class="card-img-top" alt="${titulo}" style="object-fit: cover;">
          </div>
          <div class="card-body p-2">
            <div class="fw-semibold text-truncate" title="${titulo}">${titulo}</div>
            <div class="text-muted small text-truncate" title="${desc}">${desc}</div>
          </div>
        </div>
      </div>`;
  }).join('');
  
  // Agregar event listeners a los botones de eliminar
  cont.querySelectorAll('.eliminar-imagen-btn').forEach(btn => {
    btn.addEventListener('click', function(e) {
      e.preventDefault();
      e.stopPropagation();
      const idImagen = this.dataset.imagenId;
      if (confirm('¿Estás seguro de que querés eliminar esta imagen?')) {
        eliminarImagen(idImagen);
      }
    });
  });
  
  // Agregar event listeners para editar imágenes (click en la imagen)
  cont.querySelectorAll('.editar-imagen-card').forEach(card => {
    card.addEventListener('click', function(e) {
      // No abrir si se hizo click en el botón de eliminar
      if (e.target.closest('.eliminar-imagen-btn')) return;
      
      const idImagen = this.dataset.imagenId;
      const titulo = this.dataset.titulo || '';
      const descripcion = this.dataset.descripcion || '';
      
      abrirModalEditarImagen(idImagen, titulo, descripcion);
    });
  });
}

// Función auxiliar para escapar HTML
function escapeHtml(text) {
  const div = document.createElement('div');
  div.textContent = text;
  return div.innerHTML;
}

// Función para abrir modal de editar imagen
function abrirModalEditarImagen(idImagen, titulo, descripcion) {
  document.getElementById('editImagenId').value = idImagen;
  document.getElementById('editImagenTitulo').value = titulo === '(Sin título)' ? '' : titulo;
  document.getElementById('editImagenDescripcion').value = descripcion;
  document.getElementById('imageEditForm').classList.remove('d-none');
  
  // Scroll al formulario
  document.getElementById('imageEditForm').scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

async function loadAlbumImagesForEdit(idAlbum) {
  const url = `${basePath}/api/imagenesDeAlbum?idAlbum=${encodeURIComponent(idAlbum)}`;
  try {
    const res = await fetch(url, { credentials: 'same-origin' });
    const data = await res.json();
    if (data.success) {
      renderAlbumImages(data.data);
    } else {
      console.error('imagenesDeAlbum:', data.message);
      renderAlbumImages([]);
    }
  } catch (e) {
    console.error(e);
    renderAlbumImages([]);
  }
}

// Función para eliminar una imagen
async function eliminarImagen(idImagen) {
  try {
    const formData = new FormData();
    formData.append('idImagen', idImagen);
    
    const res = await fetch(`${basePath}/api/eliminarImagen`, {
      method: 'POST',
      body: formData,
      credentials: 'same-origin'
    });
    
    const data = await res.json();
    
    if (data.success) {
      // Eliminar el elemento del DOM
      const elemento = document.querySelector(`[data-imagen-id="${idImagen}"]`);
      if (elemento) {
        elemento.remove();
      }
      
      // Si no quedan imágenes, mostrar mensaje
      const cont = document.getElementById('albumImagesList');
      if (cont && cont.querySelectorAll('[data-imagen-id]').length === 0) {
        cont.innerHTML = `
          <div class="col-12">
            <p class="text-muted mb-0">Este álbum no tiene imágenes todavía.</p>
          </div>`;
      }
    } else {
      alert('Error: ' + (data.message || 'No se pudo eliminar la imagen'));
    }
  } catch (e) {
    console.error(e);
    alert('Error de red al eliminar la imagen');
  }
}
</script>

    <!-- Bootstrap JS -->
    <script src="<?= $basePath ?>/assets/js/perfil.js"></script>

    <!-- ====== Pequeño script adicional ======
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
      1. ACEPTAR / RECHAZAR SOLICITUD DE SEGUIMIENTO
  ====================================================== */
    function actualizarBoton(btn, estado) {

        if (!btn) return;
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
    }

  document.querySelectorAll('.aceptar-seguimiento, .rechazar-seguimiento').forEach(btn => {
    btn.addEventListener('click', function() {
      const idSeguidor = this.dataset.id || this.dataset.idseguidor || this.dataset.idSeguidor;
      const accion = this.classList.contains('aceptar-seguimiento') ? 'aceptar' : 'rechazar';

      if (!idSeguidor) {
        alert('Error interno: faltan datos. Reintentá.');
        return;
      }

      fetch('<?= $basePath ?>/api/responderSolicitud', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `idSeguidor=${encodeURIComponent(idSeguidor)}&accion=${encodeURIComponent(accion)}`
      })
      .then(res => res.text())
      .then(data => {
        data = data.trim();
        const followBtn = document.querySelector('#follow-btn');

        if (t === 'aceptado' && followBtn) {
          actualizarBoton(followBtn, 'siguiendo');
          alert(" Has aceptado la solicitud. Ahora ambos se siguen.");
        } else if (t === 'rechazado' && followBtn) {
          actualizarBoton(followBtn, 'ninguno');
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
        alert(" Error de red. Intenta nuevamente.");
      });
    });
  });

  /* =====================================================
      2. BOTÓN SEGUIR / DEJAR DE SEGUIR
  ====================================================== */
  document.addEventListener('click', function(e) {
    const btn = e.target.closest('#follow-btn');
    if (!btn) return;

    const idSeguido = btn.dataset.idSeguido || btn.dataset.idseguido || btn.dataset.id;
    if (!idSeguido) return;

    // Si ya sigue o está pendiente → dejar de seguir
    if (btn.classList.contains('btn-success-full') || btn.classList.contains('btn-secondary')) {
      fetch('<?= $basePath ?>/api/dejarSeguir', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `idSeguido=${encodeURIComponent(idSeguido)}`
      })
      .then(res => res.text())
      .then(data => {
        if (data.trim() === 'ok') {
          actualizarBoton(btn, 'ninguno');
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
    fetch('<?= $basePath ?>/api/seguir', {
      method: 'POST',
      headers: {'Content-Type': 'application/x-www-form-urlencoded'},
      body: `idSeguido=${encodeURIComponent(idSeguido)}`
    })
    .then(res => res.text())
    .then(data => {
      const estado = data.trim();
      if (estado === 'pendiente' || estado === 'siguiendo') {
        actualizarBoton(btn, estado);
      }
    })
    .catch(err => console.error(err));
  });

  /* =====================================================
      POLLING: verificar estado del seguimiento cada 5s
  ====================================================== */
  setInterval(() => {
    const btn = document.querySelector('#follow-btn');
    if (!btn || btn.dataset.ignoreCheck) return;

    const idSeguido = btn.dataset.idSeguido || btn.dataset.idseguido || btn.dataset.id;
    if (!idSeguido) return;

    fetch('<?= $basePath ?>/api/checkFollowStatus', {
      method: 'POST',
      headers: {'Content-Type': 'application/x-www-form-urlencoded'},
      body: `idSeguido=${encodeURIComponent(idSeguido)}`
    })
    .then(res => res.text())
    .then(status => {
      const estado = status.trim();
      if (['activo','aceptado'].includes(estado)) actualizarBoton(btn, 'siguiendo');
      else if (estado === 'pendiente') actualizarBoton(btn, 'pendiente');
      else actualizarBoton(btn, 'ninguno');
    })
    .catch(err => console.error(err));
  }, 5000);

  /* =====================================================
                        MODAL CARRUSEL
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
<script>
document.addEventListener("DOMContentLoaded", () => {
  let idAlbumAEliminar = null;
  let btnEliminarReferencia = null;

  const modalEliminar = new bootstrap.Modal(document.getElementById("modalConfirmarEliminar"));
  const btnConfirmarEliminar = document.getElementById("btnConfirmarEliminar");
 
  const btnEliminarAlbum = document.querySelectorAll(".eliminar-album");

  // Delegación de eventos para los botones eliminar
    btnEliminarAlbum.forEach(btn => {
        btn.addEventListener("click", function (e) {

            console.log(btn);
            if (!btn) return;

            e.preventDefault();
            e.stopPropagation();

            idAlbumAEliminar = btn.dataset.id;
            btnEliminarReferencia = btn;

            // Mostramos el modal
            modalEliminar.show();
        });
    });

  // Confirmación dentro del modal
  btnConfirmarEliminar.addEventListener("click", function () {
    if (!idAlbumAEliminar) return;

    fetch('<?= $basePath ?>/api/eliminarAlbum', {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: "idAlbum=" + encodeURIComponent(idAlbumAEliminar),
      credentials: "same-origin",
    })
      .then((res) => res.json())
      .then((data) => {
        if (data.success) {
          const card = btnEliminarReferencia.closest(".album-card");
          if (card) card.remove();
          // Cerramos modal y mostramos notificación visual
          modalEliminar.hide();
         // Mostrar modal personalizado de éxito
        const modalExito = document.getElementById("modalExitoEliminar");
        modalExito.style.display = "flex";
        modalExito.classList.add("fade-in");

        // Mantenerlo visible 5 segundos, luego desvanecer y redirigir
        setTimeout(() => {
            modalExito.classList.remove("fade-in");
            modalExito.classList.add("fade-out");

            setTimeout(() => {
            modalExito.style.display = "none";
            // Redirigir al home
            window.location.href = "<?= $basePath ?>/perfil";
            }, 800); // 0.8s para la animación de salida
        }, 3000);
        } else {
          mostrarToast("Error: " + data.message, "danger");
        }
      })
      .catch((err) => {
        console.error(err);
        mostrarToast("Error inesperado.", "danger");
      });
  });

  // Función para mostrar toasts bonitos 
  function mostrarToast(mensaje, tipo = "info") {
    const toast = document.createElement("div");
    toast.className = `toast align-items-center text-bg-${tipo} border-0 position-fixed bottom-0 end-0 m-3`;
    toast.setAttribute("role", "alert");
    toast.innerHTML = `
      <div class="d-flex">
        <div class="toast-body">${mensaje}</div>
        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
      </div>
    `;
    document.body.appendChild(toast);
    const bsToast = new bootstrap.Toast(toast, { delay: 3000 });
    bsToast.show();
    toast.addEventListener("hidden.bs.toast", () => toast.remove());
  }
});
document.addEventListener('DOMContentLoaded', function() {
    // Preview cover image when selected
    document.querySelector('input[name="portada"]').addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            const preview = document.getElementById('editAlbumPortadaPreview');
            
            reader.onload = function(e) {
                preview.src = e.target.result;
                const container = document.getElementById('portadaPreviewContainer');
                if (container) {
                    container.style.display = 'block';
                } else {
                    preview.style.display = 'block';
                }
            };
            
            reader.readAsDataURL(file);
        }
    });
    
    // Botón para eliminar portada
    const btnEliminarPortada = document.querySelector('.eliminar-portada-btn');
    if (btnEliminarPortada) {
        btnEliminarPortada.addEventListener('click', async function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const idAlbum = document.getElementById('editAlbumId').value;
            if (!idAlbum) {
                alert('Error: No se encontró el ID del álbum');
                return;
            }
            
            if (!confirm('¿Estás seguro de que querés eliminar la portada?')) {
                return;
            }
            
            try {
                const formData = new FormData();
                formData.append('idAlbum', idAlbum);
                
                const res = await fetch(`${basePath}/api/eliminarPortadaAlbum`, {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin'
                });
                
                const data = await res.json();
                
                if (data.success) {
                    // Actualizar preview con imagen por defecto
                    const container = document.getElementById('portadaPreviewContainer');
                    const preview = document.getElementById('editAlbumPortadaPreview');
                    if (preview) {
                        // Cambiar a imagen por defecto
                        preview.src = `${basePath}/assets/images/imagen.png`;
                        if (container) {
                            container.style.display = 'block';
                        } else {
                            preview.style.display = 'block';
                        }
                    }
                    // Limpiar input
                    document.querySelector('input[name="portada"]').value = '';
                    alert('Portada eliminada correctamente');
                } else {
                    alert('Error: ' + (data.message || 'No se pudo eliminar la portada'));
                }
            } catch (e) {
                console.error(e);
                alert('Error de red al eliminar la portada');
            }
        });
    }

    // Show current cover when opening modal
    document.querySelectorAll('.editar-album').forEach(btn => {
        btn.addEventListener('click', async function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const albumId = this.dataset.id;
            const card = this.closest('.album-card');
            const titulo = card.querySelector('h5').textContent;
            const portadaActual = card.querySelector('img').src;
            
            // Obtener datos completos del álbum
            try {
                const res = await fetch(`${basePath}/api/obtenerDatosAlbum?idAlbum=${encodeURIComponent(albumId)}`, {
                    credentials: 'same-origin'
                });
                const data = await res.json();
                
                if (data.success) {
                    // Set values in modal
                    document.getElementById('editAlbumId').value = data.data.idAlbum;
                    document.getElementById('editAlbumTitulo').value = data.data.titulo;
                    
                    // Set privacidad
                    const selectPrivacidad = document.querySelector('select[name="esPublico"]');
                    if (selectPrivacidad) {
                        selectPrivacidad.value = data.data.esPublico;
                    }
                } else {
                    // Fallback a valores del card
                    document.getElementById('editAlbumId').value = albumId;
                    document.getElementById('editAlbumTitulo').value = titulo;
                }
            } catch (e) {
                console.error('Error al obtener datos del álbum:', e);
                // Fallback a valores del card
                document.getElementById('editAlbumId').value = albumId;
                document.getElementById('editAlbumTitulo').value = titulo;
            }
            
            // Show current cover
            const preview = document.getElementById('editAlbumPortadaPreview');
            const container = document.getElementById('portadaPreviewContainer');
            if (preview) {
                preview.src = portadaActual;
                if (container) {
                    container.style.display = 'block';
                } else {
                    preview.style.display = 'block';
                }
            }
            // Ocultar formulario de edición de imagen al abrir modal
            document.getElementById('imageEditForm').classList.add('d-none');
            loadAlbumImagesForEdit(albumId);
            new bootstrap.Modal(document.getElementById('modalEditarAlbum')).show();
        });
    });

    // Botón para agregar nuevas imágenes
    const btnAgregarImagenes = document.getElementById('btnAgregarImagenes');
    const inputNuevasImagenes = document.getElementById('inputNuevasImagenes');
    
    if (btnAgregarImagenes && inputNuevasImagenes) {
        btnAgregarImagenes.addEventListener('click', function() {
            inputNuevasImagenes.click();
        });
        
        inputNuevasImagenes.addEventListener('change', async function() {
            const files = Array.from(this.files);
            if (files.length === 0) return;
            
            const idAlbum = document.getElementById('editAlbumId').value;
            if (!idAlbum) {
                alert('Error: No se encontró el ID del álbum');
                return;
            }
            
            // Crear FormData con las nuevas imágenes
            const formData = new FormData();
            formData.append('idAlbum', idAlbum);
            formData.append('cantidadImagenes', files.length);
            
            files.forEach((file, index) => {
                formData.append(`imagen${index}`, file);
                formData.append(`tituloImagen${index}`, '');
                formData.append(`descripcionImagen${index}`, '');
                formData.append(`etiquetaImagen${index}`, '');
            });
            
            try {
                const res = await fetch(`${basePath}/api/agregarImagenesAlbum`, {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin'
                });
                
                const data = await res.json();
                
                if (data.success) {
                    // Recargar las imágenes del álbum
                    await loadAlbumImagesForEdit(idAlbum);
                    // Ocultar formulario de edición si estaba abierto
                    document.getElementById('imageEditForm').classList.add('d-none');
                    
                    // Si solo se agregó una imagen, abrir el formulario de edición automáticamente
                    if (files.length === 1) {
                        // Esperar a que se recarguen las imágenes y obtener la primera (más reciente)
                        setTimeout(async () => {
                            try {
                                const url = `${basePath}/api/imagenesDeAlbum?idAlbum=${encodeURIComponent(idAlbum)}`;
                                const res = await fetch(url, { credentials: 'same-origin' });
                                const data = await res.json();
                                if (data.success && data.data && data.data.length > 0) {
                                    // La primera imagen es la más reciente (ordenadas DESC)
                                    const nuevaImagen = data.data[0];
                                    abrirModalEditarImagen(nuevaImagen.idImagen, nuevaImagen.tituloImagen || '', nuevaImagen.descripcionImagen || '');
                                }
                            } catch (e) {
                                console.error('Error al obtener imagen nueva:', e);
                            }
                        }, 500);
                    }
                    
                    alert(data.message || 'Imágenes agregadas correctamente');
                    // Limpiar el input
                    this.value = '';
                } else {
                    alert('Error: ' + (data.message || 'No se pudieron agregar las imágenes'));
                }
            } catch (e) {
                console.error(e);
                alert('Error de red al agregar las imágenes');
            }
        });
    }

    // Handle form submission
    document.getElementById('btnGuardarEdicion').addEventListener('click', async function () {
    const form = document.getElementById('formEditarAlbum');
    const formData = new FormData(form);

    try {
        const res = await fetch(`${basePath}/api/editarAlbum`, {
        method: 'POST',
        body: formData,
        credentials: 'same-origin', // importante para la cookie de sesión
        });

        const raw = await res.text();
        let data;
        try { data = JSON.parse(raw); } catch { data = { success: false, message: raw }; }

        if (!res.ok || !data.success) {
        console.error('HTTP', res.status, data);
        alert((data && data.message) ? data.message : `Error ${res.status}`);
        return;
        }

        location.reload();
    } catch (e) {
        console.error(e);
        alert('Error de red o CORS');
    }
    });
    
    // Botón cancelar edición de imagen
    const btnCancelarEditarImagen = document.getElementById('btnCancelarEditarImagen');
    if (btnCancelarEditarImagen) {
        btnCancelarEditarImagen.addEventListener('click', function() {
            document.getElementById('imageEditForm').classList.add('d-none');
            // Limpiar formulario
            document.getElementById('editImagenId').value = '';
            document.getElementById('editImagenTitulo').value = '';
            document.getElementById('editImagenDescripcion').value = '';
        });
    }
    
    // Botón guardar edición de imagen
    const btnGuardarImagen = document.getElementById('btnGuardarImagen');
    if (btnGuardarImagen) {
        btnGuardarImagen.addEventListener('click', async function() {
            const idImagen = document.getElementById('editImagenId').value;
            const titulo = document.getElementById('editImagenTitulo').value.trim();
            const descripcion = document.getElementById('editImagenDescripcion').value.trim();
            
            if (!idImagen) {
                alert('Error: No se encontró el ID de la imagen');
                return;
            }
            
            try {
                const formData = new FormData();
                formData.append('idImagen', idImagen);
                formData.append('titulo', titulo);
                formData.append('descripcion', descripcion);
                
                const res = await fetch(`${basePath}/api/actualizarImagen`, {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin'
                });
                
                const data = await res.json();
                
                if (data.success) {
                    // Ocultar formulario
                    document.getElementById('imageEditForm').classList.add('d-none');
                    
                    // Recargar imágenes para actualizar la vista
                    const idAlbum = document.getElementById('editAlbumId').value;
                    if (idAlbum) {
                        loadAlbumImagesForEdit(idAlbum);
                    }
                    
                    alert('Imagen actualizada correctamente');
                } else {
                    alert('Error: ' + (data.message || 'No se pudo actualizar la imagen'));
                }
            } catch (e) {
                console.error(e);
                alert('Error de red al actualizar la imagen');
            }
        });
    }


});
</script>

<?php include VIEW_PATH . '/footer.php'; ?>