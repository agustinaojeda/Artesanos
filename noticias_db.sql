-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1:3306
-- Tiempo de generación: 08-05-2026 a las 23:13:35
-- Versión del servidor: 9.1.0
-- Versión de PHP: 8.3.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `noticias_db`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `auditoria`
--

DROP TABLE IF EXISTS `auditoria`;
CREATE TABLE IF NOT EXISTS `auditoria` (
  `id` int NOT NULL AUTO_INCREMENT,
  `noticia_id` int DEFAULT NULL,
  `usuario_id` int DEFAULT NULL,
  `estado_anterior` varchar(50) DEFAULT NULL,
  `estado_nuevo` varchar(50) DEFAULT NULL,
  `observacion` text,
  `fecha_hora` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `noticia_id` (`noticia_id`),
  KEY `usuario_id` (`usuario_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `noticias`
--

DROP TABLE IF EXISTS `noticias`;
CREATE TABLE IF NOT EXISTS `noticias` (
  `id` int NOT NULL AUTO_INCREMENT,
  `titulo` varchar(100) NOT NULL,
  `resumen` varchar(200) DEFAULT NULL,
  `descripcion` text NOT NULL,
  `imagen` varchar(255) DEFAULT NULL,
  `estado` enum('Borrador','Lista para Validación','Para Corrección','Publicada','Expirada','Anulada') DEFAULT 'Borrador',
  `autor_id` int DEFAULT NULL,
  `fecha_creacion` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_publicacion` datetime DEFAULT NULL,
  `fecha_expiracion` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `autor_id` (`autor_id`)
) ENGINE=MyISAM AUTO_INCREMENT=24 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `noticias`
--

INSERT INTO `noticias` (`id`, `titulo`, `resumen`, `descripcion`, `imagen`, `estado`, `autor_id`, `fecha_creacion`, `fecha_publicacion`, `fecha_expiracion`) VALUES
(23, '아몬드', '손원평 장편 소', '그날 한 명이 다치고 여섯 명이 죽었다. 먼저 엄마와 할멈. 다음으로는 남자를 말리러 온 대학생. 그 후에는 구세군 행진의 선두에 섰던 50대 아저씨 둘과 경찰 한 명이었다. 그리고 끝으로는, 그 남자 자신이었다. 그는 정신없는 칼부림의 마지막 대상으로 스스로를 선택했다. 자신의 가슴 깊이 칼을 찔러 넣은 남자는 다른 희생자들과 마찬가지로 구급차가 도착하기 전 숨이 끊어졌다. 나는 그 모든 일이 눈앞에서 벌어지는 것을 바라보고만 있었다.\r\n언제나처럼, 무표정하게.', '1778292585_69fe9769abcfd.jpeg', 'Publicada', 6, '2026-05-09 01:53:52', '2026-05-08 23:10:12', NULL),
(22, 'Resident Evil 4 y los Speedruners', 'Te presentamos a los líderes actuales en la categoría principal y los secretos para bajar tus tiempos en un speedrun.', 'Los Titanes del Récord Mundial\r\nEn la comunidad de speedrun.com, la competencia es feroz. Hee_Hee ha mantenido posiciones de liderazgo gracias a una ejecución mecánica casi perfecta en las secciones del Castillo. Por otro lado, Spicee es conocido por descubrir micro-optimizaciones en el movimiento que ahorran segundos vitales, mientras que Zhen_G destaca por su consistencia en la gestión de recursos bajo presión. Estos jugadores no solo juegan rápido, sino que memorizan los ciclos de los enemigos (sus patrones de movimiento) para pasar entre ellos sin recibir daño.\r\n\r\nConsejos Clave para Bajar tus Tiempos\r\nSi quieres empezar a correr el juego, el primer consejo es aprender los \"Skips\" con granadas. Por ejemplo, puedes saltar la sección de la puerta de la galería en el Castillo lanzando una granada pesada en el lugar exacto, o destruir la pared de la zona de obras en la Isla de un solo impacto. Además, la gestión del inventario debe ser automática: saber qué vas a comprar y vender al Buhonero antes de llegar a él te ahorrará minutos totales al final de la partida.\r\n\r\nLa Estrategia del Equipamiento\r\nEn un speedrun profesional, no todas las armas son útiles. La mayoría de los corredores optan por mejorar al máximo la Riot Gun (Escopeta de combate) por su daño concentrado y el Rifle SR M1903 por su capacidad de atravesar enemigos. Un truco fundamental es el uso del lanzacohetes en jefes específicos como Salazar o Saddler; aunque es caro, el tiempo que ahorras al eliminar una batalla de 5 minutos en solo 10 segundos es lo que define un récord.\r\n\r\nMovimiento y Mecánicas Avanzadas\r\nEl movimiento es la base de todo. Debes aprender a correr \"limpio\", evitando chocar con las paredes y optimizando los ángulos de giro. El parry es tu mejor amigo: dominar el bloqueo perfecto te permite evitar el aturdimiento y seguir avanzando. Finalmente, recuerda que en un speedrun, matar es perder tiempo. Si un enemigo no bloquea una puerta o no es un jefe, simplemente esquívalo o dispárale a la rodilla para que se tambalee y puedas pasar de largo.', '1778290488_69fe8f386431d.avif', 'Publicada', 6, '2026-05-09 01:34:48', '2026-05-08 22:35:27', NULL),
(21, 'BTS llega  a la Argentina con su Tour más reciente \"ARIRANG\"', 'Todos los tips que necesitas saber para ir a su concierto.', 'El estado actual del grupo\r\nCon el regreso de Jin y J-Hope, y la salida programada de los demás integrantes (RM, Suga, Jimin, V y Jungkook) hacia mediados de 2025, se espera que el 2026 sea el año del gran despliegue global de BTS. La empresa matriz, HYBE, ha mencionado en informes financieros su intención de organizar una gira mundial masiva una vez que el grupo esté completo. Argentina es uno de los países con mayor actividad de fans en la región, lo que lo convierte en un destino lógico, pero la logística de traer una producción de este calibre requiere anuncios con muchos meses de antelación.\r\n\r\nLa confusión con el nombre \"Arirang\"\r\nEl nombre \"Arirang\" suele generar confusión porque es el nombre de una famosa cadena de televisión coreana (Arirang TV) y de diversos festivales de cultura y K-pop apoyados por el gobierno de Corea del Sur. Es probable que los rumores mezclen la organización de algún evento cultural coreano en Buenos Aires con la gira oficial de la banda. Si bien BTS ha interpretado versiones de la canción \"Arirang\" en el pasado, un tour mundial generalmente lleva un nombre relacionado con su último concepto discográfico, como ocurrió con Love Yourself o Map of the Soul.\r\n\r\nFactores logísticos en Argentina\r\nPara que un evento de esta magnitud ocurra en el país, se necesitaría un recinto de gran escala, como el Estadio River Plate, para albergar a la masiva cantidad de seguidores. Las productoras suelen esperar a que la situación económica y la estabilidad cambiaria permitan fijar precios que cubran los altísimos costos de producción de un show de K-pop de primer nivel. Hasta que las cuentas oficiales (@bts_bighit) no publiquen un calendario de fechas, cualquier cartelera o preventa es considerada extraoficial.\r\n\r\nRecomendación para los fans\r\nLa mejor forma de estar al tanto es seguir la plataforma Weverse, que es donde el grupo lanza sus comunicados oficiales. Es común que en redes sociales como TikTok o X (Twitter) se vuelvan virales pósters creados por fans que parecen reales, pero la confirmación final siempre vendrá directamente desde Corea. Mientras tanto, el interés por la cultura coreana, desde su música hasta su historia, sigue creciendo exponencialmente en el país, preparando el terreno para lo que sería, sin duda, el evento musical de la década.', '1778287770_69fe849ab5851.jpg', 'Publicada', 9, '2026-05-09 00:49:30', '2026-05-08 21:49:54', NULL),
(20, 'Los Puntos Esenciales del Crochet', 'Aprendiendo estos puntos básicos podrás tejer a crochet como todo un experto', 'Los Puntos de Estructura (Cadena y Enano) Todo proyecto comienza con el punto cadena (ch), que crea la base necesaria para montar el resto del tejido. Es literalmente un lazo tras otro y se usa tanto para iniciar como para dar altura al comenzar una nueva vuelta. Por otro lado, el punto enano o deslizado (sl st) no tiene altura propia; su función principal es unir partes del tejido, cerrar vueltas en redondo o \"caminar\" sobre los puntos sin añadir volumen, permitiendo acabados limpios y profesionales.\r\nEl Punto Bajo (Single Crochet) Conocido también como medio punto, es el rey de la estructura. Produce un tejido denso, firme y con poco espacio entre los puntos, lo cual es vital para que el relleno de los muñecos tejidos no se escape. Es un punto corto que requiere paciencia para avanzar grandes superficies, pero ofrece una durabilidad y definición de forma que ningún otro punto básico puede igualar.\r\nLa Media Vareta y la Vareta (Half Double & Double Crochet) Estos puntos introducen la \"lazada\" previa, lo que les da más altura. La media vareta es el punto intermedio perfecto: es más suave que el punto bajo pero más tupido que la vareta. La vareta (punto alto), por su parte, es el estándar para prendas como bufandas, cardigans o gorros. Al ser un punto más largo, el tejido crece rápido y consume menos hilo en relación con la superficie cubierta, además de ofrecer una caída mucho más fluida y elástica.\r\nPuntos de Fantasía y Variaciones A partir de estos básicos surgen variaciones populares como el punto Popcorn o Garbanzo, que se logra tejiendo varias varetas en el mismo lugar para crear relieve. También es muy común el uso de disminuciones y aumentos, que son los que permiten dar curvas y formas tridimensionales a las piezas. Dominar la tensión en estos puntos básicos es el secreto para que cualquier diseño, por simple que sea, luzca como una pieza de alta calidad artesanal.\r\n', '1778287572_69fe83d42d09d.webp', 'Publicada', 9, '2026-05-09 00:46:12', '2026-05-08 21:46:35', NULL),
(19, 'Resident Evil Requiem', 'La exclusiva del juego en lanzamiento \"Resident Evil Requiem\" el tan esperado RE9', 'El Escenario y la Jugabilidad\r\nA diferencia de los entornos cerrados de entregas anteriores, las filtraciones sugieren que Resident Evil 9 utilizará una tecnología de mundo abierto similar a la vista en Dragon\'s Dogma 2. La historia nos llevaría a una isla desolada con una estética rural y costera, donde la exploración será clave. Capcom buscaría mezclar la tensión de los pasillos estrechos con la incertidumbre de espacios abiertos donde el clima y el ciclo día/noche podrían afectar el comportamiento de las criaturas.\r\n\r\nEl Regreso de los Iconos\r\nUno de los puntos que más emociona a la comunidad es el posible regreso de Leon S. Kennedy. Según los rumores, Leon sería el protagonista principal (ahora con unos 48-50 años), acompañado por Jill Valentine en un papel secundario o cooperativo. Esto marcaría un distanciamiento de la familia Winters (Ethan y Rose), volviendo a los personajes clásicos que los fans han pedido durante años para darles un cierre digno a su historia.\r\n\r\nEnemigos y Mitología\r\nEl nombre \"Requiem\" sugiere un final o un descanso eterno. Se especula que el antagonista no será un monstruo gigante desde el principio, sino una organización que ha perfeccionado el uso del \"Megamiceto\" o una variante del virus que permite crear enemigos rápidos, capaces de usar herramientas básicas y acechar al jugador de forma persistente (similar a la inteligencia del Nemesis original o Mr. X, pero más avanzada).\r\n\r\nConclusión del Arco\r\nAunque Resident Evil Village pareció concluir la historia de los Winters, este nuevo capítulo serviría para unir los cabos sueltos sobre la creación del virus original y el papel de Spencer. Se dice que el juego es el proyecto con mayor presupuesto en la historia de la franquicia, diseñado para ser el \"Resident Evil definitivo\" que celebre los 30 años de la saga (que se cumplen en 2026).\r\n', '1778287462_69fe8366498f1.avif', 'Publicada', 6, '2026-05-09 00:44:22', '2026-05-08 21:44:46', NULL),
(18, 'Guía de Supervivencia para un Nuevo Tenno', 'Warframe: Ninjas Espaciales en Acción', '1. El Gameplay y el Movimiento\r\nLo primero que notarás es que el juego es extremadamente rápido. Existe una mecánica llamada Bullet Jump (Salto Bala) que es el núcleo del movimiento. Dominar cómo encadenar saltos, volteretas y planeos en el aire no solo te hace sentir como un ninja, sino que es vital para sobrevivir a las oleadas de enemigos.\r\n\r\n2. Los Warframes y las Armas\r\nNo estás limitado a una sola \"clase\". Tu cuenta (llamada nivel de Maestría) te permite construir y cambiar entre docenas de Warframes diferentes:\r\n\r\nAlgunos son tanques pesados.\r\n\r\nOtros son magos elementales (fuego, hielo, electricidad).\r\n\r\nOtros se especializan en sigilo o en dar soporte al equipo.\r\nCada Warframe tiene 4 habilidades únicas y un estilo de juego distinto.\r\n\r\n3. El Sistema de Modificación (Mods)\r\nAquí es donde está la verdadera magia. En Warframe, el nivel de tu arma no aumenta su daño de forma automática; lo que importa son los Mods (cartas que equipas). Estos te permiten personalizarlo todo: desde la velocidad de ataque y el daño crítico hasta el alcance de tus poderes. Aprender a \"modear\" es lo que te llevará de ser un principiante a un guerrero imparable.\r\n\r\n4. ¿Qué tengo que hacer ahora?\r\nAl principio, el juego te da mucha libertad, pero mi recomendación es que te enfoques en:\r\n\r\nCompletar la Convergencia de los Planetas: Ve desbloqueando los nodos del mapa estelar. Esto te abrirá el camino a las misiones de historia (que son sorprendentemente buenas y cinemáticas más adelante).\r\n\r\nNo gastes tu Platinum inicial a la ligera: Empiezas con una pequeña cantidad de la moneda premium. Úsala exclusivamente para comprar ranuras de inventario (Slots) para más Warframes o armas.\r\n\r\nLa comunidad: Es una de las menos tóxicas en el mundo del gaming. Si te quedas trabado en una misión, no dudes en pedir ayuda en el chat de reclutamiento; siempre hay veteranos aburridos dispuestos a escoltar a un nuevo Tenno.\r\n\r\nEs un juego de \"grindeo\" (repetir misiones para conseguir materiales), así que tómatelo con calma. ¡Disfruta de la estética y de sentirte un ninja espacial!\r\n', '1778287021_69fe81ad7d230.png', 'Publicada', 8, '2026-05-09 00:37:01', '2026-05-08 21:42:42', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `parametros`
--

DROP TABLE IF EXISTS `parametros`;
CREATE TABLE IF NOT EXISTS `parametros` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre_parametro` varchar(50) DEFAULT NULL,
  `valor` varchar(10) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `parametros`
--

INSERT INTO `parametros` (`id`, `nombre_parametro`, `valor`) VALUES
(1, 'dias_expiracion', '30'),
(2, 'max_file_size', '2048');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `roles`
--

DROP TABLE IF EXISTS `roles`;
CREATE TABLE IF NOT EXISTS `roles` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre_rol` varchar(50) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `roles`
--

INSERT INTO `roles` (`id`, `nombre_rol`) VALUES
(1, 'Editor'),
(2, 'Validador');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

DROP TABLE IF EXISTS `usuarios`;
CREATE TABLE IF NOT EXISTS `usuarios` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `apellido` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `fecha_registro` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=MyISAM AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `nombre`, `apellido`, `email`, `password`, `fecha_registro`) VALUES
(9, 'Brisa', 'Dágata', 'p2usbloco@gmail.com', '$2y$10$b4RwbcyaCvRbLrBHXUHlC.P7RMzAQTOwZKSsmnHvAOwdi.xvY5uga', '2026-05-09 00:45:40'),
(8, 'Rebecca', 'Ford', 'lotus@gmail.com', '$2y$10$aZtLuPOG2UDGBVoho52qaebqLLa2SSQDlVtw6VnR4EX.Y8MKPR79m', '2026-05-09 00:34:50'),
(6, 'Grace', 'Ashcroft', 'requiem9@gmail.com', '$2y$10$WLlsiBDlmICEbG2ecAzt5ez7Dz9H/jscyAoFvWuE0q7fuQ1kAWnkG', '2026-05-09 00:29:13'),
(7, 'Leon Scott', 'Kennedy', 'raccoon@gmail.com', '$2y$10$giZ1shYHQpxib5IXc2ZWsOGCFiPuZ67QEAS96KBpcU2T/IOcsG.nu', '2026-05-09 00:31:53'),
(10, 'Samuel', 'De Luque', 'vegetta777@gmail.com', '$2y$10$O9JjrXWTcw.hl0ZbWENHcO/m73QLATqEuqAywqdkM8rzzhi0jPW8S', '2026-05-09 00:54:34');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuario_rol`
--

DROP TABLE IF EXISTS `usuario_rol`;
CREATE TABLE IF NOT EXISTS `usuario_rol` (
  `usuario_id` int NOT NULL,
  `rol_id` int NOT NULL,
  PRIMARY KEY (`usuario_id`,`rol_id`),
  KEY `rol_id` (`rol_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `usuario_rol`
--

INSERT INTO `usuario_rol` (`usuario_id`, `rol_id`) VALUES
(6, 1),
(6, 2),
(7, 2),
(8, 1),
(9, 1),
(10, 1),
(10, 2);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
