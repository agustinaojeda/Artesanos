<?php
// app/views/registro.php
require_once '../../config/conexion.php';
require_once '../../config/cerrarConexion.php';

$conexion = abrirConexion();

$serverMessage = '';
$serverMessageType = '';
$redirScript = '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $nombre = trim($_POST['nombre'] ?? '');
  $apellido = trim($_POST['apellido'] ?? '');
  $usuario = trim($_POST['usuario'] ?? '');
  $apodo = trim($_POST['apodo'] ?? '');
  $password = $_POST['password'] ?? '';
  $confirmar = $_POST['confirmar'] ?? '';
  $email = trim($_POST['email'] ?? '');

  // --- Validaciones servidor ---
  if (strlen($nombre) < 3 || strlen($apellido) < 3) {
    $serverMessage = 'El nombre y apellido deben tener al menos 3 caracteres.';
    $serverMessageType = 'warning';
  } elseif ($password !== $confirmar) {
    $serverMessage = 'Las contraseñas no coinciden.';
    $serverMessageType = 'danger';
  } elseif (strlen($password) < 6) {
    $serverMessage = 'La contraseña debe tener al menos 6 caracteres.';
    $serverMessageType = 'warning';
  } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $serverMessage = 'El correo no tiene formato válido.';
    $serverMessageType = 'warning';
  } else {
    // Verificar si el correo ya existe
    $stmt = $conexion->prepare("SELECT idUsuario FROM usuario WHERE correoUsuario = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
      $serverMessage = 'Este correo ya está registrado.';
      $serverMessageType = 'warning';
      $stmt->close();
    } else {
      $stmt->close();

      // Verificar si el usuario ya existe
      $stmt2 = $conexion->prepare("SELECT idUsuario FROM usuario WHERE arrobaUsuario = ?");
      $stmt2->bind_param("s", $usuario);
      $stmt2->execute();
      $stmt2->store_result();

      if ($stmt2->num_rows > 0) {
        $serverMessage = 'El nombre de usuario ya está en uso.';
        $serverMessageType = 'warning';
        $stmt2->close();
      } else {
        $stmt2->close();

        // Insertar el usuario
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $insert = $conexion->prepare("
          INSERT INTO usuario (nombreUsuario, apellidoUsuario, arrobaUsuario, apodoUsuario, contrasenaUsuario, correoUsuario)
          VALUES (?, ?, ?, ?, ?, ?)
        ");
        $insert->bind_param("ssssss", $nombre, $apellido, $usuario, $apodo, $passwordHash, $email);

        if ($insert->execute()) {
          $serverMessage = 'Registro exitoso. Redirigiendo al login...';
          $serverMessageType = 'success';
          $redirScript = "<script>setTimeout(()=>{ window.location.href = 'login.php'; }, 2000);</script>";
        } else {
          $serverMessage = 'Error al registrar: ' . htmlspecialchars($insert->error);
          $serverMessageType = 'danger';
        }
        $insert->close();
      }
    }
  }
  cerrarConexion($conexion);
}
?>
<?php $esStandalone = basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME']); ?>
<?php if ($esStandalone): ?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Registro | Artesanos</title>
  
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  
  <link rel="stylesheet" href="../../public/assets/css/re.css">
</head>
<body>
<?php endif; ?>


<div class="register-container" id="registro">
  <div class="register-logo">
    <img src="../../public/assets/images/logo.png" alt="Artesanos" width="80">
  </div>

  <h5>Artesanos</h5>
  <p>¡Necesitás una cuenta para seguir viendo!</p>

  <?php if (!empty($serverMessage)): ?>
  <div class="alert alert-<?php echo $serverMessageType; ?> text-center mt-3" role="alert">
    <?php echo htmlspecialchars($serverMessage); ?>
  </div>
  <?php echo $redirScript ?? ''; ?>
  <?php endif; ?>

  <div class="register-box">
    <form action="" method="POST" id="registroForm" novalidate>
      <div class="form-row">
        <div class="form-group">
          <input type="text" class="form-control" name="nombre" placeholder="Nombre" value="<?php echo htmlspecialchars($nombre ?? '') ?>" required>
          <small class="error-text"></small>
        </div>
        <div class="form-group">
          <input type="text" class="form-control" name="apellido" placeholder="Apellido" value="<?php echo htmlspecialchars($apellido ?? '') ?>" required>
          <small class="error-text"></small>
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <input type="text" class="form-control" name="usuario" placeholder="@usuario" value="<?php echo htmlspecialchars($usuario ?? '') ?>" required>
          <small class="error-text"></small>
        </div>
        <div class="form-group">
          <input type="text" class="form-control" name="apodo" placeholder="Apodo" value="<?php echo htmlspecialchars($apodo ?? '') ?>" required>
          <small class="error-text"></small>
        </div>
      </div>

      <div class="form-row">
        <div class="form-group password-wrapper">
          <input type="password" 
            class="form-control" 
            name="password" 
            id="password" 
            placeholder="Contraseña" 
            required 
            minlength="6">
          <i class="bi bi-eye-slash toggle-password" id="togglePassword"></i>
          <small class="error-text"></small>
        </div>
        <div class="form-group password-wrapper">
          <input type="password" 
            class="form-control" 
            name="confirmar" 
            id="password" 
            placeholder="Confirmar contraseña" 
            required 
            minlength="6">
          <i class="bi bi-eye-slash toggle-password" id="togglePassword"></i>
          <small class="error-text"></small>
        </div>

      </div>

      <div class="form-single form-group">
        <input type="email" class="form-control" name="email" placeholder="Correo electrónico" value="<?php echo htmlspecialchars($email ?? '') ?>" required>
        <small class="error-text"></small>
      </div>

      <button type="button" class="btn btn-outline" onclick="window.location.href='login.php'">Ya tengo una cuenta</button>
      <button type="submit" class="btn btn-main">Registrarse</button>
    </form>
  </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", () => {
  const form = document.getElementById("registroForm");
  const inputs = form.querySelectorAll(".form-control");

  inputs.forEach(input => {
    input.addEventListener("input", () => validarCampo(input));
  });

  form.addEventListener("submit", e => {
    let valido = true;
    inputs.forEach(input => {
      if (!validarCampo(input)) valido = false;
    });
    if (!valido) e.preventDefault();
  });

  function validarCampo(input) {
    const errorText = input.parentElement.querySelector(".error-text");
    let valido = true;
    let mensaje = "";

    if (input.name === "email") {
      const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      if (!emailRegex.test(input.value.trim())) {
        valido = false;
        mensaje = "Ingrese un correo válido.";
      }
    } else if (input.name === "password") {
      if (input.value.length < 6) {
        valido = false;
        mensaje = "La contraseña debe tener al menos 6 caracteres.";
      }
    } else if (input.name === "confirmar") {
      const password = form.querySelector("input[name='password']").value;
      if (input.value !== password) {
        valido = false;
        mensaje = "Las contraseñas no coinciden.";
      }
    } else if (input.value.trim().length < 3) {
      valido = false;
      mensaje = "Debe tener al menos 3 caracteres.";
    }

    if (!valido) {
      input.classList.remove("is-valid");
      input.classList.add("is-invalid");
      errorText.textContent = mensaje;
    } else {
      input.classList.remove("is-invalid");
      input.classList.add("is-valid");
      errorText.textContent = "";
    }
    return valido;
  }
});
</script>
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
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
<?php if ($esStandalone): ?>
</body>
</html>
<?php endif; ?>





