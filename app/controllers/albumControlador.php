<?php

// Clase controlador: coordina las acciones entre la vista y el modelo.
// Recibe solicitudes, llama al modelo correspondiente y devuelve los datos.
// No contiene lógica de negocio ni acceso directo a la base de datos.

require_once MODEL_PATH . '/albumModelo.php';
require_once MODEL_PATH . '/imagenModelo.php';

class AlbumCont{
    private $albumModelo;

    public function __construct(){
        $this->albumModelo = new AlbumModelo();
    }

    public function mostrarTodos(){
        return $this->albumModelo->mostrarTodos();
    }

    public function mostrarAlbumes($id){
        return $this->albumModelo->mostrarAptos($id);
    }

    public function crearAlbum(Album $album){
        return $this->albumModelo->crearAlbum($album);
    }

    public function guardarImagen($idAlbum, $titulo, $descripcion, $etiqueta, $url){
        $imagenModelo = new ImagenModelo();
        return $imagenModelo->crearImagen($idAlbum, $titulo, $descripcion, $etiqueta, $url);
    }
}
?>