<?php
// Controlador para eliminar la foto de perfil actual (poner imagen por defecto)
header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', 0);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once CONFIG_PATH . '/conexion.php';
require_once CONFIG_PATH . '/cerrarConexion.php';

try {
    if (!isset($_SESSION['usuario']['id'])) {
        throw new Exception('Sesión no iniciada');
    }

    $idUsuario = (int)$_SESSION['usuario']['id'];

    $conexion = abrirConexion();
    if ($conexion === false || $conexion->connect_error) {
        throw new Exception('Error de conexión a la base de datos');
    }

    // Poner NULL en idFotoPerfilUsuario para usar imagen por defecto
    $sql = "UPDATE usuario SET idFotoPerfilUsuario = NULL WHERE idUsuario = ?";
    $stmt = $conexion->prepare($sql);
    if (!$stmt) {
        cerrarConexion($conexion);
        throw new Exception('Error preparando consulta');
    }

    $stmt->bind_param("i", $idUsuario);
    $ok = $stmt->execute();
    $stmt->close();

    if (!$ok) {
        cerrarConexion($conexion);
        throw new Exception('Error al actualizar el perfil');
    }

    // Actualizar sesión
    if (isset($_SESSION['usuario'])) {
        $_SESSION['usuario']['avatar'] = null;
    }

    cerrarConexion($conexion);

    echo json_encode([
        'success' => true,
        'message' => 'Foto de perfil eliminada correctamente'
    ], JSON_UNESCAPED_UNICODE);
    exit;

} catch (Throwable $e) {
    error_log('[eliminarFotoPerfil] ' . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
?>

