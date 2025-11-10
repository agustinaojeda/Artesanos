<?php
// Controlador para eliminar una foto del historial
header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', 0);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once CONFIG_PATH . '/conexion.php';
require_once CONFIG_PATH . '/cerrarConexion.php';

$publicPath = rtrim(BASE_PATH, '/\\') . '/public';

try {
    if (!isset($_POST['idFotoPerfil']) || !isset($_SESSION['usuario']['id'])) {
        throw new Exception('Datos incompletos o sesión no iniciada');
    }

    $idFotoPerfil = (int)$_POST['idFotoPerfil'];
    $idUsuario = (int)$_SESSION['usuario']['id'];

    if ($idFotoPerfil <= 0) {
        throw new Exception('ID de foto inválido');
    }

    $conexion = abrirConexion();
    if ($conexion === false || $conexion->connect_error) {
        throw new Exception('Error de conexión a la base de datos');
    }

    // Verificar que la foto pertenece al usuario y obtener el nombre del archivo
    $sql = "SELECT imagenPerfil, idUsuario FROM fotosdeperfil WHERE idFotoPerfil = ? AND idUsuario = ? LIMIT 1";
    $stmt = $conexion->prepare($sql);
    if (!$stmt) {
        cerrarConexion($conexion);
        throw new Exception('Error preparando consulta');
    }

    $stmt->bind_param("ii", $idFotoPerfil, $idUsuario);
    $stmt->execute();
    $result = $stmt->get_result();
    $foto = $result->fetch_assoc();
    $stmt->close();

    if (!$foto) {
        cerrarConexion($conexion);
        throw new Exception('Foto no encontrada o no tienes permiso para eliminarla');
    }

    // Verificar si es la foto actual del usuario
    $sqlCheck = "SELECT idFotoPerfilUsuario FROM usuario WHERE idUsuario = ? LIMIT 1";
    $stmtCheck = $conexion->prepare($sqlCheck);
    $stmtCheck->bind_param("i", $idUsuario);
    $stmtCheck->execute();
    $resCheck = $stmtCheck->get_result();
    $usuarioData = $resCheck->fetch_assoc();
    $stmtCheck->close();

    $esFotoActual = ($usuarioData && $usuarioData['idFotoPerfilUsuario'] == $idFotoPerfil);

    if ($esFotoActual) {
        cerrarConexion($conexion);
        throw new Exception('No puedes eliminar tu foto de perfil actual. Primero elimínala desde el botón "Eliminar foto de perfil"');
    }

    // Eliminar el archivo físico
    $imagenPerfil = $foto['imagenPerfil'];
    if ($imagenPerfil) {
        $rutaArchivo = $publicPath . '/uploads/avatars/' . basename($imagenPerfil);
        if (is_file($rutaArchivo)) {
            @unlink($rutaArchivo);
        }
    }

    // Eliminar de la base de datos
    $sqlDelete = "DELETE FROM fotosdeperfil WHERE idFotoPerfil = ? AND idUsuario = ?";
    $stmtDelete = $conexion->prepare($sqlDelete);
    if (!$stmtDelete) {
        cerrarConexion($conexion);
        throw new Exception('Error preparando consulta de eliminación');
    }

    $stmtDelete->bind_param("ii", $idFotoPerfil, $idUsuario);
    $ok = $stmtDelete->execute();
    $stmtDelete->close();
    cerrarConexion($conexion);

    if (!$ok) {
        throw new Exception('Error al eliminar la foto del historial');
    }

    echo json_encode([
        'success' => true,
        'message' => 'Foto eliminada del historial correctamente'
    ], JSON_UNESCAPED_UNICODE);
    exit;

} catch (Throwable $e) {
    error_log('[eliminarFotoHistorial] ' . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
?>

