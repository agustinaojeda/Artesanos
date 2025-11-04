<?php
require_once '../../config/conexion.php';
require_once '../../config/cerrarConexion.php';
include 'album.php';

// Clase modelo: contiene la lógica de acceso a datos.
// Se encarga de consultar e insertar álbumes en la base de datos.
// No representa un álbum en sí, sino operaciones sobre ellos.

class AlbumModelo
{
    public function __construct() {}


    public function agregarDatosUsuario(Album $a)
    { //agrega los datos del usuario al album q le pasen
        $conexion = abrirConexion();

        $idAlbum = (int)$a->idAlbum;

        $consulta = "SELECT u.apodoUsuario, u.arrobaUsuario FROM album a JOIN usuario u ON a.idUsuarioAlbum = u.idUsuario WHERE a.idAlbum = $idAlbum";

        $resultado = mysqli_query($conexion, $consulta);

        $nfilas = mysqli_num_rows($resultado);

        if ($nfilas > 0) {
            $fila = mysqli_fetch_assoc($resultado);
            $a->setUsuario($fila['apodoUsuario'], $fila['arrobaUsuario']);
        }

        cerrarConexion($conexion);
        return $a;
    }

    public function crearAlbum(Album $album)
    { //inserta el album a la bd
        $conexion = abrirConexion();

        $titulo = mysqli_real_escape_string($conexion, $album->tituloAlbum); //hace que no se rompa la consulta sql si meten algun caracter especial
        $urlPortada = mysqli_real_escape_string($conexion, $album->urlPortada);
        $esPublico = $album->esPublico ? 1 : 0;
        $idUsuario = (int)$album->idUsuario;

        $consulta = "INSERT INTO album (tituloAlbum, esPublicoAlbum, urlPortadaAlbum, idUsuarioAlbum) VALUES ('$titulo', $esPublico, '$urlPortada', $idUsuario)";

        $resultado = mysqli_query($conexion, $consulta);

        $insertId = false;
        if ($resultado) {
            $insertId = (int) mysqli_insert_id($conexion);
            $album->idAlbum = $insertId; 
            $this->agregarDatosUsuario($album);
        }

        cerrarConexion($conexion);

        // si pudo crear el album retorna el id para poder usarlo en imagen
        return $resultado ? $insertId : false;
    }

    public function mostrarTodos()
    { //devuelve todos los albumes publicos y privados
        $conexion = abrirConexion();

        $consulta = "SELECT a.idAlbum, a.tituloAlbum, a.urlPortadaAlbum, a.esPublicoAlbum, u.apodoUsuario, u.arrobaUsuario, u.idUsuario FROM album a JOIN usuario u ON a.idUsuarioAlbum = u.idUsuario ORDER BY a.idAlbum DESC";
        $resultado = mysqli_query($conexion, $consulta);

        $albumes = [];
        $nfilas = mysqli_num_rows($resultado);
        if ($nfilas > 0) {
            while ($fila = mysqli_fetch_assoc($resultado)) {
                $album = new Album($fila['tituloAlbum'], (bool)$fila['esPublicoAlbum'], $fila['urlPortadaAlbum'], $fila['idUsuario']);
                $album->idAlbum = $fila['idAlbum'];
                $album->setUsuario($fila['apodoUsuario'], $fila['arrobaUsuario']);

                $albumes[] = $album;
            }

            cerrarConexion($conexion);
            return $albumes;
        } else { //si no hay albumes creados retorna falso
            cerrarConexion($conexion);
            return false;
        }
    }

    public function mostrarAptos($idUsuarioActual)
    { //devuelve todos los albumes que puede ver el usuario actual (publicos y privados de usuarios que sigue)
        $conexion = abrirConexion();

        $consulta = "SELECT a.idAlbum, a.tituloAlbum, a.esPublicoAlbum, a.urlPortadaAlbum,
                    u.idUsuario, u.apodoUsuario, u.arrobaUsuario,
                    i.idImagen AS idImagenPortada
             FROM album a
             JOIN usuario u ON a.idUsuarioAlbum = u.idUsuario
             LEFT JOIN imagen i ON i.idAlbumImagen = a.idAlbum
             LEFT JOIN seguimiento s ON s.idSeguido = u.idUsuario
             WHERE a.esPublicoAlbum = 1
             OR (s.idSeguidor = $idUsuarioActual AND s.estadoSeguimiento = 'seguido')
             GROUP BY a.idAlbum";

        $resultado = mysqli_query($conexion, $consulta);
        $nfilas = mysqli_num_rows($resultado);

        $albumes = [];
        if ($nfilas > 0) {
            while ($fila = mysqli_fetch_assoc($resultado)) {
                $album = new Album($fila['tituloAlbum'], (bool)$fila['esPublicoAlbum'], $fila['urlPortadaAlbum'], $fila['idUsuario']);
                $album->idAlbum = $fila['idAlbum'];
                $album->setUsuario($fila['apodoUsuario'], $fila['arrobaUsuario']);

                $albumes[] = $album;
            }

            cerrarConexion($conexion);
            return $albumes;
        } else {
            cerrarConexion($conexion);
            return false;
        }
    }

    public function mostrarAlbumId($id){
        $conexion = abrirConexion();

        $idAlbum = (int)$id;

        $consulta = "SELECT a.idAlbum, a.tituloAlbum, a.esPublicoAlbum, a.urlPortadaAlbum, a.idUsuarioAlbum,
         a.fechaCreacionAlbum, u.apodoUsuario, u.arrobaUsuario
         FROM album a
         JOIN usuario u ON a.idUsuarioAlbum = u.idUsuario
         WHERE a.idAlbum = $idAlbum";

        $resultado = mysqli_query($conexion, $consulta);

        $nfilas = mysqli_num_rows($resultado);
        if ($nfilas > 0) {
            $fila = mysqli_fetch_assoc($resultado);
            $album = new Album($fila['tituloAlbum'], (bool)$fila['esPublicoAlbum'], $fila['urlPortadaAlbum'], $fila['idUsuarioAlbum']);
            $album->idAlbum = $fila['idAlbum'];
            $album->setUsuario($fila['apodoUsuario'], $fila['arrobaUsuario']);
            $album->fechaCreacion = $fila['fechaCreacionAlbum'];

            cerrarConexion($conexion);
            return $album;
        } else {
            cerrarConexion($conexion);
            return false;
        }
    }

    public function contarAlbumesDeUsuario($idUsuario){
        $conexion = abrirConexion();

        $idUsuario = (int)$idUsuario;

        $consulta = "SELECT COUNT(*) AS total FROM album WHERE idUsuarioAlbum = $idUsuario";

        $resultado = mysqli_query($conexion, $consulta);

        $nfilas = mysqli_num_rows($resultado);
        $total = 0;
        if ($nfilas > 0) {
            $fila = mysqli_fetch_assoc($resultado);
            $total = (int)$fila['total'];
        }

        cerrarConexion($conexion);
        return $total;
    }

    public function obtenerUsuarioDe($idAlbum){
        $conexion = abrirConexion();

        $idAlbum = (int)$idAlbum;

        $consulta = "SELECT u.idUsuario, u.apodoUsuario, u.arrobaUsuario, u.IdFotoPerfilUsuario FROM album a JOIN usuario u ON a.idUsuarioAlbum = u.idUsuario WHERE a.idAlbum = $idAlbum";

        $resultado = mysqli_query($conexion, $consulta);

        $nfilas = mysqli_num_rows($resultado);
        $usuario = null;
        if ($nfilas > 0) {
            $fila = mysqli_fetch_assoc($resultado);
            $usuario = [
                'apodo' => $fila['apodoUsuario'],
                'arroba' => $fila['arrobaUsuario'],
                'fotoPerfil' => $fila['IdFotoPerfilUsuario'],
                'idUsuario' => $fila['idUsuario']
            ];
        }

        cerrarConexion($conexion);
        return $usuario;
    }

    public function contarSeguidoresDeUsuario($idUsuario){
        $conexion = abrirConexion();

        $idUsuario = (int)$idUsuario;

        $consulta = "SELECT COUNT(*) AS total FROM seguimiento WHERE idSeguido = $idUsuario AND estadoSeguimiento = 'activo'";

        $resultado = mysqli_query($conexion, $consulta);

        $nfilas = mysqli_num_rows($resultado);
        $total = 0;
        if ($nfilas > 0) {
            $fila = mysqli_fetch_assoc($resultado);
            $total = (int)$fila['total'];
        }

        cerrarConexion($conexion);
        return $total;
    }

    public function toggleLikeAlbum(int $idAlbum, int $idUsuario): array
    {
        $conexion = abrirConexion();

        $idAlbum = (int)$idAlbum;
        $idUsuario = (int)$idUsuario;

        $consultaVerificar = "SELECT idLikeAlbum FROM megusta_album WHERE idAlbumLike = $idAlbum AND idUsuarioLike = $idUsuario;";
        $resultadoVerificar = mysqli_query($conexion, $consultaVerificar);

        if (mysqli_num_rows($resultadoVerificar) > 0) {
            $consultaAccion = "DELETE FROM megusta_album WHERE idAlbumLike = $idAlbum AND idUsuarioLike = $idUsuario;";
            $accion = 'dislike';
        } else {
            $fecha = date("Y-m-d H:i:s");
            $consultaAccion = "INSERT INTO megusta_album (idAlbumLike, idUsuarioLike, fechaLike) VALUES ($idAlbum, $idUsuario, '$fecha');";
            $accion = 'like';
        }

        $resultadoAccion = mysqli_query($conexion, $consultaAccion);
        if (!$resultadoAccion) {
            cerrarConexion($conexion);
            throw new Exception("Error al procesar el like de álbum en la BD: " . mysqli_error($conexion));
        }

        $totalLikes = $this->contarLikesAlbum($idAlbum);

        cerrarConexion($conexion);
        return ['accion' => $accion, 'totalLikes' => $totalLikes];
    }

    public function contarLikesAlbum(int $idAlbum): int
    {
        $conexion = abrirConexion();
        $idAlbum = (int)$idAlbum;

        $consulta = "SELECT COUNT(*) as total FROM megusta_album WHERE idAlbumLike = $idAlbum;";
        $resultado = mysqli_query($conexion, $consulta);

        $total = 0;
        if ($resultado && $fila = mysqli_fetch_assoc($resultado)) {
            $total = (int)$fila['total'];
        }

        cerrarConexion($conexion);
        return $total;
    }
}

