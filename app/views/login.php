<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
require_once '../../config/conexion.php';
require_once '../../config/cerrarConexion.php';

$serverMessage = '';
$serverMessageType = '';
$redirScript = '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $usuario = trim($_POST['usuario'] ?? '');
  $password = $_POST['password'] ?? '';

  if (strlen($usuario) < 3) {
    $_SESSION['serverMessage'] = 'El nombre de usuario debe tener al menos 3 caracteres.';
    $_SESSION['serverMessageType'] = 'warning';
    $_SESSION['mostrarLogin'] = true;
    header('Location: home.php');
    exit;
  } elseif (strlen($password) < 6) {
    $_SESSION['serverMessage'] = 'La contraseña debe tener al menos 6 caracteres.';
    $_SESSION['serverMessageType'] = 'warning';
    $_SESSION['mostrarLogin'] = true;
    header('Location: home.php');
    exit;
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
        $_SESSION['usuario'] = [
          'id'       => $user['idUsuario'],
          'nombre'   => $user['nombreUsuario'],
          'apellido' => $user['apellidoUsuario'],
          'apodo'    => $user['apodoUsuario'],
          'arroba'   => $user['arrobaUsuario'],
          'correo'   => $user['correoUsuario'],
          'avatar'   => $user['avatar']
        ];

        // ✅ Redirigimos inmediatamente al home
        header('Location: home.php');
        exit;
      } else {
        $_SESSION['serverMessage'] = 'Contraseña incorrecta.';
        $_SESSION['serverMessageType'] = 'danger';
        $_SESSION['mostrarLogin'] = true;
        header('Location: home.php');
        exit;
      }
    } else {
      $_SESSION['serverMessage'] = 'Usuario no encontrado.';
      $_SESSION['serverMessageType'] = 'danger';
      $_SESSION['mostrarLogin'] = true;
      header('Location: home.php');
      exit;
    }

    $stmt->close();
    cerrarConexion($conexion);
  }
}
?>
<div class="register-container" id="registro">
  <div class="register-logo">
    <img src="../../public/assets/images/logo.png" alt="Artesanos" width="80">
  </div>

  <h5>Artesanos</h5>
  <p>¡Necesitás una cuenta para seguir viendo!</p>
  <?php
  if (isset($_SESSION['serverMessage'])):
  ?>
    <div class="alert alert-<?php echo htmlspecialchars($_SESSION['serverMessageType']); ?> text-center" style="width:80%; margin:10px auto;">
      <?php echo htmlspecialchars($_SESSION['serverMessage']); ?>
    </div>
  <?php
    unset($_SESSION['serverMessage']);
    unset($_SESSION['serverMessageType']);
  endif;
  ?>

  <div class="register-box">
    <form action="login.php" method="POST" id="registroForm" novalidate>
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
        <button type="button" class="btn btn-outline w-100" onclick="mostrarRegistro()">Quiero registrarme</button>
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

    const response = await fetch("recuperar.php", {
      method: "POST",
      body: data
    });
    const result = await response.json();

    alert(result.message);

    if (result.status === "success") {
      // Redirigir al formulario para cambiar contraseña
      window.location.href = result.redirect;
    }
  });
</script>