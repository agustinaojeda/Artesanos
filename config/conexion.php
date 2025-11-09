<?php
/**
 * conexion.php — Local y Producción
 * LOCAL: host 127.0.0.1 / localhost
 * PROD:  IP del sitio: 185.27.134.126
 */

function abrirConexion(): mysqli|false
{

    $hostHeader = $_SERVER['HTTP_HOST'] ?? '';
    $isLocal = (bool)preg_match('~^(localhost|127\.0\.0\.1)(:\d+)?$~', $hostHeader);

    $configLocal = [
        'host' => '127.0.0.1',
        'user' => 'root',
        'pass' => '',
        'db'   => 'artesanos',
        'port' => 3306,
    ];

    $configProd = [
        'host' => 'sql300.infinityfree.com',
        'user' => 'if0_40369342',
        'pass' => 'tyVjMGEbb1UxOr',
        'db'   => 'if0_40369342_artesanos',
        'port' => 3306,
    ];

    $cfg = $isLocal ? $configLocal : $configProd;

    // Conectar
    $mysqli = @new mysqli($cfg['host'], $cfg['user'], $cfg['pass'], $cfg['db'], $cfg['port']);
    if ($mysqli->connect_error) {
        error_log('[DB] Conexión fallida (' . ($isLocal ? 'local' : 'prod') . '): ' . $mysqli->connect_error);
        return false;
    }

    // Charset y modo
    if (!$mysqli->set_charset('utf8mb4')) {
        error_log('[DB] No se pudo establecer utf8mb4: ' . $mysqli->error);
    }
    @$mysqli->query("SET SESSION sql_mode = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION'");

    return $mysqli;
}

?>