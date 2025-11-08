<?php
// app/views/procesarEdicion.php

if (!isset($_SESSION['usuario']['id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.php');
    exit;
}

// conexión (subir 2 niveles hasta la raíz del proyecto)
require_once dirname(__DIR__, 2) . '/config/conexion.php';
    
$conexion = abrirConexion();
if ($conexion === false || $conexion->connect_error) {
    die("Error de conexión a la base de datos.");
}

$userId = (int)$_SESSION['usuario']['id'];
$errors = [];

// Helper
function clean_input_db($conexion, $value)
{
    return mysqli_real_escape_string($conexion, trim((string)$value));
}

// 1) recoger datos
$nombreUsuario = clean_input_db($conexion, $_POST['nombreUsuario'] ?? '');
$apellidoUsuario = clean_input_db($conexion, $_POST['apellidoUsuario'] ?? '');
$arrobaUsuario = clean_input_db($conexion, $_POST['arrobaUsuario'] ?? '');
$apodoUsuario = clean_input_db($conexion, $_POST['apodoUsuario'] ?? '');
$descripcionUsuario = clean_input_db($conexion, $_POST['descripcionUsuario'] ?? '');
$correoUsuario = clean_input_db($conexion, $_POST['correoUsuario'] ?? '');
$privacidadUsuario = clean_input_db($conexion, $_POST['privacidadUsuario'] ?? 'publico');

$new_password = $_POST['new_password'] ?? '';
$confirm_new_password = $_POST['confirm_new_password'] ?? '';
$selected_history_avatar_id = (int)($_POST['selected_history_avatar_id'] ?? 0);

// validaciones básicas
if ($nombreUsuario === '' || $apellidoUsuario === '' || $arrobaUsuario === '' || $correoUsuario === '') {
    $errors[] = "Nombre, apellido, arroba y correo son obligatorios.";
}
if ($correoUsuario !== '' && !filter_var($correoUsuario, FILTER_VALIDATE_EMAIL)) {
    $errors[] = "El correo no tiene formato válido.";
}
if ($new_password !== '' && $new_password !== $confirm_new_password) {
    $errors[] = "La nueva contraseña y su confirmación no coinciden.";
}
if ($new_password !== '' && strlen($new_password) < 6) {
    $errors[] = "La contraseña debe tener al menos 6 caracteres.";
}

// si hay errores, guardarlos y volver al formulario
if (!empty($errors)) {
    $_SESSION['errors'] = $errors;
    $_SESSION['form_data'] = $_POST;
    header('Location: editarPerfil.php');
    exit;
}

// Empezar transacción
$conexion->begin_transaction();

try {
    // 2) Procesar subida de avatar (si subieron)
    $newAvatarId = 0;
    if (!empty($_FILES['new_avatar']) && $_FILES['new_avatar']['error'] !== UPLOAD_ERR_NO_FILE) {
        $file = $_FILES['new_avatar'];
        if ($file['error'] !== UPLOAD_ERR_OK) throw new Exception("Error subiendo la imagen (code {$file['error']}).");

        // validaciones
        $maxSize = 2 * 1024 * 1024; // 2MB
        if ($file['size'] > $maxSize) throw new Exception("La imagen no puede superar 2MB.");

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']);
        $mimeMap = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
        if (!isset($mimeMap[$mime])) throw new Exception("Formato de imagen no permitido.");

        $ext = $mimeMap[$mime];
        $newName = 'avatar_' . $userId . '_' . time() . '.' . $ext;

        // directorio real en servidor (proyecto_root/public/uploads/avatars/)
        $uploadDir = dirname(__DIR__, 2) . '/public/uploads/avatars/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

        $targetPath = $uploadDir . $newName;
        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            throw new Exception("No se pudo mover el archivo subido.");
        }

        // insertar en fotosdeperfil (guardamos imagen y idUsuario para historial)
        $ins = $conexion->prepare("INSERT INTO fotosdeperfil (imagenPerfil, idUsuario) VALUES (?, ?)");
        if (!$ins) throw new Exception("Error preparar insert foto: " . $conexion->error);
        $ins->bind_param("si", $newName, $userId);
        if (!$ins->execute()) throw new Exception("Error insert foto: " . $ins->error);
        $newAvatarId = (int)$ins->insert_id;
        $ins->close();

        // 🟢 Actualizar la sesión inmediatamente si el usuario actualizó su propio avatar
        if (isset($_SESSION['usuario']) && $_SESSION['usuario']['id'] == $userId) {
            $_SESSION['usuario']['avatar'] = $newName;
        }
    }

    // 2b) Si seleccionó historial, prevalece sobre nueva subida
    if ($selected_history_avatar_id > 0) {
        $newAvatarId = $selected_history_avatar_id;

        // 🟢 También actualizamos la sesión con la imagen del historial
        $stmtFoto = $conexion->prepare("SELECT imagenPerfil FROM fotosdeperfil WHERE idFotoPerfil = ? AND idUsuario = ?");
        $stmtFoto->bind_param("ii", $selected_history_avatar_id, $userId);
        $stmtFoto->execute();
        $resFoto = $stmtFoto->get_result();
        if ($resFoto && $rowFoto = $resFoto->fetch_assoc()) {
            $_SESSION['usuario']['avatar'] = $rowFoto['imagenPerfil'];
        }
        $stmtFoto->close();
    }

    // 3) Preparar update dinámico con prepared statement
    $fields = [
        'nombreUsuario' => $nombreUsuario,
        'apellidoUsuario' => $apellidoUsuario,
        'arrobaUsuario' => $arrobaUsuario,
        'apodoUsuario' => $apodoUsuario,
        'descripcionUsuario' => $descripcionUsuario,
        'correoUsuario' => $correoUsuario,
        'privacidadUsuario' => $privacidadUsuario,
    ];

    if ($newAvatarId > 0) {
        $fields['idFotoPerfilUsuario'] = $newAvatarId;
    }

    if ($new_password !== '') {
        $hashed = password_hash($new_password, PASSWORD_DEFAULT);
        $fields['contrasenaUsuario'] = $hashed;
    }

    // construir SQL (NO usamos columnas que no existen)
    $setParts = [];
    $types = '';
    $values = [];
    foreach ($fields as $col => $val) {
        $setParts[] = "`$col` = ?";
        if ($col === 'idFotoPerfilUsuario') {
            $types .= 'i';
            $values[] = (int)$val;
        } else {
            $types .= 's';
            $values[] = (string)$val;
        }
    }

    $sql = "UPDATE usuario SET " . implode(', ', $setParts) . " WHERE idUsuario = ?";
    $types .= 'i';
    $values[] = $userId;

    // preparar y bind dinámico (creamos referencias)
    $stmt = $conexion->prepare($sql);
    if (!$stmt) throw new Exception("Error preparar update usuario: " . $conexion->error);

    // preparar array params (primer elemento: tipos, luego valores)
    $params = array_merge([$types], $values);
    // crear referencias necesarias para call_user_func_array
    $refs = [];
    foreach ($params as $i => $v) {
        $refs[$i] = &$params[$i];
    }
    call_user_func_array([$stmt, 'bind_param'], $refs);

    if (!$stmt->execute()) throw new Exception("Error ejecutando update usuario: " . $stmt->error);
    $stmt->close();

    $conexion->commit();

    // actualizar sesión mínima
    $_SESSION['usuario']['nombre'] = $nombreUsuario;
    $_SESSION['usuario']['apodo'] = $apodoUsuario;
    $_SESSION['usuario']['privacidad'] = $privacidadUsuario;


    $_SESSION['message'] = "Perfil actualizado correctamente.";
    header("Location: perfil.php?id={$userId}");
    exit;
} catch (Exception $ex) {
    $conexion->rollback();
    $_SESSION['errors'] = [$ex->getMessage()];
    $_SESSION['form_data'] = $_POST;
    header('Location: editarPerfil.php');
    exit;
}
