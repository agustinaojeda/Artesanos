<?php

// Clase entidad: representa un álbum como objeto.
// No accede directamente a la base de datos, solo modela los datos :p

class Album{
    private $idAlbum, $tituloAlbum, $esPublico, $urlPortada, $idUsuario, $apodoUsuario, $arrobaUsuario, $fechaCreacion;
    public function __construct($tituloAlbum, $esPublico, $urlPortada, $idUsuario){
        
        $this->tituloAlbum = $tituloAlbum;
        $this->esPublico = $esPublico;
        $this->urlPortada = $urlPortada;
        $this->idUsuario = (int)$idUsuario;
    }
    public function setUsuario($apodo, $arroba){
        $this->apodoUsuario = $apodo;
        $this->arrobaUsuario = $arroba;
    }

    public function __get($variable){
        return $this->$variable;
    }
    public function __set($variable,$valor){
        $this->$variable = $valor;
    }

}


?>