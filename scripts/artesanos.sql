-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 31-10-2025 a las 00:18:36
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `artesanos`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `album`
--

CREATE TABLE `album` (
  `idAlbum` int(10) UNSIGNED NOT NULL,
  `tituloAlbum` varchar(50) NOT NULL,
  `esPublicoAlbum` tinyint(1) NOT NULL,
  `urlPortadaAlbum` varchar(250) NOT NULL,
  `idUsuarioAlbum` int(10) UNSIGNED NOT NULL,
  `fechaCreacionAlbum` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `album`
--

INSERT INTO `album` (`idAlbum`, `tituloAlbum`, `esPublicoAlbum`, `urlPortadaAlbum`, `idUsuarioAlbum`, `fechaCreacionAlbum`) VALUES
(5, '', 1, '68ffee62b2302_', 15, '2025-10-27 19:12:50'),
(6, 'Esta soy yo', 1, '6900dbe7529fa_unnamed.jpg', 15, '2025-10-28 12:06:15'),
(7, 'Cuadros de mariposas', 1, '690220b89f3d1_mariposas2.webp', 15, '2025-10-29 11:12:08'),
(8, 'Campo de rosas azules', 1, '6902224185147_rosa 3.webp', 15, '2025-10-29 11:18:41'),
(9, 'Tejidos a crochet', 1, '69022e4ddcbb9_cardigan blanco espalda.jpeg', 16, '2025-10-29 12:10:05'),
(10, 'Cárdigan a crochet', 1, '690231c077773_cardigan color2.jpeg', 16, '2025-10-29 12:24:48'),
(11, 'Corset', 1, '69023213006fe_corset.jpeg', 16, '2025-10-29 12:26:11'),
(12, 'Chaleco a crochet', 1, '6903d8c201110_chaleco.jpeg', 16, '2025-10-30 18:29:38'),
(13, 'Bolso a crochet de macramé', 1, '6903d94529fd8_bolso rosa.jpeg', 16, '2025-10-30 18:31:49');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `comentario`
--

CREATE TABLE `comentario` (
  `idComentario` int(10) UNSIGNED NOT NULL,
  `idImagenComentario` int(10) UNSIGNED NOT NULL,
  `idUsuarioComentario` int(10) UNSIGNED NOT NULL,
  `fechaComentario` date NOT NULL,
  `mensajeComentario` varchar(200) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `fotosdeperfil`
--

CREATE TABLE `fotosdeperfil` (
  `idFotoPerfil` int(10) UNSIGNED NOT NULL,
  `imagenPerfil` varchar(250) NOT NULL,
  `idUsuario` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `fotosdeperfil`
--

INSERT INTO `fotosdeperfil` (`idFotoPerfil`, `imagenPerfil`, `idUsuario`) VALUES
(6, 'avatar_13_1761331125.png', 13),
(7, 'avatar_14_1761333147.jpg', 14),
(8, 'avatar_14_1761336581.jpg', 14),
(9, 'avatar_14_1761336796.jpg', 14),
(10, 'avatar_14_1761337984.jpg', 14),
(11, 'avatar_15_1761597765.jpg', 15),
(12, 'avatar_16_1761749824.jpg', 16),
(13, 'avatar_16_1761749906.jpg', 16);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `imagen`
--

CREATE TABLE `imagen` (
  `idImagen` int(10) UNSIGNED NOT NULL,
  `tituloImagen` varchar(50) NOT NULL,
  `descripcionImagen` varchar(100) DEFAULT NULL,
  `etiquetaImagen` varchar(100) DEFAULT NULL,
  `enRevision` tinyint(1) NOT NULL,
  `fechaImagen` date NOT NULL,
  `urlImagen` varchar(250) NOT NULL,
  `idAlbumImagen` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `imagen`
--

INSERT INTO `imagen` (`idImagen`, `tituloImagen`, `descripcionImagen`, `etiquetaImagen`, `enRevision`, `fechaImagen`, `urlImagen`, `idAlbumImagen`) VALUES
(1, 'Durmiendo entre las flores', 'Amo dormir con el aroma de las flores', '#flores', 0, '2025-10-28', '6900dbe758f26_sharpei con flores.webp', 6),
(2, 'Mariposas de colores', 'Amo las mariposas', '#bellasmariposas', 0, '2025-10-29', '690220b8a2cf7_mariposas1.jpg', 7),
(3, 'Rosas azules', 'Amo el azul', '#rosas', 0, '2025-10-29', '69022241893a0_rosa 4.jpg', 8),
(4, 'Cárdigan Pump', 'Cárdigan pump tejido a crochet.', '#diseños', 0, '2025-10-29', '69022e4ddfd0a_tejido marron 2.jpeg', 9),
(5, 'Cárdigan Pump', 'Cárdigan Pump a crochet.', '#diseños', 0, '2025-10-29', '69022e4de24ed_tejido marron1.jpeg', 9),
(6, 'Cárdigan Pump', 'Cárdigan Pump a crochet.', '#diseños', 0, '2025-10-29', '69022e4de4a11_cardigan blanco costado.jpeg', 9),
(7, 'Cárdigan', 'Cárdigan a cuadros', '#tejidos', 0, '2025-10-29', '690231c07bb15_cardigan marron.jpeg', 10),
(8, 'Cárdigan', 'Cárdigan a cuadros', '#tejidos', 0, '2025-10-29', '690231c07cf5b_cardigan marron2.jpeg', 10),
(9, 'Cárdigan', 'Cárdigan a cuadros', '#tejido', 0, '2025-10-29', '690231c07e484_cardigan3.jpeg', 10),
(10, 'Cárdigan', 'Cárdigan a cuadros', '#tejidos', 0, '2025-10-29', '690231c080e00_cardigan color.jpeg', 10),
(11, 'Cárdigan', 'Cárdigan a cuadros', '#tejidos', 0, '2025-10-29', '690231c0820cd_cardigan rosa.jpeg', 10),
(12, 'Cárdigan', 'Cárdigan a cuadros', '#tejidos', 0, '2025-10-29', '690231c084c44_cardigan1.jpeg', 10),
(13, 'Cárdigan', 'Cárdigan a cuadros', '#tejidos', 0, '2025-10-29', '690231c08ad86_cardigan2.jpeg', 10),
(14, 'Corset', 'Corset tejido a crochet.', '#corset', 0, '2025-10-29', '6902321306668_corset.jpeg', 11),
(15, 'Chaleco', 'Chaleco tejido a crochet.', '#crochet', 0, '2025-10-30', '6903d8c208ba3_chaleco.jpeg', 12),
(16, 'Bolso a crochet de macramé', 'Bolso rosa', '#bolso', 0, '2025-10-30', '6903d9452cf2f_bolso rosa2.jpeg', 13),
(17, 'Bolso a crochet de macramé', 'Bolso rosa', '#bolso', 0, '2025-10-30', '6903d9452e4fe_bolso rosa.jpeg', 13);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `megusta`
--

CREATE TABLE `megusta` (
  `idLike` int(10) UNSIGNED NOT NULL,
  `fechaLike` date NOT NULL,
  `idImagenLike` int(10) UNSIGNED NOT NULL,
  `idUsuarioLike` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `notificaciones`
--

CREATE TABLE `notificaciones` (
  `idNotificacion` int(10) UNSIGNED NOT NULL,
  `idUsuarioDestino` int(10) UNSIGNED NOT NULL,
  `idUsuarioAccion` int(10) UNSIGNED NOT NULL,
  `tipo` varchar(50) NOT NULL,
  `idReferencia` int(10) UNSIGNED DEFAULT NULL,
  `mensaje` text NOT NULL,
  `leida` tinyint(1) DEFAULT 0,
  `fecha` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `notificaciones`
--

INSERT INTO `notificaciones` (`idNotificacion`, `idUsuarioDestino`, `idUsuarioAccion`, `tipo`, `idReferencia`, `mensaje`, `leida`, `fecha`) VALUES
(4, 15, 16, 'seguir', 0, 'El usuario 1 te ha comenzado a seguir.', 1, '2025-10-30 21:59:55'),
(5, 15, 16, 'seguir', 0, 'El usuario 1 te ha comenzado a seguir.', 1, '2025-10-30 22:01:03'),
(6, 15, 16, 'seguir', 0, 'El usuario 16 te ha comenzado a seguir.', 1, '2025-10-30 22:02:12'),
(7, 15, 16, 'seguir', 0, 'El usuario 16 te ha comenzado a seguir.', 1, '2025-10-30 22:42:09'),
(8, 15, 16, 'seguir', 0, 'El usuario 16 te ha comenzado a seguir.', 1, '2025-10-30 22:44:39'),
(9, 15, 16, 'seguir', 0, 'El usuario 16 te ha comenzado a seguir.', 1, '2025-10-30 22:46:22'),
(10, 15, 16, 'seguir', 0, 'El usuario 16 te ha comenzado a seguir.', 1, '2025-10-30 22:48:27'),
(11, 15, 16, 'seguir', 0, 'El usuario 16 te ha comenzado a seguir.', 1, '2025-10-30 22:51:37'),
(12, 15, 16, 'seguir', 0, 'El usuario 16 te ha comenzado a seguir.', 1, '2025-10-30 22:52:58'),
(13, 15, 16, 'seguir', 0, 'El usuario 16 te ha comenzado a seguir.', 1, '2025-10-30 22:55:40'),
(14, 15, 16, 'seguir', 0, 'El usuario 16 te ha comenzado a seguir.', 1, '2025-10-30 23:00:03'),
(15, 15, 16, 'seguir', 0, 'El usuario 16 te ha comenzado a seguir.', 1, '2025-10-30 23:04:58'),
(16, 15, 16, 'seguir', 0, 'El usuario 16 te ha comenzado a seguir.', 1, '2025-10-30 23:12:11'),
(17, 15, 16, 'seguir', 0, 'El usuario 16 te ha comenzado a seguir.', 1, '2025-10-30 23:14:36'),
(18, 15, 16, 'seguir', 0, 'El usuario 16 te ha comenzado a seguir.', 1, '2025-10-30 23:14:44'),
(19, 15, 16, 'seguir', 0, 'El usuario 16 te ha comenzado a seguir.', 1, '2025-10-30 23:14:54');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `seguimiento`
--

CREATE TABLE `seguimiento` (
  `idSeguimiento` int(10) UNSIGNED NOT NULL,
  `idSeguidor` int(10) UNSIGNED NOT NULL,
  `idSeguido` int(10) UNSIGNED NOT NULL,
  `estadoSeguimiento` varchar(12) NOT NULL,
  `fechaSeguimiento` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuario`
--

CREATE TABLE `usuario` (
  `idUsuario` int(10) UNSIGNED NOT NULL,
  `arrobaUsuario` varchar(50) NOT NULL,
  `apodoUsuario` varchar(50) NOT NULL,
  `nombreUsuario` varchar(50) NOT NULL,
  `apellidoUsuario` varchar(50) NOT NULL,
  `correoUsuario` varchar(50) NOT NULL,
  `privacidadUsuario` enum('publico','privado') NOT NULL DEFAULT 'publico',
  `contrasenaUsuario` varchar(255) NOT NULL,
  `descripcionUsuario` varchar(200) DEFAULT NULL,
  `contactoUsuario` varchar(50) NOT NULL,
  `idFotoPerfilUsuario` int(10) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuario`
--

INSERT INTO `usuario` (`idUsuario`, `arrobaUsuario`, `apodoUsuario`, `nombreUsuario`, `apellidoUsuario`, `correoUsuario`, `privacidadUsuario`, `contrasenaUsuario`, `descripcionUsuario`, `contactoUsuario`, `idFotoPerfilUsuario`) VALUES
(13, 'nakamaduokb', 'Nakama Duo', 'Brisa', 'Dágata', 'nakamaduokb@gmail.com', 'publico', '$2y$10$u1nfdSknU8ox0Pu9MIgKoOkZzxlJ3dwRCudWIn2uKA0PskXmfib0O', '✨Hecho a mano entre amigas\\r\\n💚Pulseras, muñecos y más hechos con propósito', '', 6),
(14, 'coralineart_resin', 'Coraline Art Resin', 'Coraline', 'Jhones', 'coraline@artresin.com', 'publico', '$2y$10$QEu3f13TgbPsvhH2qja4I.pjEMkPxQrSRg.yymDXUfvWWj1eHLZuW', 'Accesorios de resina\\r\\n💫 Llaveros | Plumas | Separadores |Placa para mascota', '', 10),
(15, 'seylarivero', 'seilita', 'Seyla', 'Rivero', 'seylagiselrivero@gmail.com', 'publico', '$2y$10$bKtqnvfJ/c2eyPJaeADGy.X.0BDJR5fOgPBgZDzONW6KgAulcIPlS', 'Amo a mi charlottis', '', 11),
(16, 'hg.tejidos', 'Jae', 'Jael Maira', 'Rivero', 'jaelmairarivero@gmail.com', 'publico', '$2y$10$qkwcc0nrQHBu7zO7ucsBMOmDe3drQwjb3sXrN.6i/OxCaky4BT44G', 'Tejidos a crochet personalizados', '', 13);

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `album`
--
ALTER TABLE `album`
  ADD PRIMARY KEY (`idAlbum`),
  ADD KEY `fk_idUsuario` (`idUsuarioAlbum`);

--
-- Indices de la tabla `comentario`
--
ALTER TABLE `comentario`
  ADD PRIMARY KEY (`idComentario`),
  ADD KEY `fk_idImagenComentario` (`idImagenComentario`),
  ADD KEY `fk_idUsuarioComentario` (`idUsuarioComentario`);

--
-- Indices de la tabla `fotosdeperfil`
--
ALTER TABLE `fotosdeperfil`
  ADD PRIMARY KEY (`idFotoPerfil`);

--
-- Indices de la tabla `imagen`
--
ALTER TABLE `imagen`
  ADD PRIMARY KEY (`idImagen`),
  ADD KEY `fk_idAlbum` (`idAlbumImagen`);

--
-- Indices de la tabla `megusta`
--
ALTER TABLE `megusta`
  ADD PRIMARY KEY (`idLike`),
  ADD KEY `fk_idImagenLike` (`idImagenLike`),
  ADD KEY `fk_idUsuarioLike` (`idUsuarioLike`);

--
-- Indices de la tabla `notificaciones`
--
ALTER TABLE `notificaciones`
  ADD PRIMARY KEY (`idNotificacion`),
  ADD KEY `fk_notif_destino` (`idUsuarioDestino`),
  ADD KEY `fk_notif_accion` (`idUsuarioAccion`);

--
-- Indices de la tabla `seguimiento`
--
ALTER TABLE `seguimiento`
  ADD PRIMARY KEY (`idSeguimiento`),
  ADD KEY `fk_seguidor` (`idSeguidor`),
  ADD KEY `fk_seguido` (`idSeguido`);

--
-- Indices de la tabla `usuario`
--
ALTER TABLE `usuario`
  ADD PRIMARY KEY (`idUsuario`),
  ADD UNIQUE KEY `usuario` (`arrobaUsuario`),
  ADD KEY `fk_idfoto` (`idFotoPerfilUsuario`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `album`
--
ALTER TABLE `album`
  MODIFY `idAlbum` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT de la tabla `comentario`
--
ALTER TABLE `comentario`
  MODIFY `idComentario` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `fotosdeperfil`
--
ALTER TABLE `fotosdeperfil`
  MODIFY `idFotoPerfil` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT de la tabla `imagen`
--
ALTER TABLE `imagen`
  MODIFY `idImagen` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT de la tabla `megusta`
--
ALTER TABLE `megusta`
  MODIFY `idLike` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `notificaciones`
--
ALTER TABLE `notificaciones`
  MODIFY `idNotificacion` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT de la tabla `seguimiento`
--
ALTER TABLE `seguimiento`
  MODIFY `idSeguimiento` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `usuario`
--
ALTER TABLE `usuario`
  MODIFY `idUsuario` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `album`
--
ALTER TABLE `album`
  ADD CONSTRAINT `fk_idUsuario` FOREIGN KEY (`idUsuarioAlbum`) REFERENCES `usuario` (`idUsuario`);

--
-- Filtros para la tabla `comentario`
--
ALTER TABLE `comentario`
  ADD CONSTRAINT `fk_idImagen` FOREIGN KEY (`idImagenComentario`) REFERENCES `imagen` (`idImagen`),
  ADD CONSTRAINT `fk_idImagenComentario` FOREIGN KEY (`idImagenComentario`) REFERENCES `imagen` (`idImagen`),
  ADD CONSTRAINT `fk_idUsuarioComentario` FOREIGN KEY (`idUsuarioComentario`) REFERENCES `usuario` (`idUsuario`);

--
-- Filtros para la tabla `imagen`
--
ALTER TABLE `imagen`
  ADD CONSTRAINT `fk_idAlbum` FOREIGN KEY (`idAlbumImagen`) REFERENCES `album` (`idAlbum`);

--
-- Filtros para la tabla `megusta`
--
ALTER TABLE `megusta`
  ADD CONSTRAINT `fk_idImagenLike` FOREIGN KEY (`idImagenLike`) REFERENCES `imagen` (`idImagen`),
  ADD CONSTRAINT `fk_idUsuarioLike` FOREIGN KEY (`idUsuarioLike`) REFERENCES `usuario` (`idUsuario`);

--
-- Filtros para la tabla `notificaciones`
--
ALTER TABLE `notificaciones`
  ADD CONSTRAINT `fk_notif_accion` FOREIGN KEY (`idUsuarioAccion`) REFERENCES `usuario` (`idUsuario`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_notif_destino` FOREIGN KEY (`idUsuarioDestino`) REFERENCES `usuario` (`idUsuario`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `seguimiento`
--
ALTER TABLE `seguimiento`
  ADD CONSTRAINT `fk_seguido` FOREIGN KEY (`idSeguido`) REFERENCES `usuario` (`idUsuario`),
  ADD CONSTRAINT `fk_seguidor` FOREIGN KEY (`idSeguidor`) REFERENCES `usuario` (`idUsuario`);

--
-- Filtros para la tabla `usuario`
--
ALTER TABLE `usuario`
  ADD CONSTRAINT `fk_idfoto` FOREIGN KEY (`idFotoPerfilUsuario`) REFERENCES `fotosdeperfil` (`idFotoPerfil`) ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
