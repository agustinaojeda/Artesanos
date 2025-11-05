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
        <div class="form-group col-md-6 password-wrapper">
          <input type="password"
            class="form-control"
            name="password"
            id="passwordR"
            placeholder="Contraseña"
            required
            minlength="6">

          <!-- Ojito abierto -->
          <svg class="toggle-password eye-open"
            data-target="passwordR"
            xmlns="http://www.w3.org/2000/svg"
            width="24" height="24" viewBox="0 0 24 24" fill="gray">
            <path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zm0 13c-3.04 0-5.5-2.46-5.5-5.5S8.96 6.5 12 6.5s5.5 2.46 5.5 5.5-2.46 5.5-5.5 5.5zm0-9a3.5 3.5 0 1 0 0 7 3.5 3.5 0 0 0 0-7z" />
          </svg>

          <!-- Ojito cerrado -->
          <svg class="toggle-password eye-closed"
            data-target="passwordR"
            xmlns="http://www.w3.org/2000/svg"
            width="24" height="24" viewBox="0 0 640 512" fill="gray"
            style="display:none;">
            <path d="M320 400c-45.6 0-88.2-15.3-122.4-41.1l-60.8 47.2c-7 5.4-17 4.2-22.4-2.8s-4.2-17 2.8-22.4l480-372c7-5.4 17-4.2 22.4 2.8s4.2 17-2.8 22.4l-73.6 57c35.2 28.4 64.2 66.2 83.2 110.9a48.07 48.07 0 0 1 0 45.2C582.9 376.5 471.8 448 352 448c-11.1 0-22-1-32.7-2.9l-47.3 36.6c-7 5.4-17 4.2-22.4-2.8s-4.2-17 2.8-22.4l47.3-36.6c-20.4-7.1-39.4-17.1-56.7-29.3z" />
          </svg>


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
          <!-- Ojito abierto -->
          <svg class="toggle-password eye-open"
            data-target="passwordConfirm"
            xmlns="http://www.w3.org/2000/svg"
            width="24" height="24" viewBox="0 0 24 24" fill="gray">
            <path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zm0 13c-3.04 0-5.5-2.46-5.5-5.5S8.96 6.5 12 6.5s5.5 2.46 5.5 5.5-2.46 5.5-5.5 5.5zm0-9a3.5 3.5 0 1 0 0 7 3.5 3.5 0 0 0 0-7z" />
          </svg>

          <!-- Ojito cerrado -->
          <svg class="toggle-password eye-closed"
            data-target="passwordConfirm"
            xmlns="http://www.w3.org/2000/svg"
            width="24" height="24" viewBox="0 0 640 512" fill="gray"
            style="display:none;">
            <path d="M320 400c-45.6 0-88.2-15.3-122.4-41.1l-60.8 47.2c-7 5.4-17 4.2-22.4-2.8s-4.2-17 2.8-22.4l480-372c7-5.4 17-4.2 22.4 2.8s4.2 17-2.8 22.4l-73.6 57c35.2 28.4 64.2 66.2 83.2 110.9a48.07 48.07 0 0 1 0 45.2C582.9 376.5 471.8 448 352 448c-11.1 0-22-1-32.7-2.9l-47.3 36.6c-7 5.4-17 4.2-22.4-2.8s-4.2-17 2.8-22.4l47.3-36.6c-20.4-7.1-39.4-17.1-56.7-29.3z" />
          </svg>



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
  document.querySelectorAll('.password-wrapper').forEach(wrapper => {
    const input = wrapper.querySelector('input');
    const eyeOpen = wrapper.querySelector('.eye-open');
    const eyeClosed = wrapper.querySelector('.eye-closed');

    [eyeOpen, eyeClosed].forEach(icon => {
      icon.addEventListener('click', () => {
        if (input.type === 'password') {
          input.type = 'text';
          eyeOpen.style.display = 'none';
          eyeClosed.style.display = 'block';
        } else {
          input.type = 'password';
          eyeOpen.style.display = 'block';
          eyeClosed.style.display = 'none';
        }
      });
    });
  });


  document.querySelectorAll('.password-wrapper input').forEach(input => {
    const wrapper = input.closest('.password-wrapper');
    const icons = wrapper.querySelectorAll('.toggle-password');

    const toggleVisibility = () => {
      if (input.value || document.activeElement === input) {
        icons.forEach(i => i.style.opacity = '1');
      } else {
        icons.forEach(i => i.style.opacity = '0');
      }
    };

    input.addEventListener('input', toggleVisibility);
    input.addEventListener('focus', toggleVisibility);
    input.addEventListener('blur', toggleVisibility);

    toggleVisibility();
  });
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>