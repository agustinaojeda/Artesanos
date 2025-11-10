<?php
require_once CONFIG_PATH . '/conexion.php';
require_once CONFIG_PATH . '/cerrarConexion.php';
$conexion = abrirConexion();

// Obtenemos el correo desde GET
$correo = $_GET['correo'] ?? '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $correo = trim($_POST['correo'] ?? '');
    $nueva = trim($_POST['nueva'] ?? '');
    $confirmar = trim($_POST['confirmar'] ?? '');

    if (!empty($nueva) && $nueva === $confirmar) {
        $hash = password_hash($nueva, PASSWORD_DEFAULT);

        $stmt = $conexion->prepare("UPDATE usuario SET contrasenaUsuario = ? WHERE correoUsuario = ?");
        $stmt->bind_param("ss", $hash, $correo);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            $basePath = $GLOBALS['basePath'] ?? '';
            echo "<script>
                Swal.fire({
                    title: '¡Contraseña actualizada!',
                    text: 'Tu contraseña ha sido actualizada exitosamente.',
                    icon: 'success',
                    confirmButtonColor: '#f7931e',
                    confirmButtonText: 'Iniciar sesión',
                    timer: 3000,
                    showConfirmButton: true
                }).then(() => {
                    window.location.href='" . htmlspecialchars($basePath, ENT_QUOTES) . "/home#registro';
                });
            </script>";
        } else {
            echo "<script>
                Swal.fire({
                    title: 'Error',
                    text: 'No se pudo actualizar la contraseña. Verifica el correo.',
                    icon: 'error',
                    confirmButtonColor: '#f7931e',
                    confirmButtonText: 'Cerrar'
                });
            </script>";
        }

        $stmt->close();
    } else {
        echo "<script>
            Swal.fire({
                title: 'Error de validación',
                text: 'Las contraseñas no coinciden o están vacías',
                icon: 'warning',
                confirmButtonColor: '#f7931e',
                confirmButtonText: 'Entendido'
            });
        </script>";
    }
}

cerrarConexion($conexion);

$basePath = $GLOBALS['basePath'] ?? '';
$pageTitle = 'Artesanos - Contraseña Nueva';
include VIEW_PATH . '/header.php';
?>
<style>
    .password-reset-container {
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        padding: 20px;
    }
    
    .password-reset-box {
        background: #ffffff;
        border-radius: 16px;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
        padding: 40px;
        max-width: 450px;
        width: 100%;
        text-align: center;
    }
    
    .password-reset-logo {
        margin-bottom: 20px;
    }
    
    .password-reset-logo img {
        width: 120px;
        height: auto;
        cursor: pointer;
        transition: transform 0.3s ease;
    }
    
    .password-reset-logo img:hover {
        transform: scale(1.05);
    }
    
    .password-reset-title {
        font-size: 1.75rem;
        font-weight: 600;
        color: #333;
        margin-bottom: 10px;
    }
    
    .password-reset-subtitle {
        color: #666;
        margin-bottom: 30px;
        font-size: 0.95rem;
    }
    
    .password-reset-form {
        text-align: left;
    }
    
    .password-reset-form .form-group {
        margin-bottom: 20px;
    }
    
    .password-reset-form label {
        display: block;
        margin-bottom: 8px;
        color: #333;
        font-weight: 500;
        font-size: 0.9rem;
    }
    
    .password-reset-form input {
        width: 100%;
        padding: 12px 16px;
        border: 2px solid #e0e0e0;
        border-radius: 8px;
        font-size: 1rem;
        transition: all 0.3s ease;
        box-sizing: border-box;
    }
    
    .password-reset-form input:focus {
        outline: none;
        border-color: #f7931e;
        box-shadow: 0 0 0 3px rgba(247, 147, 30, 0.1);
    }
    
    .password-reset-form .password-wrapper {
        position: relative;
    }
    
    .password-reset-form .toggle-password {
        position: absolute;
        right: 12px;
        top: 50%;
        transform: translateY(-50%);
        cursor: pointer;
        color: #999;
        font-size: 1.2rem;
        transition: color 0.3s ease;
    }
    
    .password-reset-form .toggle-password:hover {
        color: #f7931e;
    }
    
    .password-reset-form input[type="password"] {
        padding-right: 45px;
    }
    
    .btn-reset-password {
        width: 100%;
        padding: 14px;
        background: linear-gradient(135deg, #f7931e 0%, #ff7f45 100%);
        color: white;
        border: none;
        border-radius: 8px;
        font-size: 1rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        margin-top: 10px;
        box-shadow: 0 4px 15px rgba(247, 147, 30, 0.3);
    }
    
    .btn-reset-password:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(247, 147, 30, 0.4);
    }
    
    .btn-reset-password:active {
        transform: translateY(0);
    }
    
    .back-to-login {
        margin-top: 20px;
        text-align: center;
    }
    
    .back-to-login a {
        color: #f7931e;
        text-decoration: none;
        font-size: 0.9rem;
        transition: color 0.3s ease;
    }
    
    .back-to-login a:hover {
        color: #ff7f45;
        text-decoration: underline;
    }
</style>

<div class="password-reset-container">
    <div class="password-reset-box">
        <div class="password-reset-logo" onclick="window.location.href='<?= $basePath ?>/home'">
            <img src="<?= $basePath ?>/assets/images/logo.png" alt="Artesanos">
        </div>
        
        <h2 class="password-reset-title">Restablecer contraseña</h2>
        <p class="password-reset-subtitle">Ingresá tu nueva contraseña para continuar</p>
        
        <form method="POST" action="" class="password-reset-form" id="resetPasswordForm">
            <input type="hidden" name="correo" value="<?= htmlspecialchars($correo) ?>">
            
            <div class="form-group">
                <label for="newPassword">Nueva contraseña</label>
                <div class="password-wrapper">
                    <input type="password" name="nueva" id="newPassword" placeholder="Mínimo 6 caracteres" required minlength="6">
                    <i class="bi bi-eye-slash toggle-password" id="togglePassword1"></i>
                </div>
            </div>
            
            <div class="form-group">
                <label for="confirmPassword">Confirmar nueva contraseña</label>
                <div class="password-wrapper">
                    <input type="password" name="confirmar" id="confirmPassword" placeholder="Repetí la contraseña" required minlength="6">
                    <i class="bi bi-eye-slash toggle-password" id="togglePassword2"></i>
                </div>
            </div>
            
            <button type="submit" class="btn-reset-password">
                <i class="bi bi-check-circle me-2"></i>Guardar nueva contraseña
            </button>
        </form>
        
        <div class="back-to-login">
            <a href="<?= $basePath ?>/login">
                <i class="bi bi-arrow-left me-1"></i>Volver al inicio de sesión
            </a>
        </div>
    </div>
</div>

<script>
    // Toggle password visibility
    document.getElementById('togglePassword1').addEventListener('click', function() {
        const passwordField = document.getElementById('newPassword');
        const type = passwordField.getAttribute('type') === 'password' ? 'text' : 'password';
        passwordField.setAttribute('type', type);
        this.classList.toggle('bi-eye');
        this.classList.toggle('bi-eye-slash');
    });
    
    document.getElementById('togglePassword2').addEventListener('click', function() {
        const passwordField = document.getElementById('confirmPassword');
        const type = passwordField.getAttribute('type') === 'password' ? 'text' : 'password';
        passwordField.setAttribute('type', type);
        this.classList.toggle('bi-eye');
        this.classList.toggle('bi-eye-slash');
    });
    
    // Form validation
    const form = document.getElementById('resetPasswordForm');
    const password = document.getElementById('newPassword');
    const confirm = document.getElementById('confirmPassword');

    form.addEventListener('submit', (e) => {
        if (password.value.length < 6) {
            e.preventDefault();
            Swal.fire({
                title: 'Contraseña muy corta',
                text: 'La contraseña debe tener al menos 6 caracteres',
                icon: 'warning',
                confirmButtonColor: '#f7931e',
                confirmButtonText: 'Entendido'
            });
            return;
        }
        
        if (password.value !== confirm.value) {
            e.preventDefault();
            Swal.fire({
                title: 'Las contraseñas no coinciden',
                text: 'Por favor, verifica que ambas contraseñas sean iguales',
                icon: 'error',
                confirmButtonColor: '#f7931e',
                confirmButtonText: 'Entendido'
            });
        }
    });
</script>

<?php include VIEW_PATH . '/footer.php'; ?>


