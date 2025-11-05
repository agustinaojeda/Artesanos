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
    <form action="home.php" method="POST" id="loginForm" novalidate>
      <input type="hidden" name="form_action" value="login">
      <div class="form-group mb-3">
        <input type="text" class="form-control" name="usuario" placeholder="@usuario" required>
        <small class="error-text"></small>
      </div>
      <div class="form-group password-wrapper">
        <input type="password" class="form-control" name="password" id="password" placeholder="Contraseña" required minlength="6">
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
</script>