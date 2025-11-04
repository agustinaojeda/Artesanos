<?php
if (session_status() === PHP_SESSION_NONE) session_start();

require_once '../../config/conexion.php';
require_once '../../config/cerrarConexion.php';
$conexion = abrirConexion();


$idUsuario = $_SESSION['usuario']['id'] ?? 0;

// contar notificaciones no leídas
$count = 0;
if ($idUsuario > 0) {
    $sqlNotif = "SELECT COUNT(*) AS noLeidas FROM notificaciones WHERE idUsuarioDestino = ? AND leida = 0";
    $stmt = $conexion->prepare($sqlNotif);
    $stmt->bind_param("i", $idUsuario);
    $stmt->execute();
    $result = $stmt->get_result();
    $count = $result->fetch_assoc()['noLeidas'] ?? 0;
}    
?>

<nav class="navbar navbar-expand-lg sticky-top shadow-sm" style="background-color: #FFFFF0;">
  <div class="container-fluid">
    <a class="navbar-brand" href="home.php">
      <img src="../../public/assets/images/logoConLetras.png" alt="Logo" style="height:40px;">
    </a>

    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#menuNav">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="menuNav">
      <div class="d-flex flex-column flex-lg-row align-items-center justify-content-between w-100 gap-3 py-2">

      <!-- 🔍 Barra de búsqueda -->
    <div class="busqueda-wrapper position-relative mx-auto mt-1" style="max-width:600px;">
      <form method="GET" action="busqueda.php" class="mx-auto position-relative" id="formBusqueda" style="max-width:100%;">
    
      <!-- Campo de texto -->
      <input type="text" class="form-control buscador" name="query" placeholder="Buscar...">

      <!-- Radios -->
      <div class="btn-group position-absolute top-0 end-0 me-2 mt-1 z-1">
        <input type="radio" class="btn-check" name="tipo" id="btnArtesanos" value="artesanos" autocomplete="off">
        <label class="btn btn-blanco-negro btn-sm" for="btnArtesanos">Artesanos</label>

        <input type="radio" class="btn-check" name="tipo" id="btnAlbumes" value="albumes" autocomplete="off">
        <label class="btn btn-blanco-negro btn-sm" for="btnAlbumes">Álbumes</label>
      </div>
      <!-- Botón de envío oculto -->
      <button type="submit" style="display:none"></button>
     </form>
    </div>
        <!-- 👤 Parte derecha (sesión / perfil) -->
        <div class="d-flex justify-content-center justify-content-lg-end align-items-center gap-3 ms-lg-3">
          <?php if (!isset($_SESSION['usuario'])): ?>
            <!-- No logueado -->
            <a href="login.php" class="btn follow-btn text-white px-4 rounded-5" role="button">
              <i class="bi bi-box-arrow-in-right me-1"></i> Iniciar Sesión
            </a>
          <?php else: ?>

            <div class="position-relative" id="notificacionesWrapper">
              <button type="button" class="btn position-relative" id="btnNotificaciones">
                <i class="bi bi-bell fs-5"></i>
                <?php if ($count > 0): ?>
                  <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                    <?= $count ?>
                  </span>
                <?php endif; ?>
              </button>

              <!-- 🔔 Dropdown de notificaciones -->
              <div id="dropdownNotificaciones" 
                  class="card shadow border-0 position-absolute end-0 mt-2" 
                  style="width: 350px; display: none; z-index: 1050;">
                <div class="card-header bg-light fw-bold">Mis notificaciones</div>
                <div class="card-body p-0" 
                    id="contenedorNotificaciones"
                    style="max-height: 400px; overflow-y: auto; scrollbar-width: thin; scrollbar-color: #ff9800 #f9f9f9;">
                  <div class="text-center text-muted py-3">Cargando notificaciones...</div>
                </div>

              </div>
            </div>



            <?php
              // 📦 Incluir helper y obtener avatar
              require_once dirname(__DIR__) . '/models/usuarioHelper.php';
              $avatarSrc = obtenerAvatar((int)$_SESSION['usuario']['id']);
            ?>

            <a href="perfil.php?id=<?= (int)$_SESSION['usuario']['id'] ?>" class="d-inline-block">
              <img src="<?php echo $avatarSrc; ?>" 
                   alt="Perfil" 
                   class="rounded-circle" 
                   style="height:40px;width:40px;object-fit:cover;">
            </a>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
      <style>
    #contenedorNotificaciones::-webkit-scrollbar {
      width: 8px;
    }

    #contenedorNotificaciones::-webkit-scrollbar-track {
      background: #f9f9f9;
      border-radius: 10px;
    }

    #contenedorNotificaciones::-webkit-scrollbar-thumb {
      background-color: #ff9800; /* naranja */
      border-radius: 10px;
    }

    #contenedorNotificaciones::-webkit-scrollbar-thumb:hover {
      background-color: #e68900; /* naranja más oscuro al pasar */
    }
    </style>

  </nav>
  
<script>
//  Enviar formulario de búsqueda automáticamente
document.addEventListener("DOMContentLoaded", () => {
  const form = document.getElementById("formBusqueda");
  const radios = form.querySelectorAll('input[name="tipo"]');
  const input = form.querySelector('input[name="query"]');

  radios.forEach(radio => {
    radio.addEventListener("change", () => {
      if (input.value.trim() === "") input.removeAttribute("required");
      form.submit();
    });
  });
});


//  Actualizar numerito de notificaciones
function actualizarNotificaciones() {
  fetch('../controllers/notificacionesControl.php')
    .then(res => res.json())
    .then(data => {
      const badge = document.querySelector('.badge.bg-danger');
      const bell = document.querySelector('.bi-bell').parentElement;

      if (data.noLeidas > 0) {
        if (badge) {
          badge.textContent = data.noLeidas;
        } else {
          const span = document.createElement('span');
          span.className = 'position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger';
          span.textContent = data.noLeidas;
          bell.appendChild(span);
        }
      } else if (badge) {
        badge.remove();
      }
    })
    .catch(err => console.error('Error al actualizar notificaciones:', err));
}

//  Actualiza el numerito cada 10 segundos
setInterval(actualizarNotificaciones, 10000);


//  Cargar notificaciones al abrir el modal
document.addEventListener("DOMContentLoaded", () => {
  const modal = document.getElementById('modalNotificaciones');
  if (!modal) return;
  modal.addEventListener('show.bs.modal', () => {
    fetch('../controllers/listarNotificaciones.php')
      .then(res => res.text())
      .then(html => {
        document.getElementById('contenedorNotificaciones').innerHTML = html;
        // Después de abrir el modal, actualizar el numerito (porque se marcan como leídas)
        setTimeout(actualizarNotificaciones, 1000);
      })
      .catch(err => {
        document.getElementById('contenedorNotificaciones').innerHTML =
          '<div class="alert alert-danger">Error al cargar notificaciones.</div>';
        console.error(err);
      });
  });
});
</script>

<script>
//  Mostrar / ocultar el dropdown al hacer clic en la campanita
document.addEventListener("DOMContentLoaded", () => {
  const btn = document.getElementById("btnNotificaciones");
  const dropdown = document.getElementById("dropdownNotificaciones");

  btn.addEventListener("click", (e) => {
    e.stopPropagation();
    const visible = dropdown.style.display === "block";
    dropdown.style.display = visible ? "none" : "block";

    // Si se abre, cargamos las notificaciones
    if (!visible) {
      fetch('../controllers/listarNotificaciones.php')
        .then(res => res.text())
        .then(html => dropdown.querySelector('#contenedorNotificaciones').innerHTML = html)
        .catch(() => dropdown.querySelector('#contenedorNotificaciones').innerHTML = 
          '<div class="alert alert-danger m-2">Error al cargar notificaciones.</div>');
    }
  });

  // Cerrar al hacer clic fuera
  document.addEventListener("click", () => dropdown.style.display = "none");
});
</script>
<script>
// Manejo de botones Aceptar / Rechazar
document.addEventListener("DOMContentLoaded", () => {
  const contenedor = document.getElementById('contenedorNotificaciones');
  if (!contenedor) return;

  contenedor.addEventListener('click', (e) => {
    const btn = e.target.closest('.aceptar-seguimiento, .rechazar-seguimiento');
    if (!btn) return;

    const idSeguidor = btn.getAttribute('data-idSeguidor');
    const accion = btn.classList.contains('aceptar-seguimiento') ? 'aceptar' : 'rechazar';

    fetch('responderSolicitud.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/x-www-form-urlencoded'},
      body: `idSeguidor=${idSeguidor}&accion=${accion}`
    })
    .then(res => res.text())
    .then(data => {
      if (data.trim() === 'activo' || data.trim() === 'rechazado') {
        // eliminar la notificación del DOM
        btn.closest('.list-group-item').remove();
        // actualizar numerito después de aceptar/rechazar
        setTimeout(actualizarNotificaciones, 500);
      } else {
        console.error('Error al responder solicitud:', data);
      }
    })
    .catch(err => console.error(err));
  });
});
</script>




  
