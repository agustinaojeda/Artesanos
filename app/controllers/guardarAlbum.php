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
    $publicPath = rtrim($_SERVER['DOCUMENT_ROOT'], '/\\') . (isset($GLOBALS['basePath']) ? str_replace('/', DIRECTORY_SEPARATOR, $GLOBALS['basePath']) : '');

    // Directorio destino físico
    $destDir = $publicPath . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . $carpeta;

    // Asegurar que exista
    if (!is_dir($destDir)) {
        mkdir($destDir, 0775, true);
    }

    // Nombre final 
    $nombreSeguro = preg_replace('/[^A-Za-z0-9_\-\.]+/', '_', basename($archivo['name']));
    $nombre = uniqid() . '_' . $nombreSeguro;

    $destino = $destDir . DIRECTORY_SEPARATOR . $nombre;

    // Mover y chequear errores
    if (!is_uploaded_file($archivo['tmp_name']) || !move_uploaded_file($archivo['tmp_name'], $destino)) {
        
        echo json_encode(["exito" => false, "mensaje" => "No se pudo guardar el archivo en $destino"]);
        exit;
    }

    return $nombre;
}


echo json_encode(["exito" => true, "mensaje" => "Álbum creado con éxito"]);
