
<div class="register-container" id="registro">
  <div class="register-logo">
    <img src="../../public/assets/images/logo.png" alt="Artesanos" width="80">
  </div>

  <h5>Artesanos</h5>
  <p>¡Necesitás una cuenta para seguir viendo!</p>

  <div class="register-box">
    <?php
    if (isset($_SESSION['serverMessageLogin'])):
    ?>
      <div class="alert alert-<?php echo htmlspecialchars($_SESSION['serverMessageTypeLogin']); ?> text-center mt-3" role="alert">
        <?php echo htmlspecialchars($_SESSION['serverMessageLogin']); ?>
      </div>
      <?php

      unset($_SESSION['serverMessageLogin']);
      unset($_SESSION['serverMessageTypeLogin']);
      ?>
    <?php endif; ?>
    <?php if (isset($_SESSION['mensaje_exito'])): ?>
      <div style="color: green; border: 1px solid green; padding: 10px; margin-bottom: 15px; border-radius: 5px; text-align: center;">
        <?php
        echo $_SESSION['mensaje_exito'];
        unset($_SESSION['mensaje_exito']); // Elimina el mensaje para que no se muestre al recargar
        ?>
      </div>
    <?php endif; ?>
    <form action="home.php" method="POST" id="loginForm" novalidate>
      <input type="hidden" name="form_action" value="login">
      <div class="form-group mb-3">
        <input type="text" class="form-control" name="usuario" placeholder="@usuario" required>
        <small class="error-text"></small>
      </div>
      <div class="form-group password-wrapper logPas">
        <input type="password" class="form-control" name="password" id="password" placeholder="Contraseña" required minlength="6">

        <!-- Ojito abierto -->
        <svg class="toggle-password eye-open"
          data-target="password"
          xmlns="http://www.w3.org/2000/svg"
          width="24" height="24"
          viewBox="0 0 24 24"
          fill="gray">
          <path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zm0 13c-3.04 0-5.5-2.46-5.5-5.5S8.96 6.5 12 6.5s5.5 2.46 5.5 5.5-2.46 5.5-5.5 5.5zm0-9a3.5 3.5 0 1 0 0 7 3.5 3.5 0 0 0 0-7z" />
        </svg>

        <!-- Ojito cerrado -->
        <svg class="toggle-password eye-closed"
          data-target="password"
          xmlns="http://www.w3.org/2000/svg"
          width="24" height="24"
          viewBox="0 0 640 512"
          fill="gray"
          style="display:none;">
          <path d="M320 400c-45.6 0-88.2-15.3-122.4-41.1l-60.8 47.2c-7 5.4-17 4.2-22.4-2.8s-4.2-17 2.8-22.4l480-372c7-5.4 17-4.2 22.4 2.8s4.2 17-2.8 22.4l-73.6 57c35.2 28.4 64.2 66.2 83.2 110.9a48.07 48.07 0 0 1 0 45.2C582.9 376.5 471.8 448 352 448c-11.1 0-22-1-32.7-2.9l-47.3 36.6c-7 5.4-17 4.2-22.4-2.8s-4.2-17 2.8-22.4l47.3-36.6c-20.4-7.1-39.4-17.1-56.7-29.3z" />
        </svg>

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
document.querySelectorAll('.logPas').forEach(wrapper => {
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

</script>