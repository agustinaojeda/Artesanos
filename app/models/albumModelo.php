<?php
require_once CONFIG_PATH . '/conexion.php';
require_once CONFIG_PATH . '/cerrarConexion.php';
include 'album.php';

// Clase modelo: contiene la lógica de acceso a los datos.
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
    public function actualizarAlbum($idAlbum, $titulo, $esPublico, $nuevaPortada = null) {
        $conexion = abrirConexion();

        $sql = "UPDATE album SET tituloAlbum = ?, esPublicoAlbum = ?";
        if ($nuevaPortada !== null) {
            $sql .= ", urlPortadaAlbum = ?";
        }
        $sql .= " WHERE idAlbum = ?";

        $stmt = $conexion->prepare($sql);
        if (!$stmt) {
            cerrarConexion($conexion);
            return false;
        }

        $esPublicoInt = (int)$esPublico;
        $idAlbumInt   = (int)$idAlbum;

        if ($nuevaPortada !== null) {
            // s i s i  => string, int, string, int
            $stmt->bind_param("sisi", $titulo, $esPublicoInt, $nuevaPortada, $idAlbumInt);
        } else {
            // s i i    => string, int, int
            $stmt->bind_param("sii", $titulo, $esPublicoInt, $idAlbumInt);
        }

        $ok = $stmt->execute();
        $stmt->close();
        cerrarConexion($conexion);
        return (bool)$ok;
    }

    /**
     * Elimina solo la portada de un álbum (pone imagen.png por defecto)
     * @param int $idAlbum El ID del álbum
     * @return bool True si se actualizó correctamente
     */
    public function eliminarPortadaAlbum(int $idAlbum): bool
    {
        $conexion = abrirConexion();
        
        // Poner imagen.png por defecto en lugar de NULL
        $imagenDefault = 'imagen.png';
        $sql = "UPDATE album SET urlPortadaAlbum = ? WHERE idAlbum = ?";
        $stmt = $conexion->prepare($sql);
        if (!$stmt) {
            cerrarConexion($conexion);
            return false;
        }
        
        $idAlbumInt = (int)$idAlbum;
        $stmt->bind_param("si", $imagenDefault, $idAlbumInt);
        $ok = $stmt->execute();
        $stmt->close();
        cerrarConexion($conexion);
        
        return (bool)$ok;
    }
    public function getPortadaActual(int $idAlbum, ?int $idUsuario = null): ?string
    {
        $cn = abrirConexion();

        if ($idUsuario !== null) {
            $sql = "SELECT urlPortadaAlbum 
                    FROM album 
                    WHERE idAlbum = ? AND idUsuarioAlbum = ?
                    LIMIT 1";
            $stmt = $cn->prepare($sql);
            if (!$stmt) { cerrarConexion($cn); return null; }
            $stmt->bind_param("ii", $idAlbum, $idUsuario);
        } else {
            $sql = "SELECT urlPortadaAlbum 
                    FROM album 
                    WHERE idAlbum = ?
                    LIMIT 1";
            $stmt = $cn->prepare($sql);
            if (!$stmt) { cerrarConexion($cn); return null; }
            $stmt->bind_param("i", $idAlbum);
        }

        $stmt->execute();
        $stmt->bind_result($portada);
        $stmt->fetch();
        $stmt->close();
        cerrarConexion($cn);

        if (!$portada) return null;

        $basename = basename($portada);
        return $basename !== '' ? $basename : null;
    }

    public function listarImagenesDeAlbum(int $idAlbum): array
    {
        $cn = abrirConexion();

        $sql = "SELECT 
                    idImagen,
                    tituloImagen,
                    descripcionImagen,
                    urlImagen,
                    idAlbumImagen
                FROM imagen
                WHERE idAlbumImagen = ?
                ORDER BY idImagen DESC";

        $stmt = $cn->prepare($sql);
        if (!$stmt) { 
            cerrarConexion($cn); 
            return []; 
        }
        $stmt->bind_param("i", $idAlbum);

        $ok = $stmt->execute();
        if (!$ok) {
            $stmt->close();
            cerrarConexion($cn);
            return [];
        }

        // Soporta entornos SIN mysqlnd (sin get_result)
        $rows = [];
        if (method_exists($stmt, 'get_result')) {
            $res = $stmt->get_result();
            if ($res) {
                $rows = $res->fetch_all(MYSQLI_ASSOC);
            } else {
                // Fallback manual
                $stmt->store_result();
                $idImagen = $tituloImagen = $descripcionImagen = $urlImagen = null; $idAlbumImagen = 0;
                $stmt->bind_result($idImagen, $tituloImagen, $descripcionImagen, $urlImagen, $idAlbumImagen);
                while ($stmt->fetch()) {
                    $rows[] = [
                        'idImagen'         => (int)$idImagen,
                        'tituloImagen'     => (string)$tituloImagen,
                        'descripcionImagen'=> (string)$descripcionImagen,
                        'urlImagen'        => (string)$urlImagen,
                        'idAlbumImagen'    => (int)$idAlbumImagen,
                    ];
                }
            }
        } else {
            // Fallback manual
            $stmt->store_result();
            $idImagen = $tituloImagen = $descripcionImagen = $urlImagen = null; $idAlbumImagen = 0;
            $stmt->bind_result($idImagen, $tituloImagen, $descripcionImagen, $urlImagen, $idAlbumImagen);
            while ($stmt->fetch()) {
                $rows[] = [
                    'idImagen'         => (int)$idImagen,
                    'tituloImagen'     => (string)$tituloImagen,
                    'descripcionImagen'=> (string)$descripcionImagen,
                    'urlImagen'        => (string)$urlImagen,
                    'idAlbumImagen'    => (int)$idAlbumImagen,
                ];
            }
        }

        $stmt->close();
        cerrarConexion($cn);

        // Normaliza nombre de archivo por si se guardo la ruta completa
        foreach ($rows as &$r) {
            $r['urlImagen'] = basename((string)$r['urlImagen']);
        }
        return $rows;
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
            $this->enviarNotificacion($album->idAlbum,$idUsuario);
        }

        cerrarConexion($conexion);

        // si pudo crear el album retorna el id para poder usarlo en imagen
        return $resultado ? $insertId : false;
    }
   public function enviarNotificacion($idAlbum, $idUsuarioAlbum){
    $conexion = abrirConexion();
    $sqlTitulo = "SELECT tituloAlbum FROM album WHERE idAlbum = ?"; // Obtener el título del álbum
    $stmt1 = $conexion->prepare($sqlTitulo);
    $stmt1->bind_param("i", $idAlbum);
    $stmt1->execute();
    $tituloAlbum = $stmt1->get_result()->fetch_assoc()['tituloAlbum'];
    $stmt1->close();

    $sqlSeguidores = "SELECT idSeguidor FROM seguimiento WHERE idSeguido = ? AND estadoSeguimiento = 'activo'";
    $stmt2 = $conexion->prepare($sqlSeguidores);
    $stmt2->bind_param("i", $idUsuarioAlbum);
    $stmt2->execute();
    $result = $stmt2->get_result();
    $mensaje = "creó el nuevo álbum '$tituloAlbum'.";
    $tipo = "album_nuevo";

    while ($row = $result->fetch_assoc()) {
        $idSeguidor = $row['idSeguidor'];
        $sqlNotif = "INSERT INTO notificaciones (idUsuarioDestino, idUsuarioAccion, tipo, mensaje, leida, fecha)
                     VALUES (?, ?, ?, ?, 0, NOW())";
        $stmt3 = $conexion->prepare($sqlNotif);
        $stmt3->bind_param("iiss", $idSeguidor, $idUsuarioAlbum, $tipo, $mensaje);
        $stmt3->execute();
    }
    cerrarConexion($conexion);

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

    public function obtenerDatosUsuarioPorId($idUsuario){
        $conexion = abrirConexion();

        $idUsuario = (int)$idUsuario;

        $consulta = "SELECT u.idUsuario, u.apodoUsuario, u.arrobaUsuario, u.IdFotoPerfilUsuario FROM usuario u WHERE u.idUsuario = $idUsuario LIMIT 1";

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

        // Enviar notificación cuando se da like (no cuando se quita)
        if ($accion === 'like') {
            $this->enviarNotificacionLikeAlbum($idAlbum, $idUsuario, $conexion);
        }

        $totalLikes = $this->contarLikesAlbum($idAlbum);

        cerrarConexion($conexion);
        return ['accion' => $accion, 'totalLikes' => $totalLikes];
    }

    private function enviarNotificacionLikeAlbum(int $idAlbum, int $idUsuarioLike, $conexion)
    {
        // Obtener el propietario del álbum y su título
        $sqlAlbum = "SELECT a.idUsuarioAlbum, a.tituloAlbum 
                     FROM album a 
                     WHERE a.idAlbum = ?";
        $stmt1 = $conexion->prepare($sqlAlbum);
        $stmt1->bind_param("i", $idAlbum);
        $stmt1->execute();
        $result = $stmt1->get_result();
        $row = $result->fetch_assoc();
        $stmt1->close();

        if ($row) {
            $idUsuarioDestino = $row['idUsuarioAlbum'];
            $tituloAlbum = $row['tituloAlbum'];

            // Solo enviar notificación si el usuario que da like no es el propietario del álbum
            if ($idUsuarioDestino != $idUsuarioLike) {
                $mensaje = "le dio like a tu álbum '$tituloAlbum'";
                $tipo = "like";

                $sqlNotif = "INSERT INTO notificaciones (idUsuarioDestino, idUsuarioAccion, tipo, mensaje, leida, fecha)
                             VALUES (?, ?, ?, ?, 0, NOW())";
                $stmt2 = $conexion->prepare($sqlNotif);
                $stmt2->bind_param("iiss", $idUsuarioDestino, $idUsuarioLike, $tipo, $mensaje);
                $stmt2->execute();
                $stmt2->close();
            }
        }
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
        public function esPropietarioDelAlbum($idAlbum, $idUsuario) {
        $conexion = abrirConexion();

        $sql = "SELECT COUNT(*) FROM album WHERE idAlbum = ? AND idUsuarioAlbum = ?";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("ii", $idAlbum, $idUsuario);
        $stmt->execute();
        $stmt->bind_result($count);
        $stmt->fetch();
        cerrarConexion($conexion);

        return $count > 0;
    }

    public function eliminarAlbum($idAlbum) {
        $conexion = abrirConexion();

        // Antes de eliminar álbum, eliminamos sus imágenes
        $sqlImgs = "DELETE FROM imagen WHERE idAlbumImagen = ?";
        $stmtImgs = $conexion->prepare($sqlImgs);
        $stmtImgs->bind_param("i", $idAlbum);
        $stmtImgs->execute();

        // Ahora sí eliminamos el álbum
        $sql = "DELETE FROM album WHERE idAlbum = ?";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("i", $idAlbum);
        $ok = $stmt->execute();

        cerrarConexion($conexion);
        return $ok;
    }

    /**
     * Obtiene todas las imágenes likeadas (portadas de álbumes + imágenes individuales) 
     * de un usuario específico, agrupadas por el usuario que las creó
     * @param int $idUsuarioLogueado El ID del usuario que dio los likes
     * @param int $idUsuarioSeguido El ID del usuario del que se quieren ver las imágenes likeadas
     * @return array Array con todas las imágenes (portadas como imágenes virtuales + imágenes reales)
     */
    public function obtenerTodasImagenesLikeadas(int $idUsuarioLogueado, int $idUsuarioSeguido): array
    {
        $conexion = abrirConexion();
        $imagenes = [];

        // 1. Obtener portadas de álbumes a los que dio like
        $sqlPortadas = "
            SELECT 
                a.idAlbum,
                a.tituloAlbum AS nombreAlbum,
                a.urlPortadaAlbum AS urlImagen,
                a.fechaCreacionAlbum AS fechaImagen,
                NULL AS tituloImagen,
                NULL AS descripcionImagen,
                a.idAlbum AS idAlbumImagen,
                'portada' AS tipo
            FROM megusta_album ma
            INNER JOIN album a ON a.idAlbum = ma.idAlbumLike
            WHERE ma.idUsuarioLike = ? 
            AND a.idUsuarioAlbum = ?
            ORDER BY ma.fechaLike DESC
        ";
        $stmtPortadas = $conexion->prepare($sqlPortadas);
        if ($stmtPortadas) {
            $stmtPortadas->bind_param("ii", $idUsuarioLogueado, $idUsuarioSeguido);
            $stmtPortadas->execute();
            $resPortadas = $stmtPortadas->get_result();
            while ($portada = $resPortadas->fetch_assoc()) {
                $urlImagen = $portada['urlImagen'] ?? '';
                // Si es imagen.png o está vacío, usar el valor tal cual
                if (empty($urlImagen) || $urlImagen === 'imagen.png') {
                    $urlImagen = 'imagen.png';
                } else {
                    $urlImagen = basename($urlImagen);
                }
                
                $imagenes[] = [
                    'idImagen' => 'portada_' . $portada['idAlbum'],
                    'tituloImagen' => $portada['tituloImagen'] ?? '',
                    'descripcionImagen' => $portada['descripcionImagen'] ?? '',
                    'urlImagen' => $urlImagen,
                    'idAlbumImagen' => (int)$portada['idAlbum'],
                    'nombreAlbum' => $portada['nombreAlbum'] ?? '',
                    'fechaImagen' => $portada['fechaImagen'] ?? '',
                    'tipo' => 'portada',
                    'esPortada' => true
                ];
            }
            $stmtPortadas->close();
        }

        // 2. Obtener imágenes individuales a las que dio like
        $sqlImagenes = "
            SELECT 
                i.idImagen,
                i.tituloImagen,
                i.descripcionImagen,
                i.urlImagen,
                i.idAlbumImagen,
                i.fechaImagen,
                a.tituloAlbum AS nombreAlbum
            FROM megusta m
            INNER JOIN imagen i ON i.idImagen = m.idImagenLike
            INNER JOIN album a ON a.idAlbum = i.idAlbumImagen
            WHERE m.idUsuarioLike = ? 
            AND a.idUsuarioAlbum = ?
            ORDER BY m.fechaLike DESC
        ";
        $stmtImagenes = $conexion->prepare($sqlImagenes);
        if ($stmtImagenes) {
            $stmtImagenes->bind_param("ii", $idUsuarioLogueado, $idUsuarioSeguido);
            $stmtImagenes->execute();
            $resImagenes = $stmtImagenes->get_result();
            while ($imagen = $resImagenes->fetch_assoc()) {
                $imagenes[] = [
                    'idImagen' => (int)$imagen['idImagen'],
                    'tituloImagen' => $imagen['tituloImagen'] ?? '',
                    'descripcionImagen' => $imagen['descripcionImagen'] ?? '',
                    'urlImagen' => basename($imagen['urlImagen']),
                    'idAlbumImagen' => (int)$imagen['idAlbumImagen'],
                    'nombreAlbum' => $imagen['nombreAlbum'],
                    'fechaImagen' => $imagen['fechaImagen'],
                    'tipo' => 'imagen',
                    'esPortada' => false
                ];
            }
            $stmtImagenes->close();
        }

        cerrarConexion($conexion);
        return $imagenes;
    }

}