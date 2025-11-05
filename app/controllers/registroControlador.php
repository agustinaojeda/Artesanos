<?php
// app/views/registro.php
require_once '../../config/conexion.php';
require_once '../../config/cerrarConexion.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$serverMessage = '';
$serverMessageType = '';

if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST['form_action'] ?? '') === 'registro') {
    $conexion = abrirConexion();

    $nombre = trim($_POST['nombre'] ?? '');
    $apellido = trim($_POST['apellido'] ?? '');
    $usuario = trim($_POST['usuario'] ?? '');
    $apodo = trim($_POST['apodo'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmar = $_POST['confirmar'] ?? '';
    $email = trim($_POST['email'] ?? '');

    $guardarDatosSesion = function() use ($nombre, $apellido, $usuario, $apodo, $email) {
        $_SESSION['datosRegistro'] = [
            'nombre' => $nombre,
            'apellido' => $apellido,
            'usuario' => $usuario,
            'apodo' => $apodo,
            'email' => $email
        ];
    };

    // --- Validaciones servidor ---
    if (strlen($nombre) < 3 || strlen($apellido) < 3) {
        $guardarDatosSesion();
        $_SESSION['serverMessage'] = 'El nombre y apellido deben tener al menos 3 caracteres.';
        $_SESSION['serverMessageType'] = 'warning';
        $_SESSION['mostrarLogin'] = false; // Mostrar la vista de registro
        header('Location: home.php#registroBl');
        exit;
    } elseif ($password !== $confirmar) {
        $guardarDatosSesion();
        $_SESSION['serverMessage'] = 'Las contraseñas no coinciden.';
        $_SESSION['serverMessageType'] = 'danger';
        $_SESSION['mostrarLogin'] = false; // Mostrar la vista de registro
        header('Location: home.php#registroBl');
        exit;
    } elseif (strlen($password) < 6) {
        $guardarDatosSesion();
        $_SESSION['serverMessage'] = 'La contraseña debe tener al menos 6 caracteres.';
        $_SESSION['serverMessageType'] = 'warning';
        $_SESSION['mostrarLogin'] = false; // Mostrar la vista de registro
        header('Location: home.php#registroBl');
        exit;
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $guardarDatosSesion();
        $_SESSION['serverMessage'] = 'El correo no tiene formato válido.';
        $_SESSION['serverMessageType'] = 'warning';
        $_SESSION['mostrarLogin'] = false; // Mostrar la vista de registro
        header('Location: home.php#registroBl');
        exit;
    } else {
        // Verificar si el correo ya existe
        $stmt = $conexion->prepare("SELECT idUsuario FROM usuario WHERE correoUsuario = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $guardarDatosSesion();
            $_SESSION['serverMessage'] = 'Este correo ya está registrado.';
            $_SESSION['serverMessageType'] = 'warning';
            $_SESSION['mostrarLogin'] = false; // Mostrar la vista de registro
            header('Location: home.php#registroBl');
            exit;
        } else {
            // Verificar si el usuario ya existe
            $stmt2 = $conexion->prepare("SELECT idUsuario FROM usuario WHERE arrobaUsuario = ?");
            $stmt2->bind_param("s", $usuario);
            $stmt2->execute();
            $stmt2->store_result();

            if ($stmt2->num_rows > 0) {
                $guardarDatosSesion();
                $_SESSION['serverMessage'] = 'El nombre de usuario ya está en uso.';
                $_SESSION['serverMessageType'] = 'warning';
                $_SESSION['mostrarLogin'] = false; // Mostrar la vista de registro
                header('Location: home.php#registroBl');
                exit;
            } else {
                unset($_SESSION['datosRegistro']); // Limpia si todo salio bien
                // Insertar usuario
                $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                $insert = $conexion->prepare("
                    INSERT INTO usuario (nombreUsuario, apellidoUsuario, arrobaUsuario, apodoUsuario, contrasenaUsuario, correoUsuario)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $insert->bind_param("ssssss", $nombre, $apellido, $usuario, $apodo, $passwordHash, $email);

                if ($insert->execute()) {
                    // Registro exitoso : redirigir al home
                    $_SESSION['usuario'] = [
                        'nombre' => $nombre,
                        'apellido' => $apellido,
                        'arroba' => $usuario,
                        'apodo' => $apodo,
                        'correo' => $email
                    ];
                    $insert->close();
                    cerrarConexion($conexion);
                    header("Location: home.php");
                    exit;
                } else {
                    $_SESSION['serverMessage'] = 'Error al registrar: ' . htmlspecialchars($insert->error);
                    $_SESSION['serverMessageType'] = 'danger';
                    $_SESSION['mostrarLogin'] = false; // Mostrar la vista de registro
                    header('Location: home.php#registroBl');
                    exit;
                }
                $insert->close();
            }
            $stmt2->close();
        }
        $stmt->close();
    }

    cerrarConexion($conexion);
}
?>
