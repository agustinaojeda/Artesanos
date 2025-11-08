<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json');

require_once CTRL_PATH . '/albumControlador.php';
require_once MODEL_PATH . '/albumModelo.php';
require_once MODEL_PATH . '/album.php';
require_once MODEL_PATH . '/imagenModelo.php';
require_once MODEL_PATH . '/imagen.php';

$controlador = new AlbumCont();

 if (!isset($_SESSION['usuario']['id'])) {
  echo json_encode(["exito" => false, "mensaje" => "Sesión no iniciada"]);
  exit;
} 

$album = new Album($_POST['tituloAlbum'], isset($_POST['esPublico']) ? intval($_POST['esPublico']) : 1, guardarArchivo($_FILES['portada'], 'portadas'), $_SESSION['usuario']['id']);
$idAlbum = $controlador->crearAlbum($album);

if (!$idAlbum) {
    echo json_encode(["exito" => false, "mensaje" => "No se pudo crear el álbum"]);
    exit;
}

// Guardar imágenes
$cantidad = intval($_POST['cantidadImagenes']);
for ($i = 0; $i < $cantidad; $i++) {
    $img = $_FILES["imagen$i"];
    $titulo = isset($_POST["tituloImagen$i"]) ? trim($_POST["tituloImagen$i"]) : '';
    $descripcion = isset($_POST["descripcionImagen$i"]) ? trim($_POST["descripcionImagen$i"]) : '';
    $etiqueta = isset($_POST["etiquetaImagen$i"]) ? trim($_POST["etiquetaImagen$i"]) : '';
    $url = guardarArchivo($img, 'imagenes');

    $controlador->guardarImagen($idAlbum, $titulo, $descripcion, $etiqueta, $url);
}

function guardarArchivo($archivo, $carpeta)
{
    $basePath = $GLOBALS['basePath'];
    $nombre = uniqid() . "_" . basename($archivo['name']);
    $ruta = $basePath . "/uploads/$carpeta/" . $nombre;
    move_uploaded_file($archivo['tmp_name'], $ruta);
    return $nombre;
}

echo json_encode(["exito" => true, "mensaje" => "Álbum creado con éxito"]);
