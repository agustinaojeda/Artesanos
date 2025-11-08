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
            echo "<script>
                alert('Tu contraseña ha sido actualizada exitosamente.');
                window.location.href='home.php#registro';
            </script>";
        } else {
            echo "<script>alert('No se pudo actualizar la contraseña. Verifica el correo.');</script>";
        }

        $stmt->close();
    } else {
        echo "<script>alert('Las contraseñas no coinciden o están vacías');</script>";
    }
}

cerrarConexion($conexion);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Nueva contraseña</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            text-align: center;
            margin-top: 80px;
            background-color: white;
        }
        form {
            display: inline-block;
            padding: 25px 30px;
            border-radius: 10px;
            background: #fff;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        input {
            padding: 10px;
            width: 250px;
            margin-bottom: 10px;
            border-radius: 5px;
            border: 1px solid #ccc;
        }
        button {
            padding: 10px 20px;
            background: green;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background 0.3s ease;
        }
        button:hover {
            background: #0c7a0c;
        }
        #message {
            color: red;
            margin-top: 10px;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <h2>Ingresá tu nueva contraseña</h2>

    <form method="POST" action="">
        <!-- Pasamos el correo como campo oculto -->
        <input type="hidden" name="correo" value="<?php echo htmlspecialchars($correo); ?>">

        <input type="password" name="nueva" id="newPassword" placeholder="Nueva contraseña" required><br>
        <input type="password" name="confirmar" id="confirmPassword" placeholder="Confirmar nueva contraseña" required><br>

        <button type="submit">Guardar cambio</button>
    </form>

    <p id="message"></p>

    <script>
        const form = document.querySelector("form");
        const password = document.getElementById("newPassword");
        const confirm = document.getElementById("confirmPassword");
        const message = document.getElementById("message");

        form.addEventListener("submit", (e) => {
            if (password.value !== confirm.value) {
                e.preventDefault();
                message.textContent = "Las contraseñas no coinciden.";
                message.style.color = "red";
            }
        });
    </script>
</body>
</html>


