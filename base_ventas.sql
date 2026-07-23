-- ================================================
-- BASE DE DATOS: base_ventas
-- Sistema POS - Gestion Comercial (Ecuador)
-- IVA: 15% | Moneda: USD
-- ================================================

CREATE DATABASE IF NOT EXISTS `base_ventas`
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_general_ci;

USE `base_ventas`;

-- -----------------------------------------------
-- TABLA: usuarios
-- -----------------------------------------------
CREATE TABLE IF NOT EXISTS `usuarios` (
    `id`            INT AUTO_INCREMENT PRIMARY KEY,
    `usuario`       VARCHAR(50)  NOT NULL,
    `password_hash` VARCHAR(255) NOT NULL,
    `nombre`        VARCHAR(100) NOT NULL,
    `rol`           VARCHAR(20)  NOT NULL DEFAULT 'cajero',
    `estado`        TINYINT(1)   NOT NULL DEFAULT 1,
    UNIQUE KEY `uk_usuario` (`usuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------
-- TABLA: clientes
-- -----------------------------------------------
CREATE TABLE IF NOT EXISTS `clientes` (
    `id`              INT AUTO_INCREMENT PRIMARY KEY,
    `cedula`          VARCHAR(10)  DEFAULT NULL,
    `nombre_completo` VARCHAR(150) NOT NULL,
    `correo`          VARCHAR(100) DEFAULT NULL,
    `telefono`        VARCHAR(20)  DEFAULT NULL,
    `direccion`       VARCHAR(150) DEFAULT NULL,
    `fecha_registro`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_cedula` (`cedula`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------
-- TABLA: productos
-- -----------------------------------------------
CREATE TABLE IF NOT EXISTS `productos` (
    `id`                INT AUTO_INCREMENT PRIMARY KEY,
    `codigo_barras`     VARCHAR(50)  NOT NULL,
    `nombre_producto`   VARCHAR(150) NOT NULL,
    `precio_actual`     DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `stock_disponible`  INT          NOT NULL DEFAULT 0,
    UNIQUE KEY `uk_codigo_barras` (`codigo_barras`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------
-- TABLA: ventas
-- -----------------------------------------------
CREATE TABLE IF NOT EXISTS `ventas` (
    `id`            INT AUTO_INCREMENT PRIMARY KEY,
    `cliente_id`    INT            NOT NULL,
    `usuario_id`    INT            NOT NULL,
    `subtotal`      DECIMAL(10,2)  NOT NULL DEFAULT 0.00,
    `iva`           DECIMAL(10,2)  NOT NULL DEFAULT 0.00,
    `total_factura` DECIMAL(10,2)  NOT NULL DEFAULT 0.00,
    `monto_pagado`  DECIMAL(10,2)  NOT NULL DEFAULT 0.00,
    `cambio`        DECIMAL(10,2)  NOT NULL DEFAULT 0.00,
    `fecha_emision` DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `estado`        VARCHAR(20)    NOT NULL DEFAULT 'Pagada',
    KEY `idx_cliente` (`cliente_id`),
    KEY `idx_usuario` (`usuario_id`),
    CONSTRAINT `fk_ventas_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_ventas_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------
-- TABLA: detalles_venta
-- -----------------------------------------------
CREATE TABLE IF NOT EXISTS `detalles_venta` (
    `id`              INT AUTO_INCREMENT PRIMARY KEY,
    `venta_id`        INT            NOT NULL,
    `producto_id`     INT            NOT NULL,
    `cantidad`        INT            NOT NULL DEFAULT 1,
    `precio_congelado` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    KEY `idx_venta` (`venta_id`),
    KEY `idx_producto` (`producto_id`),
    CONSTRAINT `fk_detalle_venta` FOREIGN KEY (`venta_id`) REFERENCES `ventas` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_detalle_producto` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------
-- DATOS INICIALES
-- -----------------------------------------------

-- Usuario administrador (password: admin123)
INSERT INTO `usuarios` (`usuario`, `password_hash`, `nombre`, `rol`, `estado`)
VALUES ('admin', '$2y$10$9MoqtknMPdFHm6tHwEoOcOvuYvUDp/9CKC08FxfcmlcSVkWNHdjqi', 'Administrador', 'admin', 1);

-- Cliente por defecto (Consumidor Final)
INSERT INTO `clientes` (`cedula`, `nombre_completo`, `correo`, `telefono`, `direccion`)
VALUES (NULL, 'Consumidor Final', NULL, NULL, NULL);
