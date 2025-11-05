<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../../config/conexion.php';
require_once '../../config/cerrarConexion.php';

$serverMessage = '';
$serverMessageType = '';
$redirScript = '';

if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST['form_action'] ?? '') === 'login') {
    $usuario = trim($_POST['usuario'] ?? '');
    $password = $_POST['password'] ?? '';

    if (strlen($usuario) < 3) {
        $_SESSION['serverMessageLogin'] = 'El nombre de usuario debe tener al menos 3 caracteres.';
        $_SESSION['serverMessageTypeLogin'] = 'warning';
        $_SESSION['mostrarLogin'] = true;
        header('Location: home.php#registroBl');
        exit;
    } elseif (strlen($password) < 6) {
        $_SESSION['serverMessageLogin'] = 'La contraseña debe tener al menos 6 caracteres.';
        $_SESSION['serverMessageTypeLogin'] = 'warning';
        $_SESSION['mostrarLogin'] = true;
        header('Location: home.php#registroBl');
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
                unset($_SESSION['mostrarLogin']);
                header('Location: home.php');
                exit;
            } else {
                $_SESSION['serverMessageLogin'] = 'Contraseña incorrecta.';
                $_SESSION['serverMessageTypeLogin'] = 'danger';
                $_SESSION['mostrarLogin'] = true;
                header('Location: home.php#registroBl');
                exit;
            }
        } else {
            $_SESSION['serverMessageLogin'] = 'Usuario no encontrado.';
            $_SESSION['serverMessageTypeLogin'] = 'danger';
            $_SESSION['mostrarLogin'] = true;
            header('Location: home.php#registroBl');
            exit;
        }

        $stmt->close();
        cerrarConexion($conexion);
    }
}
