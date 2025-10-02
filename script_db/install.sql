-- -------------------------------------------------------------
-- Script de Instalación para el Sistema de Gestión de Alquileres
-- Versión: 1.0
--
-- Instrucciones:
-- 1. Copiar y pegar todo el contenido de este archivo en la
--    pestaña SQL de phpMyAdmin.
-- 2. Hacer clic en "Continuar" para ejecutar el script.
--
-- Este script:
-- - Eliminará la base de datos 'gestion_alquileres' si existe.
-- - Creará la base de datos 'gestion_alquileres' con UTF-8.
-- - Creará todas las tablas, índices y relaciones.
-- - Insertará datos de ejemplo para comenzar.
-- -------------------------------------------------------------

-- Eliminar la base de datos si ya existe para una instalación limpia
DROP DATABASE IF EXISTS `gestion_alquileres`;

-- Crear la base de datos con el juego de caracteres y colación adecuados para español
CREATE DATABASE `gestion_alquileres` CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci;

-- Seleccionar la base de datos para trabajar sobre ella
USE `gestion_alquileres`;

--
-- Estructura de tabla para la tabla `inquilinos`
--
CREATE TABLE `inquilinos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) COLLATE utf8mb4_spanish_ci NOT NULL,
  `apellido` varchar(100) COLLATE utf8mb4_spanish_ci NOT NULL,
  `dni` varchar(20) COLLATE utf8mb4_spanish_ci NOT NULL,
  `telefono` varchar(50) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `email` varchar(100) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `direccion` varchar(255) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `localidad` varchar(100) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `provincia` varchar(100) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `observaciones` text COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `estado` enum('activo','inactivo','eliminado') COLLATE utf8mb4_spanish_ci NOT NULL DEFAULT 'activo',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `dni` (`dni`),
  KEY `idx_inquilino_dni` (`dni`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `inquilinos`
--
INSERT INTO `inquilinos` (`nombre`, `apellido`, `dni`, `telefono`, `email`) VALUES
('Ignacio', 'De Maria', '43465696', '1130625982', 'ignacio@email.com'),
('Fernando', 'Garcia Vigezzi', '28925473', '1568360458', 'fernando@email.com'),
('Antonella Carla', 'Soliani', '30123456', '1155667788', 'antonella@email.com');

--
-- Estructura de tabla para la tabla `propiedades`
--
CREATE TABLE `propiedades` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `codigo` varchar(50) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `direccion` varchar(200) COLLATE utf8mb4_spanish_ci NOT NULL,
  `departamento` varchar(20) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `localidad` varchar(100) COLLATE utf8mb4_spanish_ci NOT NULL,
  `descripcion` text COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `codigo` (`codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `propiedades`
--
INSERT INTO `propiedades` (`codigo`, `direccion`, `departamento`, `localidad`) VALUES
('PROP-001', 'Artigas 1159', 'A', 'General Rodriguez'),
('PROP-002', 'Artigas 1159', 'B', 'General Rodriguez'),
('PROP-003', 'Artigas 1160', 'B', 'General Rodriguez');

--
-- Estructura de tabla para la tabla `garantes`
--
CREATE TABLE `garantes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre_apellido` varchar(200) COLLATE utf8mb4_spanish_ci NOT NULL,
  `dni` varchar(20) COLLATE utf8mb4_spanish_ci NOT NULL,
  `telefono` varchar(50) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `direccion` varchar(200) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `email` varchar(100) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Estructura de tabla para la tabla `contratos`
--
CREATE TABLE `contratos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `codigo` varchar(50) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `inquilino_id` int(11) NOT NULL,
  `propiedad_id` int(11) NOT NULL,
  `garante_id` int(11) DEFAULT NULL,
  `fecha_inicio` date NOT NULL,
  `duracion_meses` int(11) NOT NULL,
  `fecha_fin` date NOT NULL,
  `deposito_ingreso` decimal(15,2) DEFAULT NULL,
  `valores_alquiler` json DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `codigo` (`codigo`),
  KEY `inquilino_id` (`inquilino_id`),
  KEY `propiedad_id` (`propiedad_id`),
  KEY `garante_id` (`garante_id`),
  KEY `idx_contrato_fechas` (`fecha_inicio`,`fecha_fin`),
  CONSTRAINT `contratos_ibfk_1` FOREIGN KEY (`inquilino_id`) REFERENCES `inquilinos` (`id`),
  CONSTRAINT `contratos_ibfk_2` FOREIGN KEY (`propiedad_id`) REFERENCES `propiedades` (`id`),
  CONSTRAINT `contratos_ibfk_3` FOREIGN KEY (`garante_id`) REFERENCES `garantes` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Volcado de datos para la tabla `contratos` (EJEMPLO)
--
INSERT INTO `contratos` (`id`, `codigo`, `inquilino_id`, `propiedad_id`, `fecha_inicio`, `duracion_meses`, `fecha_fin`, `deposito_ingreso`, `valores_alquiler`, `activo`) VALUES
(1, 'CONT-2024-001', 1, 1, '2024-01-01', 12, '2024-12-31', 150000.00, '[{\"desde\": \"1\", \"hasta\": \"12\", \"valor\": \"150000\"}]', 1);


--
-- Estructura de tabla para la tabla `cobros`
--
CREATE TABLE `cobros` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `contrato_id` int(11) NOT NULL,
  `inquilino_id` int(11) NOT NULL,
  `propiedad_id` int(11) NOT NULL,
  `periodo` varchar(20) COLLATE utf8mb4_spanish_ci NOT NULL,
  `mes` int(11) NOT NULL,
  `anio` int(11) NOT NULL,
  `alquiler` decimal(15,2) NOT NULL DEFAULT 0.00,
  `luz` decimal(15,2) DEFAULT 0.00,
  `agua` decimal(15,2) DEFAULT 0.00,
  `mantenimiento` decimal(15,2) DEFAULT 0.00,
  `abl` decimal(15,2) DEFAULT 0.00,
  `otros_conceptos` decimal(15,2) DEFAULT 0.00,
  `total` decimal(15,2) NOT NULL,
  `status` enum('PENDIENTE','PAGADO','VENCIDO','ANULADO') COLLATE utf8mb4_spanish_ci DEFAULT 'PENDIENTE',
  `fecha_cobro` date DEFAULT NULL,
  `fecha_vencimiento` date DEFAULT NULL,
  `observaciones` text COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `contrato_id` (`contrato_id`),
  KEY `inquilino_id` (`inquilino_id`),
  KEY `propiedad_id` (`propiedad_id`),
  KEY `idx_cobros_periodo` (`periodo`,`status`),
  KEY `idx_cobros_fecha` (`fecha_cobro`),
  CONSTRAINT `cobros_ibfk_1` FOREIGN KEY (`contrato_id`) REFERENCES `contratos` (`id`),
  CONSTRAINT `cobros_ibfk_2` FOREIGN KEY (`inquilino_id`) REFERENCES `inquilinos` (`id`),
  CONSTRAINT `cobros_ibfk_3` FOREIGN KEY (`propiedad_id`) REFERENCES `propiedades` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Estructura de tabla para la tabla `conceptos_cobro`
--
CREATE TABLE `conceptos_cobro` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cobro_id` int(11) NOT NULL,
  `concepto` varchar(100) COLLATE utf8mb4_spanish_ci NOT NULL,
  `monto` decimal(15,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `cobro_id` (`cobro_id`),
  CONSTRAINT `conceptos_cobro_ibfk_1` FOREIGN KEY (`cobro_id`) REFERENCES `cobros` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- Estructura de tabla para la tabla `alertas_configuracion`
--
CREATE TABLE IF NOT EXISTS `alertas_configuracion` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tipo` varchar(50) COLLATE utf8mb4_spanish_ci NOT NULL,
  `dias_anticipacion` int(11) NOT NULL DEFAULT 30,
  `activo` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- Insertar configuración por defecto
INSERT INTO `alertas_configuracion` (`tipo`, `dias_anticipacion`, `activo`) VALUES
('contrato_vencimiento', 30, 1),
('contrato_vencimiento_critico', 7, 1);
--
-- Estructura de tabla para la tabla `users`
--
-- Tabla de usuarios para el sistema de login
CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre_completo` varchar(200) COLLATE utf8mb4_spanish_ci NOT NULL,
  `username` varchar(50) COLLATE utf8mb4_spanish_ci NOT NULL,
  `email` varchar(150) COLLATE utf8mb4_spanish_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_spanish_ci NOT NULL,
  `rol` enum('admin','usuario') COLLATE utf8mb4_spanish_ci NOT NULL DEFAULT 'usuario',
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp(),
  `ultimo_acceso` timestamp NULL DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_username` (`username`),
  KEY `idx_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- Usuario por defecto para pruebas (password: admin123)
INSERT INTO users (nombre_completo, username, email, password, rol) 
VALUES ('Administrador', 'admin', 'admin@sistema.com', '$2y$10$kKFl3K/oqykaJP6/VQK4Y.3XljuDPw64dKW8YwZOBV41Wj9RWkeC.', 'admin')
ON DUPLICATE KEY UPDATE id=id;

-- -------------------------------------------------------------
-- Fin del script de instalación.
-- -------------------------------------------------------------
