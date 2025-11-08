<?php
// Clase modelo: contiene la lógica de acceso a datos.
// Se encarga de consultar e insertar comentarios en la base de datos.
// No representa un comentario en sí, sino operaciones sobre ellos.

require_once CONFIG_PATH . '/conexion.php';
require_once CONFIG_PATH . '/cerrarConexion.php';
include 'usuarioHelper.php';

class ComentarioModelo{
    public function __construct(){
    }

    public function agregarComentario($idImagen, $idUsuario, $mensaje){
        $conexion = abrirConexion();

        $idImagen = (int)$idImagen;
        $idUsuario = (int)$idUsuario;
        $mensaje = mysqli_real_escape_string($conexion, $mensaje);

        $consulta = "INSERT INTO comentario(idImagenComentario, idUsuarioComentario, mensajeComentario, fechaComentario) VALUES ($idImagen, $idUsuario, '$mensaje', NOW())";
        $resultado = mysqli_query($conexion, $consulta);

        $id = (int) mysqli_insert_id($conexion);
        cerrarConexion($conexion);

        return $resultado ? $id  : false; //devuelve el id del nuevo comentario o false
    }

    public function mostrarComentariosDeImagen($idImagen){
        $idImagen = (int)$idImagen;
        $comentarios = [];

        $conexion = abrirConexion();

        $consulta = "SELECT c.*, u.apodoUsuario, u.arrobaUsuario FROM comentario c JOIN usuario u ON u.idUsuario = c.idUsuarioComentario WHERE c.idImagenComentario = $idImagen ORDER BY c.fechaComentario ASC";
        $resultado = mysqli_query($conexion, $consulta);

        $nFilas = mysqli_num_rows($resultado);
        if($nFilas > 0){
            while($fila = mysqli_fetch_assoc($resultado)){
                $comentarios[] = [
                    'apodo' => $fila['apodoUsuario'],
                    'arroba' => $fila['arrobaUsuario'],
                    'mensaje' => $fila['mensajeComentario'],
                    'avatar' => obtenerAvatar($fila['idUsuarioComentario'])
                ];
            }

            cerrarConexion($conexion);
            return $comentarios;
        }else{
            cerrarConexion($conexion);
            return false; //no hay comentarios
        }

    }
    public function obtenerUsuario($id){
            
        $conexion = abrirConexion();

        $id = (int)$id;
        $consulta = "SELECT u.* FROM usuario u WHERE u.idUsuario = $id";
        $resultado = mysqli_query($conexion, $consulta);

        $usuario = mysqli_fetch_assoc($resultado);

        cerrarConexion($conexion);

        return $usuario;
    }
}

?>