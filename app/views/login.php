<?php

require_once CONFIG_PATH . '/conexion.php';
require_once CONFIG_PATH . '/cerrarConexion.php';

$serverMessage = '';
$serverMessageType = '';
$redirScript = '';

$pageTitle = 'Artesanos - Login';
include VIEW_PATH . '/header.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $usuario = trim($_POST['usuario'] ?? '');
  $password = $_POST['password'] ?? '';

  if (strlen($usuario) < 3) {
    $serverMessage = 'El nombre de usuario debe tener al menos 3 caracteres.';
    $serverMessageType = 'warning';
  } elseif (strlen($password) < 6) {
    $serverMessage = 'La contraseña debe tener al menos 6 caracteres.';
    $serverMessageType = 'warning';
  } else {
    $conexion = abrirConexion();

    // Traemos también la imagen de perfil del usuario
    $stmt = $conexion->prepare("
      SELECT 
        u.idUsuario,
        u.nombreUsuario,
        u.apellidoUsuario,
        u.apodoUsuario,
        u.arrobaUsuario,
        u.correoUsuario,
        u.contrasenaUsuario,
        f.imagenPerfil AS avatar
      FROM usuario u
      LEFT JOIN fotosdeperfil f ON u.idFotoPerfilUsuario = f.idFotoPerfil
      WHERE u.arrobaUsuario = ?
    ");
    $stmt->bind_param("s", $usuario);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows === 1) {
      $user = $resultado->fetch_assoc();

      if (password_verify($password, $user['contrasenaUsuario'])) {
        session_regenerate_id(true);

        $_SESSION['usuario'] = [
          'id'       => $user['idUsuario'],
          'nombre'   => $user['nombreUsuario'],
          'apellido' => $user['apellidoUsuario'],
          'apodo'    => $user['apodoUsuario'],
          'arroba'   => $user['arrobaUsuario'],
          'correo'   => $user['correoUsuario'],
          'avatar'   => $user['avatar'] 
        ];

        $serverMessage = 'Inicio de sesión exitoso. Redirigiendo...';
        $serverMessageType = 'success';
        $redirScript = "<script>setTimeout(()=>{ window.location.href = '$basePath/home'; }, 800);</script>";

      } else {
        unset($_SESSION['usuario']);
        $serverMessage = 'Contraseña incorrecta.';
        $serverMessageType = 'danger';
      }
    } else {
      unset($_SESSION['usuario']);
      $serverMessage = 'Usuario no encontrado.';
      $serverMessageType = 'danger';
    }

    $stmt->close();
    cerrarConexion($conexion);
  }
}
?>
  <div class="register-container" id="registro">
    <div class="register-logo" style="cursor: pointer;" onclick="window.location.href='<?= $basePath ?>/home'">
      <img src="<?= $basePath ?>/assets/images/logo.png" alt="Artesanos" width="80">
    </div>

    <h5 style="cursor: pointer;" onclick="window.location.href='<?= $basePath ?>/home'">Artesanos</h5>
    <p>¡Necesitás una cuenta para seguir viendo!</p>

    <?php if ($serverMessage): ?>
      <div class="alert alert-<?php echo htmlspecialchars($serverMessageType); ?> text-center" style="width:80%; margin:10px auto;">
        <?php echo htmlspecialchars($serverMessage); ?>
      </div>
      <?php echo $redirScript ?? ''; ?>
    <?php endif; ?>

    <div class="register-box">
      <form action="<?= $GLOBALS['basePath'] ?>/login" method="POST" id="registroForm" novalidate>
        <div class="form-group mb-3">
          <input type="text" class="form-control" name="usuario" placeholder="@usuario" required>
          <small class="error-text"></small>
        </div>
        <div class="form-group password-wrapper">
          <input type="password" class="form-control" name="password" id="password" placeholder="Contraseña" required minlength="6">
          <i class="bi bi-eye-slash toggle-password" id="togglePassword"></i>
          <small class="error-text"></small>
        </div>

        <div class="text-start mb-3">
          <a href="#" class="forgot-link" data-bs-toggle="modal" data-bs-target="#modalRecuperar">
            ¿Olvidaste tu contraseña?
          </a>
        </div>


        <div class="button-row">
          <button type="submit" class="btn btn-main w-100 mb-2">Iniciar sesión</button>
          <a type="button" href="<?= $basePath ?>/home#registroBl" class="btn btn-outline w-100" onclick="mostrarRegistro()">Quiero registrarme</a>
        </div>
      </form>
    </div>
  </div>
  <!-- MODAL Recuperar Contraseña -->
<div class="modal fade" id="modalRecuperar" tabindex="-1" aria-labelledby="recuperarLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="formRecuperar" method="POST" action="recuperar.php">
        <div class="modal-header">
          <h5 class="modal-title" id="recuperarLabel">Recuperar contraseña</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <p>Ingresa tu correo electrónico para restablecer tu contraseña.</p>
          <input type="email" name="correo" id="correoRecuperar" class="form-control" placeholder="Tu correo" required>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btnRecuperar">Enviar</button>
          <button type="button" class="btn btnCancelar" data-bs-dismiss="modal">Cancelar</button>
        </div>
      </form>
    </div>
  </div>
</div>

  <script>
    document.addEventListener("DOMContentLoaded", () => {
      const togglePassword = document.getElementById("togglePassword");
      const passwordField = document.getElementById("password");
      togglePassword.addEventListener("click", () => {
        const type = passwordField.getAttribute("type") === "password" ? "text" : "password";
        passwordField.setAttribute("type", type);
        togglePassword.classList.toggle("bi-eye");
        togglePassword.classList.toggle("bi-eye-slash");
      });
    });
  </script>

  <script>
document.getElementById("formRecuperar").addEventListener("submit", async (e) => {
  e.preventDefault();
  const form = e.target;
  const data = new FormData(form);

  try {
    const response = await fetch('<?= $basePath ?>/api/recuperar', { method: "POST", body: data });
    const result = await response.json();

    // Cerrar el modal primero
    const modal = bootstrap.Modal.getInstance(document.getElementById('modalRecuperar'));
    if (modal) modal.hide();

    if (result.status === "success") {
      Swal.fire({
        title: '¡Correo enviado!',
        text: result.message,
        icon: 'success',
        confirmButtonColor: '#f7931e',
        confirmButtonText: 'Continuar',
        timer: 3000,
        showConfirmButton: true
      }).then(() => {
        // Redirigir al formulario para cambiar contraseña
        window.location.href = result.redirect;
      });
    } else if (result.status === "warning") {
      Swal.fire({
        title: 'Atención',
        text: result.message,
        icon: 'warning',
        confirmButtonColor: '#f7931e',
        confirmButtonText: 'Entendido'
      });
    } else {
      Swal.fire({
        title: 'Error',
        text: result.message || 'Ocurrió un error inesperado',
        icon: 'error',
        confirmButtonColor: '#f7931e',
        confirmButtonText: 'Cerrar'
      });
    }
  } catch (error) {
    console.error('Error:', error);
    Swal.fire({
      title: 'Error de conexión',
      text: 'No se pudo conectar con el servidor. Intenta nuevamente.',
      icon: 'error',
      confirmButtonColor: '#f7931e',
      confirmButtonText: 'Cerrar'
    });
  }
});
</script>

<?php include VIEW_PATH . '/footer.php'; ?>