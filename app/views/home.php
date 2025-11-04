<?php
include '../controllers/albumControlador.php';
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

// home.php (Línea 7 - CORREGIDO)
$idUsuario = isset($_SESSION['usuario']['id']) ? (int)$_SESSION['usuario']['id'] : 0;

$albumes = new AlbumCont();
$albumes = $albumes->mostrarAlbumes($idUsuario); //recuperar los albumes de la bd
?>
<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Artesanos</title>
  <link rel="icon" href="../../public/assets/images/logo.png" type="image/x-icon">

  <!--bootstrap -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">

  <link rel="stylesheet" href="../../public/assets/css/nav.css">
  <link rel="stylesheet" href="../../public/assets/css/re.css">
  <link rel="stylesheet" href="../../public/assets/css/footer.css">
  <link rel="stylesheet" href="../../public/assets/css/home.css">

</head>

<body>
  <?php include 'nav.php'; ?>
  <div class="container mt-4">
    <div class="row row-cols-2 row-cols-md-4 g-5">

<?php
  if (!empty($albumes) && count($albumes) > 0) {
    foreach ($albumes as $a) {
      // Si necesitas portada de imagen, la sig línea la puedes seguir usando para mostrar la imagen,
      // pero el like del Home será por álbum, no por imagen.
      $idImagenPortada = isset($a->idImagenPortada) ? (int)$a->idImagenPortada : (int)$a->idAlbum;
      $urlPortada = htmlspecialchars($a->urlPortada);
      $tituloAlbum = htmlspecialchars($a->tituloAlbum);
      $apodoUsuario = htmlspecialchars($a->apodoUsuario);
      $arrobaUsuario = htmlspecialchars(ltrim($a->arrobaUsuario, '@'));

      echo '<div class="col album-item">
              <a href="#" class="abrir-modal-album" data-id="' . (int)$a->idAlbum . '" data-bs-toggle="modal" data-bs-target="#modalDetalleAlbum" style="text-decoration: none; color: inherit;">
                <div class="card-body">
                  <img class="card-img-top" style="border-radius: 10px; height: 200px; object-fit: cover;" src="../../public/uploads/portadas/' . $urlPortada . '"/>
                  <div class="d-flex justify-content-between align-items-center mt-2">
                    <h5 class="card-title mb-0">' . $tituloAlbum . '</h5>
                  </div>
                  <p class="card-text mb-0">' . $apodoUsuario . ' - @' . $arrobaUsuario . '</p>
                </div>
              </a>
              <div class="d-flex gap-1 align-items-center mt-1">
                <img src="../../public/assets/images/like.png"
                     alt="Me gusta"
                     class="img-fluid btn-like-galeria"
                     data-idalbum="' . (int)$a->idAlbum . '"
                     style="max-height: 25px; cursor: pointer;">
                <span id="likes-count-album-' . (int)$a->idAlbum . '" class="text-muted small align-self-center">0</span>

                <img src="../../public/assets/images/comentario.png"
                     alt="Comentario"
                     class="img-fluid"
                     style="max-height: 23px; cursor: pointer;">
              </div>
            </div>';
    }
  }
        if ($idUsuario != 0) { //si el usuario esta logueado muestra el boton de cargar mas
          echo '  <div class="text-center mt-4">
          <button id="loadMore" class="btn btn-primary">Mostrar más</button>
          </div>';
        
      } else {
        echo '<p class="text-center">Aún no hay álbumes disponibles.<br>¡Sé el primero en publicar!';
      }
      ?>
    </div>
  </div>
  <!-- detalle de album-->
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
            <!-- acá va el carrusel de las imágenes -->
          </div>

          <div class="col-lg-3" id="detalleAlbumDerecha">
            <!-- acá va el perfil -->
          </div>
        </div>
      </div>
    </div>
  </div>
</div>


  <?php if ($idUsuario == 0): include 'registro.php';
  else: ?>
    <!-- boton para crear album -->
    <div id="mostrarFormulario" data-bs-toggle="modal" data-bs-target="#modalCrearAlbum"><span class="fs-5 fw-bold" style="transform: translateY(-2px); display: inline-block;">+</span></div>

    <!--formulario crear album -->
    <div id="modalCrearAlbum" class="modal fade" tabindex="-1" aria-labelledby="modalCrearAlbumLabel" aria-hidden="true">

      <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content p-4">

          <div class="modal-header">
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
          </div>

          <div class="modal-body">
            <form id="formCrearAlbum" class="needs-validation" enctype="multipart/form-data">

              <div id="formParteUno">
                <div class="row align-items-center">
                  <h4 mb-3>Sube la portada</h4>
                  <div class="col-lg-6 col-12 mb-3">

                    <label for="inputPortada" class="imagenParaSubir" id="portada" style="display: block;"><img src="../../public/assets/images/agregarImagen.png" alt="Subir portada"></label>
                    <input type="file" name="subirPortada" id="inputPortada" accept="image/*" required>
                    <div class="invalid-feedback">Sube una imagen de portada.</div>


                    <img id="previoPortada" class="imagenCuadrada">

                  </div>
                  <div class="col-lg-6 col-12 ">
                    <input name="tituloAlb" id="tituloAlb" type="text" class="form-control" placeholder="Título del álbum" required>
                    <div class="invalid-feedback">Completa el título del álbum. Debe tener al menos 3 letras.</div>

                    <input name="etiquetaAlb" id="etiquetaAlb" type="text" class="form-control mt-3" placeholder="#etiqueta">
                    <div class="invalid-feedback">Si agregas alguna etiqueta, debe tener al menos 3 letras.</div>

                    <select name="privacidad" name="privacidad" class="form-select mt-3">
                      <option value="1" selected>Para todo el mundo</option>
                      <option value="0">Para mis seguidores</option>
                    </select>

                    <div class="text-end">

                      <button id="btnSiguiente" type="button" onclick="mostrarSigForm()" class="mt-5">Siguiente</button>

                    </div>
                  </div>
                </div>
              </div>
          </div>
          <!-- segunda parte del formulario: subida de imagenes -->
          <div id="formParteDos" class="d-none">
            <h4>Sube las imágenes</h4>
            <p class="mb-4">¡Recuerda que puedes subir hasta 20 imágenes!</p>
            <div class="row">
              <div class="col-lg-3"></div>
              <div class="col-lg-4 col-12">
                <label for="inputImagenes" class="imagenParaSubir"><img src="../../public/assets/images/agregarImagen.png" alt="Subir imagen"></label>
                <input type="file" name="inputImagenes" id="inputImagenes" accept="image/*">

                <div class="invalid-feedback">Sube una imagen. El máximo son 20.</div>
              </div>

            </div>
          </div>
          <!-- tercera parte del formulario: datos de las imagenes -->
          <div id="formParteTres" class="d-none">
            <h4>¿Le agregas detalles?</h4>

            <!--se carga las imagenes q subio el usuario dinamicamente -->
            <div id="bloqueImagenActual"></div>

            <div class="text-end">
              <button id="btnAnteriorImagen" type="button" class="btn btn-outline-secondary me-2">Anterior</button>

              <button id="btnSiguienteImagen" type="button" class="btn btn-secondary">Siguiente</button>

              <button id="btnAgregarOtra" type="button" class="btn btn-outline-primary ms-2">Agregar otra imagen</button>

              <button id="btnCrear" class="mt-5">Crear álbum</button>

            </div>
          </div>
        </div>
      </div>
    </div>
    </div>
    </form>
    </div>
    </div>
    </div>
  <?php endif; ?>

  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
  <script src="../../public/assets/js/home.js"></script>
</body>

</html>