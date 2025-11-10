<?php
require_once CONFIG_PATH . '/conexion.php';
require_once CONFIG_PATH . '/cerrarConexion.php';
?>
<style>
  /* Botón de 3 puntitos naranja en modal */
  #dropdownDenunciarAlbum .opciones-btn {
    background: #f7931e;
    color: white;
    border: none;
    border-radius: 50%;
    padding: 4px 6px;
  }

  #dropdownDenunciarAlbum .opciones-btn:hover {
    background: #e6821a;
    color: white;
  }

  /* Eliminar fondo azul al hacer clic en las opciones del menú */
  #dropdownDenunciarAlbum .dropdown-item:active,
  #dropdownDenunciarAlbum .dropdown-item:focus,
  #dropdownDenunciarAlbum .dropdown-item:hover {
    background-color: transparent !important;
    color: #dc3545 !important;
  }
</style>
<?php
$conexion = abrirConexion();

$busqueda = trim($_GET['query'] ?? '');

if (isset($_GET['tipo']) && in_array($_GET['tipo'], ['artesanos', 'albumes'])) {
    $tipo = $_GET['tipo'];
} else {
    if ($busqueda === '') {
        $tipo = 'artesanos'; // sin texto mostrar usuarios
    } elseif (strpos($busqueda, '@') === 0) {
        $tipo = 'artesanos'; // si empieza con @ buscar usuarios
        $busqueda = substr($busqueda, 1); // quita la arroba para buscar
    } else {
        $tipo = 'albumes'; // por defecto buscar álbumes
    }
}


// Mostrar todos los artesanos
if ($tipo === 'artesanos' && $busqueda === '') {
    $sql = "
        SELECT u.*, 
            f.imagenPerfil AS fotoPerfil,
            (SELECT COUNT(*) FROM seguimiento s WHERE s.idSeguido = u.idUsuario) AS totalSeg,
            (SELECT COUNT(*) FROM album a WHERE a.idUsuarioAlbum = u.idUsuario) AS totalAlb
        FROM usuario u
        LEFT JOIN fotosdeperfil f ON f.idFotoPerfil = u.idFotoPerfilUsuario
    ";
    $resultado = $conexion->query($sql);
}

// Mostrar todos los álbumes
elseif ($tipo === 'albumes' && $busqueda === '') {
    $sql = "
        SELECT a.*, u.apodoUsuario, u.arrobaUsuario, f.imagenPerfil AS fotoPerfil
        FROM album a
        INNER JOIN usuario u ON u.idUsuario = a.idUsuarioAlbum
        LEFT JOIN fotosdeperfil f ON f.idFotoPerfil = u.idFotoPerfilUsuario
        ORDER BY a.idAlbum DESC
    ";
    $resultado = $conexion->query($sql);
}


elseif ($busqueda !== '') {
    
    if (strpos($busqueda, '#') === 0) {
        $busqueda = substr($busqueda, 1);
        $tipo = 'albumes'; 
    }

    if ($tipo === 'artesanos') {
        // Buscar artesanos
        $sql = "
            SELECT u.*, 
                f.imagenPerfil AS fotoPerfil,
                (SELECT COUNT(*) FROM seguimiento s WHERE s.idSeguido = u.idUsuario) AS totalSeg,
                (SELECT COUNT(*) FROM album a WHERE a.idUsuarioAlbum = u.idUsuario) AS totalAlb
            FROM usuario u
            LEFT JOIN fotosdeperfil f ON f.idFotoPerfil = u.idFotoPerfilUsuario
            WHERE (u.nombreUsuario LIKE CONCAT('%', ?, '%')
                OR u.arrobaUsuario LIKE CONCAT('%', ?, '%')
                OR u.apodoUsuario LIKE CONCAT('%', ?, '%'))
        ";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param('sss', $busqueda, $busqueda, $busqueda);
    }

    elseif ($tipo === 'albumes') {
        // Buscar álbumes por título, usuario o etiquetas de imágenes
        $sql = "
            SELECT DISTINCT a.*, u.apodoUsuario, u.arrobaUsuario, f.imagenPerfil AS fotoPerfil
            FROM album a
            INNER JOIN usuario u ON u.idUsuario = a.idUsuarioAlbum
            LEFT JOIN fotosdeperfil f ON f.idFotoPerfil = u.idFotoPerfilUsuario
            LEFT JOIN imagen i ON i.idAlbumImagen = a.idAlbum
            WHERE a.tituloAlbum LIKE CONCAT('%', ?, '%')
               OR u.apodoUsuario LIKE CONCAT('%', ?, '%')
               OR u.arrobaUsuario LIKE CONCAT('%', ?, '%')
               OR i.etiquetaImagen LIKE CONCAT('%', ?, '%')
            ORDER BY a.idAlbum DESC
        ";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param('ssss', $busqueda, $busqueda, $busqueda, $busqueda);
    }

    $stmt->execute();
    $resultado = $stmt->get_result();
}

// Sin resultado o error
else {
    $resultado = false;
}

$pageTitle = 'Artesanos - Resultados de búsqueda';
include VIEW_PATH . '/header.php'; 
include VIEW_PATH . '/nav.php'; 
?>

<link rel="stylesheet" href="<?= $basePath ?>/assets/css/buscar.css">
<main class="contenedor">
<?php if ($resultado && $resultado->num_rows > 0): ?>
    <div class="grid">
        <?php if ($tipo === 'artesanos'): ?>
            <!--  Mostrar artesanos -->
            <?php while ($row = $resultado->fetch_assoc()): ?>
                <?php
                $apodo = htmlspecialchars($row['apodoUsuario']);
                $arroba = htmlspecialchars($row['arrobaUsuario']);
                $totalSeg = (int)$row['totalSeg'];
                $totalAlb = (int)$row['totalAlb'];
                $foto = !empty($row['fotoPerfil'])
                    ? "$basePath/uploads/avatars/" . htmlspecialchars($row['fotoPerfil'])
                    : "$basePath/assets/images/logo.png";
                
                $colores = ['#ffeedb', '#ffe0cc', '#ffd1a3', '#ffd6cc', '#e0ffe0', '#d9e8ff', '#f0d9ff', '#fff6cc'];
                $colorRandom = $colores[array_rand($colores)];
                ?>

                <div class="tarjeta">
                    <div class="banner" style="background-color: <?=$colorRandom?>;"></div>
                    <img class="avatar" src="<?= $foto ?>" alt="Avatar">
                    <h3><?= $apodo ?></h3>
                    <p>@<?= $arroba ?></p>
                    <div class="stats">
                        <span><?= $totalSeg ?> Seguidores</span> | 
                        <span><?= $totalAlb ?> Álbumes</span>
                    </div>
                    <a class="verPerfil" href="perfil?id=<?= urlencode($row['idUsuario']) ?>">Ver perfil</a>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <!--  Mostrar álbumes -->
            <?php while ($row = $resultado->fetch_assoc()): ?>
                <?php
                $titulo = htmlspecialchars($row['tituloAlbum']);
                $apodo = htmlspecialchars($row['apodoUsuario']);
                $arroba = htmlspecialchars($row['arrobaUsuario']);
                $foto = !empty($row['fotoPerfil'])
                    ? $basePath . '/uploads/avatars/' . $row['fotoPerfil']
                    : $basePath . '/assets/images/logo.png';
                $portada = !empty($row['urlPortadaAlbum'])
                    ? $basePath . '/uploads/portadas/' . $row['urlPortadaAlbum']
                    : 'https://placehold.co/300x100?text=Sin+Portada';
                
                ?>
                <div class="tarjeta">
                    <img class="portadas" style="border-radius: 10px; width: 100%; height: 200px; object-fit: cover; object-position: center;" 
                        src="<?= htmlspecialchars($portada, ENT_QUOTES) ?>"
                        alt="Portada del álbum">
                    <img class="avatar" 
                        src="<?= htmlspecialchars($foto, ENT_QUOTES) ?>"
                        alt="Avatar usuario">
                    <h3><?= $titulo ?></h3>
                    <p>de <?= $apodo ?> (@<?= $arroba ?>)</p>
                    <a href="#" 
                       class="abrir-modal-album verPerfil" 
                       data-id="<?= htmlspecialchars($row['idAlbum']) ?>" 
                       data-bs-toggle="modal" 
                       data-bs-target="#modalDetalleAlbum">
                       Ver álbum
                    </a>
                </div>
            <?php endwhile; ?>
        <?php endif; ?>
    </div>
<?php else: ?>
    <p class="sin-resultados">
        No se encontraron resultados para <b><?= htmlspecialchars($busqueda) ?></b>.
    </p>
<?php endif; ?>

<?php cerrarConexion($conexion); ?>
</main>
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
          <!-- Menú de 3 puntitos para denunciar (solo para álbumes ajenos) -->
          <div class="dropdown" id="dropdownDenunciarAlbum" style="display: none;">
            <button class="btn btn-light btn-sm opciones-btn" data-bs-toggle="dropdown" aria-expanded="false" type="button">
              <i class="bi bi-three-dots-vertical"></i>
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
              <li>
                <a class="dropdown-item text-danger" href="#" id="btnDenunciarAlbum">
                  <i class="bi bi-flag me-2"></i>Denunciar álbum
                </a>
              </li>
              <li>
                <a class="dropdown-item text-danger" href="#" id="btnDenunciarImagen">
                  <i class="bi bi-flag me-2"></i>Denunciar imagen actual
                </a>
              </li>
            </ul>
          </div>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>
      </div>

      <div class="modal-body">
        <div class="row">
          <div class="col-lg-9" id="detalleAlbumIzquierda">
            en construcción...
          </div>
          <div class="col-lg-3" id="detalleAlbumDerecha">
          </div>
        </div>
      </div>
    </div>
  </div>
</div>


<script src="<?= $basePath ?>/assets/js/home.js"></script>
<?php include VIEW_PATH . '/footer.php'; ?>