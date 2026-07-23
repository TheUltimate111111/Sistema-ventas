/*M!999999\- enable the sandbox mode */ 
-- MariaDB dump 10.19-11.7.2-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: base_ventas
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*M!100616 SET @OLD_NOTE_VERBOSITY=@@NOTE_VERBOSITY, NOTE_VERBOSITY=0 */;

--
-- Table structure for table `clientes`
--

DROP TABLE IF EXISTS `clientes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `clientes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cedula` varchar(20) DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `nombre_completo` varchar(100) NOT NULL,
  `correo` varchar(100) DEFAULT NULL,
  `direccion` varchar(150) DEFAULT NULL,
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `cedula` (`cedula`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `clientes`
--

LOCK TABLES `clientes` WRITE;
/*!40000 ALTER TABLE `clientes` DISABLE KEYS */;
INSERT INTO `clientes` VALUES
(1,'9999999999999',NULL,'Consumidor Final',NULL,NULL,'2026-07-20 00:13:59'),
(2,'1700000001',NULL,'Juan Perez','juan.perez@espe.edu.ec',NULL,'2026-07-20 00:13:59'),
(3,'1700000002',NULL,'Maria Gomez','maria.gomez@espe.edu.ec',NULL,'2026-07-20 00:13:59'),
(4,'1700000003',NULL,'Carlos Ruiz','carlos.ruiz@espe.edu.ec',NULL,'2026-07-20 00:13:59'),
(5,'1700000004',NULL,'Ana Silva','ana.silva@espe.edu.ec',NULL,'2026-07-20 00:13:59'),
(6,'1700000005',NULL,'Luis Torres','luis.torres@espe.edu.ec',NULL,'2026-07-20 00:13:59'),
(7,'17288017299',NULL,'Alexis Orlando','diego@gmail.com',NULL,'2026-07-20 02:31:01'),
(8,'1005225485',NULL,'Isra','isra@espe.edu.ec',NULL,'2026-07-21 22:31:46'),
(9,'0502909294',NULL,'Joaquin Olmedo','diego@espe.edu.ec',NULL,'2026-07-21 23:00:38');
/*!40000 ALTER TABLE `clientes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `detalles_venta`
--

DROP TABLE IF EXISTS `detalles_venta`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `detalles_venta` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `venta_id` int(11) NOT NULL,
  `producto_id` int(11) NOT NULL,
  `cantidad` int(11) NOT NULL,
  `precio_congelado` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `venta_id` (`venta_id`),
  KEY `producto_id` (`producto_id`),
  CONSTRAINT `detalles_venta_ibfk_1` FOREIGN KEY (`venta_id`) REFERENCES `ventas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `detalles_venta_ibfk_2` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `detalles_venta`
--

LOCK TABLES `detalles_venta` WRITE;
/*!40000 ALTER TABLE `detalles_venta` DISABLE KEYS */;
INSERT INTO `detalles_venta` VALUES
(1,1,1,1,1200.00),
(2,1,2,1,25.50),
(3,2,4,1,250.00),
(4,2,3,1,45.00),
(5,3,6,2,65.00),
(6,4,7,1,320.50),
(7,4,5,1,85.00),
(8,5,1,1,1200.00),
(9,6,1,2,1200.00),
(10,6,2,1,25.50),
(11,7,1,1,1200.00),
(12,8,3,1,45.00),
(13,9,1,1,1200.00),
(14,9,2,1,25.50),
(15,10,1,1,1200.00),
(16,11,3,1,45.00),
(17,12,1,1,1200.00),
(18,12,5,1,85.00),
(19,13,1,8,1200.00);
/*!40000 ALTER TABLE `detalles_venta` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `productos`
--

DROP TABLE IF EXISTS `productos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `productos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `codigo_barras` varchar(50) NOT NULL,
  `nombre_producto` varchar(100) NOT NULL,
  `precio_actual` decimal(10,2) NOT NULL,
  `stock_disponible` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `codigo_barras` (`codigo_barras`),
  CONSTRAINT `chk_stock_positivo` CHECK (`stock_disponible` >= 0)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `productos`
--

LOCK TABLES `productos` WRITE;
/*!40000 ALTER TABLE `productos` DISABLE KEYS */;
INSERT INTO `productos` VALUES
(1,'PROD-001','Laptop Dell XPS 13',1200.00,0),
(2,'PROD-002','Mouse Inalámbrico Logitech',25.50,48),
(3,'PROD-003','Teclado Mecánico Redragon',45.00,29),
(4,'PROD-004','Monitor LG 27 pulgadas',250.00,20),
(5,'PROD-005','Disco Duro SSD 1TB Kingston',85.00,40),
(6,'PROD-006','Memoria RAM 16GB DDR5',65.00,35),
(7,'PROD-007','Tarjeta Gráfica RTX 4060',320.50,11),
(8,'PROD-008','Audífonos HyperX Cloud',75.00,25),
(9,'PROD-009','MOUSE',50.00,3);
/*!40000 ALTER TABLE `productos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `usuarios`
--

DROP TABLE IF EXISTS `usuarios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `rol` varchar(20) DEFAULT 'cajero',
  `estado` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `usuario` (`usuario`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `usuarios`
--

LOCK TABLES `usuarios` WRITE;
/*!40000 ALTER TABLE `usuarios` DISABLE KEYS */;
INSERT INTO `usuarios` VALUES
(1,'administrador','123456','administrador',1);
/*!40000 ALTER TABLE `usuarios` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ventas`
--

DROP TABLE IF EXISTS `ventas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ventas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cliente_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL DEFAULT 0.00,
  `iva` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_factura` decimal(10,2) NOT NULL,
  `monto_pagado` decimal(10,2) NOT NULL DEFAULT 0.00,
  `cambio` decimal(10,2) NOT NULL DEFAULT 0.00,
  `fecha_emision` timestamp NOT NULL DEFAULT current_timestamp(),
  `estado` varchar(20) NOT NULL DEFAULT 'Pagada',
  PRIMARY KEY (`id`),
  KEY `cliente_id` (`cliente_id`),
  KEY `usuario_id` (`usuario_id`),
  CONSTRAINT `ventas_ibfk_1` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`),
  CONSTRAINT `ventas_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ventas`
--

LOCK TABLES `ventas` WRITE;
/*!40000 ALTER TABLE `ventas` DISABLE KEYS */;
INSERT INTO `ventas` VALUES
(1,2,1,1225.50,183.83,1409.33,1410.00,0.67,'2026-07-20 00:14:09','Pagada'),
(2,3,1,295.00,44.25,339.25,340.00,0.75,'2026-07-20 00:14:09','Pagada'),
(3,4,1,130.00,19.50,149.50,150.00,0.50,'2026-07-20 00:14:09','Pagada'),
(4,5,1,405.50,60.83,466.33,470.00,3.67,'2026-07-20 00:14:09','Anulada'),
(5,2,1,1200.00,180.00,1380.00,2000.00,620.00,'2026-07-20 00:25:51','Pagada'),
(6,2,1,2425.50,363.83,2789.33,3000.00,210.67,'2026-07-20 00:35:04','Pagada'),
(7,1,1,1200.00,180.00,1380.00,20000.00,18620.00,'2026-07-20 00:38:08','Pagada'),
(8,7,1,45.00,6.75,51.75,60.00,8.25,'2026-07-20 02:31:01','Anulada'),
(9,1,1,1225.50,183.83,1409.33,1500.00,90.67,'2026-07-20 03:59:15','Pagada'),
(10,1,1,1200.00,180.00,1380.00,1380.00,0.00,'2026-07-20 04:11:02','Pagada'),
(11,8,1,45.00,6.75,51.75,60.00,8.25,'2026-07-21 22:31:46','Pagada'),
(12,9,1,1285.00,192.75,1477.75,1500.00,22.25,'2026-07-21 23:00:38','Pagada'),
(13,9,1,9600.00,1440.00,11040.00,11500.00,460.00,'2026-07-21 23:01:21','Pagada');
/*!40000 ALTER TABLE `ventas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping routines for database 'base_ventas'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*M!100616 SET NOTE_VERBOSITY=@OLD_NOTE_VERBOSITY */;

-- Dump completed on 2026-07-23  0:11:21
