<?php
session_start();
require_once '../../config/conexion.php';
require_once '../../config/cerrarConexion.php';

$conexion = abrirConexion();

$idUsuario = $_SESSION['usuario']['id'] ?? 0;
if ($idUsuario === 0) {
    echo '<div class="alert alert-warning">Iniciá sesión para ver tus notificaciones.</div>';
    exit;
}

$sql = "SELECT n.*, u.nombreUsuario, u.arrobaUsuario
        FROM notificaciones n
        JOIN usuario u ON n.idUsuarioAccion = u.idUsuario
        WHERE n.idUsuarioDestino = ?
        ORDER BY n.fecha DESC";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("i", $idUsuario);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo '<div class="list-group list-group-flush">';

    while ($row = $result->fetch_assoc()) {

        // Íconos según tipo
        switch ($row['tipo']) {
            case 'solicitud_seguir':
                $icon = '<i class="bi bi-person-plus-fill text-warning fs-5"></i>';
                break;

            case 'aceptar_seguimiento':
                $icon = '<i class="bi bi-person-check-fill text-success fs-5"></i>';
                break;

            case 'rechazo_seguimiento':
                $icon = '<i class="bi bi-person-dash-fill text-danger fs-5"></i>';
                break;

            case 'respuesta_seguimiento': // ← para rechazos (coincide con responderSolicitud.php)
                $icon = '<i class="bi bi-person-dash-fill text-danger fs-5"></i>';
                break;

            case 'album_nuevo':
                $icon = '<i class="bi bi-images text-success fs-5"></i>';
                break;
            case 'like':
                $icon = '<i class="bi bi-heart-fill text-danger fs-5"></i>';
                break;
            case 'comentario':
                $icon = '<i class="bi bi-chat-left-text-fill text-warning fs-5"></i>';
                break;
            default:
                $icon = '<i class="bi bi-bell-fill text-secondary fs-5"></i>';
        }

         // Construir mensaje
        $mensajeAMostrar = htmlspecialchars($row['mensaje']);

        // Si es respuesta de seguimiento, agregamos enlace al perfil
        if ($row['tipo'] === 'aceptar_seguimiento') {
            $perfilUrl = "perfil.php?id=" . $row['idUsuarioAccion'];
            $mensajeAMostrar .= " <a href='$perfilUrl' class='text-decoration-none'>Ver perfil</a>";
           
        }

        echo '
        <div class="list-group-item d-flex align-items-start gap-3 border-0 ' .
            ($row['leida'] ? 'bg-white' : 'bg-light') . ' rounded-3 mb-1 p-3">
            ' . $icon . '
            <div class="flex-grow-1">
                <div class="fw-semibold">
                    @' . htmlspecialchars($row['arrobaUsuario']) . '
                    <span class="text-muted small d-block mt-1">' . $mensajeAMostrar . '</span>

                </div>
                <div class="text-muted small mt-1">' . date("d/m/Y H:i", strtotime($row['fecha'])) . '</div>
            </div>';

        // 🔹 Si es una solicitud, muestra botones
        if ($row['tipo'] === 'solicitud_seguir') {
            echo '
            <div class="ms-auto">
                <button class="btn btn-success btn-sm aceptar-seguimiento btn-aceptar" data-idSeguidor="' . $row['idUsuarioAccion'] . '">Aceptar</button>
                <button class="btn btn-danger btn-sm rechazar-seguimiento btn-rechazar" data-idSeguidor="' . $row['idUsuarioAccion'] . '">Rechazar</button>
            </div>';
        }

        echo '</div>';
    }

    echo '</div>';

    // ✅ Marcar como leídas
    $update = $conexion->prepare("UPDATE notificaciones SET leida = 1 WHERE idUsuarioDestino = ?");
    $update->bind_param("i", $idUsuario);
    $update->execute();

} else {
    echo '<div class="alert alert-secondary text-center">No tenés notificaciones aún 😊</div>';
}

cerrarConexion($conexion);
?>
<style>
.list-group-item {
  transition: background-color 0.2s ease, transform 0.1s ease;
  border: none;
  border-radius: 8px;
  margin-bottom: 6px;
}


.list-group-item:hover {
  background-color: #f0f4ff !important;
  transform: translateY(-1px);
}


.list-group-item.bg-light {
  background-color: #fff3cd  !important; /* notificación nueva */
  border-left: 4px solid #ff9800;
}


.list-group-item.bg-white {
  background-color: #fff !important; /* leída */
}


.list-group-item-action {
  text-decoration: none;
  color: #333;
}
.list-group-item-action strong {
  color: #ff9800;
}


.btn-sm {
  border-radius: 8px;
  padding: 2px 8px;
}
.btn-success {
  background-color: #28a745;
  border: none;
}
.btn-danger {
  background-color: #dc3545;
  border: none;
}


</style>



