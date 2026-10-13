-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 01-09-2026 a las 23:24:40
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
-- Base de datos: `rentcar`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `arrendatario`
--

CREATE TABLE `arrendatario` (
  `id_arrendatario` int(11) NOT NULL,
  `IdUsuario` int(11) DEFAULT NULL,
  `nombre` varchar(30) NOT NULL,
  `apellido` varchar(30) NOT NULL,
  `cedula` int(10) DEFAULT NULL,
  `telefono` int(15) DEFAULT NULL,
  `correo` varchar(50) DEFAULT NULL,
  `direccion` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `comentarios_vehiculos`
--

CREATE TABLE `comentarios_vehiculos` (
  `id_comentario` int(11) NOT NULL,
  `id_vehiculo` int(11) NOT NULL,
  `usuario_nombre` varchar(100) NOT NULL,
  `comentario` text NOT NULL,
  `fecha` datetime DEFAULT current_timestamp(),
  `puntuacion` int(11) DEFAULT 5
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `comentarios_vehiculos`
--

INSERT INTO `comentarios_vehiculos` (`id_comentario`, `id_vehiculo`, `usuario_nombre`, `comentario`, `fecha`, `puntuacion`) VALUES
(28, 46, 'T', 'chao', '2026-08-27 14:58:22', 5);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `fotos_vehiculos`
--

CREATE TABLE `fotos_vehiculos` (
  `id_foto` int(11) NOT NULL,
  `id_vehiculo` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `ruta_imagen` varchar(255) NOT NULL,
  `fecha_subida` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `fotos_vehiculos`
--

INSERT INTO `fotos_vehiculos` (`id_foto`, `id_vehiculo`, `id_usuario`, `ruta_imagen`, `fecha_subida`) VALUES
(36, 43, 66, 'img_43_1_6a87655056174.png', '2026-08-20 20:36:32'),
(37, 43, 66, 'img_43_2_6a87655056cf5.png', '2026-08-20 20:36:32'),
(38, 43, 66, 'img_43_3_6a876550575f8.webp', '2026-08-20 20:36:32'),
(39, 43, 66, 'img_43_4_6a876550594bd.webp', '2026-08-20 20:36:32'),
(44, 46, 62, 'imagenes/IDV_46_U62_1787688682_0.jpg', '2026-08-25 20:11:22'),
(45, 46, 62, 'imagenes/IDV_46_U62_1787688682_1.webp', '2026-08-25 20:11:22'),
(46, 46, 62, 'imagenes/IDV_46_U62_1787688682_2.webp', '2026-08-25 20:11:22'),
(47, 46, 62, 'imagenes/IDV_46_U62_1787688682_3.webp', '2026-08-25 20:11:22');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `metodos_pago`
--

CREATE TABLE `metodos_pago` (
  `id_pago` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `token_pasarela` varchar(255) NOT NULL,
  `tipo_tarjeta` varchar(20) DEFAULT NULL,
  `ultimos_4` varchar(4) DEFAULT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `prestamo`
--

CREATE TABLE `prestamo` (
  `id_prestamo` int(11) NOT NULL,
  `id_arrendatario` int(11) NOT NULL,
  `id_vehiculo` int(11) NOT NULL,
  `fecha_inicio` date NOT NULL,
  `fecha_fin` date NOT NULL,
  `monto` double NOT NULL,
  `estado` varchar(20) NOT NULL CHECK (`estado` in ('Activo','Finalizado'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuario`
--

CREATE TABLE `usuario` (
  `fechaDeNacimiento` date NOT NULL,
  `contraseña` varchar(255) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `apellido` varchar(100) NOT NULL,
  `documento` varchar(20) NOT NULL,
  `correo` varchar(100) NOT NULL,
  `numTelefono` bigint(20) UNSIGNED NOT NULL,
  `IdUsuario` int(11) NOT NULL,
  `tipo` tinyint(1) NOT NULL DEFAULT 2,
  `empresa` varchar(150) DEFAULT NULL,
  `nit` varchar(30) DEFAULT NULL,
  `representante_legal` varchar(150) DEFAULT NULL,
  `direccion` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuario`
--

INSERT INTO `usuario` (`fechaDeNacimiento`, `contraseña`, `nombre`, `apellido`, `documento`, `correo`, `numTelefono`, `IdUsuario`, `tipo`, `empresa`, `nit`, `representante_legal`, `direccion`) VALUES
('2008-02-09', '1234567', 'kevin', 'BARR', '12345678', 'K@Gmail.com', 0, 57, 2, NULL, NULL, NULL, NULL),
('2008-09-09', '1234567', 'kevin', 'BARR', '12345679', 'Ke@Gmail.com', 3014112590, 59, 2, NULL, NULL, NULL, NULL),
('1111-11-11', '1111111111111', 'admin', 'admin', '1111111111111', 'admin@gmail.com', 1111111111, 60, 2, NULL, NULL, NULL, NULL),
('2026-08-04', 'estoybueno1', 'roco', 'tyson', '1025777666', 'roco@gmail.com', 6667778899, 61, 2, NULL, NULL, NULL, NULL),
('2010-01-10', '12345678', 'T', 'p', '1192464569', 'thamara.puerta@ielusitania.edu.co', 3113475921, 62, 2, NULL, NULL, NULL, NULL),
('2009-09-09', '123456789', 'Juan Manuel', 'Sánchez Álvarez', '123456789', 'sanchezalvarezjuanmanuel4@gmail.com', 3116159985, 64, 2, NULL, NULL, NULL, NULL),
('0000-00-00', '$2y$10$M48QoYErms8jYhvBOumlROYBSluToMgUc5iP/UFuwjnklCsyjZG6u', '', '', '', 'ajenciauto@gmail.co', 3156542390, 66, 1, 'ajenciauto', '123456789', 'juan', 'la aurora'),
('2010-02-23', '123456789', 'jeronimo ', 'hernandez garcia', '1025766921', 'jeronimo.hernandez@ielusitania.edu.co', 3116472564, 67, 2, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `vehiculo`
--

CREATE TABLE `vehiculo` (
  `id_v` int(11) NOT NULL,
  `id_arrendatario` int(11) DEFAULT NULL,
  `tipo` varchar(50) DEFAULT NULL,
  `id_proveedor` int(11) DEFAULT NULL,
  `num_motor` int(11) DEFAULT NULL,
  `num_chasis` int(11) DEFAULT NULL,
  `traccion` varchar(50) NOT NULL,
  `motor` varchar(50) NOT NULL,
  `transmision` varchar(50) NOT NULL,
  `color` varchar(50) NOT NULL,
  `marca` varchar(50) NOT NULL,
  `placa` varchar(20) NOT NULL,
  `modelo` varchar(50) NOT NULL,
  `precio` double NOT NULL,
  `asientos` int(11) DEFAULT NULL,
  `estado` varchar(50) DEFAULT 'Disponible',
  `imagen` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `vehiculo`
--

INSERT INTO `vehiculo` (`id_v`, `id_arrendatario`, `tipo`, `id_proveedor`, `num_motor`, `num_chasis`, `traccion`, `motor`, `transmision`, `color`, `marca`, `placa`, `modelo`, `precio`, `asientos`, `estado`, `imagen`) VALUES
(43, NULL, 'Carro', 66, 1234, 1234, 'rwd', '1.6L', 'Automática', 'negro', 'renault', 'kiy582', '2022', 100000, 5, 'Disponible', NULL),
(46, NULL, 'Motocicleta', 62, 123456789, 123456789, 'trasera', '1000', 'Manual', 'negro', 'Ninja', 'JHS777', '2027', 1000, 2, 'Disponible', NULL);

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `arrendatario`
--
ALTER TABLE `arrendatario`
  ADD PRIMARY KEY (`id_arrendatario`),
  ADD UNIQUE KEY `cedula` (`cedula`),
  ADD KEY `IdUsuario` (`IdUsuario`);

--
-- Indices de la tabla `comentarios_vehiculos`
--
ALTER TABLE `comentarios_vehiculos`
  ADD PRIMARY KEY (`id_comentario`),
  ADD KEY `id_vehiculo` (`id_vehiculo`);

--
-- Indices de la tabla `fotos_vehiculos`
--
ALTER TABLE `fotos_vehiculos`
  ADD PRIMARY KEY (`id_foto`),
  ADD KEY `fk_fotos_vehiculo` (`id_vehiculo`),
  ADD KEY `fk_fotos_usuario` (`id_usuario`);

--
-- Indices de la tabla `metodos_pago`
--
ALTER TABLE `metodos_pago`
  ADD PRIMARY KEY (`id_pago`),
  ADD KEY `fk_usuario_pago` (`id_usuario`);

--
-- Indices de la tabla `prestamo`
--
ALTER TABLE `prestamo`
  ADD PRIMARY KEY (`id_prestamo`),
  ADD KEY `idx_prestamo_arrendatario` (`id_arrendatario`),
  ADD KEY `idx_prestamo_vehiculo` (`id_vehiculo`);

--
-- Indices de la tabla `usuario`
--
ALTER TABLE `usuario`
  ADD PRIMARY KEY (`IdUsuario`),
  ADD UNIQUE KEY `correo` (`correo`),
  ADD UNIQUE KEY `numTelefono` (`numTelefono`),
  ADD UNIQUE KEY `idx_documento` (`documento`),
  ADD UNIQUE KEY `idx_nit` (`nit`);

--
-- Indices de la tabla `vehiculo`
--
ALTER TABLE `vehiculo`
  ADD PRIMARY KEY (`id_v`),
  ADD UNIQUE KEY `placa` (`placa`),
  ADD KEY `idx_vehiculo_proveedor` (`id_proveedor`),
  ADD KEY `idx_vehiculo_arrendatario` (`id_arrendatario`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `arrendatario`
--
ALTER TABLE `arrendatario`
  MODIFY `id_arrendatario` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `comentarios_vehiculos`
--
ALTER TABLE `comentarios_vehiculos`
  MODIFY `id_comentario` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT de la tabla `fotos_vehiculos`
--
ALTER TABLE `fotos_vehiculos`
  MODIFY `id_foto` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=48;

--
-- AUTO_INCREMENT de la tabla `metodos_pago`
--
ALTER TABLE `metodos_pago`
  MODIFY `id_pago` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `prestamo`
--
ALTER TABLE `prestamo`
  MODIFY `id_prestamo` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `usuario`
--
ALTER TABLE `usuario`
  MODIFY `IdUsuario` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=68;

--
-- AUTO_INCREMENT de la tabla `vehiculo`
--
ALTER TABLE `vehiculo`
  MODIFY `id_v` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=48;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `arrendatario`
--
ALTER TABLE `arrendatario`
  ADD CONSTRAINT `arrendatario_ibfk_1` FOREIGN KEY (`IdUsuario`) REFERENCES `usuario` (`IdUsuario`);

--
-- Filtros para la tabla `comentarios_vehiculos`
--
ALTER TABLE `comentarios_vehiculos`
  ADD CONSTRAINT `comentarios_vehiculos_ibfk_1` FOREIGN KEY (`id_vehiculo`) REFERENCES `vehiculo` (`id_v`) ON DELETE CASCADE;

--
-- Filtros para la tabla `fotos_vehiculos`
--
ALTER TABLE `fotos_vehiculos`
  ADD CONSTRAINT `fk_fotos_vehiculo` FOREIGN KEY (`id_vehiculo`) REFERENCES `vehiculo` (`id_v`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `metodos_pago`
--
ALTER TABLE `metodos_pago`
  ADD CONSTRAINT `fk_usuario_pago` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`IdUsuario`) ON DELETE CASCADE;

--
-- Filtros para la tabla `prestamo`
--
ALTER TABLE `prestamo`
  ADD CONSTRAINT `prestamo_ibfk_1` FOREIGN KEY (`id_arrendatario`) REFERENCES `arrendatario` (`id_arrendatario`),
  ADD CONSTRAINT `prestamo_ibfk_2` FOREIGN KEY (`id_vehiculo`) REFERENCES `vehiculo` (`id_v`);

--
-- Filtros para la tabla `vehiculo`
--
ALTER TABLE `vehiculo`
  ADD CONSTRAINT `fk_arrendatario` FOREIGN KEY (`id_arrendatario`) REFERENCES `usuario` (`IdUsuario`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
