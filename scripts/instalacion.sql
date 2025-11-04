CREATE DATABASE IF NOT EXISTS artesanos CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE artesanos;

CREATE TABLE usuario (
  idUsuario INT AUTO_INCREMENT PRIMARY KEY,
  nombreUsuario VARCHAR(100) NOT NULL,
  apellidoUsuario VARCHAR(100) NOT NULL,
  arrobaUsuario VARCHAR(50) NOT NULL UNIQUE,
  apodoUsuario VARCHAR(100) NOT NULL,
  descripcionUsuario TEXT NULL,
  contactoUsuario VARCHAR(150) NULL,
  privacidadUsuario VARCHAR(20) NOT NULL DEFAULT 'publico',
  contrasenaUsuario VARCHAR(255) NOT NULL,
  correoUsuario VARCHAR(150) NOT NULL UNIQUE,
  idFotoPerfilUsuario INT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL,
  INDEX (idFotoPerfilUsuario)
) ENGINE=InnoDB;

CREATE TABLE fotosdeperfil (
  idFotoPerfil INT AUTO_INCREMENT PRIMARY KEY,
  imagenPerfil VARCHAR(255) NOT NULL,
  idUsuario INT NULL,
  INDEX (idUsuario),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

ALTER TABLE usuario
  ADD CONSTRAINT fk_usuario_foto
  FOREIGN KEY (idFotoPerfilUsuario) REFERENCES fotosdeperfil(idFotoPerfil)
  ON UPDATE CASCADE ON DELETE SET NULL;

ALTER TABLE fotosdeperfil
  ADD CONSTRAINT fk_foto_usuario
  FOREIGN KEY (idUsuario) REFERENCES usuario(idUsuario)
  ON UPDATE CASCADE ON DELETE CASCADE;

CREATE TABLE album (
  idAlbum INT AUTO_INCREMENT PRIMARY KEY,
  tituloAlbum VARCHAR(200) NOT NULL,
  esPublicoAlbum TINYINT(1) NOT NULL DEFAULT 1,
  urlPortadaAlbum VARCHAR(255) NOT NULL,
  idUsuarioAlbum INT NOT NULL,
  fechaCreacionAlbum DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX (idUsuarioAlbum)
) ENGINE=InnoDB;

ALTER TABLE album
  ADD CONSTRAINT fk_album_usuario
  FOREIGN KEY (idUsuarioAlbum) REFERENCES usuario(idUsuario)
  ON UPDATE CASCADE ON DELETE CASCADE;

CREATE TABLE imagen (
  idImagen INT AUTO_INCREMENT PRIMARY KEY,
  tituloImagen VARCHAR(200) NULL,
  descripcionImagen TEXT NULL,
  etiquetaImagen VARCHAR(100) NULL,
  enRevision TINYINT(1) NOT NULL DEFAULT 0,
  fechaImagen DATE NOT NULL,
  urlImagen VARCHAR(255) NOT NULL,
  idAlbumImagen INT NOT NULL,
  INDEX (idAlbumImagen)
) ENGINE=InnoDB;

ALTER TABLE imagen
  ADD CONSTRAINT fk_imagen_album
  FOREIGN KEY (idAlbumImagen) REFERENCES album(idAlbum)
  ON UPDATE CASCADE ON DELETE CASCADE;

CREATE TABLE megusta (
  idLike INT AUTO_INCREMENT PRIMARY KEY,
  idImagenLike INT NOT NULL,
  idUsuarioLike INT NOT NULL,
  fechaLike DATETIME NOT NULL,
  UNIQUE KEY uq_like (idImagenLike, idUsuarioLike),
  INDEX (idUsuarioLike),
  INDEX (idImagenLike)
) ENGINE=InnoDB;

ALTER TABLE megusta
  ADD CONSTRAINT fk_like_imagen FOREIGN KEY (idImagenLike) REFERENCES imagen(idImagen)
  ON UPDATE CASCADE ON DELETE CASCADE,
  ADD CONSTRAINT fk_like_usuario FOREIGN KEY (idUsuarioLike) REFERENCES usuario(idUsuario)
  ON UPDATE CASCADE ON DELETE CASCADE;

CREATE TABLE comentario (
  idComentario INT AUTO_INCREMENT PRIMARY KEY,
  idImagenComentario INT NOT NULL,
  idUsuarioComentario INT NOT NULL,
  mensajeComentario TEXT NOT NULL,
  fechaComentario DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX (idImagenComentario),
  INDEX (idUsuarioComentario)
) ENGINE=InnoDB;

ALTER TABLE comentario
  ADD CONSTRAINT fk_comentario_imagen FOREIGN KEY (idImagenComentario) REFERENCES imagen(idImagen)
  ON UPDATE CASCADE ON DELETE CASCADE,
  ADD CONSTRAINT fk_comentario_usuario FOREIGN KEY (idUsuarioComentario) REFERENCES usuario(idUsuario)
  ON UPDATE CASCADE ON DELETE CASCADE;

CREATE TABLE seguimiento (
  idSeguimiento INT AUTO_INCREMENT PRIMARY KEY,
  idSeguidor INT NOT NULL,
  idSeguido INT NOT NULL,
  estadoSeguimiento VARCHAR(20) NOT NULL DEFAULT 'seguido',
  fechaSeguimiento DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_seguimiento (idSeguidor, idSeguido),
  INDEX (idSeguidor),
  INDEX (idSeguido)
) ENGINE=InnoDB;

ALTER TABLE seguimiento
  ADD CONSTRAINT fk_seguimiento_seguidor FOREIGN KEY (idSeguidor) REFERENCES usuario(idUsuario)
  ON UPDATE CASCADE ON DELETE CASCADE,
  ADD CONSTRAINT fk_seguimiento_seguido FOREIGN KEY (idSeguido) REFERENCES usuario(idUsuario)
  ON UPDATE CASCADE ON DELETE CASCADE;

CREATE TABLE IF NOT EXISTS megusta_album (
  idLikeAlbum INT AUTO_INCREMENT PRIMARY KEY,
  idAlbumLike INT NOT NULL,
  idUsuarioLike INT NOT NULL,
  fechaLike DATETIME NOT NULL,
  UNIQUE KEY uq_like_album (idAlbumLike, idUsuarioLike),
  INDEX (idAlbumLike),
  INDEX (idUsuarioLike),
  CONSTRAINT fk_like_album_album FOREIGN KEY (idAlbumLike) REFERENCES album(idAlbum)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_like_album_usuario FOREIGN KEY (idUsuarioLike) REFERENCES usuario(idUsuario)
    ON UPDATE CASCADE ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS notificaciones (
  idNotificacion INT AUTO_INCREMENT PRIMARY KEY,
  idUsuarioDestino INT NOT NULL,
  idUsuarioAccion INT NOT NULL,
  tipo VARCHAR(50) NOT NULL,              -- e.g. solicitud_seguir, aceptar_seguimiento, respuesta_seguimiento, album_nuevo, like, comentario
  mensaje VARCHAR(255) NOT NULL,
  leida TINYINT(1) NOT NULL DEFAULT 0,
  fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX (idUsuarioDestino),
  INDEX (idUsuarioAccion),
  CONSTRAINT fk_notif_destino FOREIGN KEY (idUsuarioDestino) REFERENCES usuario(idUsuario)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_notif_accion FOREIGN KEY (idUsuarioAccion) REFERENCES usuario(idUsuario)
    ON UPDATE CASCADE ON DELETE CASCADE
);