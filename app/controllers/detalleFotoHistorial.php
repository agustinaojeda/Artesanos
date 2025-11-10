<?php
// Obtener detalles de una foto específica del historial
header('Content-Type: application/json; charset=utf-8');

require_once CONFIG_PATH . '/conexion.php';
require_once CONFIG_PATH . '/cerrarConexion.php';

if (!isset($_SESSION['usuario']['id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Usuario no autenticado']);
    exit;
}

$usuarioId = (int)$_SESSION['usuario']['id'];
$idFoto = isset($_GET['idFoto']) ? (int)$_GET['idFoto'] : 0;

if ($idFoto === 0) {
    http_response_code(400);
    echo json_encode(['error' => 'ID de foto inválido']);
    exit;
}

$conexion = abrirConexion();
if ($conexion === false || $conexion->connect_error) {
    http_response_code(500);
    echo json_encode(['error' => 'Error de conexión']);
    exit;
}

// Verificar que la foto pertenece al usuario
$sql = "
    SELECT 
        fp.idFotoPerfil,
        fp.imagenPerfil,
        fp.created_at AS fechaCreacion,
        CASE 
            WHEN u.idFotoPerfilUsuario = fp.idFotoPerfil THEN u.updated_at
            ELSE NULL
        END AS fechaUltimaVez
    FROM fotosdeperfil fp
    LEFT JOIN usuario u ON u.idUsuario = ?
    WHERE fp.idFotoPerfil = ? AND fp.idUsuario = ?
    LIMIT 1
";

$stmt = $conexion->prepare($sql);
if (!$stmt) {
    cerrarConexion($conexion);
    http_response_code(500);
    echo json_encode(['error' => 'Error preparando consulta']);
    exit;
}

$stmt->bind_param("iii", $usuarioId, $idFoto, $usuarioId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $stmt->close();
    cerrarConexion($conexion);
    http_response_code(404);
    echo json_encode(['error' => 'Foto no encontrada']);
    exit;
}

$row = $result->fetch_assoc();

// Determinar fecha de última vez usada
$fechaUltimaVez = $row['fechaUltimaVez'] ?? $row['fechaCreacion'];

$stmt->close();
cerrarConexion($conexion);

echo json_encode([
    'idFotoPerfil' => (int)$row['idFotoPerfil'],
    'imagenPerfil' => $row['imagenPerfil'],
    'fechaCreacion' => $row['fechaCreacion'],
    'fechaUltimaVez' => $fechaUltimaVez
], JSON_UNESCAPED_UNICODE);
?>

