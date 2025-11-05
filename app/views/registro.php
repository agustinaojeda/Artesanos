<?php
$data = $_SESSION['datosRegistro'] ?? [];
$nombre = $data['nombre'] ?? '';
$apellido = $data['apellido'] ?? '';
$usuario = $data['usuario'] ?? '';
$apodo = $data['apodo'] ?? '';
$email = $data['email'] ?? '';

if (!empty($data)) {
  unset($_SESSION['datosRegistro']);
}
?>
<div class="register-container" id="registro">
  <div class="register-logo">
    <img src="../../public/assets/images/logo.png" alt="Artesanos" width="80">
  </div>

  <h5>Artesanos</h5>
  <p>¡Necesitás una cuenta para seguir viendo!</p>




  <div class="register-box">

    <?php
    if (isset($_SESSION['serverMessage'])):
    ?>
      <div class="alert alert-<?php echo htmlspecialchars($_SESSION['serverMessageType']); ?> text-center mt-3" role="alert">
        <?php echo htmlspecialchars($_SESSION['serverMessage']); ?>
      </div>
      <?php

      unset($_SESSION['serverMessage']);
      unset($_SESSION['serverMessageType']);
      ?>
    <?php endif; ?>
    <form action="home.php" method="POST" id="registroForm" novalidate>
      <input type="hidden" name="form_action" value="registro">
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
            id="passwordR"
            placeholder="Contraseña"
            required
            minlength="6">
          <small class="error-text"></small>
        </div>
        <div class="form-group password-wrapper">
          <input type="password"
            class="form-control"
            name="confirmar"
            id="passwordConfirm"
            placeholder="Confirmar contraseña"
            required
            minlength="6">
          <small class="error-text"></small>
        </div>

      </div>

      <div class="form-single form-group">
        <input type="email" class="form-control" name="email" placeholder="Correo electrónico" value="<?php echo htmlspecialchars($email ?? '') ?>" required>
        <small class="error-text"></small>
      </div>

      <button type="submit" class="btn btn-main mb-2">Registrarse</button>
      <button type="button" class="btn btn-outline" onclick="mostrarLogin()">Ya tengo una cuenta</button>

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
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>