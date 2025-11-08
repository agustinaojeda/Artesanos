<?php
require_once CONFIG_PATH . '/conexion.php';
require_once CONFIG_PATH . '/cerrarConexion.php';
include 'imagen.php';

// Clase modelo: contiene la lógica de acceso a datos.
// Se encarga de consultar e insertar álbumes en la base de datos.
// No representa un álbum en sí, sino operaciones sobre ellos.

class ImagenModelo
{
    public function __construct() {}

    public function crearImagen($idAlbum, $titulo, $descripcion, $etiqueta, $url)
    {
        $conexion = abrirConexion();
        $titulo_sql = empty($titulo) ? "NULL" : "'" . mysqli_real_escape_string($conexion, $titulo) . "'";
        $descripcion_sql = empty($descripcion) ? "NULL" : "'" . mysqli_real_escape_string($conexion, $descripcion) . "'";
        $etiqueta_sql = empty($etiqueta) ? "NULL" : "'" . mysqli_real_escape_string($conexion, $etiqueta) . "'";
        $url_sql = mysqli_real_escape_string($conexion, $url);
        $idAlbum = (int)$idAlbum;
        $fecha = date("Y-m-d");

        $consulta = "INSERT INTO imagen (tituloImagen, descripcionImagen, etiquetaImagen, enRevision, fechaImagen, urlImagen, idAlbumImagen) 
        VALUES ($titulo_sql, $descripcion_sql, $etiqueta_sql, 0, '$fecha', '$url_sql', $idAlbum);";

        $resultado = mysqli_query($conexion, $consulta);

        cerrarConexion($conexion);
        return $resultado ? true : false;
    }

    public function mostrarPorAlbum(Album $a)
    {
        $conexion = abrirConexion();

        $idAlbum = (int)$a->idAlbum;

        $consulta = "SELECT * FROM imagen WHERE idAlbumImagen = $idAlbum ORDER BY idImagen DESC;";
        $resultado = mysqli_query($conexion, $consulta);

        $imagenes = [];
        if (mysqli_num_rows($resultado) > 0) {
            while ($fila = mysqli_fetch_assoc($resultado)) {
                $imagen = [
                    'idImagen' => $fila['idImagen'],
                    'tituloImagen' => $fila['tituloImagen'],
                    'descripcionImagen' => $fila['descripcionImagen'],
                    'etiquetaImagen' => $fila['etiquetaImagen'],
                    'fechaImagen' => $fila['fechaImagen'],
                    'urlImagen' => $fila['urlImagen'],
                    'idAlbumImagen' => $fila['idAlbumImagen']
                ];
                $imagenes[] = $imagen;
            }
            cerrarConexion($conexion);
            return $imagenes;
        } else {
            cerrarConexion($conexion); //si hubo errores o no hay imagenes devuelve falso
            return false;
        }
    }
    // ... después de la llave de cierre de mostrarPorAlbum()...

    /**
     * Alterna (da/quita) un 'Me Gusta' y devuelve el nuevo conteo.
     * @param int $idImagen El ID de la imagen que recibe el Like.
     * @param int $idUsuario El ID del usuario que da el Like.
     * @return array Un array con 'accion' (like/dislike) y 'totalLikes'.
     */
    public function toggleLike(int $idImagen, int $idUsuario): array
    {
        $conexion = abrirConexion();
        
        // Sanear las entradas
        $idImagen = (int)$idImagen;
        $idUsuario = (int)$idUsuario;

        // 1. Verificar si el usuario ya dio like
        $consultaVerificar = "SELECT idLike FROM megusta WHERE idImagenLike = $idImagen AND idUsuarioLike = $idUsuario;";
        $resultadoVerificar = mysqli_query($conexion, $consultaVerificar);

        if (mysqli_num_rows($resultadoVerificar) > 0) {
            // Ya existe un like: quitarlo (DISLIKE)
            $consultaAccion = "DELETE FROM megusta WHERE idImagenLike = $idImagen AND idUsuarioLike = $idUsuario;";
            $accion = 'dislike';
        } else {
            // No existe un like: agregarlo (LIKE)
            $fecha = date("Y-m-d H:i:s");
            $consultaAccion = "INSERT INTO megusta (idImagenLike, idUsuarioLike, fechaLike) VALUES ($idImagen, $idUsuario, '$fecha');";
            $accion = 'like';
        }

        $resultadoAccion = mysqli_query($conexion, $consultaAccion);

        if (!$resultadoAccion) {
             // Si la acción falla, lanzamos una excepción o devolvemos un error
             cerrarConexion($conexion);
             throw new Exception("Error al procesar el like en la BD: " . mysqli_error($conexion));
        }

        // 2. Obtener el nuevo conteo de likes
        $totalLikes = $this->contarLikes($idImagen, $conexion); 

        // 3. Cerrar conexión y devolver resultado
        cerrarConexion($conexion);
        return ['accion' => $accion, 'totalLikes' => $totalLikes];
    }

    /**
     * Cuenta el número de 'Me Gusta' para una imagen específica.
     * @param int $idImagen El ID de la imagen.
     * @param object|null $conexion Conexión abierta (opcional, para uso interno).
     * @return int El número total de likes.
     */
    public function contarLikes(int $idImagen, $conexion = null): int
    {
        $cerrar = false;
        if ($conexion === null) {
            $conexion = abrirConexion();
            $cerrar = true;
        }

        $idImagen = (int)$idImagen;
        
        $consulta = "SELECT COUNT(*) as total FROM megusta WHERE idImagenLike = $idImagen;";
        $resultado = mysqli_query($conexion, $consulta);
        
        $total = 0;
        if ($resultado && $fila = mysqli_fetch_assoc($resultado)) {
            $total = (int)$fila['total'];
        }

        if ($cerrar) {
            cerrarConexion($conexion);
        }
        
        return $total;
    }
}

