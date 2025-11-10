<?php
// Obtener historial de fotos de perfil del usuario actual
header('Content-Type: application/json; charset=utf-8');

require_once CONFIG_PATH . '/conexion.php';
require_once CONFIG_PATH . '/cerrarConexion.php';

if (!isset($_SESSION['usuario']['id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Usuario no autenticado']);
    exit;
}

$usuarioId = (int)$_SESSION['usuario']['id'];

$conexion = abrirConexion();
if ($conexion === false || $conexion->connect_error) {
    http_response_code(500);
    echo json_encode(['error' => 'Error de conexión']);
    exit;
}

// Obtener todas las fotos del usuario, ordenadas por fecha de creación (más recientes primero)
$sql = "
    SELECT 
        fp.idFotoPerfil,
        fp.imagenPerfil,
        fp.created_at AS fechaCreacion,
        CASE 
            WHEN u.idFotoPerfilUsuario = fp.idFotoPerfil THEN u.updated_at
            ELSE NULL
        END AS fechaUltimaVez,
        CASE 
            WHEN u.idFotoPerfilUsuario = fp.idFotoPerfil THEN 1
            ELSE 0
        END AS esActual
    FROM fotosdeperfil fp
    LEFT JOIN usuario u ON u.idUsuario = ?
    WHERE fp.idUsuario = ?
    ORDER BY fp.created_at DESC
";

$stmt = $conexion->prepare($sql);
if (!$stmt) {
    cerrarConexion($conexion);
    http_response_code(500);
    echo json_encode(['error' => 'Error preparando consulta']);
    exit;
}

$stmt->bind_param("ii", $usuarioId, $usuarioId);
$stmt->execute();
$result = $stmt->get_result();

$fotos = [];
while ($row = $result->fetch_assoc()) {
    // Si es la foto actual y tiene updated_at, usar esa fecha como última vez usada
    // Si no, usar la fecha de creación
    $fechaUltimaVez = null;
    if ($row['esActual'] && $row['fechaUltimaVez']) {
        $fechaUltimaVez = $row['fechaUltimaVez'];
    } else {
        $fechaUltimaVez = $row['fechaCreacion'];
    }

    $fotos[] = [
        'idFotoPerfil' => (int)$row['idFotoPerfil'],
        'imagenPerfil' => $row['imagenPerfil'],
        'fechaCreacion' => $row['fechaCreacion'],
        'fechaUltimaVez' => $fechaUltimaVez,
        'esActual' => (bool)$row['esActual']
    ];
}

$stmt->close();
cerrarConexion($conexion);

echo json_encode(['fotos' => $fotos], JSON_UNESCAPED_UNICODE);
?>

