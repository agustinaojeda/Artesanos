<?php

if (!function_exists('obtenerAvatar')) {
    /**
     * Devuelve la URL completa del avatar de un usuario.
     * Si no tiene o no existe → devuelve la imagen por defecto.
    */
    function obtenerAvatar(int $idUsuario): string {
        $basePath = $GLOBALS['basePath'] ?? '';

        // Rutas relativas
        $default = $basePath . '/assets/images/imagen.png';
        $uploadDir = $basePath . '/uploads/avatars/';

        require_once CONFIG_PATH . '/conexion.php';
        $conn = abrirConexion();
        if ($conn === false) {
            return $default; 
        }

        $sql = "
            SELECT fp.imagenPerfil
            FROM usuario u
            LEFT JOIN fotosdeperfil fp ON fp.idFotoPerfil = u.idFotoPerfilUsuario
            WHERE u.idUsuario = ? LIMIT 1
        ";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            $conn->close();
            return $default;
        }

        $stmt->bind_param("i", $idUsuario);
        $stmt->execute();
        $res = $stmt->get_result();
        $data = $res->fetch_assoc();
        $stmt->close();
        $conn->close();

        if (empty($data['imagenPerfil'])) {
            return $default;
        }

        if (preg_match('/^https?:\/\//i', $data['imagenPerfil'])) {
            return htmlspecialchars($data['imagenPerfil']);
        }

        return $uploadDir . htmlspecialchars($data['imagenPerfil']);
    }
}
