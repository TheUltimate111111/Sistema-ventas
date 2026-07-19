<?php
declare(strict_types=1);
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['usuario_activo'])) {
    http_response_code(401);
    echo json_encode(['estado' => 'error', 'mensaje' => 'No autenticado']);
    exit;
}

require_once 'includes/conexion.php';

$ventaId = (int)($_GET['venta_id'] ?? 0);
if ($ventaId <= 0) {
    http_response_code(400);
    echo json_encode(['estado' => 'error', 'mensaje' => 'ID de venta inválido']);
    exit;
}

try {
    $hasEstado = false;
    $colsStmt = $pdo->prepare("SHOW COLUMNS FROM ventas LIKE 'estado'");
    $colsStmt->execute();
    if ($colsStmt->fetch()) {
        $hasEstado = true;
    }
    $estadoCol = $hasEstado ? "v.estado" : "'Pagada' AS estado";

    $stmt = $pdo->prepare(
        "SELECT v.id, v.fecha_emision, v.subtotal, v.iva, v.total_factura, v.monto_pagado, v.cambio, $estadoCol,
                c.nombre_completo AS cliente, c.cedula,
                u.usuario AS cajero
         FROM ventas v
         INNER JOIN clientes c ON v.cliente_id = c.id
         INNER JOIN usuarios u ON v.usuario_id = u.id
         WHERE v.id = ? LIMIT 1"
    );
    $stmt->execute([$ventaId]);
    $venta = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$venta) {
        http_response_code(404);
        echo json_encode(['estado' => 'error', 'mensaje' => 'Venta no encontrada']);
        exit;
    }

    $detStmt = $pdo->prepare(
        'SELECT dv.cantidad, dv.precio_congelado, p.nombre_producto, p.codigo_barras
         FROM detalles_venta dv
         INNER JOIN productos p ON dv.producto_id = p.id
         WHERE dv.venta_id = ?'
    );
    $detStmt->execute([$ventaId]);
    $detalles = $detStmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'estado' => 'success',
        'venta' => $venta,
        'detalles' => $detalles
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['estado' => 'error', 'mensaje' => 'Error interno']);
}
